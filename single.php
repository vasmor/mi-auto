<?php
/**
 * Single post (article) template.
 *
 * @package miauto
 */

get_header();

// Breadcrumbs.
get_template_part( 'template-parts/sections/breadcrumbs' );

// Article content.
get_template_part( 'template-parts/sections/article' );

// Form Section.
get_template_part( 'template-parts/sections/form-section' );

get_footer();
