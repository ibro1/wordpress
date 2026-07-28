# wookiee-sd — self-hosted image generation

DreamShaper 8 LCM and Realistic Vision V5.1, served over the OpenAI images
wire format so `wookiee-api`'s image catalogue can use them with no bespoke
adapter. Intended as a higher-tier entitlement: grant `local:*` models to a
licence the same way you grant `openai:*` ones.

## Read this before selling it

**This host has no GPU, and that is the dominant fact about this service.**

| Model | Steps | Rough time per image, CPU | Verdict |
| --- | --- | --- | --- |
| DreamShaper 8 LCM | 6 | tens of seconds | usable |
| Realistic Vision V5.1 | 25 | several minutes | marginal |

Those are estimates from the step counts and the fact that this is float32
CPU inference — they have not been measured on your box. Measure before you
put numbers in front of a customer:

```bash
docker compose exec wookiee_sd python -c "
import time,urllib.request,json
b=json.dumps({'prompt':'a compact camping stove on a rock, morning light','model':'dreamshaper-8-lcm'}).encode()
r=urllib.request.Request('http://localhost:7860/v1/images/generations',b,{'Content-Type':'application/json'})
t=time.time(); urllib.request.urlopen(r,timeout=1800).read(); print(f'{time.time()-t:.0f}s')"
```

Run it twice. The first call also downloads ~2GB of weights and loads them,
so only the second is representative.

The timeout chain is 600s at the WordPress end and 900s in the backend. A
Realistic Vision render that exceeds 600s fails the step — the Rebrand screen
records it and offers to continue, so nothing is lost but the compute. If
that happens routinely, either drop `SD_REALISTIC_VISION_STEPS` or only grant
DreamShaper to licences.

## What it does about being CPU-bound

- **Generates at SD1.5's trained resolution and upscales.** A request for
  1536x1024 renders at 768x512 and scales up. Asking SD1.5 for 1536x1024
  directly is both far slower and visibly worse — it duplicates subjects,
  because it was never trained at that size. Output is softer than a native
  render; that is the trade.
- **One pipeline resident at a time.** Two SD1.5 pipelines in float32 is
  several GB. Switching models costs a reload, which is cheaper than swapping.
- **Generation is serialised.** Parallel CPU inference makes every request
  slower rather than overlapping usefully.
- **`TORCH_THREADS` defaults to 4**, not every core, so a render does not
  starve PHP and MySQL on the same box.

## Configuration

All optional; defaults suit the compose file.

| Env | Default | Notes |
| --- | --- | --- |
| `TORCH_THREADS` | all cores | compose sets 4 |
| `DREAMSHAPER_STEPS` | 6 | LCM needs few steps; above ~8 wastes time |
| `DREAMSHAPER_GUIDANCE` | 1.5 | LCM wants low guidance; 7 gives mush |
| `REALISTIC_VISION_STEPS` | 25 | the main time lever |
| `REALISTIC_VISION_GUIDANCE` | 7.0 | |
| `DREAMSHAPER_REPO` | `Lykon/dreamshaper-8-lcm` | override if renamed upstream |
| `REALISTIC_VISION_REPO` | `SG161222/Realistic_Vision_V5.1_noVAE` | as above |
| `NEGATIVE_PROMPT` | see `app.py` | applied to every request |

Weights are fetched from Hugging Face on first use into the
`wookiee_sd_models` volume. **The repo ids above are the well-known ones but
are not verified from here** — if a pull 404s, correct it with the env var
rather than rebuilding.

## Wiring

1. Deploy. First request per model downloads ~2GB.
2. In the backend settings UI, **Self-hosted image models → Self-hosted image
   base URL** = `http://wookiee_sd:7860/v1`. The compose file already passes
   this as `IMAGE_LOCAL_BASE_URL`, so it should be pre-filled.
3. Open a licence → **Image models** → tick the two under *Self-hosted*.

No API key: the service has no credential and is on the `default` compose
network only, never `dokploy-network`. **Do not expose it publicly** — there
is no auth in front of it, and an open image generator will be found and used.

## Licensing

Both checkpoints are CreativeML OpenRAIL-M. Commercial use is permitted, with
use restrictions attached that propagate to anyone you serve. Read the model
cards before offering these as a paid tier — that is a decision about your
terms of service, not something this README settles.
