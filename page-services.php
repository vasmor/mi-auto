<?php
/**
 * Template Name: Услуги
 * Template for the Services listing page.
 *
 * @package miauto
 */

get_header();

$miauto_args = array( 'post_id' => get_the_ID() );

// Breadcrumbs.
get_template_part( 'template-parts/sections/breadcrumbs/breadcrumbs', null, array(
    'breadcrumbs' => array(
        array( 'label' => 'Главная', 'url' => home_url( '/' ) ),
        array( 'label' => get_the_title() ),
    ),
) );

// Services grid (reused from homepage, without "Смотреть еще" button).
get_template_part( 'template-parts/sections/services/services', null, array_merge( $miauto_args, array(
    'show_all' => true,
) ) );

// Form Section.
get_template_part( 'template-parts/sections/form-section/form-section', null, $miauto_args );

get_footer();
