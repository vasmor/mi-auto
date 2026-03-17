<?php
/**
 * Blog posts listing template (WordPress "home" template).
 *
 * @package miauto
 */

get_header();

// Breadcrumbs.
get_template_part( 'template-parts/sections/breadcrumbs' );

// Blog grid.
get_template_part( 'template-parts/sections/blog' );

get_footer();
