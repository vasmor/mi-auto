<?php
/**
 * Blog posts listing template (WordPress "home" template).
 *
 * @package miauto
 */

get_header();

// Breadcrumbs.
get_template_part( 'template-parts/sections/breadcrumbs', null, array(
    'breadcrumbs' => array(
        array( 'label' => 'Главная', 'url' => home_url( '/' ) ),
        array( 'label' => 'Блог' ),
    ),
) );

// Blog grid.
get_template_part( 'template-parts/sections/blog' );

get_footer();
