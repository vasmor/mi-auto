#!/usr/bin/env python3
"""
KIE AI — генерация изображений для страницы «Ремонт двигателя».
Модель: nano-banana-2 (Google Gemini 3.1 Flash)

Запуск:
    python3 docs/generate-images-remont-dvigatelya.py

Изображения сохраняются в img/remont-dvigatelya/
"""

import os
import time
import json
import urllib.request
import urllib.error

# ── Настройки ──────────────────────────────────────────────────────
API_KEY   = "e2c7b4e7874446f32af373c411644a72"
MODEL     = "nano-banana-2"
BASE_URL  = "https://api.kie.ai/api/v1"
OUT_DIR   = os.path.join(os.path.dirname(__file__), "../img/remont-dvigatelya")
POLL_WAIT = 5
MAX_POLLS = 60

# ── Список изображений ─────────────────────────────────────────────
IMAGES = [
    {
        "filename": "sym-dym.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Close-up of car exhaust pipe emitting thick blue or white smoke, "
            "Mitsubishi car parked, engine problem visible, dramatic dark background, "
            "photorealistic automotive photography, no text, no logos"
        ),
    },
    {
        "filename": "sym-maslo.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Oil puddle or leak under car engine, mechanic inspecting oil stains "
            "on workshop floor under Mitsubishi, oil dipstick showing low level, "
            "photorealistic automotive service, no text"
        ),
    },
    {
        "filename": "sym-mosnost.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Car engine misfiring or losing power, driver pressing accelerator pedal "
            "with no response, engine struggling under hood, Mitsubishi interior view, "
            "cinematic automotive photography, photorealistic, no text"
        ),
    },
    {
        "filename": "sym-stuk.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Mechanic listening to car engine knocking sound with stethoscope tool, "
            "Mitsubishi engine bay open, diagnostic process, professional workshop "
            "lighting, photorealistic automotive, no text"
        ),
    },
    {
        "filename": "sym-peregrev.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Car temperature gauge needle in red zone overheating warning, steam rising "
            "from under Mitsubishi car hood, dramatic roadside scene, photorealistic "
            "automotive photography, no text"
        ),
    },
    {
        "filename": "sym-check.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Close-up of Mitsubishi car dashboard with check engine warning light "
            "glowing orange, dark interior background, other warning lights visible, "
            "dramatic moody lighting, photorealistic automotive photography, no text"
        ),
    },
]


# ── Хелперы ────────────────────────────────────────────────────────

def api_request(method, path, body=None):
    url = BASE_URL + path
    data = json.dumps(body).encode() if body else None
    req = urllib.request.Request(
        url,
        data=data,
        headers={
            "Authorization": f"Bearer {API_KEY}",
            "Content-Type": "application/json",
        },
        method=method,
    )
    with urllib.request.urlopen(req, timeout=30) as resp:
        return json.loads(resp.read().decode())


def create_task(prompt, aspect_ratio):
    return api_request("POST", "/jobs/createTask", {
        "model": MODEL,
        "input": {
            "prompt": prompt,
            "aspect_ratio": aspect_ratio,
            "resolution": "1K",
            "output_format": "jpg",
        },
    })


def poll_task(task_id):
    for i in range(MAX_POLLS):
        time.sleep(POLL_WAIT)
        resp = api_request("GET", f"/jobs/recordInfo?taskId={task_id}")
        data = resp.get("data", {})
        state    = data.get("state", "")
        progress = data.get("progress", 0)
        print(f"  [{i+1}/{MAX_POLLS}] state={state} progress={progress}%")

        if state == "success":
            result = json.loads(data.get("resultJson", "{}"))
            urls = result.get("resultUrls", [])
            return urls[0] if urls else None

        if state == "fail":
            print(f"  Ошибка: {data.get('failMsg', 'unknown')}")
            return None

    print("  Таймаут — изображение не готово")
    return None


def download_image(url, filepath):
    req = urllib.request.Request(
        url,
        headers={"User-Agent": "Mozilla/5.0"},
    )
    with urllib.request.urlopen(req, timeout=30) as resp:
        with open(filepath, "wb") as f:
            f.write(resp.read())


# ── Основной цикл ──────────────────────────────────────────────────

def main():
    os.makedirs(OUT_DIR, exist_ok=True)
    print(f"Папка: {OUT_DIR}\n")

    for idx, img in enumerate(IMAGES, 1):
        filepath = os.path.join(OUT_DIR, img["filename"])

        if os.path.exists(filepath):
            print(f"[{idx}/{len(IMAGES)}] Пропускаем {img['filename']} (уже есть)")
            continue

        print(f"[{idx}/{len(IMAGES)}] Генерируем {img['filename']} ...")

        try:
            resp = create_task(img["prompt"], img["aspect_ratio"])
            task_id = resp.get("data", {}).get("taskId")

            if not task_id:
                print(f"  Не получен taskId: {resp}")
                continue

            print(f"  taskId: {task_id}")
            image_url = poll_task(task_id)

            if image_url:
                download_image(image_url, filepath)
                print(f"  Сохранено: {filepath}")
            else:
                print(f"  Не удалось получить изображение")

        except Exception as e:
            print(f"  Ошибка: {e}")

        print()

    print("Готово!")


if __name__ == "__main__":
    main()
