<?php
/**
 * Section: SEO Text — WP editor content for service page.
 *
 * @package miauto
 */

$content = get_the_content();
if ( ! $content ) return;
?>
<section class="seo-text" aria-label="<?php the_title_attribute(); ?>">
	<div class="container">
		<div class="seo-text__body">
			<?php echo wp_kses_post( apply_filters( 'the_content', $content ) ); ?>
		</div>
	</div>
</section>
