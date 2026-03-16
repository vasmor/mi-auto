<?php
/**
 * Section: Top Bar (promo banner).
 *
 * @package miauto
 */

$miauto_enabled = miauto_get_option( 'miauto_top_bar_enabled' );

if ( empty( $miauto_enabled ) ) {
    return;
}

$miauto_label = miauto_get_option( 'miauto_top_bar_label' );
$miauto_text  = miauto_get_option( 'miauto_top_bar_text' );

if ( empty( $miauto_label ) && empty( $miauto_text ) ) {
    return;
}
?>

<div class="top-bar" role="banner" aria-label="Акция">
    <div class="top-bar__strip">
        <div class="top-bar__content">
            <?php if ( ! empty( $miauto_label ) ) : ?>
            <span class="top-bar__label"><?php echo esc_html( $miauto_label ); ?></span>
            <?php endif; ?>
            <?php if ( ! empty( $miauto_text ) ) : ?>
            <p class="top-bar__text"><?php echo esc_html( $miauto_text ); ?></p>
            <?php endif; ?>
        </div>
        <button class="top-bar__close" type="button" aria-label="Закрыть акцию">
            <svg viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M1 1L11 11M11 1L1 11" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </button>
    </div>
</div>
