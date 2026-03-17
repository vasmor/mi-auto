<?php
/**
 * Breadcrumbs section - секция хлебных крошек.
 *
 * Выводит хлебные крошки через плагин Yoast SEO (yoast_breadcrumb).
 * Для работы необходима установка и активация плагина Yoast SEO.
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
	wp_enqueue_style( 'miauto-breadcrumbs' );
}

// Проверяем наличие функции Yoast SEO.
if ( ! function_exists( 'yoast_breadcrumb' ) ) {
	return;
}
?>

<section class="breadcrumbs-section">
	<div class="container">
		<?php
		yoast_breadcrumb(
			'<nav class="breadcrumbs" aria-label="Хлебные крошки">',
			'</nav>'
		);
		?>
	</div>
</section>
