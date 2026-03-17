# MI-AUTO — Состояние и структура темы

## 1. Общие сведения

| Параметр | Значение |
|----------|----------|
| Название темы | MI-AUTO |
| Версия | 1.0.0 |
| Автор | Dynamic IT |
| Text Domain | `miauto` |
| Carbon Fields | v3.6.9 (Composer) |
| Зависимости | Contact Form 7, Yoast SEO |

---

## 2. Структура файлов

```
mi-auto/
├── functions.php                       # Автозагрузка Composer, подключение модулей
├── style.css                           # Метаданные темы
├── front-page.php                      # Главная страница
├── home.php                            # Листинг блога
├── page.php                            # Страница по умолчанию
├── page-about.php                      # Шаблон «О компании»
├── page-contacts.php                   # Шаблон «Контакты»
├── page-prices.php                     # Шаблон «Цены»
├── page-services.php                   # Шаблон «Услуги»
├── page-works.php                      # Шаблон «Наши работы»
├── single.php                          # Одиночная запись (блог)
├── single-miauto_service.php           # Одиночная услуга (CPT)
├── header.php / footer.php / index.php
├── vendor/                             # Carbon Fields (Composer)
├── inc/
│   ├── theme-setup.php                 # Константы, theme supports, меню
│   ├── helpers.php                     # Утилиты (miauto_get_option, miauto_get_meta, и др.)
│   ├── custom-post-types.php           # 4 CPT
│   ├── enqueue/enqueue.php             # Скрипты и стили
│   ├── carbon-fields/
│   │   ├── fields-init.php             # Boot CF + подключение всех полей
│   │   ├── fields-theme-options.php    # 2 контейнера theme_options (вкладки)
│   │   ├── fields-home.php             # Поля главной страницы
│   │   ├── fields-about.php            # Поля «О компании»
│   │   ├── fields-prices.php           # Поля «Цены»
│   │   ├── fields-service.php          # Поля CPT miauto_service
│   │   ├── fields-works.php            # Поля CPT miauto_work
│   │   └── fields-contacts.php         # Поля «Контакты»
│   └── demo-import/demo-import.php     # Импорт демо-контента
├── template-parts/sections/            # 19 секций-компонентов (плоская структура)
├── css/                                # Все стили (base + 22 секции)
├── js/                                 # Все скрипты (11 секций)
└── img/                                # Демо-изображения для импорта
```

---

## 3. Пользовательские типы записей (CPT)

**Файл:** `inc/custom-post-types.php` — хук `init`

| CPT | Slug | Метка | Архив | Публичный | REST | Поддержка |
|-----|------|-------|-------|-----------|------|-----------|
| `miauto_brand` | `brand` | Бренды | Нет | Да | Да | title, thumbnail |
| `miauto_model` | `models` | Модели авто | Да | Да | Да | title, thumbnail |
| `miauto_service` | `services` | Услуги | Да | Да | Нет | title, thumbnail |
| `miauto_work` | `works` | Работы | Да | Да | Нет | title, thumbnail |

---

## 4. Таксономии

Кастомных таксономий **нет**. Для блога используется стандартная таксономия `category`.

---

## 5. Навигационные меню

**Файл:** `inc/theme-setup.php`

| Локация | Название |
|---------|----------|
| `primary` | Основная навигация |
| `mobile` | Мобильная навигация |
| `footer` | Меню в подвале |

---

## 6. Шаблоны страниц

| Файл | Template Name | Назначение |
|------|---------------|------------|
| `front-page.php` | Главная | Главная (WP hierarchy + шаблон) |
| `page-about.php` | О компании | Страница «О компании» |
| `page-contacts.php` | Контакты | Страница «Контакты» |
| `page-prices.php` | Цены | Страница «Цены» |
| `page-services.php` | Услуги | Листинг услуг |
| `page-works.php` | Наши работы | Листинг работ/портфолио |
| `home.php` | — | Листинг блога |
| `single-miauto_service.php` | — | Одиночная услуга |
| `single.php` | — | Одиночная статья блога |

---

## 7. Carbon Fields — контейнеры и поля

### 7.1. Инициализация

**Файл:** `inc/carbon-fields/fields-init.php`

