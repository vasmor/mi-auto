#!/usr/bin/env python3
"""
KIE AI — генерация изображений для страницы «Автоэлектрика».
Модель: nano-banana-2 (Google Gemini 3.1 Flash)

Запуск:
    python3 docs/generate-images-avtoelektrika.py

Изображения сохраняются в img/avtoelektrika/
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
OUT_DIR   = os.path.join(os.path.dirname(__file__), "../img/avtoelektrika")
POLL_WAIT = 5    # секунд между проверками
MAX_POLLS = 60   # максимум попыток (~5 минут)

# ── Список изображений ─────────────────────────────────────────────
IMAGES = [
    {
        "filename": "hero-main.jpg",
        "aspect_ratio": "3:2",
        "prompt": (
            "Professional auto electrician in dark branded uniform connecting "
            "OBD-II diagnostic scanner to Mitsubishi car interior, laptop screen "
            "showing diagnostic software interface with error codes, clean modern "
            "car service workshop, soft professional lighting, shallow depth of field, "
            "photorealistic, high quality automotive photography, no text, no logos"
        ),
    },
    {
        "filename": "sym-check-engine.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Close-up of Mitsubishi car dashboard with orange check engine warning "
            "light glowing, dark interior background, other dashboard lights visible, "
            "dramatic moody lighting, photorealistic automotive photography, "
            "shallow depth of field, no text"
        ),
    },
    {
        "filename": "sym-engine-start.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Car owner hand turning ignition key in Mitsubishi car, frustration implied, "
            "dashboard warning lights on, dark car interior, cinematic lighting, "
            "photorealistic, close-up shot, no text, no logos"
        ),
    },
    {
        "filename": "sym-battery.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Mechanic in gloves checking car battery voltage with digital multimeter, "
            "open car hood in service workshop, battery terminals visible, professional "
            "automotive service, soft workshop lighting, photorealistic, no text"
        ),
    },
    {
        "filename": "sym-abs-srs.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Car dashboard with multiple warning lights illuminated including ABS and "
            "SRS airbag warning indicators glowing red and orange, dark Mitsubishi car "
            "interior, dramatic close-up shot, photorealistic, high quality, no text"
        ),
    },
    {
        "filename": "sym-ac.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Close-up of car air conditioning vents not blowing cold air, driver hand "
            "touching vent, hot summer atmosphere inside car, Mitsubishi interior, "
            "warm tones, photorealistic, cinematic style, no text"
        ),
    },
    {
        "filename": "sym-alarm.jpg",
        "aspect_ratio": "4:3",
        "prompt": (
            "Car key fob with alarm remote control in hand, Mitsubishi car in background "
            "with hazard lights flashing, underground parking evening setting, "
            "cinematic lighting, photorealistic, no text, no logos"
        ),
    },
    {
        "filename": "example-diagnostics.jpg",
        "aspect_ratio": "3:2",
        "prompt": (
            "Professional auto electrician connecting laptop with diagnostic software "
            "to Mitsubishi Outlander, reading live sensor data on screen, clean service "
            "workshop, overhead lighting, photorealistic professional photo, no text"
        ),
    },
    {
        "filename": "example-wiring.jpg",
        "aspect_ratio": "3:2",
        "prompt": (
            "Close-up of auto electrician hands carefully repairing car wiring harness "
            "with precision tools, heat shrink tubing on wire connections, professional "
            "workshop environment, macro photography style, sharp focus, photorealistic, "
            "no text"
        ),
    },
    {
        "filename": "example-starter.jpg",
        "aspect_ratio": "3:2",
        "prompt": (
            "Mechanic in gloves holding disassembled car alternator or starter motor, "
            "clean workshop background, professional lighting, components clearly visible, "
            "automotive repair photography, photorealistic, no text, no logos"
        ),
    },
    {
        "filename": "example-ecu.jpg",
        "aspect_ratio": "3:2",
        "prompt": (
            "Auto electrician working on car electronic control unit ECU on workbench, "
            "diagnostic equipment connected, oscilloscope screen visible in background, "
            "professional electronics repair workshop, sharp lighting, photorealistic, "
            "no text"
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
