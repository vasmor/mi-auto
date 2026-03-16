<?php
/**
 * Theme setup: constants, supports, menus.
 *
 * @package miauto
 */

define( 'MIAUTO_VERSION', '1.0.0' );
define( 'MIAUTO_DIR', get_template_directory() );
define( 'MIAUTO_URI', get_template_directory_uri() );

/**
 * Theme setup.
 */
function miauto_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    register_nav_menus( array(
        'primary' => 'Основная навигация',
        'mobile'  => 'Мобильная навигация',
        'footer'  => 'Меню в подвале',
    ) );
}
add_action( 'after_setup_theme', 'miauto_setup' );
