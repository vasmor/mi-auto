# Рабочий процесс: Наполнение темы MI-AUTO

## Context
Тема `mi-auto` разработана и готова к наполнению. WordPress уже развёрнут на `miauto.dev-dynamic.ru`. Задача — перенести и адаптировать контент со старого сайта `mi-auto.ru` в новую тему через Carbon Fields, WP-CLI и rsync-деплой. Верстку сверять с Figma по мере указания пользователя.

---

## Архитектура рабочего процесса

### Инструменты
| Инструмент | Назначение |
|---|---|
| `WebFetch` + агент | Парсинг контента со старого сайта mi-auto.ru |
| `WP-CLI` (SSH) | Создание/обновление постов, страниц, опций, вложений |
| `bash deploy.sh` | Деплой изменений PHP/CSS/JS файлов (~5 сек) |
| `Figma WebFetch` | Получение структуры макетов для сверки верстки |
| `Edit/Write` | Правки в файлах темы |

### Цикл изменений
```
1. Анализ контента (WebFetch старого сайта)
2. Адаптация → Claude генерирует/правит текст под новый дизайн
3. Заполнение через WP-CLI (SSH) или wp-admin
4. Деплой изменений темы: bash deploy.sh
5. Сверка с Figma при указании пользователя
6. Правка CSS/PHP файлов → deploy.sh → проверка
```

---

## Фаза 0: Настройка окружения

### 0.0 Первичная установка темы на сервер

**Шаг 1 — Загрузить файлы темы через rsync:**

`vendor/` не исключён из deploy.sh → Carbon Fields загрузится вместе с темой.

```bash
bash deploy.sh
```

Rsync создаст директорию `/wp-content/themes/mi-auto/` автоматически, если её нет.

**Шаг 2 — Активировать тему через WP Admin:**

Перейти в браузере: `https://miauto.dev-dynamic.ru/wp-admin/themes.php`
→ Найти «MI-AUTO» → нажать «Активировать»

> **Альтернатива через WP-CLI** (после установки WP-CLI в шаге 0.1):
> ```bash
> wp theme activate mi-auto --path=/var/www/u1791919/data/www/miauto.dev-dynamic.ru
> ```

---

### 0.1 Установка WP-CLI без root

SSH на сервер и выполнить последовательно:
```bash
# Скачать WP-CLI в домашнюю директорию
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mkdir -p ~/bin
mv wp-cli.phar ~/bin/wp

# Проверить что ~/bin в PATH (добавить в ~/.bashrc если нет)
echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc

# Проверка
wp --info --path=/var/www/u1791919/data/www/miauto.dev-dynamic.ru
```

Все дальнейшие WP-CLI команды используют флаг:
`--path=/var/www/u1791919/data/www/miauto.dev-dynamic.ru`

### 0.2 Базовая настройка WordPress
```bash
wp option update blogname "МИ-АВТО" --path=/var/www/u1791919/data/www/miauto.dev-dynamic.ru
wp option update timezone_string "Europe/Moscow" --path=/var/www/u1791919/data/www/miauto.dev-dynamic.ru
```

### 0.3 Запуск demo-import
Перейти в браузере: `https://miauto.dev-dynamic.ru/wp-admin/?miauto_setup=1`

Это создаст:
- Все страницы (Front, About, Services, Prices, Contacts, Works)
- CPT записи (miauto_service, miauto_model, miauto_work) с тестовыми данными
- Форму CF7
- Навигационное меню
- Настройки страниц и WordPress

---

## Фаза 1: Глобальные настройки (Theme Options)

**Источник:** парсинг mi-auto.ru (header, footer, contacts)
**Carbon Fields:** `inc/carbon-fields/fields-theme-options.php`
**Редактирование:** WP Admin → Настройки → MI-AUTO Options

### Порядок заполнения:
1. **Контакты** — адрес, телефоны, часы, email, соцсети, iframe карты
2. **Header** — логотип (текст), слоган, тексты кнопок
3. **Footer** — партнёры, преимущества, ссылки, копирайт
4. **Рейтинг** — звёзды, количество отзывов, источник
5. **Форма записи** — ID CF7 формы, фоновое изображение

---

## Фаза 2: Наполнение главной страницы

**Шаблон:** `front-page.php`

### 2.1 Hero-слайдер
**Поля:** `miauto_hero_slides`, `miauto_hero_features`
- Адаптированные заголовки и тексты слайдов
- Фото из старого сайта или из `img/`

### 2.2 Модели авто — CPT `miauto_model`
- Записи для каждой марки (Toyota, Lexus, Hyundai и т.д.)
- Обложка = логотип марки

### 2.3 Услуги — CPT `miauto_service`
**Поля на карточке:** `title`, `thumbnail`, `miauto_service_price`
- ~8-10 услуг: ТО, диагностика, ходовая, кузов, электрика и т.д.

### 2.4 О компании
**Поля:** `miauto_about_title`, `miauto_about_text`, `miauto_about_image`
- Адаптированный текст из старого сайта

