# Промпт для агента Claude Code: интеграция верстки в WordPress-тему

---

## Контекст задачи

Ты — агент, выполняющий задачу без участия пользователя. Задача будет выполняться в автономном режиме (например, ночью). Твоя цель — создать полноценную тему WordPress на основе готовой HTML/CSS/JS-верстки, соблюдая правила из файла `RULES_WORDPRESS.md`.

**Важно:** Тему нужно только создать как набор файлов, готовых к установке в WordPress. Разворачивать WordPress не требуется.

---

## Критическое правило: управление опасными операциями

> ⚠️ **ЗАПРЕТ НА УДАЛЕНИЕ ФАЙЛОВ БЕЗ ПОДТВЕРЖДЕНИЯ**
>
> В ходе работы ты можешь столкнуться с необходимостью удалить файлы (например, исходные HTML-файлы верстки, дубликаты, временные файлы). **Не выполняй никакие операции удаления** — ни `rm`, ни `unlink`, ни перезапись с пустым содержимым.
>
> Вместо этого:
> 1. Продолжай работу дальше — не останавливайся из-за этого.
> 2. Накапливай список файлов-кандидатов на удаление в файле `_PENDING_DELETIONS.md` (в корне темы) в формате:
>    ```
>    ## Файлы, ожидающие подтверждения удаления
>    - `путь/к/файлу` — причина (например: «исходный HTML, уже интегрирован в шаблон»)
>    ```
> 3. После завершения всей работы — напомни пользователю об этом файле и попроси подтвердить удаление.

---

## Шаг 0: Подготовка и анализ

Перед началом работы выполни следующее:

1. **Изучи структуру верстки.** Просмотри все HTML-файлы, CSS, JS, изображения. Составь для себя карту:
   - Список страниц (например: главная, о нас, услуги, контакты и т.д.)
   - Список секций на каждой странице (шапка, герой, преимущества, CTA, футер и т.д.)
   - Список CSS-файлов и к каким секциям/страницам они относятся
   - Список JS-файлов и их назначение
   - Сторонние библиотеки (Swiper, Fancybox, AOS и т.д.) — их версии и способ подключения

2. **Выбери slug темы.** Определи короткий латинский slug из названия проекта (или используй `mytheme` как fallback). Все функции, константы, handles ресурсов будут использовать этот префикс.

3. **Зафикси план** — создай файл `_INTEGRATION_PLAN.md` в корне темы с кратким планом: список страниц → шаблоны, список секций → файлы шаблонов, список ресурсов → handles.

---

## Шаг 1: Структура папок темы

Создай следующую структуру в папке темы (папка называется по slug, например `mytheme/`):

```
mytheme/
├── style.css                  # Заголовок темы (обязательно для WP)
├── functions.php              # Подключение всех inc-файлов
├── index.php                  # Fallback-шаблон
├── front-page.php             # Главная страница (если есть)
├── page.php                   # Базовый шаблон страницы
├── page-{slug}.php            # Шаблоны конкретных страниц (по одному на страницу)
├── header.php                 # Шапка сайта
├── footer.php                 # Подвал сайта
│
├── template-parts/
│   └── sections/
│       ├── hero/
│       │   ├── hero.php
│       │   ├── hero.css
│       │   └── hero.js        # (если есть интерактивность)
│       ├── [section-name]/
│       │   ├── [section-name].php
│       │   ├── [section-name].css
│       │   └── [section-name].js
│       └── ...
│
├── assets/
│   ├── css/
│   │   ├── base/
│   │   │   ├── reset.css
│   │   │   ├── variables.css
│   │   │   └── typography.css
│   │   └── components/
│   │       ├── buttons.css
│   │       └── forms.css
│   ├── js/
│   │   ├── main.js            # Базовый скрипт темы
│   │   └── libs/              # Локальные копии библиотек (если нужны)
│   └── images/
│       └── (статичные изображения темы — иконки, фоны и т.д.)
│
└── inc/
    ├── enqueue/
    │   └── enqueue.php        # Централизованная регистрация всех ресурсов
    ├── carbon-fields/
    │   ├── fields-init.php    # Подключение Carbon Fields
    │   └── fields-{page}.php  # Поля для каждой страницы/секции
    ├── helpers.php            # Вспомогательные функции (highlight_title и т.д.)
    └── theme-setup.php        # add_theme_support, register_nav_menus и т.д.
```

