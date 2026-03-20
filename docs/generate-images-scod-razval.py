#!/usr/bin/env python3
"""
KIE AI — генерация изображений для страницы «Сход-развал».
Модель: nano-banana-2 (Google Gemini 3.1 Flash)

Запуск:
    python3 docs/generate-images-scod-razval.py

Изображения сохраняются в img/scod-razval/
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
OUT_DIR   = os.path.join(os.path.dirname(__file__), "../img/scod-razval")
POLL_WAIT = 5    # секунд между проверками
MAX_POLLS = 60   # максимум попыток (~5 минут)

# ── Список изображений ─────────────────────────────────────────────
IMAGES = [
    {
        "filename": "hero-main.jpg",
        "aspect_ratio": "3:2",
        "prompt": (
            "Professional wheel alignment specialist setting up 3D alignment system "
            "on Mitsubishi car, modern car service workshop, alignment targets on wheels, "
            "computer screen showing wheel angles, professional lighting, photorealistic, "
            "no text"
        ),
    },
    {
        "filename": "sym-uvod.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Driver hand on steering wheel of Mitsubishi car being pulled to the side "
            "on straight road, close-up POV shot, dashboard visible, photorealistic, no text"
        ),
    },
    {
        "filename": "sym-iznos.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Close-up of car tire showing uneven tread wear on one side, worn inner or "
            "outer edge, comparison visible, photorealistic automotive, no text"
        ),
    },
    {
        "filename": "sym-vibraciya.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Close-up of car steering wheel vibrating at speed, motion blur on wheel, "
            "driver hands gripping, Mitsubishi interior, dramatic lighting, photorealistic, "
            "no text"
        ),
    },
    {
        "filename": "sym-vozvrat.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Car steering wheel not returning to center position after turn, driver manually "
            "correcting, Mitsubishi interior close-up, photorealistic, no text"
        ),
    },
    {
        "filename": "sym-skrip.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Mechanic inspecting front wheel and suspension components of Mitsubishi, "
            "checking ball joint and tie rod end, workshop setting, photorealistic, no text"
        ),
    },
    {
        "filename": "sym-podveska.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Mechanic replacing suspension arm or control arm on Mitsubishi, workshop pit, "
            "professional lighting, suspension components visible, photorealistic, no text"
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
