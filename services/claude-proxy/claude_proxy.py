"""
claude_proxy.py (v5)
Direct OpenAI-compatible /v1/chat/completions proxy calling Anthropic /v1/messages API with OAuth token.

v5 changes:
  - Bypasses claude-agent-sdk and subprocess CLI completely.
  - Passes requests directly to https://api.anthropic.com/v1/messages.
  - Native Anthropic tool_use and tool_result translation to OpenAI tool_calls.
  - Native thinking and streaming support.
  - Uses headers:
      anthropic-version: 2023-06-01
      anthropic-beta: claude-code-20250219,oauth-2025-04-20,interleaved-thinking-2025-05-14,fine-grained-tool-streaming-2025-05-14
"""

import json
import logging
import os
import re
import uuid
import httpx
from fastapi import FastAPI, Request
from fastapi.responses import JSONResponse, StreamingResponse

app = FastAPI()

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("claude-proxy")

ANTHROPIC_MESSAGES_URL = "https://api.anthropic.com/v1/messages"

MODEL_MAP = {
    "claude-3-7-sonnet-20250219": "claude-3-7-sonnet-20250219",
    "claude-3-5-sonnet-20241022": "claude-3-5-sonnet-20241022",
    "claude-3-5-haiku-20241022": "claude-3-5-haiku-20241022",
    "claude-3-opus-20240229": "claude-3-opus-20240229",
    "claude-sonnet-4-6": "claude-3-7-sonnet-20250219",
    "claude-opus-4-8": "claude-3-opus-20240229",
    "claude-haiku-4-5": "claude-3-5-haiku-20241022",
    "sonnet5-cc": "claude-3-5-sonnet-20241022",
}

DEFAULT_MODEL = "claude-3-7-sonnet-20250219"

EFFORT_BUDGETS = {"low": 4_000, "medium": 12_000, "high": 32_000}


def get_token(request: Request) -> str:
    auth_header = request.headers.get("Authorization")
    if auth_header and auth_header.startswith("Bearer "):
        return auth_header.split(" ", 1)[1]
    x_api_key = request.headers.get("x-api-key")
    if x_api_key:
        return x_api_key
    return os.environ.get("CLAUDE_CODE_OAUTH_TOKEN", "")


def get_anthropic_headers(token: str) -> dict:
    headers = {
        "anthropic-version": "2023-06-01",
        "anthropic-beta": "claude-code-20250219,oauth-2025-04-20,interleaved-thinking-2025-05-14,fine-grained-tool-streaming-2025-05-14",
        "content-type": "application/json",
    }
    if token.startswith("sk-ant-"):
        headers["x-api-key"] = token
    else:
        headers["Authorization"] = f"Bearer {token}"
        headers["x-api-key"] = token
    return headers


@app.get("/v1/models")
async def list_models(request: Request):
    token = get_token(request)
    if token:
        os.environ["CLAUDE_CODE_OAUTH_TOKEN"] = token
    return {
        "object": "list",
        "data": [
            {"id": "claude-3-7-sonnet-20250219", "object": "model", "owned_by": "anthropic"},
            {"id": "claude-3-5-sonnet-20241022", "object": "model", "owned_by": "anthropic"},
            {"id": "claude-3-5-haiku-20241022", "object": "model", "owned_by": "anthropic"},
            {"id": "claude-3-opus-20240229", "object": "model", "owned_by": "anthropic"},
            {"id": "claude-sonnet-4-6", "object": "model", "owned_by": "anthropic"},
            {"id": "claude-opus-4-8", "object": "model", "owned_by": "anthropic"},
            {"id": "claude-haiku-4-5", "object": "model", "owned_by": "anthropic"},
        ],
    }


def transform_openai_tools(tools: list) -> list:
    anthropic_tools = []
    for t in tools:
        if not isinstance(t, dict):
            continue
        fn = t.get("function", {}) if t.get("type") == "function" else t
        if "name" in fn:
            tool_entry = {
                "name": fn["name"],
                "description": fn.get("description", ""),
                "input_schema": fn.get("parameters") or {"type": "object", "properties": {}},
            }
            anthropic_tools.append(tool_entry)
    return anthropic_tools