---

## Шаг 2: Базовые файлы темы

### `style.css`
```css
/*
Theme Name: [Название темы]
Theme URI:
Author: [Автор]
Description: [Описание]
Version: 1.0.0
Text Domain: mytheme
*/
```

### `functions.php`
Подключает все файлы из `inc/` через `require_once`. Не содержит логики напрямую.

### `inc/theme-setup.php`
- `add_theme_support( 'title-tag' )`
- `add_theme_support( 'post-thumbnails' )`
- `add_theme_support( 'html5', [...] )`
- `register_nav_menus()`
- Определение констант: `MYTHEME_VERSION`, `MYTHEME_DIR`, `MYTHEME_URI`

---

## Шаг 3: Перенос CSS

1. **Разбей CSS верстки** на логические части:
   - Глобальные стили (reset, переменные CSS, типографика) → `assets/css/base/`
   - Стили компонентов (кнопки, формы, модальные окна) → `assets/css/components/`
   - Стили каждой секции → `template-parts/sections/{section-name}/{section-name}.css`

2. **Не переименовывай классы** — сохраняй исходные BEM-классы верстки.

3. **Критические стили** (секции в первых ~1200px экрана — как правило: header, hero, первый content-блок):
   - Подключай их через `wp_add_inline_style` относительно базового стиля темы, или выводи в `<head>` отдельной очередью с высоким приоритетом.

---

## Шаг 4: Перенос JS

1. **Разбей JS** по назначению:
   - Глобальная логика (меню, scroll, resize, lazy) → `assets/js/main.js`
   - Логика каждой интерактивной секции → `template-parts/sections/{section-name}/{section-name}.js`

2. **Сторонние библиотеки:**
   - Если подключались через CDN — зарегистрируй через `wp_register_script()` с CDN-ссылкой
   - Если подключались локально — перенеси в `assets/js/libs/` и зарегистрируй с локальным путём
   - Тяжёлые библиотеки (Swiper, Fancybox и др.) для секций ниже первого экрана — регистрируй, но **не подключай сразу**: реализуй ленивую загрузку через IntersectionObserver в `main.js`

3. **Передача данных в JS** — через `wp_localize_script()`:
   - `ajax_url` → `admin_url( 'admin-ajax.php' )`
   - `theme_uri` → `MYTHEME_URI`
   - Любые строки и настройки, использовавшиеся в inline-скриптах верстки

---

## Шаг 5: Централизованная регистрация ресурсов (`inc/enqueue/enqueue.php`)

Создай функцию `mytheme_register_assets()`, вешаемую на `wp_enqueue_scripts`:

```php
// Пример структуры:

function mytheme_register_assets() {

    // --- РЕГИСТРАЦИЯ ---

    // Базовые стили
    wp_register_style( 'mytheme-variables', ... );
    wp_register_style( 'mytheme-reset', ..., array( 'mytheme-variables' ) );
    wp_register_style( 'mytheme-typography', ..., array( 'mytheme-reset' ) );

    // Компоненты
    wp_register_style( 'mytheme-buttons', ..., array( 'mytheme-typography' ) );

    // Стили секций
    wp_register_style( 'mytheme-section-hero', ..., array( 'mytheme-buttons' ) );
    wp_register_style( 'mytheme-section-about', ... );
    // ... и т.д.

    // Библиотеки JS
    wp_register_script( 'swiper', 'https://...', array(), '11.x', true );

    // Базовый JS темы
    wp_register_script( 'mytheme-main', ..., array( 'jquery' ), MYTHEME_VERSION, true );

    // Скрипты секций
    wp_register_script( 'mytheme-section-hero', ..., array( 'mytheme-main' ), MYTHEME_VERSION, true );
    // ...

    // --- ГЛОБАЛЬНОЕ ПОДКЛЮЧЕНИЕ ---

    wp_enqueue_style( 'mytheme-variables' );
    wp_enqueue_style( 'mytheme-reset' );
    wp_enqueue_style( 'mytheme-typography' );
    wp_enqueue_style( 'mytheme-buttons' );

    wp_enqueue_script( 'mytheme-main' );

    wp_localize_script( 'mytheme-main', 'mythemeData', array(
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'themeUri' => MYTHEME_URI,
        'nonce'    => wp_create_nonce( 'mytheme_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'mytheme_register_assets' );
```

