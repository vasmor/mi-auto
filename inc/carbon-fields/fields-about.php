<?php
/**
 * Carbon Fields: About page meta fields.
 *
 * @package miauto
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 * About Hero section fields.
 */
Container::make( 'post_meta', 'miauto_about_hero', 'О компании — Герой' )
    ->where( 'post_template', '=', 'page-about.php' )
    ->add_fields( array(
        Field::make( 'text', 'miauto_about_hero_badge', 'Бейдж' )
            ->set_default_value( 'Официальный сервис' ),
        Field::make( 'text', 'miauto_about_hero_title', 'Заголовок' )
            ->set_required( true ),
        Field::make( 'text', 'miauto_about_hero_accent', 'Акцентная часть заголовка' )
            ->set_help_text( 'Часть заголовка, которая будет выделена красным цветом.' ),
        Field::make( 'complex', 'miauto_about_hero_texts', 'Абзацы текста' )
            ->set_layout( 'tabbed-vertical' )
            ->add_fields( array(
                Field::make( 'textarea', 'text', 'Текст' ),
            ) ),
        Field::make( 'image', 'miauto_about_hero_image', 'Изображение' ),
    ) );

/**
 * About Intro section fields.
 */
Container::make( 'post_meta', 'miauto_about_intro', 'О компании — Подробнее' )
    ->where( 'post_template', '=', 'page-about.php' )
    ->add_fields( array(
        Field::make( 'text', 'miauto_about_intro_title', 'Заголовок' )
            ->set_required( true ),
        Field::make( 'complex', 'miauto_about_intro_texts', 'Абзацы текста' )
            ->set_layout( 'tabbed-vertical' )
            ->add_fields( array(
                Field::make( 'textarea', 'text', 'Текст' ),
            ) ),
        Field::make( 'image', 'miauto_about_intro_image', 'Изображение' ),
    ) );

/**
 * Work Process section fields.
 */
Container::make( 'post_meta', 'miauto_work_process', 'Как мы работаем' )
    ->where( 'post_template', '=', 'page-about.php' )
    ->add_fields( array(
        Field::make( 'text', 'miauto_work_process_title', 'Заголовок' )
            ->set_default_value( 'Как мы работаем' ),
        Field::make( 'text', 'miauto_work_process_subtitle', 'Подзаголовок' ),
        Field::make( 'complex', 'miauto_work_process_steps', 'Шаги' )
            ->set_layout( 'tabbed-vertical' )
            ->add_fields( array(
                Field::make( 'textarea', 'svg', 'SVG-иконка' )
                    ->set_help_text( 'SVG-код иконки (тег &lt;svg&gt;...&lt;/svg&gt;).' ),
                Field::make( 'text', 'title', 'Заголовок шага' ),
                Field::make( 'text', 'text', 'Описание шага' ),
            ) ),
    ) );

/**
 * Advantages section fields.
 */
Container::make( 'post_meta', 'miauto_advantages', 'Наши преимущества' )
    ->where( 'post_template', '=', 'page-about.php' )
    ->add_fields( array(
        Field::make( 'text', 'miauto_advantages_title', 'Заголовок' )
            ->set_default_value( 'Наши преимущества' ),
        Field::make( 'complex', 'miauto_advantages_cards', 'Карточки' )
            ->set_layout( 'tabbed-vertical' )
            ->add_fields( array(
                Field::make( 'textarea', 'svg', 'SVG-иконка' )
                    ->set_help_text( 'SVG-код иконки (тег &lt;svg&gt;...&lt;/svg&gt;).' ),
                Field::make( 'text', 'title', 'Заголовок' ),
                Field::make( 'textarea', 'text', 'Описание' ),
            ) ),
    ) );
