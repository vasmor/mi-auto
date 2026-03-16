<?php
/**
 * Section: Car Models.
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-car-models' );
}

$post_id = $args['post_id'] ?? get_the_ID();
$title   = miauto_get_meta( 'miauto_car_models_title', $post_id );

$models_query = new WP_Query( array(
    'post_type'      => 'miauto_model',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
) );

if ( ! $models_query->have_posts() ) {
    return;
}
?>

<section class="car-models" aria-label="Модели автомобилей">
    <div class="car-models__container">

        <?php if ( ! empty( $title ) ) : ?>
        <h2 class="car-models__title"><?php echo esc_html( $title ); ?></h2>
        <?php endif; ?>

        <div class="car-models__grid">
            <?php while ( $models_query->have_posts() ) : $models_query->the_post(); ?>
            <a class="car-models__card" href="<?php the_permalink(); ?>">
                <?php
                if ( has_post_thumbnail() ) {
                    the_post_thumbnail( 'medium', array(
                        'class'   => 'car-models__image',
                        'loading' => 'lazy',
                    ) );
                }
                ?>
                <span class="car-models__name"><?php the_title(); ?></span>
            </a>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>

    </div><!-- /.car-models__container -->
</section><!-- /.car-models -->
