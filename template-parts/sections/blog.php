<?php
/**
 * Section: Blog grid (standard posts with category filter).
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-blog' );
}
wp_enqueue_script( 'miauto-blog' );

$categories = get_categories( array(
    'hide_empty' => true,
) );
?>

<section class="blog" aria-label="Полезные статьи">
    <div class="blog__container">
        <h1 class="blog__title">Блог</h1>

        <?php if ( ! empty( $categories ) ) : ?>
        <div class="blog__filters">
            <button class="blog__filter -active" type="button" data-filter="all">Все</button>
            <?php foreach ( $categories as $cat ) : ?>
            <button class="blog__filter" type="button" data-filter="<?php echo esc_attr( $cat->slug ); ?>">#<?php echo esc_html( $cat->name ); ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="blog__grid">
            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php
                    $post_cats   = get_the_category();
                    $cat_slugs   = wp_list_pluck( $post_cats, 'slug' );
                    $data_cat    = ! empty( $cat_slugs ) ? $cat_slugs[0] : '';
                    ?>
                    <a class="blog__card" href="<?php the_permalink(); ?>" data-category="<?php echo esc_attr( $data_cat ); ?>">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'medium', array(
                                'class'   => 'blog__card-image',
                                'loading' => 'lazy',
                            ) ); ?>
                        <?php endif; ?>
                        <div class="blog__card-body">
                            <h2 class="blog__card-title"><?php the_title(); ?></h2>
                            <?php if ( has_excerpt() ) : ?>
                            <p class="blog__card-desc"><?php echo esc_html( get_the_excerpt() ); ?></p>
                            <?php endif; ?>
                            <span class="blog__card-link">
                                Читать подробнее
                                <svg class="blog__card-link-arrow" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M1 4H7M7 4L4 1M7 4L4 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

    </div>
</section><!-- /.blog -->
