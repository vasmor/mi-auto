<?php
/**
 * Register Custom Post Types and Taxonomies.
 *
 * @package miauto
 */

/**
 * Register CPTs.
 */
function miauto_register_post_types() {

    // Car Model (e.g. Lancer, Outlander)
    register_post_type( 'miauto_model', array(
        'labels' => array(
            'name'               => 'Модели авто',
            'singular_name'      => 'Модель',
            'add_new'            => 'Добавить модель',
            'add_new_item'       => 'Добавить новую модель',
            'edit_item'          => 'Редактировать модель',
            'all_items'          => 'Все модели',
            'search_items'       => 'Искать модели',
            'not_found'          => 'Модели не найдены',
            'not_found_in_trash' => 'В корзине не найдено',
        ),
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-admin-generic',
        'supports'     => array( 'title', 'thumbnail' ),
        'rewrite'      => array( 'slug' => 'models' ),
        'show_in_rest' => true,
    ) );

    // Service
    register_post_type( 'miauto_service', array(
        'labels' => array(
            'name'               => 'Услуги',
            'singular_name'      => 'Услуга',
            'add_new'            => 'Добавить услугу',
            'add_new_item'       => 'Добавить новую услугу',
            'edit_item'          => 'Редактировать услугу',
            'all_items'          => 'Все услуги',
            'search_items'       => 'Искать услуги',
            'not_found'          => 'Услуги не найдены',
            'not_found_in_trash' => 'В корзине не найдено',
        ),
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-admin-tools',
        'supports'     => array( 'title', 'thumbnail', 'editor' ),
        'rewrite'      => array( 'slug' => 'services' ),
        'show_in_rest' => false,
    ) );

    // Work (portfolio)
    register_post_type( 'miauto_work', array(
        'labels' => array(
            'name'               => 'Работы',
            'singular_name'      => 'Работа',
            'add_new'            => 'Добавить работу',
            'add_new_item'       => 'Добавить новую работу',
            'edit_item'          => 'Редактировать работу',
            'all_items'          => 'Все работы',
            'search_items'       => 'Искать работы',
            'not_found'          => 'Работы не найдены',
            'not_found_in_trash' => 'В корзине не найдено',
        ),
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-portfolio',
        'supports'     => array( 'title', 'thumbnail' ),
        'rewrite'      => array( 'slug' => 'works' ),
        'show_in_rest' => false,
    ) );

}
add_action( 'init', 'miauto_register_post_types' );
