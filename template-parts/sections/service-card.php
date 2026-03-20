<?php
/**
 * Section: Service Card (sc-hero, symptoms, svc-list, sc-prices).
 *
 * Renders unique sections for the single service page (warranty moved to warranty.php).
 *
 * @package miauto
 */

wp_enqueue_style( 'miauto-service-card' );
wp_enqueue_script( 'miauto-service-card' );

$post_id = $args['post_id'] ?? get_the_ID();

// ── SC-HERO data ────────────────────────────────────────────────
$sc_subtitle      = miauto_get_meta( 'miauto_sc_hero_subtitle', $post_id );
$sc_features      = miauto_get_meta( 'miauto_sc_hero_features', $post_id );
$sc_cta_primary   = miauto_get_meta( 'miauto_sc_hero_cta_primary_text', $post_id );
$sc_cta_secondary = miauto_get_meta( 'miauto_sc_hero_cta_secondary_text', $post_id );
$sc_image         = miauto_get_meta( 'miauto_sc_hero_image', $post_id );
$sc_stats         = miauto_get_meta( 'miauto_sc_hero_stats', $post_id );

// Partners from theme options (Общие блоки → Партнёры).
$partners_gallery = miauto_get_option( 'miauto_partners_items' );

// ── Symptoms data ───────────────────────────────────────────────
$sym_title    = miauto_get_meta( 'miauto_sc_symptoms_title', $post_id );
$sym_subtitle = miauto_get_meta( 'miauto_sc_symptoms_subtitle', $post_id );
$sym_cards    = miauto_get_meta( 'miauto_sc_symptoms_cards', $post_id );
$sym_cta_text = miauto_get_meta( 'miauto_sc_symptoms_cta_text', $post_id );
$sym_btn_text = miauto_get_meta( 'miauto_sc_symptoms_cta_btn_text', $post_id );

// ── Svc-list data ───────────────────────────────────────────────
$svc_title = miauto_get_meta( 'miauto_sc_svc_list_title', $post_id );
$svc_items = miauto_get_meta( 'miauto_sc_svc_list_items', $post_id );

// ── Prices data ─────────────────────────────────────────────────
$pr_title          = miauto_get_meta( 'miauto_sc_prices_title', $post_id );
$pr_subtitle       = miauto_get_meta( 'miauto_sc_prices_subtitle', $post_id );
$pr_rows           = miauto_get_meta( 'miauto_sc_prices_rows', $post_id );
$pr_footer_heading = miauto_get_meta( 'miauto_sc_prices_footer_heading', $post_id );
$pr_footer_desc    = miauto_get_meta( 'miauto_sc_prices_footer_desc', $post_id );
$pr_footer_btn     = miauto_get_meta( 'miauto_sc_prices_footer_btn_text', $post_id );

?>

<?php // ═══════════════════════════════════════════════════════════════
      // SC-HERO
      // ═══════════════════════════════════════════════════════════════ ?>