Стили и скрипты **секций** подключай в самих шаблонах секций (см. Шаг 7).

---

## Шаг 6: Шаблоны страниц

Для каждой HTML-страницы верстки создай соответствующий PHP-шаблон:

- `front-page.php` или `page-home.php` — главная
- `page-{slug}.php` — остальные страницы

Каждый шаблон страницы:
1. Вызывает `get_header()`
2. Последовательно подключает секции через `get_template_part( 'template-parts/sections/{name}/{name}', null, $args )`
3. Вызывает `get_footer()`

Аргументы `$args` передают `post_id` (ID текущей страницы) и при необходимости `skip_styles`.

---

## Шаг 7: Шаблоны секций

Для каждой секции (`template-parts/sections/{name}/{name}.php`):

### Начало файла — подключение ресурсов
```php
<?php
if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'mytheme-section-{name}' );
}
// Если секция интерактивна:
wp_enqueue_script( 'mytheme-section-{name}' );
```

### Получение данных
```php
$post_id = $args['post_id'] ?? get_the_ID();
$title   = carbon_get_post_meta( $post_id, '{name}_title' );
// ... остальные поля

// Ранний выход при отсутствии обязательных данных
if ( empty( $title ) ) {
    return;
}
```

### Разметка
- Перенести HTML из верстки
- Статичный текст заменить на PHP-переменные с экранированием:
  - Обычный текст: `<?php echo esc_html( $title ); ?>`
  - Rich text: `<?php echo wp_kses_post( wpautop( $description ) ); ?>`
  - URL: `<?php echo esc_url( $link ); ?>`
  - Атрибуты: `<?php echo esc_attr( $class ); ?>`
  - Изображения: `<?php echo wp_get_attachment_image( $image_id, 'full', false, array( 'loading' => 'lazy' ) ); ?>`
- Если заголовок содержит подсвечиваемую часть — использовать хелпер `mytheme_highlight_title( $title, $highlight )`

### Списки (циклы)
```php
foreach ( $items as $item ) {
    // вывод элемента списка
}
```

---

## Шаг 8: Метаполя Carbon Fields (`inc/carbon-fields/`)

### `fields-init.php`
```php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action( 'carbon_fields_register_fields', 'mytheme_register_fields' );
add_action( 'after_setup_theme', function() {
    \Carbon_Fields\Carbon_Fields::boot();
} );
```

### `fields-{section}.php` или `fields-{page}.php`
Для каждой секции определи контейнер и поля:

```php
Container::make( 'post_meta', __( 'Секция: Hero', 'mytheme' ) )
    ->where( 'post_id', '=', get_option( 'page_on_front' ) )
    ->add_fields( array(
        Field::make( 'text', 'hero_title', __( 'Заголовок', 'mytheme' ) ),
        Field::make( 'text', 'hero_title_highlight', __( 'Подсвечиваемая часть заголовка', 'mytheme' ) )
            ->set_help_text( __( 'Часть заголовка, которая будет выделена акцентным цветом', 'mytheme' ) ),
        Field::make( 'rich_text', 'hero_description', __( 'Описание', 'mytheme' ) ),
        Field::make( 'image', 'hero_image', __( 'Изображение', 'mytheme' ) ),
        // Кнопка как группа:
        Field::make( 'complex', 'hero_button', __( 'Кнопка', 'mytheme' ) )
            ->set_max( 1 )
            ->add_fields( array(
                Field::make( 'text', 'label', __( 'Текст кнопки', 'mytheme' ) ),
                Field::make( 'text', 'url', __( 'Ссылка', 'mytheme' ) ),
            ) ),
    ) );
```

Правила выбора типа поля:
| Данные               | Тип Carbon Fields  |
|----------------------|--------------------|
| Заголовок            | `text`             |
| Текст/описание       | `rich_text`        |
| Изображение          | `image`            |
| Кнопка               | `complex` (max 1)  |
| Список карточек      | `complex`          |
| Связь с постом       | `association`      |
| Переключатель        | `checkbox`         |
| Выпадающий список    | `select`           |
| Число                | `text` (number)    |

