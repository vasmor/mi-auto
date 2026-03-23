<?php
/**
 * Centralized registration and enqueue of all theme assets.
 *
 * @package miauto
 */

/**
 * Register and enqueue theme assets.
 */
function miauto_register_assets() {
    $uri = MIAUTO_URI;
    $dir = MIAUTO_DIR;

    // ── Base styles (global) ─────────────────────────────────

    wp_register_style(
        'miauto-base',
        $uri . '/css/base.css',
        array(),
        filemtime( $dir . '/css/base.css' )
    );

    // ── Section styles ───────────────────────────────────────

    $sections_css = array(
        'miauto-top-bar'       => 'top-bar.css',
        'miauto-header'        => 'header.css',
        'miauto-hero'          => 'hero.css',
        'miauto-car-models'    => 'car-models.css',
        'miauto-services'      => 'services.css',
        'miauto-about'         => 'about.css',
        'miauto-partners'      => 'partners.css',
        'miauto-svc-details'   => 'svc-details.css',
        'miauto-contacts'      => 'contacts.css',
        'miauto-form'          => 'form-section.css',
        'miauto-footer'        => 'footer.css',
        'miauto-scroll-top'    => 'scroll-top.css',
        'miauto-breadcrumbs'   => 'breadcrumbs.css',
        'miauto-about-hero'    => 'about-hero.css',
        'miauto-about-intro'   => 'about-intro.css',
        'miauto-work-process'  => 'work-process.css',
        'miauto-advantages'    => 'advantages.css',
        'miauto-works'         => 'works.css',
        'miauto-prices'        => 'prices.css',
        'miauto-blog'          => 'blog.css',
        'miauto-article'       => 'article.css',
        'miauto-service-card'  => 'service-card.css',
        'miauto-model-card'    => 'model-card.css',
        'miauto-model-text'    => 'model-text.css',
        'miauto-model-tabs'    => 'model-tabs.css',
        'miauto-reviews'       => 'reviews.css',
        'miauto-faq'           => 'faq.css',
    );

    foreach ( $sections_css as $handle => $path ) {
        $full_path = $dir . '/css/' . $path;
        wp_register_style(
            $handle,
            $uri . '/css/' . $path,
            array( 'miauto-base' ),
            filemtime( $full_path )
        );
    }

    // ── Section scripts ──────────────────────────────────────

    $sections_js = array(
        'miauto-top-bar'       => 'top-bar.js',
        'miauto-header'        => 'header.js',
        'miauto-hero'          => 'hero.js',
        'miauto-services'      => 'services.js',
        'miauto-svc-details'   => 'svc-details.js',
        'miauto-footer'        => 'footer.js',
        'miauto-scroll-top'    => 'scroll-top.js',
        'miauto-works'         => 'works.js',
        'miauto-prices'        => 'prices.js',
        'miauto-blog'          => 'blog.js',
        'miauto-service-card'  => 'service-card.js',
        'miauto-model-tabs'    => 'model-tabs.js',
        'miauto-reviews'       => 'reviews.js',
        'miauto-faq'           => 'faq.js',
    );

    foreach ( $sections_js as $handle => $path ) {
        $full_path = $dir . '/js/' . $path;
        wp_register_script(
            $handle,
            $uri . '/js/' . $path,
            array(),
            filemtime( $full_path ),
            true
        );
    }

    // ── Global enqueue ───────────────────────────────────────

    wp_enqueue_style( 'miauto-base' );
    wp_enqueue_style( 'miauto-top-bar' );
    wp_enqueue_style( 'miauto-header' );
    wp_enqueue_style( 'miauto-footer' );
    wp_enqueue_style( 'miauto-scroll-top' );

    wp_enqueue_script( 'miauto-top-bar' );
    wp_enqueue_script( 'miauto-header' );
    wp_enqueue_script( 'miauto-footer' );
    wp_enqueue_script( 'miauto-scroll-top' );

    wp_localize_script( 'miauto-header', 'miAutoData', array(
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'themeUri' => MIAUTO_URI,
        'nonce'    => wp_create_nonce( 'miauto_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'miauto_register_assets' );

function miauto_admin_styles() {
    ?>
    <style>
        .cf-container__tabs-list .cf-container__tabs-item.cf-container__tabs-item--current button,
        .cf-complex__tabs-item--tabbed-horizontal.cf-complex__tabs-item--current,
        .cf-complex__tabs-item--current {
            background: #ddd !important;
        }
    </style>
    <?php
}
add_action( 'admin_head', 'miauto_admin_styles' );