- Composer autoload загружается в `functions.php`
- `Carbon_Fields::boot()` вызывается на хуке `after_setup_theme`
- Все поля регистрируются на хуке `carbon_fields_register_fields`

### 7.2. Theme Options (2 контейнера с вкладками)

**Файл:** `inc/carbon-fields/fields-theme-options.php`

#### Контейнер «Опции темы» (главный пункт меню)

**Вкладка: Top Bar**
| Поле | Тип | По умолчанию |
|------|-----|-------------|
| `miauto_top_bar_enabled` | checkbox | true |
| `miauto_top_bar_label` | text | — |
| `miauto_top_bar_text` | textarea | — |

**Вкладка: Header**
| Поле | Тип | По умолчанию |
|------|-----|-------------|
| `miauto_logo_text` | text | MI-AUTO.ru |
| `miauto_slogan` | text | Ремонт Mitsubishi всех моделей в одном центре |
| `miauto_online_text` | text | Задайте вопрос, мы сейчас онлайн |
| `miauto_callback_text` | text | Обратный звонок |

**Вкладка: Footer**
| Поле | Тип |
|------|-----|
| `miauto_footer_partners` | complex (title + url) |
| `miauto_footer_advantages` | complex (title + url) |
| `miauto_footer_privacy_text` | text |
| `miauto_footer_privacy_url` | text |
| `miauto_footer_developer_text` | text |

**Вкладка: Контакты**
| Поле | Тип | По умолчанию |
|------|-----|-------------|
| `miauto_address` | text | г. Москва, ул. Остаповский проезд 1, д. 10, стр. 1 |
| `miauto_hours` | text | Понедельник-Воскресенье с 10:00 до 21:00 |
| `miauto_hours_short` | text | Пн-Вс с 10:00 до 21:00 |
| `miauto_email` | text | info@mi-auto.ru |
| `miauto_phones` | complex | — (number + raw) |
| `miauto_vk_url` | text | — |
| `miauto_telegram_url` | text | — |

#### Контейнер «Общие блоки» (подпункт «Опции темы»)

**Вкладка: Рейтинг**
| Поле | Тип | По умолчанию |
|------|-----|-------------|
| `miauto_rating_stars` | text (number) | 5 |
| `miauto_rating_reviews` | text | (500+ отзывов) |
| `miauto_rating_source` | text | Рейтинг организации в Яндексе |

**Вкладка: Форма записи**
| Поле | Тип | По умолчанию |
|------|-----|-------------|
| `miauto_form_title` | text | Запишитесь на ТО или бесплатный осмотр! |
| `miauto_form_bg` | image | — |
| `miauto_form_cf7_id` | text | — |

**Вкладка: Партнёры**
| Поле | Тип | По умолчанию |
|------|-----|-------------|
| `miauto_partners_title` | text | Наши партнеры |
| `miauto_partners_gallery` | media_gallery (image) | — |

### 7.3. Post Meta — Главная страница (2 контейнера)

**Файл:** `inc/carbon-fields/fields-home.php`
**Условие:** `->where('post_template', '=', 'front-page.php')` ✅

#### «Главная: Hero-слайдер»
| Поле | Тип |
|------|-----|
| `miauto_hero_slides` | complex (image, image_alt, title, description, cta_text, cta_url) |
| `miauto_hero_features` | complex (text, svg) |

#### «Главная: Секции»
| Поле | Тип |
|------|-----|
| `miauto_car_models_title` | text |
| `miauto_services_title` | text |
| `miauto_services_more_text` | text |
| `miauto_about_title` | text |
| `miauto_about_image` | image |
| `miauto_about_text` | rich_text |
| `miauto_articles_title` | text |
| `miauto_articles_link_text` | text |
| `miauto_articles_count` | text (number) |
| `miauto_svc_details_title` | text |
| `miauto_svc_details_tabs` | complex (tab_id, tab_title, badge, panel_title, panel_text, features, price_label, price_value, cta_text, cta_url) |
| `miauto_contacts_title` | text |
| `miauto_contacts_decoration` | image |
| `miauto_contacts_map` | image |

### 7.4. Post Meta — О компании (4 контейнера)

**Файл:** `inc/carbon-fields/fields-about.php`
**Условие:** `->where('post_template', '=', 'page-about.php')` ✅

