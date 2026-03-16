<?php
/**
 * Section: Hero slider.
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-hero' );
}
wp_enqueue_script( 'miauto-hero' );

$post_id  = $args['post_id'] ?? get_the_ID();
$slides   = miauto_get_meta( 'miauto_hero_slides', $post_id );
$features = miauto_get_meta( 'miauto_hero_features', $post_id );

if ( empty( $slides ) ) {
    return;
}

// Service menu — from CPT miauto_service
$services_query = new WP_Query( array(
    'post_type'      => 'miauto_service',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
) );
?>

<section class="hero" aria-label="Главный экран">
    <div class="hero__inner">

        <!-- Service menu card -->
        <?php if ( $services_query->have_posts() ) : ?>
        <aside class="hero__service-menu" aria-label="Категории услуг">
            <ul class="hero__service-list">
                <?php while ( $services_query->have_posts() ) : $services_query->the_post(); ?>
                <li><a class="hero__service-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                <?php endwhile; wp_reset_postdata(); ?>
            </ul>
        </aside>
        <?php endif; ?>

        <!-- Slider -->
        <div class="hero__bg-wrap">

            <?php foreach ( $slides as $index => $slide ) :
                $is_active  = ( 0 === $index );
                $image_id   = $slide['image'] ?? '';
                $image_alt  = $slide['image_alt'] ?? '';
                $title      = $slide['title'] ?? '';
                $desc       = $slide['description'] ?? '';
                $cta_text   = $slide['cta_text'] ?? '';
                $cta_url    = $slide['cta_url'] ?? '';
                $heading_tag = ( 0 === $index ) ? 'h1' : 'h2';
            ?>
            <div class="hero__slide<?php echo $is_active ? ' -active' : ''; ?>" data-slide="<?php echo esc_attr( $index ); ?>">
                <?php
                if ( ! empty( $image_id ) ) {
                    echo wp_get_attachment_image( $image_id, 'full', false, array(
                        'class'   => 'hero__bg-img',
                        'alt'     => esc_attr( $image_alt ),
                        'loading' => ( 0 === $index ) ? 'eager' : 'lazy',
                    ) );
                }
                ?>
                <div class="hero__gradient" aria-hidden="true"></div>
                <div class="hero__content">
                    <<?php echo esc_attr( $heading_tag ); ?> class="hero__title"><?php echo esc_html( $title ); ?></<?php echo esc_attr( $heading_tag ); ?>>
                    <?php if ( ! empty( $desc ) ) : ?>
                    <p class="hero__description"><?php echo esc_html( $desc ); ?></p>
                    <?php endif; ?>

                    <?php if ( ! empty( $features ) ) : ?>
                    <div class="hero__features-mobile" aria-label="Преимущества">
                        <?php foreach ( $features as $feat ) : ?>
                        <div class="hero__feature-badge">
                            <span class="hero__feature-badge-text"><?php echo esc_html( $feat['text'] ); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $cta_text ) && ! empty( $cta_url ) ) : ?>
                    <a class="hero__cta" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_text ); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Shelf overlay -->
            <svg class="hero__overlay" viewBox="0 0 715 77" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M20.5165 30.4C20.5165 22.1157 27.2322 15.4 35.5165 15.4L700 15.4C708.284 15.4 715 8.68427 715 0.4L715 0L715 57C715 68.0457 706.046 77 695 77L0 77L0.5165 77C11.5622 77 20.5165 68.0457 20.5165 57L20.5165 30.4Z"/>
            </svg>

            <!-- Pagination dots -->
            <div class="hero__pagination" role="tablist" aria-label="Слайдер">
                <?php for ( $i = 0; $i < count( $slides ); $i++ ) : ?>
                <button class="hero__pagination-dot<?php echo ( 0 === $i ) ? ' -active' : ''; ?>" role="tab" aria-selected="<?php echo ( 0 === $i ) ? 'true' : 'false'; ?>" aria-label="Слайд <?php echo esc_attr( $i + 1 ); ?>" data-slide="<?php echo esc_attr( $i ); ?>" tabindex="0" type="button"></button>
                <?php endfor; ?>
            </div>

        </div><!-- /.hero__bg-wrap -->

        <!-- Feature pills — desktop -->
        <?php if ( ! empty( $features ) ) : ?>
        <div class="hero__features" aria-label="Преимущества">
            <?php foreach ( $features as $feat ) : ?>
            <div class="hero__feature-item">
                <?php if ( ! empty( $feat['svg'] ) ) : ?>
                <?php echo miauto_kses_svg( $feat['svg'] ); ?>
                <?php endif; ?>
                <span class="hero__feature-text"><?php echo esc_html( $feat['text'] ); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /.hero__inner -->
</section><!-- /.hero -->
