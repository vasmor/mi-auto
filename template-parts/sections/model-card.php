<?php
/**
 * Section: Model Card hero (md-hero).
 *
 * @package miauto
 */

wp_enqueue_style( 'miauto-model-card' );

$post_id = $args['post_id'] ?? get_the_ID();

// ── MD-HERO data ────────────────────────────────────────────────
$md_subtitle      = miauto_get_meta( 'miauto_md_hero_subtitle', $post_id );
$md_features      = miauto_get_meta( 'miauto_md_hero_features', $post_id );
$md_cta_primary   = miauto_get_meta( 'miauto_md_hero_cta_primary_text', $post_id );
$md_cta_secondary = miauto_get_meta( 'miauto_md_hero_cta_secondary_text', $post_id );
$md_image         = miauto_get_meta( 'miauto_md_hero_image', $post_id );
$md_stats         = miauto_get_meta( 'miauto_md_hero_stats', $post_id );

// Partners from theme options.
$partners_gallery = miauto_get_option( 'miauto_partners_items' );

?>

<section class="md-hero" aria-label="<?php echo esc_attr( get_the_title( $post_id ) ); ?>">
	<div class="container">
		<div class="md-hero__content">

			<!-- Left text column -->
			<div class="md-hero__text-col">
				<h1 class="md-hero__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h1>

				<?php if ( ! empty( $md_subtitle ) ) : ?>
				<p class="md-hero__subtitle"><?php echo esc_html( $md_subtitle ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $md_features ) ) : ?>
				<ul class="md-hero__features">
					<?php foreach ( $md_features as $feature ) : ?>
					<li class="md-hero__feature">
						<svg class="md-hero__feature-icon" viewBox="0 0 14 10" aria-hidden="true"><path d="M1 5L5 9L13 1"/></svg>
						<?php echo esc_html( $feature['md_feature_text'] ); ?>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>

				<div class="md-hero__buttons">
					<?php if ( ! empty( $md_cta_primary ) ) : ?>
					<button class="md-hero__btn -primary" type="button"><?php echo esc_html( $md_cta_primary ); ?></button>
					<?php endif; ?>
					<?php if ( ! empty( $md_cta_secondary ) ) : ?>
					<button class="md-hero__btn -outline" type="button"><?php echo esc_html( $md_cta_secondary ); ?></button>
					<?php endif; ?>
				</div>
			</div>

			<!-- Right image column -->
			<div class="md-hero__image-col">
				<?php
				if ( ! empty( $md_image ) ) {
					echo wp_get_attachment_image( $md_image, 'large', false, array(
						'class'   => 'md-hero__image',
						'loading' => 'eager',
					) );
				}
				?>

				<?php if ( ! empty( $md_stats ) ) : ?>
				<div class="md-hero__stats">
					<div class="md-hero__stats-grid">
						<?php foreach ( $md_stats as $index => $stat ) : ?>
							<?php if ( $index > 0 ) : ?>
							<div class="md-hero__stat-divider" aria-hidden="true"></div>
							<?php endif; ?>
							<div class="md-hero__stat">
								<?php if ( 0 === $index ) : ?>
								<div class="md-hero__stat-rating-row">
									<svg class="md-hero__stat-icon" viewBox="0 0 17 17" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8.5 1L10.7 6.3L16.5 6.9L12.3 10.6L13.6 16.3L8.5 13.3L3.4 16.3L4.7 10.6L0.5 6.9L6.3 6.3L8.5 1Z"/></svg>
									<span class="md-hero__stat-value"><?php echo esc_html( $stat['md_stat_value'] ); ?></span>
								</div>
								<?php else : ?>
								<span class="md-hero__stat-value"><?php echo esc_html( $stat['md_stat_value'] ); ?></span>
								<?php endif; ?>
								<span class="md-hero__stat-label"><?php echo esc_html( $stat['md_stat_label'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>

					<?php if ( ! empty( $partners_gallery ) ) : ?>
					<hr class="md-hero__divider" aria-hidden="true">
					<div class="md-hero__partners">
						<span class="md-hero__partners-title">Партнеры:</span>
						<div class="md-hero__partners-logos">
							<?php foreach ( $partners_gallery as $partner ) : ?>
								<?php if ( ! empty( $partner['pitem_image'] ) ) : ?>
								<?php echo wp_get_attachment_image( $partner['pitem_image'], 'full', false, array(
									'class'   => 'md-hero__partner-logo',
									'loading' => 'lazy',
								) ); ?>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>

		</div>
	</div>
</section><!-- /.md-hero -->
