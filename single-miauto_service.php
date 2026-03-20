<?php
/**
 * Template for single Service (miauto_service) CPT.
 *
 * @package miauto
 */

get_header();

$miauto_args = array( 'post_id' => get_the_ID() );

// Breadcrumbs.
get_template_part( 'template-parts/sections/breadcrumbs' );

// Service Card — unique sections (sc-hero, symptoms, svc-list, sc-prices).
get_template_part( 'template-parts/sections/service-card', null, $miauto_args );

// Work Process (reused — data from theme options).
get_template_part( 'template-parts/sections/work-process' );

// Warranty (after work-process — per design order).
get_template_part( 'template-parts/sections/warranty', null, $miauto_args );

// Works gallery (reused — queries miauto_work CPT, filtered by current service).
get_template_part( 'template-parts/sections/works', null, array(
	'post_id' => get_the_ID(),
	'layout'  => 'sc-examples',
) );

// Reviews (filtered by current service, fallback — all reviews).
get_template_part( 'template-parts/sections/reviews', null, $miauto_args );

// FAQ accordion (per-service, optional).
get_template_part( 'template-parts/sections/faq', null, $miauto_args );

// SEO text (WP editor content, optional).
get_template_part( 'template-parts/sections/seo-text' );

// Contacts (reused).
get_template_part( 'template-parts/sections/contacts', null, $miauto_args );

// Form Section (reused).
get_template_part( 'template-parts/sections/form-section', null, $miauto_args );

get_footer();
