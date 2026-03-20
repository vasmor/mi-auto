<?php
/**
 * Section: Service Details (tabs + description).
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-svc-details' );
}
wp_enqueue_script( 'miauto-svc-details' );

$post_id = $args['post_id'] ?? get_the_ID();
$title   = miauto_get_meta( 'miauto_svc_details_title', $post_id );
$tabs    = miauto_get_meta( 'miauto_svc_details_tabs', $post_id );

if ( empty( $tabs ) ) {
    return;
}
?>

<section class="svc-details" aria-label="Услуги СТО">
    <div class="container">

        <?php if ( ! empty( $title ) ) : ?>
        <h2 class="svc-details__title"><?php echo esc_html( $title ); ?></h2>
        <?php endif; ?>

        <div class="svc-details__content">

            <!-- Tabs list -->
            <div class="svc-details__tabs" role="tablist" aria-label="Категории услуг">
                <?php foreach ( $tabs as $index => $tab ) :
                    $is_active = ( 0 === $index );
                    $tab_icon  = $tab['tab_icon'] ?? '';
                ?>
                <button class="svc-details__tab<?php echo $is_active ? ' -active' : ''; ?>" role="tab" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>" data-tab="<?php echo esc_attr( $tab['tab_id'] ); ?>" type="button">
                    <?php if ( ! empty( $tab_icon ) ) : ?>
                    <span class="svc-details__tab-icon" aria-hidden="true"><?php echo miauto_kses_svg( $tab_icon ); ?></span>
                    <?php endif; ?>
                    <?php echo esc_html( $tab['tab_title'] ); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Panels -->
            <?php foreach ( $tabs as $index => $tab ) :
                $is_active   = ( 0 === $index );
                $badge       = $tab['badge'] ?? '';
                $panel_title = $tab['panel_title'] ?? '';
                $panel_text  = $tab['panel_text'] ?? '';
                $features    = $tab['tab_features'] ?? array();
                $price_label = $tab['price_label'] ?? '';
                $price_value = $tab['price_value'] ?? '';
                $cta_text    = $tab['tab_cta_text'] ?? '';
                $cta_url     = $tab['tab_cta_url'] ?? '';
            ?>
            <div class="svc-details__panel<?php echo $is_active ? ' -active' : ''; ?>" role="tabpanel" data-panel="<?php echo esc_attr( $tab['tab_id'] ); ?>">

                <?php if ( ! empty( $badge ) ) : ?>
                <span class="svc-details__badge"><?php echo esc_html( $badge ); ?></span>
                <?php endif; ?>

                <?php if ( ! empty( $panel_title ) ) : ?>
                <h3 class="svc-details__panel-title"><?php echo esc_html( $panel_title ); ?></h3>
                <?php endif; ?>

                <?php if ( ! empty( $panel_text ) ) : ?>
                <div class="svc-details__panel-text"><?php echo apply_filters( 'the_content', $panel_text ); ?></div>
                <?php endif; ?>

                <?php if ( ! empty( $features ) ) : ?>
                <div class="svc-details__features">
                    <?php foreach ( $features as $feat ) : ?>
                    <div class="svc-details__feature">
                        <svg class="svc-details__feature-icon" viewBox="0 0 14 10" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M1 5L5 9L13 1" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="svc-details__feature-text"><?php echo esc_html( $feat['tabfeat_item'] ); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $price_value ) || ! empty( $cta_text ) ) : ?>
                <div class="svc-details__cta">
                    <?php if ( ! empty( $price_value ) ) : ?>
                    <div class="svc-details__cta-price">
                        <span class="svc-details__cta-label"><?php echo esc_html( $price_label ); ?></span>
                        <span class="svc-details__cta-value"><?php echo esc_html( $price_value ); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $cta_text ) && ! empty( $cta_url ) ) : ?>
                    <a class="svc-details__cta-btn" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_text ); ?></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div><!-- /panel -->
            <?php endforeach; ?>

        </div><!-- /.svc-details__content -->

    </div><!-- /.container -->
</section><!-- /.svc-details -->
