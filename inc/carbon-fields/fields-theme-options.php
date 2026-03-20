<?php
/**
 * Theme Options fields (Carbon Fields).
 *
 * Two containers:
 * 1. "Опции темы"  — tabs: Top Bar, Header, Footer, Контакты
 * 2. "Общие блоки" — sub-page of "Опции темы", tabs: Рейтинг, Форма записи, Партнёры
 *
 * @package miauto
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

// ── Main page: Опции темы ───────────────────────────────────

$miauto_options = Container::make( 'theme_options', 'Опции темы' )
    ->set_page_menu_title( 'Опции темы' )

    ->add_tab( 'Top Bar', array(
        Field::make( 'checkbox', 'miauto_top_bar_enabled', 'Показать верхнюю панель' )
            ->set_default_value( true ),
        Field::make( 'text', 'miauto_top_bar_label', 'Метка (дата акции)' )
            ->set_help_text( 'Например: "Только до 24 февраля"' ),
        Field::make( 'textarea', 'miauto_top_bar_text', 'Текст акции' ),
    ) )

    ->add_tab( 'Header', array(
        Field::make( 'text', 'miauto_logo_text', 'Текст логотипа' )
            ->set_default_value( 'MI-AUTO.ru' ),
        Field::make( 'text', 'miauto_slogan', 'Слоган' )
            ->set_default_value( 'Ремонт Mitsubishi всех моделей в одном центре' ),
        Field::make( 'text', 'miauto_online_text', 'Текст онлайн-статуса' )
            ->set_default_value( 'Задайте вопрос, мы сейчас онлайн' ),
        Field::make( 'text', 'miauto_callback_text', 'Текст кнопки обратного звонка' )
            ->set_default_value( 'Обратный звонок' ),
    ) )

    ->add_tab( 'Footer', array(
        Field::make( 'complex', 'miauto_footer_partners', 'Партнёрские ссылки' )
            ->add_fields( array(
                Field::make( 'text', 'fpartner_title', 'Название' ),
                Field::make( 'text', 'fpartner_url', 'Ссылка' ),
            ) )
            ->set_header_template( '<%- fpartner_title %>' )
            ->set_layout( 'tabbed-horizontal' ),
        Field::make( 'text', 'miauto_footer_privacy_text', 'Текст политики конфиденциальности' )
            ->set_default_value( 'Политика конфиденциальности данных' ),
        Field::make( 'text', 'miauto_footer_privacy_url', 'Ссылка на политику' )
            ->set_default_value( '/privacy/' ),
        Field::make( 'text', 'miauto_footer_developer_text', 'Текст разработчика' )
            ->set_default_value( 'Разработка сайта Dynamic IT' ),
    ) )

    ->add_tab( 'Контакты', array(
        Field::make( 'text', 'miauto_contacts_section_title', 'Заголовок секции "Наши контакты"' )
            ->set_default_value( 'Наши контакты' ),
        Field::make( 'image', 'miauto_contacts_decoration', 'Декоративное изображение секции контактов' ),
        Field::make( 'textarea', 'miauto_contacts_map_embed', 'Код карты (iframe)' )
            ->set_help_text( 'Вставьте iframe-код карты, например от Яндекс.Карт' ),
        Field::make( 'text', 'miauto_address', 'Адрес' )
            ->set_default_value( 'г. Москва, ул. Остаповский проезд 1, д. 10, стр. 1' ),
        Field::make( 'text', 'miauto_hours', 'Время работы' )
            ->set_default_value( 'Понедельник-Воскресенье с 10:00 до 21:00' ),
        Field::make( 'text', 'miauto_hours_short', 'Время работы (краткое)' )
            ->set_default_value( 'Пн-Вс с 10:00 до 21:00' ),
        Field::make( 'text', 'miauto_email', 'E-mail' )
            ->set_default_value( 'info@mi-auto.ru' ),
        Field::make( 'complex', 'miauto_phones', 'Телефоны' )
            ->add_fields( array(
                Field::make( 'text', 'phone_number', 'Номер телефона' )
                    ->set_help_text( 'Формат: +7 (926) 338-39-29' ),
                Field::make( 'text', 'phone_raw', 'Номер для ссылки tel:' )
                    ->set_help_text( 'Формат: +79263383929' ),
            ) )
            ->set_header_template( '<%- phone_number %>' )
            ->set_layout( 'tabbed-horizontal' ),
        Field::make( 'text', 'miauto_vk_url', 'Ссылка ВКонтакте' ),
        Field::make( 'text', 'miauto_telegram_url', 'Ссылка Telegram' ),
    ) );

// ── Sub-page: Общие блоки ───────────────────────────────────

Container::make( 'theme_options', 'Общие блоки' )
    ->set_page_parent( $miauto_options )

    ->add_tab( 'Рейтинг', array(
        Field::make( 'text', 'miauto_rating_stars', 'Количество звёзд' )
            ->set_attribute( 'type', 'number' )
            ->set_default_value( '5' ),
        Field::make( 'text', 'miauto_rating_reviews', 'Текст отзывов' )
            ->set_default_value( '(500+ отзывов)' ),
        Field::make( 'text', 'miauto_rating_source', 'Источник рейтинга' )
            ->set_default_value( 'Рейтинг организации в Яндексе' ),
    ) )

    ->add_tab( 'Форма записи', array(
        Field::make( 'text', 'miauto_form_title', 'Заголовок формы' )
            ->set_default_value( 'Запишитесь на ТО или бесплатный осмотр!' ),
        Field::make( 'image', 'miauto_form_bg', 'Фоновое изображение' ),
        Field::make( 'text', 'miauto_form_cf7_id', 'ID формы Contact Form 7' )
            ->set_help_text( 'Укажите ID формы CF7. Пусто — выводится HTML-форма по умолчанию.' ),
    ) )

    ->add_tab( 'Партнёры', array(
        Field::make( 'text', 'miauto_partners_title', 'Заголовок секции' )
            ->set_default_value( 'Наши партнеры' ),
        Field::make( 'complex', 'miauto_partners_items', 'Партнёры' )
            ->add_fields( array(
                Field::make( 'image', 'pitem_image', 'Логотип' ),
                Field::make( 'text',  'pitem_title', 'Название партнёра' ),
                Field::make( 'text',  'pitem_url',   'Ссылка' ),
            ) )
            ->set_header_template( '<%- pitem_title %>' )
            ->set_layout( 'tabbed-horizontal' ),
    ) )

    ->add_tab( 'Как мы работаем', array(
        Field::make( 'text', 'miauto_work_process_title', 'Заголовок' )
            ->set_default_value( 'Как мы работаем' ),
        Field::make( 'text', 'miauto_work_process_subtitle', 'Подзаголовок' ),
        Field::make( 'complex', 'miauto_work_process_steps', 'Шаги' )
            ->set_layout( 'tabbed-horizontal' )
            ->set_header_template( '<%- step_title %>' )
            ->add_fields( array(
                Field::make( 'textarea', 'step_svg', 'SVG-иконка' )
                    ->set_help_text( 'SVG-код иконки (тег &lt;svg&gt;...&lt;/svg&gt;).' ),
                Field::make( 'text', 'step_title', 'Заголовок шага' ),
                Field::make( 'text', 'step_text', 'Описание шага' ),
            ) ),
    ) )

    ->add_tab( 'Отзывы', array(
        Field::make( 'complex', 'miauto_reviews', 'Карточки отзывов' )
            ->set_layout( 'tabbed-horizontal' )
            ->set_header_template( '<%- review_author_name %>' )
            ->add_fields( array(
                Field::make( 'text', 'review_author_name', 'Имя автора' ),
                Field::make( 'text', 'review_author_car', 'Автомобиль' ),
                Field::make( 'textarea', 'review_text', 'Текст отзыва' ),
                Field::make( 'text', 'review_source_label', 'Источник (текст)' )
                    ->set_default_value( 'Яндекс.Карты' ),
                Field::make( 'text', 'review_source_url', 'Ссылка на источник' ),
                Field::make( 'text', 'review_rating', 'Рейтинг' )
                    ->set_default_value( '5,0' ),
                Field::make( 'association', 'review_services', 'Привязка к услуге' )
                    ->set_types( array(
                        array( 'type' => 'post', 'post_type' => 'miauto_service' ),
                    ) )
                    ->set_help_text( 'Если не выбрана — отзыв виден на всех страницах услуг.' ),
            ) ),
    ) );
