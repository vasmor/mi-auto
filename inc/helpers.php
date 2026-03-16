<?php
/**
 * Helper functions.
 *
 * @package miauto
 */

/**
 * Render title with highlighted accent part.
 *
 * @param string $title       Full title text.
 * @param string $accent      Substring to highlight.
 * @param string $accent_class CSS class for accent span.
 * @return string HTML with accent wrapped in span.
 */
function miauto_highlight_title( $title, $accent, $accent_class = 'title-accent' ) {
    if ( empty( $title ) ) {
        return '';
    }

    if ( empty( $accent ) ) {
        return esc_html( $title );
    }

    $highlighted = '<span class="' . esc_attr( $accent_class ) . '">' . esc_html( $accent ) . '</span>';

    return str_replace( esc_html( $accent ), $highlighted, esc_html( $title ) );
}

/**
 * Get theme option from Carbon Fields.
 *
 * @param string $key Option name.
 * @return mixed Option value.
 */
function miauto_get_option( $key ) {
    if ( ! function_exists( 'carbon_get_theme_option' ) ) {
        return '';
    }

    return carbon_get_theme_option( $key );
}

/**
 * Get post meta from Carbon Fields.
 *
 * @param string   $key     Meta field name.
 * @param int|null $post_id Post ID. Defaults to current post.
 * @return mixed Meta value.
 */
function miauto_get_meta( $key, $post_id = null ) {
    if ( ! function_exists( 'carbon_get_post_meta' ) ) {
        return '';
    }

    if ( null === $post_id ) {
        $post_id = get_the_ID();
    }

    return carbon_get_post_meta( $post_id, $key );
}

/**
 * Output SVG with allowed tags via wp_kses.
 *
 * @param string $svg SVG markup.
 * @return string Sanitized SVG.
 */
function miauto_kses_svg( $svg ) {
    $allowed = array(
        'svg'    => array(
            'class'       => true,
            'viewbox'     => true,
            'fill'        => true,
            'xmlns'       => true,
            'aria-hidden' => true,
            'width'       => true,
            'height'      => true,
        ),
        'path'   => array(
            'd'               => true,
            'fill'            => true,
            'fill-rule'       => true,
            'clip-rule'       => true,
            'stroke'          => true,
            'stroke-width'    => true,
            'stroke-linecap'  => true,
            'stroke-linejoin' => true,
            'transform'       => true,
        ),
        'circle' => array(
            'cx'              => true,
            'cy'              => true,
            'r'               => true,
            'fill'            => true,
            'stroke'          => true,
            'stroke-width'    => true,
            'stroke-linecap'  => true,
            'stroke-linejoin' => true,
        ),
        'rect' => array(
            'x'               => true,
            'y'               => true,
            'width'           => true,
            'height'          => true,
            'rx'              => true,
            'ry'              => true,
            'fill'            => true,
            'stroke'          => true,
            'stroke-width'    => true,
            'stroke-linecap'  => true,
            'stroke-linejoin' => true,
        ),
        'line' => array(
            'x1'              => true,
            'y1'              => true,
            'x2'              => true,
            'y2'              => true,
            'stroke'          => true,
            'stroke-width'    => true,
            'stroke-linecap'  => true,
            'stroke-linejoin' => true,
        ),
        'polyline' => array(
            'points'          => true,
            'fill'            => true,
            'stroke'          => true,
            'stroke-width'    => true,
            'stroke-linecap'  => true,
            'stroke-linejoin' => true,
        ),
    );

    return wp_kses( $svg, $allowed );
}
