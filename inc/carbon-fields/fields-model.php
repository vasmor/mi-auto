<?php
/**
 * Carbon Fields: Model card (miauto_model CPT) post meta.
 *
 * @package miauto
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

// ── MD-Hero ──────────────────────────────────────────────────────────

Container::make( 'post_meta', 'miauto_md_hero', 'Модель — Герой' )
	->where( 'post_type', '=', 'miauto_model' )
	->add_fields( array(
		Field::make( 'text', 'miauto_md_hero_subtitle', 'Подзаголовок' )
			->set_help_text( 'Краткое описание под заголовком (H1 берётся из заголовка записи).' ),
		Field::make( 'complex', 'miauto_md_hero_features', 'Список преимуществ (галочки)' )
			->set_layout( 'tabbed-horizontal' )
			->add_fields( array(
				Field::make( 'text', 'md_feature_text', 'Текст пункта' ),
			) )
			->set_header_template( '<%- md_feature_text %>' ),
		Field::make( 'text', 'miauto_md_hero_cta_primary_text', 'Текст основной кнопки' )
			->set_default_value( 'Записаться на диагностику' ),
		Field::make( 'text', 'miauto_md_hero_cta_secondary_text', 'Текст второй кнопки' )
			->set_default_value( 'Рассчитать стоимость' ),
		Field::make( 'image', 'miauto_md_hero_image', 'Изображение' ),
		Field::make( 'complex', 'miauto_md_hero_stats', 'Статистика (рейтинг, отзывы, опыт)' )
			->set_layout( 'tabbed-horizontal' )
			->add_fields( array(
				Field::make( 'text', 'md_stat_value', 'Значение' )
					->set_help_text( 'Например: 5,0 / 500+ / с 2018' ),
				Field::make( 'text', 'md_stat_label', 'Подпись' )
					->set_help_text( 'Например: Рейтинг на картах / Отзывов на картах / Опыт работы' ),
			) )
			->set_header_template( '<%- md_stat_value %> — <%- md_stat_label %>' ),
	) );

// ── Tabs ─────────────────────────────────────────────────────────────

Container::make( 'post_meta', 'miauto_md_tabs', 'Модель — Табы' )
	->where( 'post_type', '=', 'miauto_model' )
	->add_fields( array(
		Field::make( 'text', 'miauto_md_tabs_title', 'Заголовок секции' )
			->set_default_value( 'Стоимость обслуживания и ремонта' ),

		// ── Таб: Ремонтные работы ────────────────────────────────
		Field::make( 'separator', 'miauto_md_sep_repair', 'Таб «Ремонтные работы»' ),
		Field::make( 'complex', 'miauto_md_repair_rows', 'Ремонтные работы' )
			->set_layout( 'tabbed-horizontal' )
			->setup_labels( array( 'singular_name' => 'Работа', 'plural_name' => 'Работы' ) )
			->add_fields( array(
				Field::make( 'text', 'md_repair_name', 'Наименование работы' ),
				Field::make( 'text', 'md_repair_price', 'Стоимость' ),
			) )
			->set_header_template( '<%- md_repair_name %>' ),

		// ── Таб: Стоимость ТО ────────────────────────────────────
		Field::make( 'separator', 'miauto_md_sep_to', 'Таб «Стоимость ТО»' ),
		Field::make( 'complex', 'miauto_md_to_variants', 'Варианты ТО' )
			->set_layout( 'tabbed-horizontal' )
			->setup_labels( array( 'singular_name' => 'Вариант', 'plural_name' => 'Варианты' ) )
			->add_fields( array(
				Field::make( 'text', 'md_to_variant_name', 'Название варианта' )
					->set_help_text( 'Например: ASX 1.6, OUT3 2.0 2WD' ),
				Field::make( 'complex', 'md_to_variant_rows', 'Строки по пробегу' )
					->set_layout( 'tabbed-horizontal' )
					->setup_labels( array( 'singular_name' => 'Строка', 'plural_name' => 'Строки' ) )
					->add_fields( array(
						Field::make( 'text', 'md_to_mileage', 'Пробег (км)' ),
						Field::make( 'text', 'md_to_work_price', 'Стоимость работ' ),
						Field::make( 'text', 'md_to_parts_price', 'Стоимость запчастей' ),
					) )
					->set_header_template( '<%- md_to_mileage %> км' ),
			) )
			->set_header_template( '<%- md_to_variant_name %>' ),

		// ── Таб: Карты ТО ────────────────────────────────────────
		Field::make( 'separator', 'miauto_md_sep_cards', 'Таб «Карты ТО»' ),
		Field::make( 'rich_text', 'miauto_md_to_cards_content', 'Контент «Карты ТО»' )
			->set_help_text( 'HTML-контент вкладки «Карты ТО» (импортируется автоматически).' ),
	) );
