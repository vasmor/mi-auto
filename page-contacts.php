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
get_template_part( 'template-parts/sections/breadcrumbs' );

// Contacts section (reused).
get_template_part( 'template-parts/sections/contacts', null, $miauto_args );

get_footer();
