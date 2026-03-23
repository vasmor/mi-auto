<?php
/**
 * Section: Model Tabs (repair works, TO costs, TO cards).
 *
 * @package miauto
 */

wp_enqueue_style( 'miauto-model-tabs' );
wp_enqueue_script( 'miauto-model-tabs' );

$post_id = $args['post_id'] ?? get_the_ID();

$tabs_title   = miauto_get_meta( 'miauto_md_tabs_title', $post_id );
$repair_rows  = miauto_get_meta( 'miauto_md_repair_rows', $post_id );
$to_variants  = miauto_get_meta( 'miauto_md_to_variants', $post_id );
$cards_content = miauto_get_meta( 'miauto_md_to_cards_content', $post_id );

// At least one tab must have content.
if ( empty( $repair_rows ) && empty( $to_variants ) && empty( $cards_content ) ) {
	return;
}

// Popup content from theme options.
$karta_normal = miauto_get_option( 'miauto_karta_to_normal' );
$karta_heavy  = miauto_get_option( 'miauto_karta_to_heavy' );

$tabs = array();
if ( ! empty( $repair_rows ) ) {
	$tabs[] = array( 'id' => 'repair', 'label' => 'Ремонтные работы' );
}
if ( ! empty( $to_variants ) ) {
	$tabs[] = array( 'id' => 'to-cost', 'label' => 'Стоимость ТО' );
}
if ( ! empty( $cards_content ) ) {
	$tabs[] = array( 'id' => 'to-cards', 'label' => 'Карты ТО' );
}
?>

<section class="model-tabs" aria-label="<?php echo esc_attr( $tabs_title ); ?>">
	<div class="container">

		<?php if ( ! empty( $tabs_title ) ) : ?>
		<h2 class="model-tabs__title"><?php echo esc_html( $tabs_title ); ?></h2>
		<?php endif; ?>

		<!-- Tab buttons -->
		<div class="model-tabs__buttons" role="tablist">
			<?php foreach ( $tabs as $i => $tab ) : ?>
			<button class="model-tabs__tab<?php echo 0 === $i ? ' -active' : ''; ?>" type="button" data-tab="<?php echo esc_attr( $tab['id'] ); ?>" role="tab" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"><?php echo esc_html( $tab['label'] ); ?></button>
			<?php endforeach; ?>
		</div>

		<!-- Tab panels -->
		<div class="model-tabs__panels">

			<?php // ── Ремонтные работы ─────────────────────────────── ?>
			<?php if ( ! empty( $repair_rows ) ) : ?>
			<div class="model-tabs__panel<?php echo 'repair' === $tabs[0]['id'] ? ' -active' : ''; ?>" data-panel="repair" role="tabpanel">
				<div class="model-tabs__table-scroll">
					<table class="model-tabs__table">
						<thead>
							<tr>
								<th>Наименование работы</th>
								<th>Стоимость</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $repair_rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['md_repair_name'] ); ?></td>
								<td><?php echo esc_html( number_format( (int) $row['md_repair_price'], 0, '', ' ' ) . ' ₽' ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php endif; ?>

			<?php // ── Стоимость ТО ─────────────────────────────────── ?>
			<?php if ( ! empty( $to_variants ) ) : ?>
			<div class="model-tabs__panel<?php echo 'to-cost' === $tabs[0]['id'] ? ' -active' : ''; ?>" data-panel="to-cost" role="tabpanel">
				<div class="model-tabs__table-scroll">
					<table class="model-tabs__table model-tabs__table--to">
						<thead>
							<tr>
								<th rowspan="2">Пробег, км</th>
								<?php foreach ( $to_variants as $variant ) : ?>
								<th colspan="2"><?php echo esc_html( $variant['md_to_variant_name'] ); ?></th>
								<?php endforeach; ?>
							</tr>
							<tr>
								<?php foreach ( $to_variants as $variant ) : ?>
								<th>Работа</th>
								<th>Запчасти</th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php
							// Collect all mileage rows from first variant (all variants share same mileages).
							$mileages = array();
							if ( ! empty( $to_variants[0]['md_to_variant_rows'] ) ) {
								foreach ( $to_variants[0]['md_to_variant_rows'] as $row ) {
									$mileages[] = $row['md_to_mileage'];
								}
							}

							foreach ( $mileages as $mi => $mileage ) : ?>
							<tr>
								<td><?php echo esc_html( number_format( (int) $mileage, 0, '', ' ' ) ); ?></td>
								<?php foreach ( $to_variants as $variant ) :
									$vrow = isset( $variant['md_to_variant_rows'][ $mi ] ) ? $variant['md_to_variant_rows'][ $mi ] : array();
								?>
								<td><?php echo esc_html( ! empty( $vrow['md_to_work_price'] ) ? number_format( (int) $vrow['md_to_work_price'], 0, '', ' ' ) . ' ₽' : '—' ); ?></td>
								<td><?php echo esc_html( ! empty( $vrow['md_to_parts_price'] ) ? number_format( (int) $vrow['md_to_parts_price'], 0, '', ' ' ) . ' ₽' : '—' ); ?></td>
								<?php endforeach; ?>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php endif; ?>

			<?php // ── Карты ТО ─────────────────────────────────────── ?>
			<?php if ( ! empty( $cards_content ) ) : ?>
			<div class="model-tabs__panel<?php echo 'to-cards' === $tabs[0]['id'] ? ' -active' : ''; ?>" data-panel="to-cards" role="tabpanel">
				<div class="model-tabs__cards-content">
					<?php echo wp_kses_post( $cards_content ); ?>
				</div>
			</div>
			<?php endif; ?>

		</div><!-- /.model-tabs__panels -->

	</div>
</section><!-- /.model-tabs -->

<?php // ── Popups ───────────────────────────────────────────────────── ?>

<?php if ( ! empty( $karta_normal ) ) : ?>
<div class="miauto-popup" id="popup-karta-normal" aria-hidden="true">
	<div class="miauto-popup__overlay"></div>
	<div class="miauto-popup__content">
		<button class="miauto-popup__close" type="button" aria-label="Закрыть">&times;</button>
		<div class="miauto-popup__body">
			<?php echo $karta_normal; ?>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if ( ! empty( $karta_heavy ) ) : ?>
<div class="miauto-popup" id="popup-karta-heavy" aria-hidden="true">
	<div class="miauto-popup__overlay"></div>
	<div class="miauto-popup__content">
		<button class="miauto-popup__close" type="button" aria-label="Закрыть">&times;</button>
		<div class="miauto-popup__body">
			<?php echo wp_kses_post( $karta_heavy ); ?>
		</div>
	</div>
</div>
<?php endif; ?>
