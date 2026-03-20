<?php
/**
 * Section: Advantages (grid of cards with icons).
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-advantages' );
}

$post_id = $args['post_id'] ?? get_the_ID();
$title   = miauto_get_meta( 'miauto_advantages_title', $post_id );
$cards   = miauto_get_meta( 'miauto_advantages_cards', $post_id );

if ( empty( $cards ) ) {
    return;
}
?>

<section class="advantages" aria-label="<?php echo esc_attr( $title ); ?>">
    <div class="container">

        <?php if ( ! empty( $title ) ) : ?>
        <h2 class="advantages__title"><?php echo esc_html( $title ); ?></h2>
        <?php endif; ?>

        <div class="advantages__grid">
            <?php foreach ( $cards as $card ) : ?>
            <div class="advantages__card">
                <?php if ( ! empty( $card['adv_svg'] ) ) : ?>
                <div class="advantages__icon">
                    <?php echo miauto_kses_svg( $card['adv_svg'] ); ?>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $card['adv_title'] ) ) : ?>
                <h3 class="advantages__card-title"><?php echo esc_html( $card['adv_title'] ); ?></h3>
                <?php endif; ?>

                <?php if ( ! empty( $card['adv_text'] ) ) : ?>
                <p class="advantages__card-text"><?php echo esc_html( $card['adv_text'] ); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section><!-- /.advantages -->