---

## Шаг 9: Хелперы (`inc/helpers.php`)

Реализуй минимум следующие функции:

```php
/**
 * Оборачивает подстроку заголовка в акцентный span.
 *
 * @param string $title     Полный текст заголовка.
 * @param string $highlight Подстрока для выделения.
 * @param string $class     CSS-класс span (по умолчанию из BEM-элемента).
 * @return string           Заголовок с обёрнутой подстрокой.
 */
function mytheme_highlight_title( $title, $highlight, $class = 'title-accent' ) {
    if ( empty( $highlight ) || empty( $title ) ) {
        return esc_html( $title );
    }
    $highlighted = '<span class="' . esc_attr( $class ) . '">' . esc_html( $highlight ) . '</span>';
    return str_replace( esc_html( $highlight ), $highlighted, esc_html( $title ) );
}
```

---

## Шаг 10: header.php и footer.php

### `header.php`
- `<!DOCTYPE html>`, `<html>`, `<head>` с `wp_head()`
- Логотип, навигационное меню через `wp_nav_menu()`
- Гамбургер/мобильное меню — разметка из верстки

### `footer.php`
- Разметка подвала из верстки
- `wp_footer()` перед `</body>`

---

## Шаг 11: Финальная проверка

После завершения работы пройдись по чеклисту и зафикси результат в `_INTEGRATION_PLAN.md`:

- [ ] `style.css` содержит корректный заголовок темы
- [ ] `functions.php` подключает все `inc/`-файлы
- [ ] Все ресурсы зарегистрированы в `inc/enqueue/enqueue.php`
- [ ] Стили секций подключаются условно — в шаблоне секции
- [ ] Скрипты секций подключаются условно — в шаблоне секции
- [ ] Все выводимые данные экранированы (`esc_html`, `esc_url`, `esc_attr`, `wp_kses_post`)
- [ ] Каждый шаблон секции имеет ранний выход при отсутствии обязательных данных
- [ ] Метаполя Carbon Fields определены для каждой секции каждой страницы
- [ ] Хелпер `mytheme_highlight_title()` используется везде, где есть подсвечиваемый текст
- [ ] Тяжёлые библиотеки (если есть) загружаются лениво
- [ ] PHPDoc добавлен ко всем функциям
- [ ] Файл `_PENDING_DELETIONS.md` заполнен (или содержит запись «нет файлов для удаления»)

---

## Финальный шаг: Сообщение пользователю

По завершении всей работы выведи итоговый отчёт:

```
✅ Интеграция завершена.

Тема: [название] / slug: [slug]
Страниц: [N]
Секций: [N]
Ресурсов зарегистрировано: [N стилей, N скриптов]

📋 Файлы, ожидающие подтверждения удаления: см. _PENDING_DELETIONS.md
   Пожалуйста, просмотри список и подтверди удаление вручную.

⚠️ Требует ручной проверки:
- [перечисли любые места, где ты сделал допущения или оставил TODO]
```

---

## Справочник: правила кодинга (краткая выжимка из RULES_WORDPRESS.md)

| Аспект | Правило |
|--------|---------|
| Отступы | 1 tab (4 пробела) |
| Файлы | kebab-case |
| Функции/переменные | snake_case с префиксом темы |
| Классы PHP | StudlyCaps с префиксом |
| Константы | UPPER_CASE |
| PHP-теги | Только `<?php ?>`, не `<? ?>` |
| Стили: пути | `get_template_directory_uri()` |
| Стили: версии | `MYTHEME_VERSION` или `filemtime()` |
| Стили: секции | Условно, в шаблоне секции |
| Скрипты: footer | Последний аргумент `true` |
| Скрипты: данные | `wp_localize_script()`, не inline |
| Экранирование текста | `esc_html()` |
| Экранирование URL | `esc_url()` |
| Экранирование attr | `esc_attr()` |
| Rich text | `wp_kses_post( wpautop() )` |
| Изображения | `wp_get_attachment_image()` с `loading="lazy"` |
| Удаление файлов | ❌ Запрещено без подтверждения пользователя |
