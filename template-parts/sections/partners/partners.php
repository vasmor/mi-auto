<?php
/**
 * Section: Partners.
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-partners' );
}

$title   = miauto_get_option( 'miauto_partners_title' );
$gallery = miauto_get_option( 'miauto_partners_gallery' );

if ( empty( $gallery ) ) {
    return;
}
?>

<section class="partners" aria-label="Наши партнеры">
    <div class="partners__container">

        <?php if ( ! empty( $title ) ) : ?>
        <h2 class="partners__title"><?php echo esc_html( $title ); ?></h2>
        <?php endif; ?>

        <div class="partners__grid">
            <?php foreach ( $gallery as $image_id ) : ?>
            <div class="partners__card">
                <?php
                echo wp_get_attachment_image( $image_id, 'medium', false, array(
                    'class'   => 'partners__logo',
                    'loading' => 'lazy',
                ) );
                ?>
            </div>
            <?php endforeach; ?>
        </div>

    </div><!-- /.partners__container -->
</section><!-- /.partners -->
