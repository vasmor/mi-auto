<?php
/**
 * Carbon Fields: Service card (miauto_service CPT) post meta.
 *
 * @package miauto
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

// ── SC-Hero ─────────────────────────────────────────────────────────

Container::make( 'post_meta', 'miauto_sc_hero', 'Услуга — Герой' )
	->where( 'post_type', '=', 'miauto_service' )
	->add_fields( array(
		Field::make( 'text', 'miauto_sc_hero_subtitle', 'Подзаголовок' )
			->set_help_text( 'Краткое описание под заголовком (H1 берется из заголовка записи).' ),
		Field::make( 'complex', 'miauto_sc_hero_features', 'Список преимуществ (галочки)' )
			->set_layout( 'tabbed-horizontal' )
			->add_fields( array(
				Field::make( 'text', 'feature_text', 'Текст пункта' ),
			) )
			->set_header_template( '<%- feature_text %>' ),
		Field::make( 'text', 'miauto_sc_hero_cta_primary_text', 'Текст основной кнопки' )
			->set_default_value( 'Записаться на диагностику' ),
		Field::make( 'text', 'miauto_sc_hero_cta_secondary_text', 'Текст второй кнопки' )
			->set_default_value( 'Рассчитать стоимость' ),
		Field::make( 'image', 'miauto_sc_hero_image', 'Изображение' ),
		Field::make( 'complex', 'miauto_sc_hero_stats', 'Статистика (рейтинг, отзывы, опыт)' )
			->set_layout( 'tabbed-horizontal' )
			->add_fields( array(
				Field::make( 'text', 'stat_value', 'Значение' )
					->set_help_text( 'Например: 5,0 / 500+ / с 2018' ),
				Field::make( 'text', 'stat_label', 'Подпись' )
					->set_help_text( 'Например: Рейтинг на картах / Отзывов на картах / Опыт работы' ),
			) )
			->set_header_template( '<%- stat_value %> — <%- stat_label %>' ),
	) );

// ── Symptoms ────────────────────────────────────────────────────────

Container::make( 'post_meta', 'miauto_sc_symptoms', 'Услуга — Симптомы' )
	->where( 'post_type', '=', 'miauto_service' )
	->add_fields( array(
		Field::make( 'text', 'miauto_sc_symptoms_title', 'Заголовок' )
			->set_default_value( 'Когда нужен ремонт транспорта' ),
		Field::make( 'text', 'miauto_sc_symptoms_subtitle', 'Подзаголовок' ),
		Field::make( 'complex', 'miauto_sc_symptoms_cards', 'Карточки симптомов' )
			->set_layout( 'tabbed-horizontal' )
			->add_fields( array(
				Field::make( 'image', 'sym_image', 'Изображение' ),
				Field::make( 'text', 'symptom_title', 'Заголовок' ),
				Field::make( 'textarea', 'symptom_desc', 'Описание' ),
			) )
			->set_header_template( '<%- symptom_title %>' ),
		Field::make( 'text', 'miauto_sc_symptoms_cta_text', 'Текст призыва (над кнопкой)' )
			->set_default_value( 'Запишитесь — проверим причину и предложим варианты решения' ),
		Field::make( 'text', 'miauto_sc_symptoms_cta_btn_text', 'Текст кнопки' )
			->set_default_value( 'Записаться на диагностику' ),
	) );

// ── Services List ───────────────────────────────────────────────────

Container::make( 'post_meta', 'miauto_sc_svc_list', 'Услуга — Список работ' )
	->where( 'post_type', '=', 'miauto_service' )
	->add_fields( array(
		Field::make( 'text', 'miauto_sc_svc_list_title', 'Заголовок' )
			->set_default_value( 'Какие работы выполняем' ),
		Field::make( 'complex', 'miauto_sc_svc_list_items', 'Пункты работ' )
			->set_layout( 'tabbed-horizontal' )
			->add_fields( array(
				Field::make( 'text', 'svc_title', 'Заголовок' ),
				Field::make( 'textarea', 'svc_desc', 'Описание' ),
			) )
			->set_header_template( '<%- svc_title %>' ),
	) );

// ── Prices ──────────────────────────────────────────────────────────

Container::make( 'post_meta', 'miauto_sc_prices', 'Услуга — Цены' )
	->where( 'post_type', '=', 'miauto_service' )
	->add_fields( array(
		Field::make( 'text', 'miauto_sc_prices_title', 'Заголовок' )
			->set_default_value( 'Стоимость ремонта' ),
		Field::make( 'text', 'miauto_sc_prices_subtitle', 'Подзаголовок' ),
		Field::make( 'complex', 'miauto_sc_prices_rows', 'Строки прайса' )
			->set_layout( 'tabbed-horizontal' )
			->add_fields( array(
				Field::make( 'text', 'service_name', 'Название услуги' ),
				Field::make( 'text', 'sc_service_price', 'Цена' )
					->set_help_text( 'Например: от 5 000 ₽' ),
			) )
			->set_header_template( '<%- service_name %> — <%- sc_service_price %>' ),
		Field::make( 'text', 'miauto_sc_prices_footer_heading', 'Заголовок футера' )
			->set_default_value( 'Получите точную смету после дефектовки' ),
		Field::make( 'text', 'miauto_sc_prices_footer_desc', 'Описание футера' )
			->set_default_value( 'До начала работ всё согласуем' ),
		Field::make( 'text', 'miauto_sc_prices_footer_btn_text', 'Текст кнопки футера' )
			->set_default_value( 'Записаться на диагностику' ),
	) );

// ── Warranty ────────────────────────────────────────────────────────

Container::make( 'post_meta', 'miauto_sc_warranty', 'Услуга — Гарантия' )
	->where( 'post_type', '=', 'miauto_service' )
	->add_fields( array(
		Field::make( 'text', 'miauto_sc_warranty_title', 'Заголовок' )
			->set_default_value( 'Гарантия и ответственность' ),
		Field::make( 'text', 'miauto_sc_warranty_subtitle', 'Подзаголовок' ),
		Field::make( 'complex', 'miauto_sc_warranty_cards', 'Карточки гарантии' )
			->set_layout( 'tabbed-horizontal' )
			->add_fields( array(
				Field::make( 'textarea', 'war_svg', 'SVG-иконка' )
					->set_help_text( 'SVG-код иконки (тег &lt;svg&gt;...&lt;/svg&gt;).' ),
				Field::make( 'text', 'warranty_text', 'Текст' ),
			) )
			->set_header_template( '<%- warranty_text %>' ),
	) );

// ── Service Price (for listings / cards) ────────────────────────────

Container::make( 'post_meta', 'miauto_sc_price_display', 'Услуга — Цена (для карточки)' )
	->where( 'post_type', '=', 'miauto_service' )
	->add_fields( array(
		Field::make( 'text', 'miauto_service_price', 'Цена услуги' )
			->set_help_text( 'Отображается в карточке услуги на странице каталога. Например: от 25 000 ₽' ),
	) );

// ── SC-Examples (works on service page) ─────────────────────────────

Container::make( 'post_meta', 'miauto_sc_examples', 'Услуга — Примеры работ' )
	->where( 'post_type', '=', 'miauto_service' )
	->add_fields( array(
		Field::make( 'text', 'miauto_sc_examples_title', 'Заголовок секции примеров' )
			->set_default_value( 'Примеры наших работ' )
			->set_help_text( 'Если пусто — выведется «Примеры наших работ».' ),
	) );

// ── FAQ ────────────────────────────────────────────────────────────

Container::make( 'post_meta', 'miauto_sc_faq', 'Услуга — FAQ' )
	->where( 'post_type', '=', 'miauto_service' )
	->add_fields( array(
		Field::make( 'text', 'miauto_sc_faq_heading', 'Заголовок секции' )
			->set_default_value( 'FAQ' ),
		Field::make( 'complex', 'miauto_sc_faq_entries', 'Пункты FAQ' )
			->set_layout( 'tabbed-horizontal' )
			->add_fields( array(
				Field::make( 'checkbox', 'faq_entry_active', 'Активный пункт (раскрыт при загрузке)' ),
				Field::make( 'text', 'faq_entry_question', 'Вопрос' ),
				Field::make( 'rich_text', 'faq_entry_answer', 'Ответ' ),
			) )
			->set_header_template( '<%- faq_entry_question %>' ),
	) );
