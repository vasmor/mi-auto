<?php
/**
 * Carbon Fields bootstrap and field registration.
 *
 * @package miauto
 */

use Carbon_Fields\Carbon_Fields;

/**
 * Boot Carbon Fields.
 */
function miauto_carbon_fields_boot() {
    Carbon_Fields::boot();
}
add_action( 'after_setup_theme', 'miauto_carbon_fields_boot' );

/**
 * Load field definitions after Carbon Fields is ready.
 */
function miauto_carbon_fields_register() {
    require_once MIAUTO_DIR . '/inc/carbon-fields/fields-theme-options.php';
    require_once MIAUTO_DIR . '/inc/carbon-fields/fields-home.php';
    require_once MIAUTO_DIR . '/inc/carbon-fields/fields-about.php';
    require_once MIAUTO_DIR . '/inc/carbon-fields/fields-works.php';
    require_once MIAUTO_DIR . '/inc/carbon-fields/fields-prices.php';
    require_once MIAUTO_DIR . '/inc/carbon-fields/fields-service.php';
    require_once MIAUTO_DIR . '/inc/carbon-fields/fields-model.php';
}
add_action( 'carbon_fields_register_fields', 'miauto_carbon_fields_register' );
