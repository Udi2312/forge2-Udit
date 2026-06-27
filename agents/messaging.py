import os
import json
import time
from typing import List, Dict, Any, Tuple


BASE_DIR = os.path.dirname(__file__)
CHANNELS_DIR = os.path.join(BASE_DIR, "channels")
os.makedirs(CHANNELS_DIR, exist_ok=True)


def _channel_path(name: str) -> str:
    safe = name.strip('#').replace(' ', '-').lower()
    return os.path.join(CHANNELS_DIR, f"{safe}.log")


def publish(channel: str, sender: str, text: str, meta: Dict[str, Any] = None) -> None:
    """Append a JSON message to the channel log."""
    meta = meta or {}
    msg = {"ts": time.time(), "sender": sender, "text": text, "meta": meta}
    path = _channel_path(channel)
    with open(path, 'a', encoding='utf-8') as f:
        f.write(json.dumps(msg, ensure_ascii=False) + "\n")


def read_since(channel: str, line_offset: int = 0) -> Tuple[List[Dict[str, Any]], int]:
    """Return messages since line_offset and the new offset."""
    path = _channel_path(channel)
    if not os.path.exists(path):
        return [], 0
    with open(path, 'r', encoding='utf-8') as f:
        lines = f.read().splitlines()
    new_lines = lines[line_offset:]
    msgs = [json.loads(l) for l in new_lines]
    return msgs, len(lines)


def get_offset(channel: str) -> int:
    path = _channel_path(channel)
    if not os.path.exists(path):
        return 0
    with open(path, 'r', encoding='utf-8') as f:
        return len(f.read().splitlines())
