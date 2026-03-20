#!/usr/bin/env python3
"""
KIE AI — генерация изображений для страницы «Заправка кондиционера».
Модель: nano-banana-2 (Google Gemini 3.1 Flash)

Запуск:
    python3 docs/generate-images-zapravka-konditsionera.py

Изображения сохраняются в img/zapravka-konditsionera/
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
OUT_DIR   = os.path.join(os.path.dirname(__file__), "../img/zapravka-konditsionera")
POLL_WAIT = 5    # секунд между проверками
MAX_POLLS = 60   # максимум попыток (~5 минут)

# ── Список изображений ─────────────────────────────────────────────
IMAGES = [
    {
        "filename": "hero-main.jpg",
        "aspect_ratio": "3:2",
        "prompt": (
            "Auto technician charging car air conditioning system with refrigerant equipment, "
            "gauges and hoses connected to AC port, professional workshop, photorealistic, "
            "no text"
        ),
    },
    {
        "filename": "sym-ne-holodaet.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Car driver touching AC vent feeling warm air not cold, frustrated in hot summer "
            "car, Mitsubishi interior, photorealistic, no text"
        ),
    },
    {
        "filename": "sym-zapah.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Car interior with unpleasant smell from AC vents, driver reaction, "
            "photorealistic, no text"
        ),
    },
    {
        "filename": "sym-shum.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Close-up of car AC compressor clutch engaging with unusual noise, engine bay, "
            "Mitsubishi, photorealistic, no text"
        ),
    },
    {
        "filename": "sym-ne-vkl.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Car dashboard AC button not responding, AC light not illuminating, Mitsubishi "
            "climate control panel, photorealistic, no text"
        ),
    },
    {
        "filename": "sym-zimoy.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Car windshield interior fogged in winter morning, driver using defroster and "
            "AC to clear, Mitsubishi interior, photorealistic, no text"
        ),
    },
    {
        "filename": "sym-dva-goda.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Service calendar showing 2 year interval for AC recharge, car maintenance "
            "schedule, photorealistic, no text"
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
