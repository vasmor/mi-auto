<?php
/**
 * Section: Single article content.
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-article' );
}
?>

<section class="article-page" aria-label="<?php echo esc_attr( get_the_title() ); ?>">
    <div class="container">

        <div class="article-page__header">
            <?php
            $categories = get_the_category();
            if ( ! empty( $categories ) ) :
                foreach ( $categories as $cat ) : ?>
            <span class="article-page__tag">#<?php echo esc_html( $cat->name ); ?></span>
                <?php endforeach;
            endif; ?>

            <h1 class="article-page__title"><?php the_title(); ?></h1>

            <div class="article-page__meta">
                <span><?php echo esc_html( get_the_date() ); ?></span>
            </div>
        </div>

        <?php if ( has_post_thumbnail() ) : ?>
        <?php the_post_thumbnail( 'large', array(
            'class'   => 'article-page__image',
            'loading' => 'eager',
        ) ); ?>
        <?php endif; ?>

        <div class="article-page__body">
            <?php the_content(); ?>
        </div>

        <?php
        $blog_page = get_option( 'page_for_posts' );
        $blog_url  = $blog_page ? get_permalink( $blog_page ) : home_url( '/blog/' );
        ?>
        <a class="article-page__back" href="<?php echo esc_url( $blog_url ); ?>">
            <svg class="article-page__back-arrow" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M1 4H7M7 4L4 1M7 4L4 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Вернуться к статьям
        </a>

    </div><!-- /.container -->
</section><!-- /.article-page -->
