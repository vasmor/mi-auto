<?php
/**
 * Content installer: Компьютерная диагностика авто (miauto_service).
 *
 * Triggered by visiting: /wp-admin/?miauto_fill_diagnostika=1
 * Safe to run multiple times — fills only empty fields.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function miauto_diagnostika_init() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_GET['miauto_fill_diagnostika'] ) || '1' !== $_GET['miauto_fill_diagnostika'] ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_fill_diagnostika' ) ) {
		add_action( 'admin_notices', function () {
			$url = wp_nonce_url( admin_url( '?miauto_fill_diagnostika=1' ), 'miauto_fill_diagnostika' );
			echo '<div class="notice notice-info"><p>MI-AUTO: Для заполнения Компьютерной диагностики перейдите по ссылке: <a href="' . esc_url( $url ) . '">Заполнить контент Компьютерной диагностики</a></p></div>';
		} );
		return;
	}

	$result = miauto_run_fill_diagnostika();

	if ( is_wp_error( $result ) ) {
		$msg = $result->get_error_message();
		add_action( 'admin_notices', function () use ( $msg ) {
			echo '<div class="notice notice-error"><p>MI-AUTO Компьютерная диагностика Error: ' . esc_html( $msg ) . '</p></div>';
		} );
		return;
	}

	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-success"><p>MI-AUTO: Контент страницы «Компьютерная диагностика» успешно заполнен! Заполнены только пустые поля.</p></div>';
	} );
}
add_action( 'admin_init', 'miauto_diagnostika_init' );

function miauto_run_fill_diagnostika() {
	set_time_limit( 300 );
	wp_raise_memory_limit( 'admin' );

	$post_id = miauto_diagnostika_get_post_id();
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$imgs = miauto_diagnostika_upload_images();

	miauto_diagnostika_fill_fields( $post_id, $imgs );

	if ( '' === get_post_field( 'post_content', $post_id ) ) {
		wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => miauto_diagnostika_seo_text(),
		) );
	}

	miauto_set_yoast_meta_if_empty( $post_id,
		'Компьютерная диагностика Mitsubishi в Москве — дилерский сканер | MI-AUTO',
		'Профессиональная компьютерная диагностика Mitsubishi: считываем ошибки всех блоков дилерским сканером, выдаём письменный отчёт. От 1 500 ₽.',
		'компьютерная диагностика mitsubishi'
	);

	return true;
}

function miauto_diagnostika_get_post_id() {
	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'name'        => 'komp-diagnostika',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'title'       => 'Компьютерная диагностика авто',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	// Try shorter title.
	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'title'       => 'Компьютерная диагностика',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	return new WP_Error( 'post_not_found', 'Запись «Компьютерная диагностика» не найдена. Сначала запустите demo-import.' );
}

function miauto_diagnostika_upload_images() {
	$files = array(
		'hero'  => 'hero-main.jpg',
		'sym_1' => 'sym-check.jpg',
		'sym_2' => 'sym-troenie.jpg',
		'sym_3' => 'sym-panel.jpg',
		'sym_4' => 'sym-rashod.jpg',
		'sym_5' => 'sym-akpp.jpg',
		'sym_6' => 'sym-pokupka.jpg',
	);

	$ids = array();

	foreach ( $files as $key => $filename ) {
		$ids[ $key ] = miauto_diagnostika_upload_single( $filename );
	}

	return $ids;
}

function miauto_diagnostika_upload_single( $filename ) {
	$source_key = 'komp-diagnostika/' . $filename;

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

	$file_path = get_template_directory() . '/img/komp-diagnostika/' . $filename;

	if ( ! file_exists( $file_path ) ) {
		return 0;
	}

	$upload_dir      = wp_upload_dir();
	$upload_filename = 'diagnostika-' . $filename;
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

function miauto_diagnostika_get_fallback_id() {
	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'meta_key'    => '_miauto_demo_source',
		'meta_value'  => 'svc-diagnostics.png',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	return ! empty( $existing ) ? $existing[0] : 0;
}

function miauto_diagnostika_fill_fields( $post_id, $imgs ) {
	$fallback = miauto_diagnostika_get_fallback_id();

	$hero  = $imgs['hero']  ?: $fallback;
	$sym_1 = $imgs['sym_1'] ?: $fallback;
	$sym_2 = $imgs['sym_2'] ?: $fallback;
	$sym_3 = $imgs['sym_3'] ?: $fallback;
	$sym_4 = $imgs['sym_4'] ?: $fallback;
	$sym_5 = $imgs['sym_5'] ?: $fallback;
	$sym_6 = $imgs['sym_6'] ?: $fallback;

	// ── SC-HERO ──────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_subtitle',
		'Компьютерная диагностика Mitsubishi дилерским сканером — точно определяем причину неисправности, выдаём письменный отчёт'
	);

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_features', array(
		array( 'feature_text' => 'Дилерский сканер — доступ ко всем блокам управления' ),
		array( 'feature_text' => 'Живые данные датчиков в реальном времени' ),
		array( 'feature_text' => 'Письменный отчёт с кодами и расшифровкой' ),
		array( 'feature_text' => 'Диагностика — от 30 минут' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_primary_text',   'Записаться на диагностику' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_secondary_text', 'Рассчитать стоимость' );

	if ( $hero ) {
		miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_image', $hero );
	}

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_stats', array(
		array( 'stat_value' => '5,0',    'stat_label' => 'Рейтинг на картах' ),
		array( 'stat_value' => '500+',   'stat_label' => 'Отзывов на картах' ),
		array( 'stat_value' => 'с 2005', 'stat_label' => 'Опыт работы' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_examples_title', 'Примеры работ по компьютерной диагностике' );

	// ── SYMPTOMS ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_title',    'Когда нужна диагностика' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_subtitle', 'Диагностика — первый шаг к любому ремонту. Экономит время и деньги' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cards', array(
		array(
			'sym_image'     => $sym_1,
			'symptom_title' => 'Горит Check Engine',
			'symptom_desc'  => 'Индикатор не гаснет — блок управления зафиксировал ошибку. Диагностика выявит точный код и причину без лишних замен.',
		),
		array(
			'sym_image'     => $sym_2,
			'symptom_title' => 'Нестабильная работа двигателя',
			'symptom_desc'  => 'Троение, провалы при разгоне, нестабильные обороты холостого хода — диагностика покажет, какой датчик или компонент вышел из строя.',
		),
		array(
			'sym_image'     => $sym_3,
			'symptom_title' => 'Ошибки на панели приборов',
			'symptom_desc'  => 'Загорелись индикаторы ABS, SRS, TPMS, ESP или других систем — каждый требует считывания кода и расшифровки.',
		),
		array(
			'sym_image'     => $sym_4,
			'symptom_title' => 'Повышенный расход топлива',
			'symptom_desc'  => 'Расход вырос на 15–20% без видимых причин — диагностика «живых данных» выявит неэффективный датчик или утечку в топливной системе.',
		),
		array(
			'sym_image'     => $sym_5,
			'symptom_title' => 'Проблемы с АКПП',
			'symptom_desc'  => 'Рывки, запаздывание переключений, ошибки коробки — дилерский сканер читает ошибки блока АКПП и позволяет принять верное решение.',
		),
		array(
			'sym_image'     => $sym_6,
			'symptom_title' => 'Перед покупкой б/у авто',
			'symptom_desc'  => 'Проверка скрытых ошибок всех блоков — защита от скрытых неисправностей и раскрытие реального технического состояния автомобиля.',
		),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_text',     'Запишитесь — считаем ошибки и дадим точное заключение' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_btn_text', 'Записаться на диагностику' );

	// ── SVC-LIST ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_title', 'Какие работы выполняем' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_items', array(
		array(
			'svc_title' => 'Считывание кодов ошибок всех блоков',
			'svc_desc'  => 'Подключаем дилерский сканер Mitsubishi — считываем активные и архивные ошибки двигателя, трансмиссии, ABS, SRS, климата и других систем.',
		),
		array(
			'svc_title' => 'Анализ живых данных',
			'svc_desc'  => 'Мониторим параметры датчиков в реальном времени — температура, давление, обороты, нагрузка — выявляем отклонения от нормы.',
		),
		array(
			'svc_title' => 'Диагностика двигателя',
			'svc_desc'  => 'Проверяем систему питания, зажигания, охлаждения, управления двигателем — определяем причину нестабильной работы или потери мощности.',
		),
		array(
			'svc_title' => 'Диагностика АКПП',
			'svc_desc'  => 'Считываем ошибки блока управления коробкой, проверяем адаптивные настройки и гидравлическое давление — рекомендуем ремонт или замену.',
		),
		array(
			'svc_title' => 'Диагностика ABS и ESP',
			'svc_desc'  => 'Проверяем датчики скорости колёс, гидроблок и блок управления. Тест-приводы компонентов выявляют механически неисправный элемент.',
		),
		array(
			'svc_title' => 'Проверка SRS / подушек безопасности',
			'svc_desc'  => 'Считываем коды блока SRS, проверяем датчики удара, шлейфы и пиропатроны. Сброс аварийного кода после устранения неисправности.',
		),
		array(
			'svc_title' => 'Осциллографическая диагностика',
			'svc_desc'  => 'Для сложных случаев используем осциллограф — анализируем форму сигналов датчиков кислорода, положения распредвала, форсунок.',
		),
		array(
			'svc_title' => 'Проверка перед покупкой',
			'svc_desc'  => 'Комплексная диагностика всех систем б/у автомобиля Mitsubishi — выявляем скрытые неисправности, узнаём реальный пробег и состояние.',
		),
	) );

	// ── SC-PRICES ────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_title',    'Стоимость компьютерной диагностики' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_subtitle', 'Стоимость диагностики засчитывается при заказе ремонта' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_rows', array(
		array( 'service_name' => 'Диагностика двигателя (дилерский сканер)', 'sc_service_price' => 'от 1 500 ₽' ),
		array( 'service_name' => 'Диагностика всех блоков управления',       'sc_service_price' => 'от 2 500 ₽' ),
		array( 'service_name' => 'Диагностика АКПП',                         'sc_service_price' => 'от 2 000 ₽' ),
		array( 'service_name' => 'Осциллографическая диагностика',           'sc_service_price' => 'от 2 000 ₽' ),
		array( 'service_name' => 'Проверка перед покупкой (комплекс)',        'sc_service_price' => 'от 3 500 ₽' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_heading',  'Стоимость диагностики входит в стоимость ремонта' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_desc',     'Определяем причину — согласуем ремонт — выполняем' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_btn_text', 'Записаться на диагностику' );

	// ── WARRANTY ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_title',    'Гарантия и ответственность' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_subtitle', 'Не сбрасываем ошибки без устранения причины — только реальный ремонт' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_cards', array(
		array(
			'war_svg'       => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 0L0 3.636V9.091C0 14.136 3.84 18.855 9 20c5.16-1.145 9-5.864 9-10.909V3.636L9 0zM7 14.546L3.773 11.611a.82.82 0 010-1.404.826.826 0 011.275-.002L7 11.973l5.948-5.407a.826.826 0 011.282.003.82.82 0 01-.003 1.406L7 14.546z" fill="white"/></svg>',
			'warranty_text' => 'Гарантия на работы — 1 год',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M18 0H2C1 0 0 .9 0 2v3.01c0 .72.43 1.34 1 1.69V18c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V6.7c.57-.35 1-.97 1-1.69V2c0-1.1-1-.9-2 0zM13 12H7v-2h6v2zM18 5H2V2l16-.02V5z" fill="white"/></svg>',
			'warranty_text' => 'Письменный отчёт с кодами и расшифровкой',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.613.256a.29.29 0 00-.29-.256H3.194C2.347 0 1.534.297.935.826.336 1.355 0 2.072 0 2.82v14.36c0 .748.336 1.465.935 1.994.6.529 1.412.826 2.259.826h11.612c.847 0 1.66-.297 2.258-.826.6-.529.936-1.246.936-1.994V7.074a.29.29 0 00-.29-.257h-5.226a.87.87 0 01-.612-.226.77.77 0 01-.259-.543V.256zM12.484 10.256c.231 0 .452.081.616.226a.77.77 0 01.255.543.77.77 0 01-.255.544.87.87 0 01-.616.226H5.516a.87.87 0 01-.616-.226.77.77 0 01-.255-.544c0-.208.092-.404.255-.543a.87.87 0 01.616-.226h6.968zm0 4.103c.231 0 .452.081.616.225a.77.77 0 01.255.544.77.77 0 01-.255.544.87.87 0 01-.616.225H5.516a.87.87 0 01-.616-.225.77.77 0 01-.255-.544c0-.208.092-.404.255-.544a.87.87 0 01.616-.225h6.968z" fill="white"/><path d="M13.356.59c0-.19.224-.31.39-.192.14.1.266.217.375.35l3.499 4.305c.079.098-.007.226-.144.226h-3.83a.29.29 0 01-.29-.257V.59z" fill="white"/></svg>',
			'warranty_text' => 'Документы: заказ-наряд, акт выполненных работ',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 20 18" xmlns="http://www.w3.org/2000/svg"><path d="M10 13.2c1.77 0 3.2-1.43 3.2-3.2 0-1.77-1.43-3.2-3.2-3.2-1.77 0-3.2 1.43-3.2 3.2 0 1.77 1.43 3.2 3.2 3.2zM7 0L5.17 2H2C.9 2 0 2.9 0 4v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2h-3.17L13 0H7zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z" fill="white"/></svg>',
			'warranty_text' => 'Скриншоты экрана диагностики (по запросу)',
		),
	) );

	// ── FAQ ──────────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_heading', 'FAQ' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_entries', array(
		array(
			'faq_entry_active'   => true,
			'faq_entry_question' => 'Чем отличается дилерский сканер от обычного?',
			'faq_entry_answer'   => 'Обычные OBD-II сканеры читают только стандартные коды двигателя. Дилерский сканер Mitsubishi подключается ко всем блокам — коробка, ABS, SRS, климат, электронный ключ — и показывает «живые» данные датчиков.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Можно ли просто сбросить ошибку?',
			'faq_entry_answer'   => 'Сброс без устранения причины — временная мера. Ошибка вернётся, а в некоторых случаях система перейдёт в аварийный режим. Мы находим и устраняем причину, а затем сбрасываем код.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Сколько времени занимает диагностика?',
			'faq_entry_answer'   => 'Базовое считывание ошибок всех блоков — 30–45 минут. Углублённая диагностика с анализом живых данных и осциллографом — 1,5–2 часа. Время зависит от сложности симптома.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Засчитывается ли стоимость диагностики при ремонте?',
			'faq_entry_answer'   => 'Да. Если после диагностики вы делаете ремонт в нашем сервисе, стоимость диагностики полностью вычитается из стоимости ремонтных работ.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Что вы проверяете при диагностике перед покупкой?',
			'faq_entry_answer'   => 'Считываем ошибки всех блоков управления, проверяем реальный пробег по данным ЭБУ, смотрим историю обновлений прошивки, осматриваем кузов на подъёмнике.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Может ли горить Check Engine без реальной поломки?',
			'faq_entry_answer'   => 'Да — незакрученная крышка бензобака, некачественное топливо или временный сбой датчика могут дать код. Но игнорировать индикатор нельзя — только диагностика покажет настоящую причину.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Диагностируете ли АКПП?',
			'faq_entry_answer'   => 'Да. Считываем коды блока АКПП, проверяем адаптивные настройки, гидравлическое давление по каналам — определяем причину рывков, пробуксовки или ошибки трансмиссии.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Нужна ли предварительная запись?',
			'faq_entry_answer'   => 'Рекомендуем записаться — это гарантирует наличие нужного специалиста и оборудования. При срочных случаях постараемся принять в день обращения.',
		),
	) );
}

function miauto_diagnostika_seo_text() {
	return '<p>Технический центр МИ АВТО выполняет компьютерную диагностику всего модельного ряда Mitsubishi в Москве с применением дилерского диагностического оборудования.</p>'
		. '<p>В отличие от универсальных OBD-II сканеров, дилерский сканер обеспечивает доступ ко всем электронным блокам автомобиля и позволяет анализировать «живые» данные датчиков в режиме реального времени.</p>'
		. '<p>По результатам диагностики клиент получает письменный отчёт с перечнем ошибок и рекомендациями. Стоимость диагностики засчитывается при заказе ремонта.</p>'
		. '<p>Компьютерная диагностика Mitsubishi в МИ АВТО — точно, быстро, с отчётом и без лишних замен запчастей.</p>';
}