- **О компании — Герой:** badge, title, accent, texts (complex), image
- **О компании — Подробнее:** title, texts (complex), image
- **Как мы работаем:** title, subtitle, steps (complex: svg, title, text)
- **Наши преимущества:** title, cards (complex: svg, title, text)

### 7.5. Post Meta — Цены (1 контейнер)

**Файл:** `inc/carbon-fields/fields-prices.php`
**Условие:** `->where('post_template', '=', 'page-prices.php')` ✅

- **Прайс-лист:** title, subtitle, models → categories → rows (3 уровня вложенности)

### 7.6. Post Meta — Услуга / miauto_service (6 контейнеров)

**Файл:** `inc/carbon-fields/fields-service.php`
**Условие:** `->where('post_type', '=', 'miauto_service')` ✅

- **Услуга — Герой:** subtitle, features, CTA кнопки, image, stats
- **Услуга — Симптомы:** title, subtitle, cards (image, title, desc), CTA
- **Услуга — Список работ:** title, items (title, desc)
- **Услуга — Цены:** title, subtitle, rows (name, price), footer
- **Услуга — Гарантия:** title, subtitle, cards (svg, text)
- **Услуга — Цена (для карточки):** `miauto_service_price`

### 7.7. Post Meta — Работа / miauto_work (1 контейнер)

**Файл:** `inc/carbon-fields/fields-works.php`
**Условие:** `->where('post_type', '=', 'miauto_work')` ✅

- **Детали работы:** model, mileage, issue, defects, done, price, duration, gallery

### 7.8. Post Meta — Контакты (1 контейнер)

**Файл:** `inc/carbon-fields/fields-contacts.php`
**Условие:** `->where('post_template', '=', 'page-contacts.php')` ✅

- **Контакты — Секция:** title, decoration (image), map (image)

---

## 8. Вспомогательные функции

**Файл:** `inc/helpers.php`

| Функция | Назначение |
|---------|------------|
| `miauto_highlight_title($title, $accent, $class)` | Оборачивает акцентную часть заголовка в `<span>` |
| `miauto_get_option($key)` | Обёртка над `carbon_get_theme_option()` с проверкой |
| `miauto_get_meta($key, $post_id)` | Обёртка над `carbon_get_post_meta()` с проверкой |
| `miauto_kses_svg($svg)` | Санитизация SVG через `wp_kses()` |

---

## 9. Подключение скриптов и стилей

**Файл:** `inc/enqueue/enqueue.php` — хук `wp_enqueue_scripts`

- **CSS:** `css/base.css` (глобальный) + 22 файла секций в `css/` (регистрируются с зависимостью от base)
- **JS:** 11 файлов секций в `js/`
- **Глобально подключаются:** base, top-bar, header, footer, scroll-top (CSS + JS)
- **Остальные секции:** регистрируются в `enqueue.php`, подключаются по хэндлу внутри шаблонов секций
- **Cache busting:** через `filemtime()`
- **Nonce:** передаётся в JS через `wp_localize_script('miauto-header', 'miAutoData', ...)`

---

## 10. Найденные ошибки и несоответствия

Все ранее найденные ошибки исправлены:

- ~~10.1. `get_option('page_on_front')` в CF~~ → заменено на `->where('post_template', '=', 'front-page.php')`
- ~~10.2. Мёртвый код (4 файла)~~ → удалены
- ~~10.3. `$_SERVER['HTTP_HOST']` без санитизации~~ → заменено на `wp_parse_url( home_url(), PHP_URL_HOST )`
- ~~10.4. Нет nonce-проверки~~ → добавлена `wp_verify_nonce()`

---

## 11. Шаги развертывания на хостинге

1. Загрузить тему через ZIP-архив или FTP (включая `vendor/`)
2. Активировать тему в админке WordPress
3. Установить и активировать плагин **Contact Form 7**
4. Установить и активировать плагин **Yoast SEO** (необходим для хлебных крошек)
5. Если `vendor/` не включён в архив — запустить `composer install` в директории темы
6. Перейти по адресу `/wp-admin/?miauto_setup=1` для импорта демо-контента
7. Проверить: Настройки → Чтение → Статическая главная страница
8. Проверить: Внешний вид → Меню → Привязки к областям
9. Проверить: Настройки → Постоянные ссылки (должно быть `/%postname%/`)
10. Проверить все страницы на фронтенде
