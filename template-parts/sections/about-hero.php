<?php
/**
 * Section: About Hero.
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-about-hero' );
}

$post_id = $args['post_id'] ?? get_the_ID();
$badge   = miauto_get_meta( 'miauto_about_hero_badge', $post_id );
$title   = miauto_get_meta( 'miauto_about_hero_title', $post_id );
$accent  = miauto_get_meta( 'miauto_about_hero_accent', $post_id );
$texts   = miauto_get_meta( 'miauto_about_hero_texts', $post_id );
$image   = miauto_get_meta( 'miauto_about_hero_image', $post_id );

if ( empty( $title ) ) {
    return;
}
?>

<section class="about-hero" aria-label="<?php echo esc_attr( $title ); ?>">
    <div class="container">
        <div class="about-hero__content">
            <?php if ( ! empty( $badge ) ) : ?>
            <span class="about-hero__badge"><?php echo esc_html( $badge ); ?></span>
            <?php endif; ?>

            <h1 class="about-hero__title"><?php echo miauto_highlight_title( $title, $accent, 'about-hero__title-accent' ); ?></h1>

            <?php if ( ! empty( $texts ) ) : ?>
                <?php foreach ( $texts as $item ) : ?>
                    <?php if ( ! empty( $item['hero_text'] ) ) : ?>
                    <p class="about-hero__text"><?php echo esc_html( $item['hero_text'] ); ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php
        if ( ! empty( $image ) ) {
            echo wp_get_attachment_image( $image, 'large', false, array(
                'class'   => 'about-hero__image',
                'loading' => 'eager',
            ) );
        }
        ?>
    </div>
</section><!-- /.about-hero -->
