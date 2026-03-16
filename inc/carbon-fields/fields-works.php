<?php
/**
 * Carbon Fields: Work (miauto_work) CPT post meta.
 *
 * @package miauto
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'post_meta', 'miauto_work_details', 'Детали работы' )
    ->where( 'post_type', '=', 'miauto_work' )
    ->add_fields( array(
        Field::make( 'text', 'miauto_work_model', 'Модель авто' )
            ->set_help_text( 'Например: Mitsubishi Outlander XL' )
            ->set_required( true ),
        Field::make( 'text', 'miauto_work_mileage', 'Пробег' )
            ->set_help_text( 'Например: 128 000 км' ),
        Field::make( 'text', 'miauto_work_issue', 'Проблема' )
            ->set_help_text( 'Например: Жор масла, дым' ),
        Field::make( 'complex', 'miauto_work_defects', 'Дефектовка' )
            ->set_layout( 'tabbed-vertical' )
            ->add_fields( array(
                Field::make( 'text', 'text', 'Пункт дефектовки' ),
            ) ),
        Field::make( 'complex', 'miauto_work_done', 'Выполненные работы' )
            ->set_layout( 'tabbed-vertical' )
            ->add_fields( array(
                Field::make( 'text', 'text', 'Пункт работ' ),
            ) ),
        Field::make( 'text', 'miauto_work_price', 'Стоимость работ' )
            ->set_help_text( 'Например: 85 000 ₽' ),
        Field::make( 'text', 'miauto_work_duration', 'Срок ремонта' )
            ->set_help_text( 'Например: 7 дней' ),
        Field::make( 'media_gallery', 'miauto_work_gallery', 'Фотогалерея' )
            ->set_type( array( 'image' ) ),
    ) );
