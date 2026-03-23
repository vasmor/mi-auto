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
require_once get_template_directory() . '/inc/demo-import/service-scod-razval.php';
require_once get_template_directory() . '/inc/demo-import/service-shinomontazh.php';
require_once get_template_directory() . '/inc/demo-import/service-tormoznaya-sistema.php';
require_once get_template_directory() . '/inc/demo-import/service-remont-podveski.php';
require_once get_template_directory() . '/inc/demo-import/service-remont-rulevogo.php';
require_once get_template_directory() . '/inc/demo-import/service-remont-vyhlopnoy.php';
require_once get_template_directory() . '/inc/demo-import/service-komp-diagnostika.php';
require_once get_template_directory() . '/inc/demo-import/service-zamena-remnya-grm.php';
require_once get_template_directory() . '/inc/demo-import/service-zapravka-konditsionera.php';
require_once get_template_directory() . '/inc/demo-import/service-all.php';
require_once get_template_directory() . '/inc/demo-import/import-model-data.php';
require_once get_template_directory() . '/inc/demo-import/import-model-hero-asx.php';
require_once get_template_directory() . '/inc/demo-import/import-model-hero-all.php';
