<?php
/**
 * Section: About Intro (image + text block).
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-about-intro' );
}

$post_id = $args['post_id'] ?? get_the_ID();
$title   = miauto_get_meta( 'miauto_about_intro_title', $post_id );
$texts   = miauto_get_meta( 'miauto_about_intro_texts', $post_id );
$image   = miauto_get_meta( 'miauto_about_intro_image', $post_id );

if ( empty( $title ) ) {
    return;
}
?>

<section class="about-intro" aria-label="<?php echo esc_attr( $title ); ?>">
    <div class="container">
        <?php
        if ( ! empty( $image ) ) {
            echo wp_get_attachment_image( $image, 'large', false, array(
                'class'   => 'about-intro__image',
                'loading' => 'lazy',
            ) );
        }
        ?>

        <div class="about-intro__content">
            <h2 class="about-intro__title"><?php echo esc_html( $title ); ?></h2>

            <?php if ( ! empty( $texts ) ) : ?>
                <?php foreach ( $texts as $item ) : ?>
                    <?php if ( ! empty( $item['text'] ) ) : ?>
                    <p class="about-intro__text"><?php echo esc_html( $item['text'] ); ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section><!-- /.about-intro -->
