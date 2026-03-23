<?php
/**
 * Section: Model Text — WP editor content for model page.
 *
 * @package miauto
 */

wp_enqueue_style( 'miauto-model-text' );

$content = get_the_content();
if ( ! $content ) return;
?>
<section class="model-text" aria-label="<?php the_title_attribute(); ?>">
	<div class="container">
		<div class="model-text__body">
			<?php echo wp_kses_post( apply_filters( 'the_content', $content ) ); ?>
		</div>
	</div>
</section>
