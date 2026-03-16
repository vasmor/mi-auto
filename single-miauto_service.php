<?php
/**
 * Template for single Service (miauto_service) CPT.
 *
 * @package miauto
 */

get_header();

$miauto_args = array( 'post_id' => get_the_ID() );

// Breadcrumbs with parent services page.
$services_page = get_page_by_path( 'services' );
$breadcrumbs   = array(
	array( 'label' => 'Главная', 'url' => home_url( '/' ) ),
);
if ( $services_page ) {
	$breadcrumbs[] = array( 'label' => get_the_title( $services_page ), 'url' => get_permalink( $services_page ) );
}
$breadcrumbs[] = array( 'label' => get_the_title() );

get_template_part( 'template-parts/sections/breadcrumbs/breadcrumbs', null, array(
	'breadcrumbs' => $breadcrumbs,
) );

// Service Card — all unique sections (sc-hero, symptoms, svc-list, sc-prices, warranty).
get_template_part( 'template-parts/sections/service-card/service-card', null, $miauto_args );

// Work Process (reused — data stored on the about page or current post).
$about_page    = get_page_by_path( 'about' );
$wp_source_id  = $about_page ? $about_page->ID : get_the_ID();
get_template_part( 'template-parts/sections/work-process/work-process', null, array( 'post_id' => $wp_source_id ) );

// Works gallery (reused — queries miauto_work CPT).
get_template_part( 'template-parts/sections/works/works', null, $miauto_args );

// Advantages (reused — data stored on the about page).
get_template_part( 'template-parts/sections/advantages/advantages', null, array( 'post_id' => $wp_source_id ) );

// Contacts (reused).
get_template_part( 'template-parts/sections/contacts/contacts', null, $miauto_args );

// Form Section (reused).
get_template_part( 'template-parts/sections/form-section/form-section', null, $miauto_args );

get_footer();