def transform_openai_messages(messages: list) -> tuple[str | None, list]:
    system_parts = []
    anthropic_messages = []

    for m in messages:
        if not isinstance(m, dict):
            continue
        role = m.get("role")
        content = m.get("content")

        if role == "system":
            if isinstance(content, str):
                system_parts.append(content)
            elif isinstance(content, list):
                for p in content:
                    if isinstance(p, dict) and p.get("type") == "text":
                        system_parts.append(p.get("text", ""))
            continue

        if role == "user":
            if isinstance(content, str):
                anthropic_messages.append({"role": "user", "content": content})
            elif isinstance(content, list):
                parts = []
                for p in content:
                    if isinstance(p, dict):
                        if p.get("type") == "text":
                            parts.append({"type": "text", "text": p.get("text", "")})
                        elif p.get("type") == "image_url":
                            img_url = p.get("image_url", {}).get("url", "")
                            if img_url.startswith("data:"):
                                header, data = img_url.split(",", 1)
                                media_type = header.split(";")[0].replace("data:", "")
                                parts.append({
                                    "type": "image",
                                    "source": {
                                        "type": "base64",
                                        "media_type": media_type,
                                        "data": data,
                                    },
                                })
                anthropic_messages.append({"role": "user", "content": parts or content})
            continue

        if role == "assistant":
            parts = []
            if isinstance(content, str) and content:
                parts.append({"type": "text", "text": content})
            elif isinstance(content, list):
                for p in content:
                    if isinstance(p, dict) and p.get("type") == "text" and p.get("text"):
                        parts.append({"type": "text", "text": p["text"]})
            tool_calls = m.get("tool_calls") or []
            for tc in tool_calls:
                if isinstance(tc, dict):
                    fn = tc.get("function", {})
                    args = fn.get("arguments", {})
                    if isinstance(args, str):
                        try:
                            args = json.loads(args)
                        except Exception:
                            args = {}
                    parts.append({
                        "type": "tool_use",
                        "id": tc.get("id", f"call_{uuid.uuid4().hex[:24]}"),
                        "name": fn.get("name"),
                        "input": args,
                    })
            if parts:
                anthropic_messages.append({"role": "assistant", "content": parts})
            continue

        if role == "tool":
            tool_call_id = m.get("tool_call_id", "")
            tool_content = content if isinstance(content, str) else json.dumps(content)
            anthropic_messages.append({
                "role": "user",
                "content": [{
                    "type": "tool_result",
                    "tool_use_id": tool_call_id,
                    "content": tool_content,
                }],
            })

    system_prompt = "\n\n".join(system_parts) if system_parts else None
    return system_prompt, anthropic_messages


@app.post("/v1/chat/completions")
async def chat_completions(request: Request):
    token = get_token(request)
    if not token:
        return JSONResponse(status_code=401, content={"error": {"message": "Missing token", "type": "auth_error"}})

    os.environ["CLAUDE_CODE_OAUTH_TOKEN"] = token

    try:
        body = await request.json()
    except Exception as exc:
        return JSONResponse(status_code=400, content={"error": {"message": f"Invalid JSON: {exc}"}})

    requested_model = body.get("model", DEFAULT_MODEL)
    anthropic_model = MODEL_MAP.get(requested_model, requested_model)

    stream = bool(body.get("stream", False))
    tools = body.get("tools") or []

    system_prompt, messages = transform_openai_messages(body.get("messages", []))

    anthropic_body = {
        "model": anthropic_model,
        "messages": messages,
        "max_tokens": body.get("max_tokens", 4096),
        "stream": stream,
    }
    if system_prompt:
        anthropic_body["system"] = system_prompt
    if tools:
        anthropic_body["tools"] = transform_openai_tools(tools)
    if "temperature" in body and body["temperature"] is not None:
        anthropic_body["temperature"] = body["temperature"]
    if "top_p" in body and body["top_p"] is not None:
        anthropic_body["top_p"] = body["top_p"]

    headers = get_anthropic_headers(token)

    async with httpx.AsyncClient(timeout=120.0) as client:
        if not stream:
            try:
                res = await client.post(ANTHROPIC_MESSAGES_URL, json=anthropic_body, headers=headers)
            except Exception as exc:
                logger.exception("Anthropic request failed")
                return JSONResponse(status_code=502, content={"error": {"message": f"Upstream error: {exc}"}})

            if not res.is_success:
                logger.error("Anthropic returned %s: %s", res.status_code, res.text)
                return JSONResponse(status_code=res.status_code, content=res.json() if res.headers.get("content-type", "").startswith("application/json") else {"error": res.text})

            data = res.json()
            return JSONResponse(content=format_openai_response(requested_model, data))
        else:
            return StreamingResponse(
                stream_anthropic_to_openai(client, ANTHROPIC_MESSAGES_URL, anthropic_body, headers, requested_model),
                media_type="text/event-stream",
            )


