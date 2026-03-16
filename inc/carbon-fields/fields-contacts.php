<?php
/**
 * Carbon Fields: Contacts page meta fields.
 *
 * @package miauto
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'post_meta', 'miauto_contacts_page', 'Контакты — Секция' )
    ->where( 'post_template', '=', 'page-contacts.php' )
    ->add_fields( array(
        Field::make( 'text', 'miauto_contacts_title', 'Заголовок' )
            ->set_default_value( 'Наши контакты' ),
        Field::make( 'image', 'miauto_contacts_decoration', 'Декоративное изображение' ),
        Field::make( 'image', 'miauto_contacts_map', 'Изображение карты (заглушка)' ),
    ) );