### 2.5 Детали услуг / табы
**Поле:** `miauto_svc_details_tabs`
- Вкладки с иконками SVG, описанием, списком работ, ценой, CTA

### 2.6 Статьи
**Поля:** `miauto_articles_title`, `miauto_articles_count`
- 3-5 записей блога

---

## Фаза 3: Наполнение страниц

### 3.1 Страница «Услуги» (`page-services.php`)
Отображает все CPT `miauto_service` автоматически — достаточно создать их в Фазе 2.3.

### 3.2 Страница «О компании» (`page-about.php`)
| Секция | Поля Carbon Fields |
|---|---|
| Hero | `miauto_about_hero_badge/title/accent/texts/image` |
| Intro | `miauto_about_intro_title/texts/image` |
| Процесс работы | `miauto_work_process_title/subtitle/steps` |
| Преимущества | `miauto_advantages_title/cards` |

### 3.3 Страница «Цены» (`page-prices.php`)
**Поле:** `miauto_prices_models` — вложенная структура: марка → категория → строки с ценами.
Данные из прайс-листа старого сайта.

### 3.4 Страница «Контакты» (`page-contacts.php`)
Заполняется автоматически из Theme Options (Фаза 1).

### 3.5 Страницы услуг (`single-miauto_service.php`)
Для каждого Service CPT:
| Секция | Поля |
|---|---|
| Hero | `miauto_sc_hero_subtitle/features/cta_*/image/stats` |
| Симптомы | `miauto_sc_symptoms_title/subtitle/cards/cta_*` |
| Список работ | `miauto_sc_svc_list_title/items` |
| Цены | `miauto_sc_prices_title/subtitle/rows/footer_*` |
| Гарантия | `miauto_sc_warranty_title/subtitle/cards` |

### 3.6 Портфолио — CPT `miauto_work`
5-10 кейсов с полями: марка, пробег, проблема, что сделано, цена, галерея.

---

## Фаза 4: Сверка верстки с Figma (по запросу)

**Figma:** https://www.figma.com/design/7hBqN6MHAfzJAFtywbvIl1/CTO--Copy-?node-id=8207-2294&m=dev

**Процесс для каждой секции:**
1. Пользователь указывает секцию
2. Claude читает CSS + PHP шаблон секции
3. Получает данные из Figma через WebFetch API
4. Сравнивает отступы, шрифты, цвета, сетку
5. Правит файл → `bash deploy.sh` → проверка

**Соответствие файлов:**
| Секция | CSS | PHP шаблон |
|---|---|---|
| Hero | `css/hero.css` | `template-parts/sections/hero.php` |
| Модели авто | `css/car-models.css` | `template-parts/sections/car-models.php` |
| Услуги | `css/services.css` | `template-parts/sections/services.php` |
| О компании | `css/about.css` | `template-parts/sections/about.php` |
| Детали услуг | `css/svc-details.css` | `template-parts/sections/svc-details.php` |
| Контакты | `css/contacts.css` | `template-parts/sections/contacts.php` |
| Форма записи | `css/form-section.css` | `template-parts/sections/form-section.php` |
| О компании Hero | `css/about-hero.css` | `template-parts/sections/about-hero.php` |
| Преимущества | `css/advantages.css` | `template-parts/sections/advantages.php` |
| Процесс работы | `css/work-process.css` | `template-parts/sections/work-process.php` |
| Цены | `css/prices.css` | `template-parts/sections/prices.php` |
| Страница услуги | `css/service-card.css` | `template-parts/sections/service-card.php` |
| Хедер | `css/header.css` | `header.php` |
| Футер | `css/footer.css` | `footer.php` |

---

## Деплой изменений

```bash
# Проверка без применения
bash deploy.sh --dry-run

# Реальный деплой (~5 сек)
bash deploy.sh
```

Контент в БД (посты, опции Carbon Fields) деплой не затрагивает — только PHP/CSS/JS файлы темы.

---

## Порядок выполнения

| Приоритет | Этап |
|---|---|
| 1 | **Фаза 0:** deploy.sh → активация темы → WP-CLI → demo-import |
| 2 | **Фаза 1:** Theme Options (контакты, header, footer) |
| 3 | **Фаза 2:** Главная страница |
| 4 | **Фаза 3:** Остальные страницы |
| По запросу | **Фаза 4:** Сверка с Figma |

---

## Критические файлы темы

| Файл | Назначение |
|---|---|
| `deploy.sh` | Деплой на сервер |
| `inc/demo-import/demo-import.php` | Базовая структура данных |
| `inc/carbon-fields/fields-theme-options.php` | Глобальные настройки |
| `inc/carbon-fields/fields-home.php` | Поля главной страницы |
| `inc/carbon-fields/fields-service.php` | Поля страниц услуг |
| `inc/carbon-fields/fields-about.php` | Поля страницы о компании |
| `inc/carbon-fields/fields-prices.php` | Поля страницы цен |
| `template-parts/sections/` | 19 шаблонов секций |
