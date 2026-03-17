<?php
/**
 * Carbon Fields: Prices page meta fields.
 *
 * @package miauto
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

Container::make( 'post_meta', 'miauto_prices', 'Прайс-лист' )
    ->where( 'post_template', '=', 'page-prices.php' )
    ->add_fields( array(
        Field::make( 'text', 'miauto_prices_title', 'Заголовок' )
            ->set_default_value( 'Прайс-лист' ),
        Field::make( 'text', 'miauto_prices_subtitle', 'Подзаголовок' ),
        Field::make( 'complex', 'miauto_prices_models', 'Модели' )
            ->set_layout( 'tabbed-vertical' )
            ->setup_labels( array( 'singular_name' => 'Модель', 'plural_name' => 'Модели' ) )
            ->add_fields( array(
                Field::make( 'text', 'model_name', 'Название модели' )
                    ->set_required( true ),
                Field::make( 'complex', 'price_cats', 'Категории услуг' )
                    ->set_layout( 'tabbed-horizontal' )
                    ->setup_labels( array( 'singular_name' => 'Категория', 'plural_name' => 'Категории' ) )
                    ->add_fields( array(
                        Field::make( 'text', 'cat_name', 'Название категории' )
                            ->set_required( true ),
                        Field::make( 'complex', 'price_rows', 'Строки прайса' )
                            ->set_layout( 'tabbed-vertical' )
                            ->add_fields( array(
                                Field::make( 'text', 'row_name', 'Наименование услуги' ),
                                Field::make( 'text', 'price', 'Стоимость' ),
                            ) ),
                    ) ),
            ) ),
    ) );
