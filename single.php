<?php
/**
 * Single post (article) template.
 *
 * @package miauto
 */

get_header();

// Breadcrumbs.
$blog_page_id = get_option( 'page_for_posts' );
$blog_url     = $blog_page_id ? get_permalink( $blog_page_id ) : home_url( '/blog/' );
get_template_part( 'template-parts/sections/breadcrumbs/breadcrumbs', null, array(
    'breadcrumbs' => array(
        array( 'label' => 'Главная', 'url' => home_url( '/' ) ),
        array( 'label' => 'Блог', 'url' => $blog_url ),
        array( 'label' => get_the_title() ),
    ),
) );

// Article content.
get_template_part( 'template-parts/sections/article/article' );

// Form Section.
get_template_part( 'template-parts/sections/form-section/form-section' );

get_footer();
