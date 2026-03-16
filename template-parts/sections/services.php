<?php
/**
 * Section: Service Categories.
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-services' );
}
wp_enqueue_script( 'miauto-services' );

$post_id   = $args['post_id'] ?? get_the_ID();
$title     = miauto_get_meta( 'miauto_services_title', $post_id );
$more_text = miauto_get_meta( 'miauto_services_more_text', $post_id );

$services_query = new WP_Query( array(
    'post_type'      => 'miauto_service',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
) );

if ( ! $services_query->have_posts() ) {
    return;
}
?>

<section class="services" aria-label="Категории услуг">
    <div class="services__container">

        <?php if ( ! empty( $title ) ) : ?>
        <h2 class="services__title"><?php echo esc_html( $title ); ?></h2>
        <?php endif; ?>

        <div class="services__grid">
            <?php while ( $services_query->have_posts() ) : $services_query->the_post();
                $service_price = miauto_get_meta( 'miauto_service_price' );
            ?>
            <article class="services__card">
                <h3 class="services__card-title"><?php the_title(); ?></h3>
                <div class="services__card-footer">
                    <?php if ( ! empty( $service_price ) ) : ?>
                    <span class="services__card-price"><?php echo esc_html( $service_price ); ?></span>
                    <?php endif; ?>
                    <a class="services__card-btn" href="<?php the_permalink(); ?>">Подробнее</a>
                </div>
                <?php
                if ( has_post_thumbnail() ) {
                    the_post_thumbnail( 'medium', array(
                        'class'   => 'services__card-img',
                        'loading' => 'lazy',
                    ) );
                }
                ?>
            </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <?php if ( empty( $args['show_all'] ) && ! empty( $more_text ) ) : ?>
        <button class="services__more" type="button"><?php echo esc_html( $more_text ); ?></button>
        <?php endif; ?>

    </div><!-- /.services__container -->
</section><!-- /.services -->
