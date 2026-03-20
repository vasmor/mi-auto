<?php
/**
 * Section: Reviews carousel.
 *
 * Accepted $args:
 *   'post_id' => int  Service post ID for filtering reviews by association.
 *                     If no reviews match the service, all reviews are shown.
 *
 * @package miauto
 */

$all_reviews = carbon_get_theme_option( 'miauto_reviews' );

if ( empty( $all_reviews ) ) {
	return;
}

$service_id = ! empty( $args['post_id'] ) ? (int) $args['post_id'] : 0;

if ( $service_id ) {
	$filtered = array_filter( $all_reviews, function( $r ) use ( $service_id ) {
		if ( empty( $r['review_services'] ) ) {
			return false;
		}
		foreach ( $r['review_services'] as $assoc ) {
			if ( (int) ( $assoc['id'] ?? 0 ) === $service_id ) {
				return true;
			}
		}
		return false;
	} );
	$reviews = ! empty( $filtered ) ? array_values( $filtered ) : $all_reviews;
} else {
	$reviews = $all_reviews;
}

if ( empty( $reviews ) ) {
	return;
}

$count = count( $reviews );

wp_enqueue_style( 'miauto-reviews' );
wp_enqueue_script( 'miauto-reviews' );
?>

<section class="reviews" aria-label="Отзывы наших клиентов" data-count="<?php echo esc_attr( $count ); ?>">
	<div class="container">

		<div class="reviews__header">
			<h2 class="reviews__title">Отзывы наших клиентов</h2>
			<div class="reviews__nav">
				<button class="reviews__nav-btn -prev" type="button" aria-label="Назад">
					<svg viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M15 20L10 15M10 15L15 10M10 15L20 15" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
				<button class="reviews__nav-btn -next" type="button" aria-label="Вперёд">
					<svg viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M15 10L20 15M20 15L15 20M20 15L10 15" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			</div>
		</div>

		<div class="reviews__viewport">
			<div class="reviews__track">

				<?php foreach ( $reviews as $r ) : ?>
				<div class="reviews__card">

					<div class="reviews__author">
						<span class="reviews__author-name"><?php echo esc_html( $r['review_author_name'] ); ?></span>
						<span class="reviews__author-car"><?php echo esc_html( $r['review_author_car'] ); ?></span>
					</div>

					<p class="reviews__text"><?php echo esc_html( $r['review_text'] ); ?></p>

					<div class="reviews__footer">
						<a class="reviews__source" href="<?php echo esc_url( $r['review_source_url'] ); ?>">
							<?php echo esc_html( $r['review_source_label'] ); ?>
							<svg class="reviews__source-icon" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M1 7L7 1M7 1H3M7 1V5"/>
							</svg>
						</a>
						<div class="reviews__rating">
							<svg class="reviews__star" viewBox="0 0 17 17" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
								<path d="M8.5 1L10.7 6.3L16.5 6.9L12.3 10.6L13.6 16.3L8.5 13.3L3.4 16.3L4.7 10.6L0.5 6.9L6.3 6.3L8.5 1Z"/>
							</svg>
							<span class="reviews__rating-value"><?php echo esc_html( $r['review_rating'] ); ?></span>
						</div>
					</div>

				</div>
				<?php endforeach; ?>

			</div>
		</div>

		<div class="reviews__dots">
			<?php for ( $i = 0; $i < $count; $i++ ) : ?>
			<button class="reviews__dot<?php echo 0 === $i ? ' -active' : ''; ?>"
			        type="button"
			        aria-label="<?php echo esc_attr( 'Отзыв ' . ( $i + 1 ) ); ?>"></button>
			<?php endfor; ?>
		</div>

	</div>
</section><!-- /.reviews -->
