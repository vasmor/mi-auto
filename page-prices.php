<?php
/**
 * Template Name: Цены
 * Template for the Prices page.
 *
 * @package miauto
 */

get_header();

$miauto_args = array( 'post_id' => get_the_ID() );

// Breadcrumbs.
get_template_part( 'template-parts/sections/breadcrumbs', null, array(
    'breadcrumbs' => array(
        array( 'label' => 'Главная', 'url' => home_url( '/' ) ),
        array( 'label' => get_the_title() ),
    ),
) );

// Prices section.
get_template_part( 'template-parts/sections/prices', null, $miauto_args );

// Form Section.
get_template_part( 'template-parts/sections/form-section', null, $miauto_args );

get_footer();