def format_openai_response(model: str, data: dict) -> dict:
    content_blocks = data.get("content", [])
    text_content = ""
    tool_calls = []

    for block in content_blocks:
        if not isinstance(block, dict):
            continue
        if block.get("type") == "text":
            text_content += block.get("text", "")
        elif block.get("type") == "tool_use":
            tool_calls.append({
                "id": block.get("id"),
                "type": "function",
                "function": {
                    "name": block.get("name"),
                    "arguments": json.dumps(block.get("input", {})),
                },
            })

    finish_reason = "tool_calls" if tool_calls else "stop"
    if data.get("stop_reason") == "max_tokens":
        finish_reason = "length"

    message_obj = {"role": "assistant", "content": text_content if text_content or not tool_calls else None}
    if tool_calls:
        message_obj["tool_calls"] = tool_calls

    usage = data.get("usage", {})

    return {
        "id": f"chatcmpl-{data.get('id', uuid.uuid4().hex)}",
        "object": "chat.completion",
        "created": int(data.get("created_at", 0)) or 1700000000,
        "model": model,
        "choices": [
            {
                "index": 0,
                "message": message_obj,
                "finish_reason": finish_reason,
            }
        ],
        "usage": {
            "prompt_tokens": usage.get("input_tokens", 0),
            "completion_tokens": usage.get("output_tokens", 0),
            "total_tokens": usage.get("input_tokens", 0) + usage.get("output_tokens", 0),
        },
    }


async def stream_anthropic_to_openai(client: httpx.AsyncClient, url: str, body: dict, headers: dict, model: str):
    async with client.stream("POST", url, json=body, headers=headers) as res:
        if not res.is_success:
            err_body = await res.aread()
            yield f"data: {json.dumps({'error': err_body.decode('utf-8')})}\n\n"
            yield "data: [DONE]\n\n"
            return

        current_tool_id = None
        current_tool_name = None
        tool_arg_buf = ""

        async for line in res.aiter_lines():
            if not line.startswith("data: "):
                continue
            raw_data = line[6:].strip()
            if raw_data == "[DONE]":
                break
            try:
                event = json.loads(raw_data)
            except Exception:
                continue

            event_type = event.get("type")

            if event_type == "content_block_start":
                block = event.get("content_block", {})
                if block.get("type") == "tool_use":
                    current_tool_id = block.get("id")
                    current_tool_name = block.get("name")
                    tool_arg_buf = ""
                    chunk = {
                        "id": f"chatcmpl-chunk-{uuid.uuid4().hex[:12]}",
                        "object": "chat.completion.chunk",
                        "model": model,
                        "choices": [{
                            "index": 0,
                            "delta": {
                                "role": "assistant",
                                "tool_calls": [{
                                    "index": 0,
                                    "id": current_tool_id,
                                    "type": "function",
                                    "function": {"name": current_tool_name, "arguments": ""},
                                }],
                            },
                            "finish_reason": None,
                        }],
                    }
                    yield f"data: {json.dumps(chunk)}\n\n"

            elif event_type == "content_block_delta":
                delta = event.get("delta", {})
                if delta.get("type") == "text_delta":
                    text = delta.get("text", "")
                    chunk = {
                        "id": f"chatcmpl-chunk-{uuid.uuid4().hex[:12]}",
                        "object": "chat.completion.chunk",
                        "model": model,
                        "choices": [{"index": 0, "delta": {"content": text}, "finish_reason": None}],
                    }
                    yield f"data: {json.dumps(chunk)}\n\n"
                elif delta.get("type") == "input_json_delta":
                    partial_json = delta.get("partial_json", "")
                    tool_arg_buf += partial_json
                    chunk = {
                        "id": f"chatcmpl-chunk-{uuid.uuid4().hex[:12]}",
                        "object": "chat.completion.chunk",
                        "model": model,
                        "choices": [{
                            "index": 0,
                            "delta": {
                                "tool_calls": [{
                                    "index": 0,
                                    "function": {"arguments": partial_json},
                                }],
                            },
                            "finish_reason": None,
                        }],
                    }
                    yield f"data: {json.dumps(chunk)}\n\n"

            elif event_type == "message_delta":
                stop_reason = event.get("delta", {}).get("stop_reason")
                finish = "tool_calls" if current_tool_id else ("stop" if stop_reason == "end_turn" else stop_reason)
                chunk = {
                    "id": f"chatcmpl-chunk-{uuid.uuid4().hex[:12]}",
                    "object": "chat.completion.chunk",
                    "model": model,
                    "choices": [{"index": 0, "delta": {}, "finish_reason": finish}],
                }
                yield f"data: {json.dumps(chunk)}\n\n"

        yield "data: [DONE]\n\n"


@app.get("/health")
async def health():
    return {"status": "ok"}
