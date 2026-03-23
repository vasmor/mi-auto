<?php
/**
 * Template for single Model (miauto_model) CPT.
 *
 * @package miauto
 */

get_header();

$miauto_args = array( 'post_id' => get_the_ID() );

// Breadcrumbs.
get_template_part( 'template-parts/sections/breadcrumbs' );

// Model Card — hero section.
get_template_part( 'template-parts/sections/model-card', null, $miauto_args );

// Model text (WP editor content, optional).
get_template_part( 'template-parts/sections/model-text' );

// Model Tabs (ремонтные работы, стоимость ТО, карты ТО).
get_template_part( 'template-parts/sections/model-tabs', null, $miauto_args );

// Contacts (reused).
get_template_part( 'template-parts/sections/contacts', null, $miauto_args );

// Form Section (reused).
get_template_part( 'template-parts/sections/form-section', null, $miauto_args );

get_footer();
