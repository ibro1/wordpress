"""
Self-hosted Stable Diffusion image generation, CPU-only.

Speaks the OpenAI images wire format (`GET /v1/models`,
`POST /v1/images/generations` returning `{"data":[{"b64_json": ...}]}`) so it
plugs into the existing image catalogue without the backend needing a bespoke
adapter for it.

CPU REALITY, stated plainly because it drives every decision below. Measured
on the production box at 768x512 with TORCH_THREADS=4: roughly 18 SECONDS PER
DENOISING STEP. So DreamShaper 8 LCM at 6 steps is about 1m48s per image, and
Realistic Vision V5.1 at 25 steps is around 7-8 minutes. Nothing here is going
to feel like calling OpenAI. The mitigations that actually matter without a
GPU:

  * Generate at SD1.5's trained resolution (512 on the short edge) and
    upscale to the requested size. Asking SD1.5 for 1536x1024 directly is
    both far slower and visibly worse - it duplicates subjects and limbs,
    because it was never trained at that size.
  * Hold ONE pipeline in memory at a time. Two SD1.5 pipelines in float32
    is several GB of RAM, and a swapping container is slower than a reload.
  * Serialise generation behind a lock. Concurrent CPU inference on a shared
    box makes every request slower rather than overlapping usefully.
"""

import base64
import io
import os
import re
import threading
import time

import torch
from diffusers import LCMScheduler, StableDiffusionPipeline
from fastapi import FastAPI
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from PIL import Image

# Model ids are env-overridable. A checkpoint being renamed or moved upstream
# should be a config change on the host, not a rebuild of this image.
MODELS = {
    "dreamshaper-8-lcm": {
        "repo": os.getenv("DREAMSHAPER_REPO", "Lykon/dreamshaper-8-lcm"),
        "label": "DreamShaper 8 LCM",
        # LCM is distilled for very few steps and near-zero guidance; running
        # it at ordinary SD settings produces washed-out mush.
        "steps": int(os.getenv("DREAMSHAPER_STEPS", "6")),
        "guidance": float(os.getenv("DREAMSHAPER_GUIDANCE", "1.5")),
        "lcm": True,
    },
    "realistic-vision-v5-1": {
        "repo": os.getenv("REALISTIC_VISION_REPO", "SG161222/Realistic_Vision_V5.1_noVAE"),
        "label": "Realistic Vision V5.1",
        "steps": int(os.getenv("REALISTIC_VISION_STEPS", "25")),
        "guidance": float(os.getenv("REALISTIC_VISION_GUIDANCE", "7.0")),
        "lcm": False,
    },
}

CACHE_DIR = os.getenv("HF_HOME", "/models")

# Quality floor for a store photograph. Applied to every request so the
# operator does not have to remember it, and so the two checkpoints produce
# comparably clean output.
# Appended to every positive prompt. Compact on purpose: CLIP has 77 tokens
# to spend and the subject deserves most of them.
STYLE_SUFFIX = os.getenv(
    "STYLE_SUFFIX",
    "product photography, natural light, shallow depth of field, "
    "uncluttered composition, empty space to one side",
)

NEGATIVE_PROMPT = os.getenv(
    "NEGATIVE_PROMPT",
    "text, watermark, signature, logo, lettering, caption, username, "
    "blurry, low quality, jpeg artifacts, deformed, disfigured, extra limbs, "
    "bad anatomy, bad hands, cropped, out of frame",
)

torch.set_num_threads(int(os.getenv("TORCH_THREADS", str(os.cpu_count() or 4))))

app = FastAPI(title="wookiee-sd")

_lock = threading.Lock()
_loaded = {"key": None, "pipe": None}


def _load(key):
    """
    Returns the pipeline for `key`, loading it if a different one is resident.

    One at a time on purpose - see the RAM note in the module docstring. The
    caller already holds _lock, so this is never re-entered concurrently.
    """
    if _loaded["key"] == key:
        return _loaded["pipe"]

    spec = MODELS[key]

    # Drop the previous pipeline before building the next one, or the peak
    # briefly holds both and a memory-constrained box gets OOM-killed.
    _loaded["key"] = None
    _loaded["pipe"] = None

    pipe = StableDiffusionPipeline.from_pretrained(
        spec["repo"],
        torch_dtype=torch.float32,  # float16 is a GPU optimisation; on CPU it is slower and can NaN.
        safety_checker=None,
        cache_dir=CACHE_DIR,
    )

    if spec["lcm"]:
        pipe.scheduler = LCMScheduler.from_config(pipe.scheduler.config)

    pipe = pipe.to("cpu")
    # Both trade a little speed for a lot less peak RAM, which is the binding
    # constraint on a shared VPS.
    pipe.enable_attention_slicing()
    pipe.enable_vae_slicing()

    _loaded["key"] = key
    _loaded["pipe"] = pipe
    return pipe


