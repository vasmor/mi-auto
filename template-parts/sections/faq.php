<?php
/**
 * Section: FAQ accordion.
 *
 * @package miauto
 *
 * @param array $args {
 *     @type int $post_id  Service post ID.
 * }
 */

wp_enqueue_style( 'miauto-faq' );
wp_enqueue_script( 'miauto-faq' );

$post_id     = $args['post_id'] ?? get_the_ID();
$faq_heading = miauto_get_meta( 'miauto_sc_faq_heading', $post_id );
$faq_entries = miauto_get_meta( 'miauto_sc_faq_entries', $post_id );

if ( empty( $faq_entries ) || ! is_array( $faq_entries ) ) {
	return;
}

// Find first active item index.
$active_index = null;
foreach ( $faq_entries as $i => $entry ) {
	if ( ! empty( $entry['faq_entry_active'] ) ) {
		$active_index = $i;
		break;
	}
}

// Split into two columns (left gets more if odd).
$total      = count( $faq_entries );
$left_count = (int) ceil( $total / 2 );
$left_items  = array_slice( $faq_entries, 0, $left_count, true );
$right_items = array_slice( $faq_entries, $left_count, null, true );

/**
 * Render a single FAQ item.
 *
 * @param array    $entry        Complex field row.
 * @param int      $index        Original index in the full array.
 * @param int|null $active_index Index of the active item.
 */
function miauto_render_faq_item( $entry, $index, $active_index ) {
	$is_active = ( $index === $active_index );
	$class     = 'faq__item' . ( $is_active ? ' -open' : '' );
	$expanded  = $is_active ? 'true' : 'false';
	?>
	<div class="<?php echo esc_attr( $class ); ?>">
		<button class="faq__question" type="button" aria-expanded="<?php echo esc_attr( $expanded ); ?>">
			<?php echo esc_html( $entry['faq_entry_question'] ); ?>
			<svg class="faq__icon" viewBox="0 0 14 14"><line x1="7" y1="1" x2="7" y2="13"/><line x1="1" y1="7" x2="13" y2="7"/></svg>
		</button>
		<div class="faq__answer">
			<div class="faq__answer-content">
				<?php echo wp_kses_post( apply_filters( 'the_content', $entry['faq_entry_answer'] ) ); ?>
			</div>
		</div>
	</div>
	<?php
}
?>

<section class="faq" aria-label="<?php echo esc_attr( $faq_heading ?: 'FAQ' ); ?>">
	<div class="faq__container">

		<?php if ( ! empty( $faq_heading ) ) : ?>
			<h2 class="faq__title"><?php echo esc_html( $faq_heading ); ?></h2>
		<?php endif; ?>

		<div class="faq__grid">
			<div class="faq__column">
				<?php foreach ( $left_items as $i => $entry ) : ?>
					<?php miauto_render_faq_item( $entry, $i, $active_index ); ?>
				<?php endforeach; ?>
			</div>

			<?php if ( ! empty( $right_items ) ) : ?>
			<div class="faq__column">
				<?php foreach ( $right_items as $i => $entry ) : ?>
					<?php miauto_render_faq_item( $entry, $i, $active_index ); ?>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>

	</div>
</section>
