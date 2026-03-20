<?php
/**
 * Section: Prices (model/service tabbed price tables).
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-prices' );
}
wp_enqueue_script( 'miauto-prices' );

$post_id  = $args['post_id'] ?? get_the_ID();
$title    = miauto_get_meta( 'miauto_prices_title', $post_id );
$subtitle = miauto_get_meta( 'miauto_prices_subtitle', $post_id );
$models   = miauto_get_meta( 'miauto_prices_models', $post_id );

if ( empty( $models ) ) {
    return;
}
?>

<section class="prices" aria-label="<?php echo esc_attr( $title ); ?>">
    <div class="container">

        <div class="prices__header">
            <?php if ( ! empty( $title ) ) : ?>
            <h1 class="prices__title"><?php echo esc_html( $title ); ?></h1>
            <?php endif; ?>
            <?php if ( ! empty( $subtitle ) ) : ?>
            <p class="prices__subtitle"><?php echo esc_html( $subtitle ); ?></p>
            <?php endif; ?>
        </div>

        <!-- Model tabs -->
        <div class="prices__model-tabs" role="tablist" aria-label="Выбор модели">
            <?php foreach ( $models as $i => $model ) : ?>
            <button class="prices__model-tab<?php echo 0 === $i ? ' -active' : ''; ?>" type="button" data-model="<?php echo esc_attr( sanitize_title( $model['model_name'] ) ); ?>"><?php echo esc_html( $model['model_name'] ); ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Service tabs (from first model's categories) -->
        <?php
        $service_tabs = array();
        foreach ( $models as $model ) {
            if ( ! empty( $model['price_cats'] ) ) {
                foreach ( $model['price_cats'] as $cat ) {
                    $slug = sanitize_title( $cat['cat_name'] );
                    if ( ! isset( $service_tabs[ $slug ] ) ) {
                        $service_tabs[ $slug ] = $cat['cat_name'];
                    }
                }
            }
        }
        ?>
        <?php if ( ! empty( $service_tabs ) ) : ?>
        <div class="prices__service-tabs" role="tablist" aria-label="Выбор типа услуги">
            <?php $first = true; ?>
            <?php foreach ( $service_tabs as $slug => $label ) : ?>
            <button class="prices__service-tab<?php echo $first ? ' -active' : ''; ?>" type="button" data-service="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></button>
            <?php $first = false; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Price tables -->
        <div class="prices__tables">
            <?php $first_table = true; ?>
            <?php foreach ( $models as $model ) : ?>
                <?php if ( ! empty( $model['price_cats'] ) ) : ?>
                    <?php foreach ( $model['price_cats'] as $cat ) : ?>
                    <div class="prices__table-wrap<?php echo ! $first_table ? ' -hidden' : ''; ?>" data-model="<?php echo esc_attr( sanitize_title( $model['model_name'] ) ); ?>" data-service="<?php echo esc_attr( sanitize_title( $cat['cat_name'] ) ); ?>">
                        <table class="prices__table">
                            <thead>
                                <tr>
                                    <th>Наименование услуги</th>
                                    <th>Стоимость</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty( $cat['price_rows'] ) ) : ?>
                                    <?php foreach ( $cat['price_rows'] as $row ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $row['row_name'] ); ?></td>
                                        <td><?php echo esc_html( $row['row_price'] ); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php $first_table = false; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>

            <p class="prices__empty">Выберите модель и тип услуги для просмотра цен</p>
        </div>

    </div>
</section><!-- /.prices -->
