<?php
/**
 * Section: Breadcrumbs.
 *
 * Expects $args['breadcrumbs'] — array of items:
 *   [ ['label' => 'Главная', 'url' => '/'], ['label' => 'О компании'] ]
 * Last item without 'url' is treated as the current page.
 *
 * @package miauto
 */

$breadcrumbs = $args['breadcrumbs'] ?? array();

if ( empty( $breadcrumbs ) ) {
    return;
}

if ( empty( $args['skip_styles'] ) ) {
    wp_enqueue_style( 'miauto-breadcrumbs' );
}

$last_index = count( $breadcrumbs ) - 1;
?>

<nav class="breadcrumbs" aria-label="Хлебные крошки">
    <ol class="breadcrumbs__list">
        <?php foreach ( $breadcrumbs as $i => $item ) : ?>
            <?php if ( $i === $last_index || empty( $item['url'] ) ) : ?>
                <li><span class="breadcrumbs__current" aria-current="page"><?php echo esc_html( $item['label'] ); ?></span></li>
            <?php else : ?>
                <li><a class="breadcrumbs__link" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
                <li><span class="breadcrumbs__sep" aria-hidden="true">/</span></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>