<section class="sc-hero" aria-label="<?php echo esc_attr( get_the_title( $post_id ) ); ?>">
	<div class="container">
		<div class="sc-hero__content">

			<!-- Left text column -->
			<div class="sc-hero__text-col">
				<h1 class="sc-hero__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h1>

				<?php if ( ! empty( $sc_subtitle ) ) : ?>
				<p class="sc-hero__subtitle"><?php echo esc_html( $sc_subtitle ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $sc_features ) ) : ?>
				<ul class="sc-hero__features">
					<?php foreach ( $sc_features as $feature ) : ?>
					<li class="sc-hero__feature">
						<svg class="sc-hero__feature-icon" viewBox="0 0 14 10" aria-hidden="true"><path d="M1 5L5 9L13 1"/></svg>
						<?php echo esc_html( $feature['feature_text'] ); ?>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>

				<div class="sc-hero__buttons">
					<?php if ( ! empty( $sc_cta_primary ) ) : ?>
					<button class="sc-hero__btn -primary" type="button"><?php echo esc_html( $sc_cta_primary ); ?></button>
					<?php endif; ?>
					<?php if ( ! empty( $sc_cta_secondary ) ) : ?>
					<button class="sc-hero__btn -outline" type="button"><?php echo esc_html( $sc_cta_secondary ); ?></button>
					<?php endif; ?>
				</div>
			</div>

			<!-- Right image column -->
			<div class="sc-hero__image-col">
				<?php
				if ( ! empty( $sc_image ) ) {
					echo wp_get_attachment_image( $sc_image, 'large', false, array(
						'class'   => 'sc-hero__image',
						'loading' => 'eager',
					) );
				}
				?>

				<?php if ( ! empty( $sc_stats ) ) : ?>
				<div class="sc-hero__stats">
					<div class="sc-hero__stats-grid">
						<?php foreach ( $sc_stats as $index => $stat ) : ?>
							<?php if ( $index > 0 ) : ?>
							<div class="sc-hero__stat-divider" aria-hidden="true"></div>
							<?php endif; ?>
							<div class="sc-hero__stat">
								<?php if ( 0 === $index ) : ?>
								<div class="sc-hero__stat-rating-row">
									<svg class="sc-hero__stat-icon" viewBox="0 0 17 17" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8.5 1L10.7 6.3L16.5 6.9L12.3 10.6L13.6 16.3L8.5 13.3L3.4 16.3L4.7 10.6L0.5 6.9L6.3 6.3L8.5 1Z"/></svg>
									<span class="sc-hero__stat-value"><?php echo esc_html( $stat['stat_value'] ); ?></span>
								</div>
								<?php else : ?>
								<span class="sc-hero__stat-value"><?php echo esc_html( $stat['stat_value'] ); ?></span>
								<?php endif; ?>
								<span class="sc-hero__stat-label"><?php echo esc_html( $stat['stat_label'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>

					<?php if ( ! empty( $partners_gallery ) ) : ?>
					<hr class="sc-hero__divider" aria-hidden="true">
					<div class="sc-hero__partners">
						<span class="sc-hero__partners-title">Партнеры:</span>
						<div class="sc-hero__partners-logos">
							<?php foreach ( $partners_gallery as $partner ) : ?>
								<?php if ( ! empty( $partner['pitem_image'] ) ) : ?>
								<?php echo wp_get_attachment_image( $partner['pitem_image'], 'full', false, array(
									'class'   => 'sc-hero__partner-logo',
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
</section><!-- /.sc-hero -->

<?php // ═══════════════════════════════════════════════════════════════
      // SYMPTOMS
      // ═══════════════════════════════════════════════════════════════ ?>
<?php if ( ! empty( $sym_cards ) ) : ?>
<section class="symptoms" aria-label="<?php echo esc_attr( $sym_title ); ?>">
	<div class="container">

		<div class="symptoms__header">
			<?php if ( ! empty( $sym_title ) ) : ?>
			<h2 class="symptoms__title"><?php echo esc_html( $sym_title ); ?></h2>
			<?php endif; ?>
			<?php if ( ! empty( $sym_subtitle ) ) : ?>
			<p class="symptoms__subtitle"><?php echo esc_html( $sym_subtitle ); ?></p>
			<?php endif; ?>
		</div>

		<div class="symptoms__grid">
			<?php foreach ( $sym_cards as $card ) : ?>
			<div class="symptoms__card">
				<?php
				if ( ! empty( $card['sym_image'] ) ) {
					echo wp_get_attachment_image( $card['sym_image'], 'medium', false, array(
						'class'   => 'symptoms__card-image',
						'loading' => 'lazy',
					) );
				}
				?>
				<div class="symptoms__card-content">
					<div class="symptoms__card-text">
						<?php if ( ! empty( $card['symptom_title'] ) ) : ?>
						<h3 class="symptoms__card-title"><?php echo esc_html( $card['symptom_title'] ); ?></h3>
						<?php endif; ?>
						<?php if ( ! empty( $card['symptom_desc'] ) ) : ?>
						<p class="symptoms__card-desc"><?php echo esc_html( $card['symptom_desc'] ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<?php if ( ! empty( $sym_cta_text ) || ! empty( $sym_btn_text ) ) : ?>
		<div class="symptoms__cta">
			<?php if ( ! empty( $sym_cta_text ) ) : ?>
			<p class="symptoms__cta-text"><?php echo esc_html( $sym_cta_text ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $sym_btn_text ) ) : ?>
			<button class="symptoms__cta-btn" type="button"><?php echo esc_html( $sym_btn_text ); ?></button>
			<?php endif; ?>
		</div>
		<?php endif; ?>

	</div>
</section><!-- /.symptoms -->
<?php endif; ?>

<?php // ═══════════════════════════════════════════════════════════════
      // SVC-LIST
      // ═══════════════════════════════════════════════════════════════ ?>
<?php if ( ! empty( $svc_items ) ) : ?>
<section class="svc-list" aria-label="<?php echo esc_attr( $svc_title ); ?>">
	<div class="container">

		<?php if ( ! empty( $svc_title ) ) : ?>
		<h2 class="svc-list__title"><?php echo esc_html( $svc_title ); ?></h2>
		<?php endif; ?>

		<div class="svc-list__grid">
			<?php foreach ( $svc_items as $index => $item ) : ?>
			<div class="svc-list__item">
				<span class="svc-list__number"><?php echo esc_html( str_pad( $index + 1, 2, '0', STR_PAD_LEFT ) ); ?></span>
				<div class="svc-list__item-body">
					<?php if ( ! empty( $item['svc_title'] ) ) : ?>
					<h3 class="svc-list__item-title"><?php echo esc_html( $item['svc_title'] ); ?></h3>
					<?php endif; ?>
					<?php if ( ! empty( $item['svc_desc'] ) ) : ?>
					<p class="svc-list__item-desc"><?php echo esc_html( $item['svc_desc'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

	</div>
</section><!-- /.svc-list -->
<?php endif; ?>

<?php // ═══════════════════════════════════════════════════════════════
      // SC-PRICES
      // ═══════════════════════════════════════════════════════════════ ?>
<?php if ( ! empty( $pr_rows ) ) : ?>
<section class="sc-prices" aria-label="<?php echo esc_attr( $pr_title ); ?>">
	<div class="container">

		<div class="sc-prices__header">
			<?php if ( ! empty( $pr_title ) ) : ?>
			<h2 class="sc-prices__title"><?php echo esc_html( $pr_title ); ?></h2>
			<?php endif; ?>
			<?php if ( ! empty( $pr_subtitle ) ) : ?>
			<p class="sc-prices__subtitle"><?php echo esc_html( $pr_subtitle ); ?></p>
			<?php endif; ?>
		</div>

		<div class="sc-prices__content">
			<div class="sc-prices__list">
				<?php foreach ( $pr_rows as $row_index => $row ) : ?>
					<?php if ( $row_index > 0 ) : ?>
					<hr class="sc-prices__divider" aria-hidden="true">
					<?php endif; ?>
					<div class="sc-prices__row">
						<span class="sc-prices__service-name"><?php echo esc_html( $row['service_name'] ); ?></span>
						<span class="sc-prices__service-price"><?php echo esc_html( $row['sc_service_price'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( ! empty( $pr_footer_heading ) || ! empty( $pr_footer_btn ) ) : ?>
			<div class="sc-prices__footer">
				<div class="sc-prices__footer-text">
					<?php if ( ! empty( $pr_footer_heading ) ) : ?>
					<span class="sc-prices__footer-heading"><?php echo esc_html( $pr_footer_heading ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $pr_footer_desc ) ) : ?>
					<span class="sc-prices__footer-desc"><?php echo esc_html( $pr_footer_desc ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $pr_footer_btn ) ) : ?>
				<button class="sc-prices__footer-btn" type="button"><?php echo esc_html( $pr_footer_btn ); ?></button>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>

	</div>
</section><!-- /.sc-prices -->
<?php endif; ?>

