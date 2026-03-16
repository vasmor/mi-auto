<?php
/**
 * Template Name: О компании
 * Template for the About page.
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

// About Hero.
get_template_part( 'template-parts/sections/about-hero/about-hero', null, $miauto_args );

// About Intro.
get_template_part( 'template-parts/sections/about-intro/about-intro', null, $miauto_args );

// Work Process.
get_template_part( 'template-parts/sections/work-process/work-process', null, $miauto_args );

// Advantages.
get_template_part( 'template-parts/sections/advantages/advantages', null, $miauto_args );

// Partners (reused from homepage).
get_template_part( 'template-parts/sections/partners/partners', null, $miauto_args );

// Contacts (reused from homepage).
get_template_part( 'template-parts/sections/contacts/contacts', null, $miauto_args );

// Form Section (reused from homepage).
get_template_part( 'template-parts/sections/form-section/form-section', null, $miauto_args );

get_footer();
