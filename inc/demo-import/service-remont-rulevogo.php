<?php
/**
 * Content installer: Ремонт рулевого управления (miauto_service).
 *
 * Triggered by visiting: /wp-admin/?miauto_fill_rulevoe=1
 * Safe to run multiple times — fills only empty fields.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function miauto_rulevoe_init() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_GET['miauto_fill_rulevoe'] ) || '1' !== $_GET['miauto_fill_rulevoe'] ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_fill_rulevoe' ) ) {
		add_action( 'admin_notices', function () {
			$url = wp_nonce_url( admin_url( '?miauto_fill_rulevoe=1' ), 'miauto_fill_rulevoe' );
			echo '<div class="notice notice-info"><p>MI-AUTO: Для заполнения Рулевого управления перейдите по ссылке: <a href="' . esc_url( $url ) . '">Заполнить контент Рулевого управления</a></p></div>';
		} );
		return;
	}

	$result = miauto_run_fill_rulevoe();

	if ( is_wp_error( $result ) ) {
		$msg = $result->get_error_message();
		add_action( 'admin_notices', function () use ( $msg ) {
			echo '<div class="notice notice-error"><p>MI-AUTO Рулевое управление Error: ' . esc_html( $msg ) . '</p></div>';
		} );
		return;
	}

	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-success"><p>MI-AUTO: Контент страницы «Ремонт рулевого управления» успешно заполнен! Заполнены только пустые поля.</p></div>';
	} );
}
add_action( 'admin_init', 'miauto_rulevoe_init' );

function miauto_run_fill_rulevoe() {
	set_time_limit( 300 );
	wp_raise_memory_limit( 'admin' );

	$post_id = miauto_rulevoe_get_post_id();
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$imgs = miauto_rulevoe_upload_images();

	miauto_rulevoe_fill_fields( $post_id, $imgs );

	if ( '' === get_post_field( 'post_content', $post_id ) ) {
		wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => miauto_rulevoe_seo_text(),
		) );
	}

	miauto_set_yoast_meta_if_empty( $post_id,
		'Ремонт рулевого управления Mitsubishi в Москве — ремонт рейки и ГУР | MI-AUTO',
		'Профессиональный ремонт рулевого управления Mitsubishi: рейка, наконечники, ГУР, электроусилитель. Гарантия 1 год. Точная диагностика.',
		'ремонт рулевого управления mitsubishi'
	);

	return true;
}

function miauto_rulevoe_get_post_id() {
	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'name'        => 'remont-rulevogo',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'title'       => 'Ремонт рулевого управления',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	return new WP_Error( 'post_not_found', 'Запись «Ремонт рулевого управления» не найдена. Сначала запустите demo-import.' );
}

function miauto_rulevoe_upload_images() {
	$files = array(
		'hero'  => 'hero-main.jpg',
		'sym_1' => 'sym-tyazhelyy.jpg',
		'sym_2' => 'sym-lyuft.jpg',
		'sym_3' => 'sym-stuk.jpg',
		'sym_4' => 'sym-vibraciya.jpg',
		'sym_5' => 'sym-techj.jpg',
		'sym_6' => 'sym-otklon.jpg',
	);

	$ids = array();

	foreach ( $files as $key => $filename ) {
		$ids[ $key ] = miauto_rulevoe_upload_single( $filename );
	}

	return $ids;
}

function miauto_rulevoe_upload_single( $filename ) {
	$source_key = 'remont-rulevogo/' . $filename;

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

	$file_path = get_template_directory() . '/img/remont-rulevogo/' . $filename;

	if ( ! file_exists( $file_path ) ) {
		return 0;
	}

	$upload_dir      = wp_upload_dir();
	$upload_filename = 'rulevogo-' . $filename;
	$target          = $upload_dir['path'] . '/' . $upload_filename;

	if ( ! copy( $file_path, $target ) ) {
		return 0;
	}

	$filetype  = wp_check_filetype( $upload_filename );
	$attach_id = wp_insert_attachment( array(
		'guid'           => $upload_dir['url'] . '/' . $upload_filename,
		'post_mime_type' => $filetype['type'],
		'post_title'     => pathinfo( $upload_filename, PATHINFO_FILENAME ),
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

function miauto_rulevoe_get_fallback_id() {
	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'meta_key'    => '_miauto_demo_source',
		'meta_value'  => 'svc-steering.png',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	return ! empty( $existing ) ? $existing[0] : 0;
}

function miauto_rulevoe_fill_fields( $post_id, $imgs ) {
	$fallback = miauto_rulevoe_get_fallback_id();

	$hero  = $imgs['hero']  ?: $fallback;
	$sym_1 = $imgs['sym_1'] ?: $fallback;
	$sym_2 = $imgs['sym_2'] ?: $fallback;
	$sym_3 = $imgs['sym_3'] ?: $fallback;
	$sym_4 = $imgs['sym_4'] ?: $fallback;
	$sym_5 = $imgs['sym_5'] ?: $fallback;
	$sym_6 = $imgs['sym_6'] ?: $fallback;

	// ── SC-HERO ──────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_subtitle',
		'Ремонт рулевой рейки, ГУР и наконечников Mitsubishi — устраняем люфт и тяжёлый руль с гарантией'
	);

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_features', array(
		array( 'feature_text' => 'Ремонт рулевой рейки без замены в сборе' ),
		array( 'feature_text' => 'Промывка и заправка ГУР в стоимости работ' ),
		array( 'feature_text' => 'После ремонта — обязательный сход-развал' ),
		array( 'feature_text' => 'Гарантия на работы и запчасти' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_primary_text',   'Записаться на диагностику рулевого' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_secondary_text', 'Рассчитать стоимость' );

	if ( $hero ) {
		miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_image', $hero );
	}

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_stats', array(
		array( 'stat_value' => '5,0',    'stat_label' => 'Рейтинг на картах' ),
		array( 'stat_value' => '500+',   'stat_label' => 'Отзывов на картах' ),
		array( 'stat_value' => 'с 2005', 'stat_label' => 'Опыт работы' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_examples_title', 'Примеры работ по рулевому управлению' );

	// ── SYMPTOMS ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_title',    'Когда нужен ремонт рулевого' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_subtitle', 'Неисправное рулевое управление опасно — не откладывайте диагностику' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cards', array(
		array(
			'sym_image'     => $sym_1,
			'symptom_title' => 'Тяжёлый руль',
			'symptom_desc'  => 'Руль крутится с большим усилием или усилие неравномерное — признак неисправности насоса ГУР, электроусилителя или рулевой рейки.',
		),
		array(
			'sym_image'     => $sym_2,
			'symptom_title' => 'Люфт рулевого колеса',
			'symptom_desc'  => 'Рулевое колесо имеет свободный ход — износ наконечников рулевых тяг, рулевой рейки или промежуточного вала.',
		),
		array(
			'sym_image'     => $sym_3,
			'symptom_title' => 'Стук в руле при движении',
			'symptom_desc'  => 'Стуки передаются на рулевое колесо при проезде неровностей — изношены наконечники тяг или появился люфт в рулевой рейке.',
		),
		array(
			'sym_image'     => $sym_4,
			'symptom_title' => 'Вибрация руля',
			'symptom_desc'  => 'Постоянная вибрация рулевого колеса — возможна неисправность промежуточного вала, крестовин или дисбаланс колёс.',
		),
		array(
			'sym_image'     => $sym_5,
			'symptom_title' => 'Течь жидкости ГУР',
			'symptom_desc'  => 'Масляные пятна под автомобилем или запах горящего масла — утечка из рулевой рейки, шлангов или насоса ГУР.',
		),
		array(
			'sym_image'     => $sym_6,
			'symptom_title' => 'Руль смещён от центра',
			'symptom_desc'  => 'Рулевое колесо не стоит прямо при движении по прямой — нарушена регулировка наконечников или геометрия подвески.',
		),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_text',     'Запишитесь — проверим рулевое управление и устраним неисправность' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_btn_text', 'Записаться на диагностику рулевого' );

	// ── SVC-LIST ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_title', 'Какие работы выполняем' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_items', array(
		array(
			'svc_title' => 'Диагностика рулевого управления',
			'svc_desc'  => 'Проверяем люфт, усилие на рулевом колесе, состояние наконечников, рейки, насоса ГУР и электроусилителя — определяем точную причину неисправности.',
		),
		array(
			'svc_title' => 'Ремонт рулевой рейки',
			'svc_desc'  => 'Разбираем рейку, заменяем изношенные вкладыши, уплотнения и пыльники — без дорогостоящей замены в сборе там, где это возможно.',
		),
		array(
			'svc_title' => 'Замена наконечников рулевых тяг',
			'svc_desc'  => 'Заменяем наконечники при люфте или некорректном схождении. После замены обязателен сход-развал.',
		),
		array(
			'svc_title' => 'Ремонт насоса ГУР',
			'svc_desc'  => 'Диагностируем давление насоса, при необходимости восстанавливаем или заменяем. Промываем систему и заправляем свежей жидкостью.',
		),
		array(
			'svc_title' => 'Диагностика электроусилителя (EPS)',
			'svc_desc'  => 'Считываем коды ошибок блока EPS, проверяем датчик угла поворота и момент на рулевом колесе — определяем неисправный элемент.',
		),
		array(
			'svc_title' => 'Замена крестовин карданного вала',
			'svc_desc'  => 'Меняем изношенные крестовины промежуточного вала — устраняем стуки и вибрацию при повороте рулевого колеса.',
		),
		array(
			'svc_title' => 'Замена рулевых тяг',
			'svc_desc'  => 'При критическом износе рулевых тяг заменяем их в сборе с наконечниками, после чего выполняем регулировку схождения.',
		),
		array(
			'svc_title' => 'Замена жидкости ГУР',
			'svc_desc'  => 'Полная замена масла ГУР со шприцеванием системы — восстанавливает работу насоса и продлевает ресурс рейки.',
		),
	) );

	// ── SC-PRICES ────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_title',    'Стоимость ремонта рулевого управления' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_subtitle', 'Точная стоимость — после диагностики и дефектовки' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_rows', array(
		array( 'service_name' => 'Диагностика рулевого управления',         'sc_service_price' => 'от 700 ₽' ),
		array( 'service_name' => 'Замена наконечника рулевой тяги (1 шт)', 'sc_service_price' => 'от 800 ₽' ),
		array( 'service_name' => 'Ремонт рулевой рейки',                   'sc_service_price' => 'от 5 000 ₽' ),
		array( 'service_name' => 'Замена насоса ГУР (работа)',              'sc_service_price' => 'от 3 000 ₽' ),
		array( 'service_name' => 'Замена жидкости ГУР с промывкой',        'sc_service_price' => 'от 1 200 ₽' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_heading',  'Итоговая стоимость — после осмотра и согласования' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_desc',     'Называем цену до начала работ' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_btn_text', 'Записаться на диагностику рулевого' );

	// ── WARRANTY ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_title',    'Гарантия и ответственность' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_subtitle', 'После ремонта рулевого обязательно выполняем сход-развал' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_cards', array(
		array(
			'war_svg'       => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 0L0 3.636V9.091C0 14.136 3.84 18.855 9 20c5.16-1.145 9-5.864 9-10.909V3.636L9 0zM7 14.546L3.773 11.611a.82.82 0 010-1.404.826.826 0 011.275-.002L7 11.973l5.948-5.407a.826.826 0 011.282.003.82.82 0 01-.003 1.406L7 14.546z" fill="white"/></svg>',
			'warranty_text' => 'Гарантия на работы — 1 год',
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
			'warranty_text' => 'Сход-развал после ремонта рулевого — в стоимости',
		),
	) );

	// ── FAQ ──────────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_heading', 'FAQ' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_entries', array(
		array(
			'faq_entry_active'   => true,
			'faq_entry_question' => 'Насколько опасен люфт рулевого колеса?',
			'faq_entry_answer'   => 'Люфт более 10–15° — основание для запрета эксплуатации. Свободный ход замедляет реакцию на управление, что критично при экстренном манёвре.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Можно ли отремонтировать рулевую рейку или нужно менять?',
			'faq_entry_answer'   => 'В большинстве случаев рейку можно восстановить — заменить вкладыши, уплотнения и пыльники. Полная замена нужна при трещинах корпуса или критическом износе зубчатого зацепления.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Как понять, что течёт рулевая рейка?',
			'faq_entry_answer'   => 'Масляные подтёки у пыльников рейки или в районе рулевых тяг. Также признак — мягкость руля при малом количестве жидкости ГУР в бачке.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Что такое EPS и почему горит его индикатор?',
			'faq_entry_answer'   => 'EPS — электроусилитель руля. Индикатор загорается при ошибке в системе: отказ датчика угла, мотора или блока управления. Требует диагностики считывателем ошибок.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Нужен ли сход-развал после замены наконечников?',
			'faq_entry_answer'   => 'Да, обязательно. Наконечники рулевых тяг определяют схождение колёс. После их замены схождение нарушается — нужна регулировка на стенде.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Как часто менять жидкость ГУР?',
			'faq_entry_answer'   => 'По регламенту Mitsubishi — каждые 60 000–80 000 км или при потемнении и помутнении жидкости. Своевременная замена продлевает ресурс насоса и рейки.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Стучит в руле только на холодную — это опасно?',
			'faq_entry_answer'   => 'Стуки на холодную, исчезающие после прогрева — признак увеличенных зазоров в рейке. Со временем они становятся постоянными. Рекомендуем не откладывать диагностику.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Даёте ли гарантию на ремонт рулевого?',
			'faq_entry_answer'   => 'Да, 1 год на все виды работ. Гарантийный талон и акт выполненных работ выдаём в обязательном порядке.',
		),
	) );
}

function miauto_rulevoe_seo_text() {
	return '<p>Технический центр МИ АВТО специализируется на диагностике и ремонте рулевого управления автомобилей Mitsubishi в Москве — от наконечников рулевых тяг до рулевой рейки и насоса ГУР.</p>'
		. '<p>Применяем специализированный инструмент для разборки рулевых реек и проверки давления насоса ГУР — это позволяет точно определить износ и устранить неисправность без лишних замен.</p>'
		. '<p>После любого ремонта рулевого управления в обязательном порядке выполняем регулировку схождения колёс — это гарантирует правильную работу рулевого и равномерный износ шин.</p>'
		. '<p>Ремонт рулевого управления Mitsubishi в МИ АВТО — профессионально, с гарантией 1 год и закрывающими документами.</p>';
}
