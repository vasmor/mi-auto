<?php
/**
 * Content installer: Замена ремня ГРМ (miauto_service).
 *
 * Triggered by visiting: /wp-admin/?miauto_fill_grm=1
 * Safe to run multiple times — fills only empty fields.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function miauto_grm_init() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_GET['miauto_fill_grm'] ) || '1' !== $_GET['miauto_fill_grm'] ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_fill_grm' ) ) {
		add_action( 'admin_notices', function () {
			$url = wp_nonce_url( admin_url( '?miauto_fill_grm=1' ), 'miauto_fill_grm' );
			echo '<div class="notice notice-info"><p>MI-AUTO: Для заполнения Замены ремня ГРМ перейдите по ссылке: <a href="' . esc_url( $url ) . '">Заполнить контент Замены ремня ГРМ</a></p></div>';
		} );
		return;
	}

	$result = miauto_run_fill_grm();

	if ( is_wp_error( $result ) ) {
		$msg = $result->get_error_message();
		add_action( 'admin_notices', function () use ( $msg ) {
			echo '<div class="notice notice-error"><p>MI-AUTO Замена ремня ГРМ Error: ' . esc_html( $msg ) . '</p></div>';
		} );
		return;
	}

	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-success"><p>MI-AUTO: Контент страницы «Замена ремня ГРМ» успешно заполнен! Заполнены только пустые поля.</p></div>';
	} );
}
add_action( 'admin_init', 'miauto_grm_init' );

function miauto_run_fill_grm() {
	set_time_limit( 300 );
	wp_raise_memory_limit( 'admin' );

	$post_id = miauto_grm_get_post_id();
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$imgs = miauto_grm_upload_images();

	miauto_grm_fill_fields( $post_id, $imgs );

	if ( '' === get_post_field( 'post_content', $post_id ) ) {
		wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => miauto_grm_seo_text(),
		) );
	}

	miauto_set_yoast_meta_if_empty( $post_id,
		'Замена ремня ГРМ Mitsubishi в Москве — комплексная замена с помпой | MI-AUTO',
		'Профессиональная замена ремня ГРМ Mitsubishi: меняем ремень, ролики, помпу — полный комплект. Гарантия 1 год или 30 000 км. Запись онлайн.',
		'замена ремня грм mitsubishi'
	);

	return true;
}

function miauto_grm_get_post_id() {
	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'name'        => 'zamena-remnya-grm',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'title'       => 'Замена ремня ГРМ',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	return new WP_Error( 'post_not_found', 'Запись «Замена ремня ГРМ» не найдена. Сначала запустите demo-import.' );
}

function miauto_grm_upload_images() {
	$files = array(
		'hero'  => 'hero-main.jpg',
		'sym_1' => 'sym-probeg.jpg',
		'sym_2' => 'sym-shum.jpg',
		'sym_3' => 'sym-troenie.jpg',
		'sym_4' => 'sym-maslo.jpg',
		'sym_5' => 'sym-zavodka.jpg',
		'sym_6' => 'sym-istoriya.jpg',
	);

	$ids = array();

	foreach ( $files as $key => $filename ) {
		$ids[ $key ] = miauto_grm_upload_single( $filename );
	}

	return $ids;
}

function miauto_grm_upload_single( $filename ) {
	$source_key = 'zamena-remnya-grm/' . $filename;

	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'meta_key'    => '_miauto_source',
		'meta_value'  => $source_key,
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $existing ) ) {
		return $existing[0];
	}

	$file_path = get_template_directory() . '/img/zamena-remnya-grm/' . $filename;

	if ( ! file_exists( $file_path ) ) {
		return 0;
	}

	$upload_dir = wp_upload_dir();
	$target     = $upload_dir['path'] . '/' . $filename;

	if ( ! copy( $file_path, $target ) ) {
		return 0;
	}

	$filetype  = wp_check_filetype( $filename );
	$attach_id = wp_insert_attachment( array(
		'guid'           => $upload_dir['url'] . '/' . $filename,
		'post_mime_type' => $filetype['type'],
		'post_title'     => pathinfo( $filename, PATHINFO_FILENAME ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	), $target );

	if ( is_wp_error( $attach_id ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $target ) );
	update_post_meta( $attach_id, '_miauto_source', $source_key );

	return $attach_id;
}

function miauto_grm_get_fallback_id() {
	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'meta_key'    => '_miauto_demo_source',
		'meta_value'  => 'svc-timing-belt.png',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	return ! empty( $existing ) ? $existing[0] : 0;
}

function miauto_grm_fill_fields( $post_id, $imgs ) {
	$fallback = miauto_grm_get_fallback_id();

	$hero  = $imgs['hero']  ?: $fallback;
	$sym_1 = $imgs['sym_1'] ?: $fallback;
	$sym_2 = $imgs['sym_2'] ?: $fallback;
	$sym_3 = $imgs['sym_3'] ?: $fallback;
	$sym_4 = $imgs['sym_4'] ?: $fallback;
	$sym_5 = $imgs['sym_5'] ?: $fallback;
	$sym_6 = $imgs['sym_6'] ?: $fallback;

	// ── SC-HERO ──────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_subtitle',
		'Замена ремня ГРМ Mitsubishi в комплекте с роликами и помпой — предотвращаем обрыв и загиб клапанов'
	);

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_features', array(
		array( 'feature_text' => 'Замена ремня, роликов и помпы одним комплектом' ),
		array( 'feature_text' => 'Оригинальные комплекты Mitsubishi или Gates / Dayco' ),
		array( 'feature_text' => 'Метки распределительного вала — строго по заводскому регламенту' ),
		array( 'feature_text' => 'Гарантия 1 год или 30 000 км' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_primary_text',   'Записаться на замену ремня ГРМ' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_secondary_text', 'Рассчитать стоимость' );

	if ( $hero ) {
		miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_image', $hero );
	}

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_stats', array(
		array( 'stat_value' => '5,0',    'stat_label' => 'Рейтинг на картах' ),
		array( 'stat_value' => '500+',   'stat_label' => 'Отзывов на картах' ),
		array( 'stat_value' => 'с 2005', 'stat_label' => 'Опыт работы' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_examples_title', 'Примеры работ по замене ремня ГРМ' );

	// ── SYMPTOMS ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_title',    'Когда менять ремень ГРМ' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_subtitle', 'Обрыв ремня — дорогостоящий ремонт двигателя. Меняйте вовремя' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cards', array(
		array(
			'sym_image'     => $sym_1,
			'symptom_title' => 'Истёк ресурс по пробегу',
			'symptom_desc'  => 'По регламенту Mitsubishi ремень ГРМ меняется каждые 90 000–100 000 км или 5 лет. Не ждите разрыва — последствия катастрофические.',
		),
		array(
			'sym_image'     => $sym_2,
			'symptom_title' => 'Шум из-под крышки ГРМ',
			'symptom_desc'  => 'Посторонний звук при работе двигателя в районе ГРМ — признак износа натяжного или обводного ролика, которые меняются в одном комплекте с ремнём.',
		),
		array(
			'sym_image'     => $sym_3,
			'symptom_title' => 'Двигатель троит или теряет мощность',
			'symptom_desc'  => 'Вытянутый ремень нарушает фазы газораспределения — двигатель теряет мощность, растёт расход топлива.',
		),
		array(
			'sym_image'     => $sym_4,
			'symptom_title' => 'Течь масла из-под крышки',
			'symptom_desc'  => 'Утечка масла через сальники распредвала или коленвала — при замене ремня ГРМ сальники меняются превентивно.',
		),
		array(
			'sym_image'     => $sym_5,
			'symptom_title' => 'Двигатель не запускается',
			'symptom_desc'  => 'Ремень ГРМ порвался — двигатель не заводится. Немедленная эвакуация и дефектовка двигателя для оценки ущерба.',
		),
		array(
			'sym_image'     => $sym_6,
			'symptom_title' => 'Покупка б/у авто',
			'symptom_desc'  => 'Неизвестна история обслуживания — меняем ремень ГРМ комплектом при покупке Mitsubishi с пробегом свыше 80 000 км.',
		),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_text',     'Запишитесь — проверим состояние ремня ГРМ и выполним замену' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_btn_text', 'Записаться на замену ремня ГРМ' );

	// ── SVC-LIST ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_title', 'Какие работы выполняем' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_items', array(
		array(
			'svc_title' => 'Замена ремня ГРМ комплектом',
			'svc_desc'  => 'Снимаем защитный кожух, заменяем ремень, натяжной и обводной ролики — строго по меткам распределительного вала Mitsubishi.',
		),
		array(
			'svc_title' => 'Замена помпы системы охлаждения',
			'svc_desc'  => 'При каждой замене ремня ГРМ рекомендуем превентивную замену помпы — она приводится тем же ремнём и её замена требует разборки того же узла.',
		),
		array(
			'svc_title' => 'Замена сальников распредвала и коленвала',
			'svc_desc'  => 'При разборке ГРМ проверяем состояние сальников. Замена обходится в разы дешевле при уже снятом ремне, чем отдельной операцией.',
		),
		array(
			'svc_title' => 'Проверка натяжения и фаз ГРМ',
			'svc_desc'  => 'После установки проверяем натяжение ремня динамометром и правильность установки меток — гарантируем точные фазы газораспределения.',
		),
		array(
			'svc_title' => 'Замена цепи ГРМ',
			'svc_desc'  => 'На моделях с цепным приводом ГРМ меняем цепь, натяжитель и успокоитель при признаках растяжения или шума при холодном запуске.',
		),
		array(
			'svc_title' => 'Устранение последствий обрыва ремня',
			'svc_desc'  => 'После обрыва ремня ГРМ — дефектовка двигателя, определение объёма повреждений, правка или замена погнутых клапанов.',
		),
		array(
			'svc_title' => 'Диагностика системы охлаждения',
			'svc_desc'  => 'При замене помпы проверяем давление в системе охлаждения, состояние термостата и шлангов — исключаем скрытые утечки антифриза.',
		),
		array(
			'svc_title' => 'Замена антифриза',
			'svc_desc'  => 'При снятии системы охлаждения для замены помпы — заменяем антифриз на новый по регламенту Mitsubishi.',
		),
	) );

	// ── SC-PRICES ────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_title',    'Стоимость замены ремня ГРМ' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_subtitle', 'Цены на работы — без стоимости запчастей. Расчёт по VIN — бесплатно' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_rows', array(
		array( 'service_name' => 'Замена ремня ГРМ с роликами (работа)',     'sc_service_price' => 'от 4 000 ₽' ),
		array( 'service_name' => 'Замена помпы (работа, совместно с ремнём)', 'sc_service_price' => 'от 1 500 ₽' ),
		array( 'service_name' => 'Замена сальника распредвала (1 шт)',        'sc_service_price' => 'от 800 ₽' ),
		array( 'service_name' => 'Замена цепи ГРМ (работа)',                  'sc_service_price' => 'от 8 000 ₽' ),
		array( 'service_name' => 'Замена антифриза',                          'sc_service_price' => 'от 1 000 ₽' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_heading',  'Точная стоимость — после расчёта по VIN вашего автомобиля' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_desc',     'Все работы согласуем до начала ремонта' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_btn_text', 'Записаться на замену ремня ГРМ' );

	// ── WARRANTY ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_title',    'Гарантия и ответственность' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_subtitle', 'Работаем только с оригинальными комплектами или Gates / Dayco — проверенные поставщики' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_cards', array(
		array(
			'war_svg'       => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 0L0 3.636V9.091C0 14.136 3.84 18.855 9 20c5.16-1.145 9-5.864 9-10.909V3.636L9 0zM7 14.546L3.773 11.611a.82.82 0 010-1.404.826.826 0 011.275-.002L7 11.973l5.948-5.407a.826.826 0 011.282.003.82.82 0 01-.003 1.406L7 14.546z" fill="white"/></svg>',
			'warranty_text' => 'Гарантия 1 год или 30 000 км',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M18 0H2C1 0 0 .9 0 2v3.01c0 .72.43 1.34 1 1.69V18c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V6.7c.57-.35 1-.97 1-1.69V2c0-1.1-1-.9-2 0zM13 12H7v-2h6v2zM18 5H2V2l16-.02V5z" fill="white"/></svg>',
			'warranty_text' => 'Запчасти — по условиям поставщика',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.613.256a.29.29 0 00-.29-.256H3.194C2.347 0 1.534.297.935.826.336 1.355 0 2.072 0 2.82v14.36c0 .748.336 1.465.935 1.994.6.529 1.412.826 2.259.826h11.612c.847 0 1.66-.297 2.258-.826.6-.529.936-1.246.936-1.994V7.074a.29.29 0 00-.29-.257h-5.226a.87.87 0 01-.612-.226.77.77 0 01-.259-.543V.256zM12.484 10.256c.231 0 .452.081.616.226a.77.77 0 01.255.543.77.77 0 01-.255.544.87.87 0 01-.616.226H5.516a.87.87 0 01-.616-.226.77.77 0 01-.255-.544c0-.208.092-.404.255-.543a.87.87 0 01.616-.226h6.968zm0 4.103c.231 0 .452.081.616.225a.77.77 0 01.255.544.77.77 0 01-.255.544.87.87 0 01-.616.225H5.516a.87.87 0 01-.616-.225.77.77 0 01-.255-.544c0-.208.092-.404.255-.544a.87.87 0 01.616-.225h6.968z" fill="white"/><path d="M13.356.59c0-.19.224-.31.39-.192.14.1.266.217.375.35l3.499 4.305c.079.098-.007.226-.144.226h-3.83a.29.29 0 01-.29-.257V.59z" fill="white"/></svg>',
			'warranty_text' => 'Документы: заказ-наряд, акт выполненных работ',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 20 18" xmlns="http://www.w3.org/2000/svg"><path d="M10 13.2c1.77 0 3.2-1.43 3.2-3.2 0-1.77-1.43-3.2-3.2-3.2-1.77 0-3.2 1.43-3.2 3.2 0 1.77 1.43 3.2 3.2 3.2zM7 0L5.17 2H2C.9 2 0 2.9 0 4v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2h-3.17L13 0H7zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z" fill="white"/></svg>',
			'warranty_text' => 'Фото замены и новых запчастей (по запросу)',
		),
	) );

	// ── FAQ ──────────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_heading', 'FAQ' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_entries', array(
		array(
			'faq_entry_active'   => true,
			'faq_entry_question' => 'Как часто менять ремень ГРМ на Mitsubishi?',
			'faq_entry_answer'   => 'По регламенту большинства моделей Mitsubishi — каждые 90 000–100 000 км или раз в 5 лет (в зависимости от того, что наступит раньше). Точный интервал уточняем по VIN.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Нужно ли менять помпу вместе с ремнём?',
			'faq_entry_answer'   => 'Рекомендуем — на большинстве Mitsubishi помпа приводится тем же ремнём ГРМ. Замена помпы при уже разобранном узле стоит минимум, а выход помпы из строя потребует повторной разборки.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Что произойдёт при обрыве ремня ГРМ?',
			'faq_entry_answer'   => 'На большинстве двигателей Mitsubishi — загиб клапанов. Ремонт двигателя после обрыва стоит в 10–20 раз дороже своевременной замены ремня.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Какие запчасти используете?',
			'faq_entry_answer'   => 'Оригинальные комплекты Mitsubishi или Gates / Dayco — ведущие производители ремней ГРМ. Выбор согласовываем с клиентом с учётом бюджета.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Сколько времени занимает замена?',
			'faq_entry_answer'   => 'В зависимости от модели двигателя — от 3 до 6 часов. Рекомендуем записаться с утра, чтобы забрать автомобиль в этот же день.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Ремень ГРМ или цепь — чем мой Mitsubishi оснащён?',
			'faq_entry_answer'   => 'Зависит от модели и года. Colt, Lancer до 2007 — ремень. Outlander XL, ASX, Lancer X (2.0) — цепь. Точно определим по VIN — укажите при записи.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Нужно ли менять сальники при замене ремня?',
			'faq_entry_answer'   => 'Если сальники распредвала или коленвала текут или имеют признаки засыхания — рекомендуем менять сразу. Стоимость при разобранном ГРМ в разы ниже, чем отдельной операцией.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Даёте ли гарантию на замену ремня ГРМ?',
			'faq_entry_answer'   => 'Да — 1 год или 30 000 км на работы. На запчасти — по условиям поставщика. Выдаём гарантийный талон с датой и пробегом.',
		),
	) );
}

function miauto_grm_seo_text() {
	return '<p>Технический центр МИ АВТО выполняет замену ремня ГРМ и комплекта ГРМ на всех моделях Mitsubishi в Москве — строго по заводскому регламенту.</p>'
		. '<p>Работаем только с оригинальными комплектами Mitsubishi или продукцией проверенных производителей: Gates, Dayco, Contitech. Выбор запчастей согласовываем заранее.</p>'
		. '<p>При каждой замене ремня рекомендуем попутную замену помпы и сальников — это позволяет сэкономить на повторной разборке и исключить течи в ближайшие годы.</p>'
		. '<p>Замена ремня ГРМ Mitsubishi в МИ АВТО — строго по меткам, с гарантией 1 год или 30 000 км и закрывающими документами.</p>';
}
