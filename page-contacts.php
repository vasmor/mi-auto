<?php
/**
 * Template Name: Контакты
 * Template for the Contacts page.
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

// Contacts section (reused).
get_template_part( 'template-parts/sections/contacts/contacts', null, $miauto_args );

get_footer();
