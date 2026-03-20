<?php
/**
 * MI-AUTO theme functions.
 *
 * @package miauto
 */

// Composer autoloader (Carbon Fields).
$miauto_autoload = get_template_directory() . '/vendor/autoload.php';
if ( file_exists( $miauto_autoload ) ) {
    require_once $miauto_autoload;
}


require_once get_template_directory() . '/inc/nav-walker.php';
require_once get_template_directory() . '/inc/theme-setup.php';
require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/enqueue/enqueue.php';
require_once get_template_directory() . '/inc/custom-post-types.php';
require_once get_template_directory() . '/inc/carbon-fields/fields-init.php';
require_once get_template_directory() . '/inc/demo-import/demo-import.php';
require_once get_template_directory() . '/inc/demo-import/service-avtoelektrika.php';
