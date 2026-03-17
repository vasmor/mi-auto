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

/**
 * Notify admin if required plugins are missing.
 */
function miauto_check_required_plugins() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    $missing = array();
    if ( ! function_exists( 'yoast_breadcrumb' ) ) {
        $missing[] = 'Yoast SEO (требуется для хлебных крошек)';
    }
    if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
        $missing[] = 'Contact Form 7 (требуется для формы записи)';
    }
    if ( empty( $missing ) ) {
        return;
    }
    add_action( 'admin_notices', function () use ( $missing ) {
        echo '<div class="notice notice-warning"><p><strong>MI-AUTO:</strong> Не установлены плагины: ' . esc_html( implode( ', ', $missing ) ) . '</p></div>';
    } );
}
add_action( 'admin_init', 'miauto_check_required_plugins' );
