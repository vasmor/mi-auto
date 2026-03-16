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

get_template_part( 'template-parts/sections/hero/hero', null, $miauto_args );
get_template_part( 'template-parts/sections/car-models/car-models', null, $miauto_args );
get_template_part( 'template-parts/sections/services/services', null, $miauto_args );
get_template_part( 'template-parts/sections/about/about', null, $miauto_args );
get_template_part( 'template-parts/sections/partners/partners', null, $miauto_args );
get_template_part( 'template-parts/sections/svc-details/svc-details', null, $miauto_args );
get_template_part( 'template-parts/sections/contacts/contacts', null, $miauto_args );
get_template_part( 'template-parts/sections/form-section/form-section', null, $miauto_args );

get_footer();