def _generation_size(size):
    """
    Maps a requested output size to what SD1.5 should actually generate.

    Keeps the aspect ratio, pins the short edge to 512 (its trained
    resolution), and rounds to a multiple of 8 as the VAE requires. The
    caller upscales the result back to the requested size.
    """
    try:
        want_w, want_h = (int(n) for n in str(size).lower().split("x"))
    except (ValueError, AttributeError):
        want_w, want_h = 1536, 1024

    want_w = max(256, min(2048, want_w))
    want_h = max(256, min(2048, want_h))

    # Clamp the aspect ratio to what SD1.5 renders coherently at all.
    ratio = max(0.5, min(2.0, want_w / want_h))

    if ratio >= 1:
        gen_h, gen_w = 512, int(512 * ratio)
    else:
        gen_w, gen_h = 512, int(512 / ratio)

    # Cap the long edge: beyond ~768 SD1.5 starts duplicating subjects, and
    # the time cost climbs faster than the quality does. Rounded to a
    # multiple of 8, which the VAE requires.
    gen_w = max(384, min(768, gen_w - (gen_w % 8)))
    gen_h = max(384, min(768, gen_h - (gen_h % 8)))

    # The output is the GENERATED size scaled up, never the raw request.
    # Deriving it from the request would stretch the render whenever the two
    # ratios disagree - which they do as soon as either the ratio clamp or
    # the long-edge cap above has bitten. Scale to cover the requested box so
    # nothing is smaller than asked for.
    scale = max(want_w / gen_w, want_h / gen_h)
    out_w = int(round(gen_w * scale))
    out_h = int(round(gen_h * scale))

    return (gen_w, gen_h), (out_w, out_h)


def _prepare_prompt(pipe, prompt):
    """
    Fits a prompt written for a hosted model into what SD1.5 can actually read.

    Two separate problems, both silent before this existed:

    1. CLIP hard-caps at 77 tokens and DROPS the remainder without failing.
       The theme's prompt runs to ~205 tokens, so roughly two thirds of it -
       every one of the quality requirements - never reached the model at all.

    2. Those requirements are instruction-style prose ("do not substitute a
       generic lifestyle scene"). Stable Diffusion is not instruction
       following; it matches CLIP similarity. A negation in a POSITIVE prompt
       reliably summons the thing it forbids, because the tokens are present
       either way. Those constraints belong in the negative prompt, which
       already carries them.

    So: keep the descriptive lead, drop the requirements block, append compact
    photographic tags, and truncate on a real token boundary if it is still
    long - deliberately rather than letting CLIP do it silently.
    """
    lead = re.split(r"\n\s*(?:Requirements?|Rules?)\s*:", prompt, maxsplit=1)[0]
    lead = re.sub(r"\s+", " ", lead).strip()

    tokenizer = pipe.tokenizer
    limit = tokenizer.model_max_length

    suffix_len = len(tokenizer(STYLE_SUFFIX, truncation=False).input_ids)
    # -2 for the BOS/EOS the tokenizer adds around the whole thing.
    budget = max(16, limit - suffix_len - 2)

    words = lead.split()
    while words and len(tokenizer(" ".join(words), truncation=False).input_ids) > budget:
        words.pop()
    lead = " ".join(words)

    composed = f"{lead}, {STYLE_SUFFIX}" if lead else STYLE_SUFFIX
    used = len(tokenizer(composed, truncation=False).input_ids)
    return composed, used


class GenerateRequest(BaseModel):
    prompt: str
    model: str = "dreamshaper-8-lcm"
    size: str = "1536x1024"
    n: int = 1
    response_format: str = "b64_json"


@app.get("/healthz")
def healthz():
    return {"ok": True, "loaded": _loaded["key"], "threads": torch.get_num_threads()}


@app.get("/v1/models")
def list_models():
    """OpenAI-shaped, so the backend's existing provider listing works as-is."""
    return {
        "object": "list",
        "data": [
            {
                "id": key,
                "object": "model",
                "owned_by": "self-hosted",
                "display_name": spec["label"],
            }
            for key, spec in MODELS.items()
        ],
    }


@app.post("/v1/images/generations")
def generate(req: GenerateRequest):
    key = req.model if req.model in MODELS else None
    if key is None:
        # Accept a provider-prefixed id too ("local:dreamshaper-8-lcm"), since
        # that is how the catalogue names models internally.
        tail = str(req.model).split(":")[-1]
        key = tail if tail in MODELS else None

    if key is None:
        return JSONResponse(
            status_code=400,
            content={"error": {"message": f"Unknown model '{req.model}'. Available: {', '.join(MODELS)}."}},
        )

    spec = MODELS[key]
    (gen_w, gen_h), (out_w, out_h) = _generation_size(req.size)

    started = time.time()

    # Serialised: parallel CPU inference on a shared box makes every request
    # slower rather than overlapping usefully, and doubles peak RAM.
    with _lock:
        try:
            pipe = _load(key)
            prompt, prompt_tokens = _prepare_prompt(pipe, req.prompt)
            result = pipe(
                prompt=prompt,
                negative_prompt=NEGATIVE_PROMPT,
                num_inference_steps=spec["steps"],
                guidance_scale=spec["guidance"],
                width=gen_w,
                height=gen_h,
            )
        except Exception as exc:  # noqa: BLE001 - reported to the caller verbatim
            return JSONResponse(
                status_code=500,
                content={"error": {"message": f"{spec['label']} failed: {exc}"}},
            )

    image = result.images[0]
    if (out_w, out_h) != (gen_w, gen_h):
        image = image.resize((out_w, out_h), Image.LANCZOS)

    buffer = io.BytesIO()
    image.save(buffer, format="PNG")

    return {
        "created": int(started),
        "data": [{"b64_json": base64.b64encode(buffer.getvalue()).decode("ascii")}],
        "wookiee_meta": {
            "model": key,
            "generated_at": f"{gen_w}x{gen_h}",
            "upscaled_to": f"{out_w}x{out_h}",
            "steps": spec["steps"],
            "seconds": round(time.time() - started, 1),
            # Surfaced so a prompt silently losing its tail is visible in the
            # response rather than only in a warning buried in the logs.
            "prompt_tokens": prompt_tokens,
            "prompt_sent": prompt,
        },
    }
