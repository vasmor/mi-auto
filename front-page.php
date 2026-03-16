<?php
/**
 * Template Name: Главная
 * Front page template.
 *
 * @package miauto
 */

get_header();

$miauto_page_id = get_the_ID();
$miauto_args    = array( 'post_id' => $miauto_page_id );

get_template_part( 'template-parts/sections/hero', null, $miauto_args );
get_template_part( 'template-parts/sections/car-models', null, $miauto_args );
get_template_part( 'template-parts/sections/services', null, $miauto_args );
get_template_part( 'template-parts/sections/about', null, $miauto_args );
get_template_part( 'template-parts/sections/partners', null, $miauto_args );
get_template_part( 'template-parts/sections/svc-details', null, $miauto_args );
get_template_part( 'template-parts/sections/contacts', null, $miauto_args );
get_template_part( 'template-parts/sections/form-section', null, $miauto_args );

get_footer();
