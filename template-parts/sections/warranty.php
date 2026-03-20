<?php
/**
 * Section: Warranty (miauto_service).
 *
 * Accepted $args:
 *   'post_id' => int  Service post ID
 *
 * @package miauto
 */

$post_id = ! empty( $args['post_id'] ) ? (int) $args['post_id'] : get_the_ID();

$wr_title    = miauto_get_meta( 'miauto_sc_warranty_title', $post_id );
$wr_subtitle = miauto_get_meta( 'miauto_sc_warranty_subtitle', $post_id );
$wr_cards    = miauto_get_meta( 'miauto_sc_warranty_cards', $post_id );

if ( empty( $wr_cards ) ) {
	return;
}
?>

<section class="warranty" aria-label="<?php echo esc_attr( $wr_title ); ?>">
	<div class="container">

		<div class="warranty__header">
			<?php if ( ! empty( $wr_title ) ) : ?>
			<h2 class="warranty__title"><?php echo esc_html( $wr_title ); ?></h2>
			<?php endif; ?>
			<?php if ( ! empty( $wr_subtitle ) ) : ?>
			<p class="warranty__subtitle"><?php echo esc_html( $wr_subtitle ); ?></p>
			<?php endif; ?>
		</div>

		<div class="warranty__grid">
			<?php foreach ( $wr_cards as $card ) : ?>
			<div class="warranty__card">
				<?php if ( ! empty( $card['war_svg'] ) ) : ?>
				<div class="warranty__card-icon">
					<?php echo miauto_kses_svg( $card['war_svg'] ); ?>
				</div>
				<?php endif; ?>
				<?php if ( ! empty( $card['warranty_text'] ) ) : ?>
				<p class="warranty__card-text"><?php echo esc_html( $card['warranty_text'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>

	</div>
</section><!-- /.warranty -->
