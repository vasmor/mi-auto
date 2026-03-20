<?php
/**
 * Section: Partners.
 *
 * @package miauto
 */

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-partners' );
}

$title = miauto_get_option( 'miauto_partners_title' );
$items = miauto_get_option( 'miauto_partners_items' );

if ( empty( $items ) ) {
    return;
}
?>

<section class="partners" aria-label="Наши партнеры">
    <div class="container">

        <?php if ( ! empty( $title ) ) : ?>
        <h2 class="partners__title"><?php echo esc_html( $title ); ?></h2>
        <?php endif; ?>

        <div class="partners__grid">
            <?php foreach ( $items as $item ) : ?>
            <div class="partners__card">
                <?php if ( ! empty( $item['pitem_url'] ) ) : ?>
                <a href="<?php echo esc_url( $item['pitem_url'] ); ?>"
                   title="<?php echo esc_attr( $item['pitem_title'] ); ?>"
                   target="_blank" rel="noopener noreferrer">
                <?php endif; ?>
                    <?php
                    echo wp_get_attachment_image( $item['pitem_image'], 'medium', false, array(
                        'class'   => 'partners__logo',
                        'loading' => 'lazy',
                        'alt'     => esc_attr( $item['pitem_title'] ),
                    ) );
                    ?>
                <?php if ( ! empty( $item['pitem_url'] ) ) : ?></a><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

    </div><!-- /.container -->
</section><!-- /.partners -->
