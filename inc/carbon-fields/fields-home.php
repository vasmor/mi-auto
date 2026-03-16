<?php
/**
 * Carbon Fields for the homepage (front page).
 *
 * @package miauto
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

// ── Hero ─────────────────────────────────────────────────────

Container::make( 'post_meta', 'Главная: Hero-слайдер' )
    ->where( 'post_template', '=', 'front-page.php' )
    ->add_fields( array(
        Field::make( 'complex', 'miauto_hero_slides', 'Слайды' )
            ->add_fields( array(
                Field::make( 'image', 'image', 'Фоновое изображение' ),
                Field::make( 'text', 'image_alt', 'Alt изображения' ),
                Field::make( 'text', 'title', 'Заголовок' ),
                Field::make( 'textarea', 'description', 'Описание' ),
                Field::make( 'text', 'cta_text', 'Текст кнопки' ),
                Field::make( 'text', 'cta_url', 'Ссылка кнопки' ),
            ) )
            ->set_header_template( '<%- title %>' ),

        Field::make( 'complex', 'miauto_hero_features', 'Преимущества (значки под слайдером)' )
            ->add_fields( array(
                Field::make( 'text', 'text', 'Текст' ),
                Field::make( 'textarea', 'svg', 'SVG-иконка' )
                    ->set_help_text( 'Вставьте SVG-код иконки' ),
            ) )
            ->set_header_template( '<%- text %>' ),
    ) );

// ── Секции главной страницы ──────────────────────────────────

Container::make( 'post_meta', 'Главная: Секции' )
    ->where( 'post_template', '=', 'front-page.php' )
    ->add_fields( array(

        // Car models
        Field::make( 'text', 'miauto_car_models_title', 'Заголовок "Модели авто"' )
            ->set_default_value( 'Модели авто' ),

        // Services
        Field::make( 'text', 'miauto_services_title', 'Заголовок "Категории услуг"' )
            ->set_default_value( 'Категории услуг' ),
        Field::make( 'text', 'miauto_services_more_text', 'Текст кнопки "Смотреть ещё"' )
            ->set_default_value( 'Смотреть еще' ),

        // About
        Field::make( 'text', 'miauto_about_title', 'Заголовок "О нас"' )
            ->set_default_value( 'О нас' ),
        Field::make( 'image', 'miauto_about_image', 'Изображение "О нас"' ),
        Field::make( 'rich_text', 'miauto_about_text', 'Текст "О нас"' ),

        // Articles
        Field::make( 'text', 'miauto_articles_title', 'Заголовок "Полезные статьи"' )
            ->set_default_value( 'Полезные статьи' ),
        Field::make( 'text', 'miauto_articles_link_text', 'Текст ссылки на блог' )
            ->set_default_value( 'Узнать больше' ),
        Field::make( 'text', 'miauto_articles_count', 'Количество статей' )
            ->set_attribute( 'type', 'number' )
            ->set_default_value( '2' ),

        // Service details (tabs)
        Field::make( 'text', 'miauto_svc_details_title', 'Заголовок "Услуги СТО"' )
            ->set_default_value( 'Услуги CTO' ),
        Field::make( 'complex', 'miauto_svc_details_tabs', 'Табы услуг СТО' )
            ->add_fields( array(
                Field::make( 'text', 'tab_id', 'ID таба (латиница)' )
                    ->set_help_text( 'Уникальный ID, например: to, suspension, engine' ),
                Field::make( 'text', 'tab_title', 'Название таба' ),
                Field::make( 'text', 'badge', 'Бейдж' )
                    ->set_help_text( 'Пусто — не показывать' ),
                Field::make( 'text', 'panel_title', 'Заголовок панели' ),
                Field::make( 'rich_text', 'panel_text', 'Описание' ),
                Field::make( 'complex', 'features', 'Список работ' )
                    ->add_fields( array(
                        Field::make( 'text', 'text', 'Название работы' ),
                    ) )
                    ->set_header_template( '<%- text %>' ),
                Field::make( 'text', 'price_label', 'Метка цены' )
                    ->set_default_value( 'Стоимость работ от' ),
                Field::make( 'text', 'price_value', 'Цена' ),
                Field::make( 'text', 'cta_text', 'Текст кнопки' )
                    ->set_default_value( 'Записаться на ремонт' ),
                Field::make( 'text', 'cta_url', 'Ссылка кнопки' ),
            ) )
            ->set_header_template( '<%- tab_title %>' ),

        // Contacts section
        Field::make( 'text', 'miauto_contacts_title', 'Заголовок "Наши контакты"' )
            ->set_default_value( 'Наши контакты' ),
        Field::make( 'image', 'miauto_contacts_decoration', 'Декоративное изображение' ),
        Field::make( 'image', 'miauto_contacts_map', 'Изображение карты (заглушка)' ),
    ) );
