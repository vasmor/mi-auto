<?php
/**
 * Custom Nav Walker for MI-AUTO theme.
 *
 * Adds SVG chevron icons to menu items with children.
 * Desktop: hover-opened dropdown via CSS.
 * Mobile: click-on-chevron toggles .-open class via JS, with smooth grid-template-rows animation.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Miauto_Nav_Walker extends Walker_Nav_Menu {

    /**
     * SVG chevron icon markup.
     */
    const CHEVRON_SVG = '<svg class="header__nav-arrow" viewBox="0 0 7 4" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M1 1L3.5 3.5L6 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    /**
     * Opens submenu wrapper.
     */
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '<ul class="header__submenu"><div>';
    }

    /**
     * Closes submenu wrapper.
     */
    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '</div></ul>';
    }

    /**
     * Outputs a menu item opening tag and link.
     *
     * @param string   $output            Used to append additional content.
     * @param WP_Post  $data_object       Menu item data object.
     * @param int      $depth             Depth of menu item.
     * @param stdClass $args              An object of wp_nav_menu() arguments.
     * @param int      $current_object_id Optional. ID of the current item. Default 0.
     */
    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        $item = $data_object;

        $classes   = empty( $item->classes ) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;

        $has_children = in_array( 'menu-item-has-children', $classes, true );

        // Build <li> class attribute.
        $class_names = join( ' ', array_filter( array_map( 'trim', $classes ) ) );
        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        // Build <li> id attribute.
        $id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth );
        $id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

        $output .= '<li' . $id . $class_names . '>';

        // Build link attributes.
        $atts = array();
        $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
        $atts['target'] = ! empty( $item->target ) ? $item->target : '';
        if ( '_blank' === $item->target && empty( $item->xfn ) ) {
            $atts['rel'] = 'noopener noreferrer';
        } else {
            $atts['rel'] = $item->xfn;
        }
        $atts['href']         = ! empty( $item->url ) ? $item->url : '';
        $atts['aria-current'] = $item->current ? 'page' : '';

        $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( is_scalar( $value ) && '' !== $value && false !== $value ) {
                $value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $title = apply_filters( 'the_title', $item->title, $item->ID );
        $title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

        $item_output  = isset( $args->before ) ? $args->before : '';
        $item_output .= '<a' . $attributes . '>';
        $item_output .= ( isset( $args->link_before ) ? $args->link_before : '' ) . $title . ( isset( $args->link_after ) ? $args->link_after : '' );
        $item_output .= '</a>';

        // Add chevron button for items with children.
        if ( $has_children ) {
            $item_output .= '<button class="header__nav-toggle" type="button" aria-label="Открыть подменю" aria-expanded="false">';
            $item_output .= self::CHEVRON_SVG;
            $item_output .= '</button>';
        }

        $item_output .= isset( $args->after ) ? $args->after : '';

        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
    }
}
