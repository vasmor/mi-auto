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
        $uri . '/assets/css/base/base.css',
        array(),
        filemtime( $dir . '/assets/css/base/base.css' )
    );

    // ── Section styles ───────────────────────────────────────

    $sections_css = array(
        'miauto-top-bar'      => 'top-bar/top-bar.css',
        'miauto-header'       => 'header/header.css',
        'miauto-hero'         => 'hero/hero.css',
        'miauto-car-models'   => 'car-models/car-models.css',
        'miauto-services'     => 'services/services.css',
        'miauto-about'        => 'about/about.css',
        'miauto-partners'     => 'partners/partners.css',
        'miauto-svc-details'  => 'svc-details/svc-details.css',
        'miauto-contacts'     => 'contacts/contacts.css',
        'miauto-form'         => 'form-section/form-section.css',
        'miauto-footer'       => 'footer/footer.css',
        'miauto-scroll-top'   => 'scroll-top/scroll-top.css',
        'miauto-breadcrumbs'  => 'breadcrumbs/breadcrumbs.css',
        'miauto-about-hero'   => 'about-hero/about-hero.css',
        'miauto-about-intro'  => 'about-intro/about-intro.css',
        'miauto-work-process'  => 'work-process/work-process.css',
        'miauto-advantages'    => 'advantages/advantages.css',
        'miauto-works'         => 'works/works.css',
        'miauto-prices'        => 'prices/prices.css',
        'miauto-blog'          => 'blog/blog.css',
        'miauto-article'       => 'article/article.css',
        'miauto-service-card'  => 'service-card/service-card.css',
    );

    foreach ( $sections_css as $handle => $path ) {
        $full_path = $dir . '/template-parts/sections/' . $path;
        wp_register_style(
            $handle,
            $uri . '/template-parts/sections/' . $path,
            array( 'miauto-base' ),
            filemtime( $full_path )
        );
    }

    // ── Section scripts ──────────────────────────────────────

    $sections_js = array(
        'miauto-top-bar'     => 'top-bar/top-bar.js',
        'miauto-header'      => 'header/header.js',
        'miauto-hero'        => 'hero/hero.js',
        'miauto-services'    => 'services/services.js',
        'miauto-svc-details' => 'svc-details/svc-details.js',
        'miauto-footer'       => 'footer/footer.js',
        'miauto-scroll-top'   => 'scroll-top/scroll-top.js',
        'miauto-works'         => 'works/works.js',
        'miauto-prices'        => 'prices/prices.js',
        'miauto-blog'          => 'blog/blog.js',
        'miauto-service-card'  => 'service-card/service-card.js',
    );

    foreach ( $sections_js as $handle => $path ) {
        $full_path = $dir . '/template-parts/sections/' . $path;
        wp_register_script(
            $handle,
            $uri . '/template-parts/sections/' . $path,
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
