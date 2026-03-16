<?php
/**
 * Section: About Us + Articles.
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-about' );
}

$post_id         = $args['post_id'] ?? get_the_ID();
$title           = miauto_get_meta( 'miauto_about_title', $post_id );
$image_id        = miauto_get_meta( 'miauto_about_image', $post_id );
$text            = miauto_get_meta( 'miauto_about_text', $post_id );
$articles_title  = miauto_get_meta( 'miauto_articles_title', $post_id );
$articles_link   = miauto_get_meta( 'miauto_articles_link_text', $post_id );
$articles_count  = (int) miauto_get_meta( 'miauto_articles_count', $post_id );

if ( empty( $title ) && empty( $text ) ) {
    return;
}

if ( $articles_count < 1 ) {
    $articles_count = 2;
}

$articles_query = new WP_Query( array(
    'post_type'      => 'post',
    'posts_per_page' => $articles_count,
    'orderby'        => 'date',
    'order'          => 'DESC',
) );
?>

<section class="about" aria-label="О нас">
    <div class="about__container">

        <!-- Left column — About -->
        <div class="about__main">
            <?php if ( ! empty( $title ) ) : ?>
            <h2 class="about__title"><?php echo esc_html( $title ); ?></h2>
            <?php endif; ?>
            <div class="about__content">
                <?php
                if ( ! empty( $image_id ) ) {
                    echo wp_get_attachment_image( $image_id, 'large', false, array(
                        'class'   => 'about__image',
                        'loading' => 'lazy',
                    ) );
                }
                ?>
                <?php if ( ! empty( $text ) ) : ?>
                <div class="about__text"><?php echo wp_kses_post( wpautop( $text ) ); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right column — Articles -->
        <?php if ( $articles_query->have_posts() ) : ?>
        <aside class="articles">
            <div class="articles__header">
                <?php if ( ! empty( $articles_title ) ) : ?>
                <h2 class="articles__title"><?php echo esc_html( $articles_title ); ?></h2>
                <?php endif; ?>
                <?php if ( ! empty( $articles_link ) ) : ?>
                <a class="articles__link" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">
                    <?php echo esc_html( $articles_link ); ?>
                    <svg class="articles__link-icon" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M1 4H7M7 4L4 1M7 4L4 7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <?php endif; ?>
            </div>
            <div class="articles__grid">
                <?php while ( $articles_query->have_posts() ) : $articles_query->the_post(); ?>
                <a class="articles__card" href="<?php the_permalink(); ?>">
                    <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'medium', array(
                        'class'   => 'articles__card-image',
                        'loading' => 'lazy',
                    ) ); ?>
                    <?php endif; ?>
                    <div class="articles__card-body">
                        <h3 class="articles__card-title"><?php the_title(); ?></h3>
                        <?php if ( has_excerpt() ) : ?>
                        <p class="articles__card-desc"><?php echo esc_html( get_the_excerpt() ); ?></p>
                        <?php endif; ?>
                        <span class="articles__card-link">Читать подробнее</span>
                    </div>
                </a>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </aside>
        <?php endif; ?>

    </div><!-- /.about__container -->
</section><!-- /.about -->
