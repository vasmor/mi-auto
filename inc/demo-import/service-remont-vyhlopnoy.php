<?php
/**
 * Content installer: Ремонт выхлопной системы (miauto_service).
 *
 * Triggered by visiting: /wp-admin/?miauto_fill_vyhlopnaya=1
 * Safe to run multiple times — fills only empty fields.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function miauto_vyhlopnaya_init() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_GET['miauto_fill_vyhlopnaya'] ) || '1' !== $_GET['miauto_fill_vyhlopnaya'] ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_fill_vyhlopnaya' ) ) {
		add_action( 'admin_notices', function () {
			$url = wp_nonce_url( admin_url( '?miauto_fill_vyhlopnaya=1' ), 'miauto_fill_vyhlopnaya' );
			echo '<div class="notice notice-info"><p>MI-AUTO: Для заполнения Выхлопной системы перейдите по ссылке: <a href="' . esc_url( $url ) . '">Заполнить контент Выхлопной системы</a></p></div>';
		} );
		return;
	}

	$result = miauto_run_fill_vyhlopnaya();

	if ( is_wp_error( $result ) ) {
		$msg = $result->get_error_message();
		add_action( 'admin_notices', function () use ( $msg ) {
			echo '<div class="notice notice-error"><p>MI-AUTO Выхлопная система Error: ' . esc_html( $msg ) . '</p></div>';
		} );
		return;
	}

	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-success"><p>MI-AUTO: Контент страницы «Ремонт выхлопной системы» успешно заполнен! Заполнены только пустые поля.</p></div>';
	} );
}
add_action( 'admin_init', 'miauto_vyhlopnaya_init' );

function miauto_run_fill_vyhlopnaya() {
	set_time_limit( 300 );
	wp_raise_memory_limit( 'admin' );

	$post_id = miauto_vyhlopnaya_get_post_id();
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$imgs = miauto_vyhlopnaya_upload_images();

	miauto_vyhlopnaya_fill_fields( $post_id, $imgs );

	if ( '' === get_post_field( 'post_content', $post_id ) ) {
		wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => miauto_vyhlopnaya_seo_text(),
		) );
	}

	miauto_set_yoast_meta_if_empty( $post_id,
		'Ремонт выхлопной системы Mitsubishi в Москве — замена глушителя и катализатора | MI-AUTO',
		'Профессиональный ремонт выхлопной системы Mitsubishi: замена глушителя, катализатора, гофры, лямбда-зонда. Гарантия 1 год. Устраняем шум и запах.',
		'ремонт выхлопной системы mitsubishi'
	);

	return true;
}

function miauto_vyhlopnaya_get_post_id() {
	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'name'        => 'remont-vyhlopnoy',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'title'       => 'Ремонт выхлопной системы',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	return new WP_Error( 'post_not_found', 'Запись «Ремонт выхлопной системы» не найдена. Сначала запустите demo-import.' );
}

function miauto_vyhlopnaya_upload_images() {
	$files = array(
		'hero'  => 'hero-main.jpg',
		'sym_1' => 'sym-rev.jpg',
		'sym_2' => 'sym-zapah.jpg',
		'sym_3' => 'sym-dinamika.jpg',
		'sym_4' => 'sym-check.jpg',
		'sym_5' => 'sym-vibraciya.jpg',
		'sym_6' => 'sym-rzha.jpg',
	);

	$ids = array();

	foreach ( $files as $key => $filename ) {
		$ids[ $key ] = miauto_vyhlopnaya_upload_single( $filename );
	}

	return $ids;
}

function miauto_vyhlopnaya_upload_single( $filename ) {
	$source_key = 'remont-vyhlopnoy/' . $filename;

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

	$file_path = get_template_directory() . '/img/remont-vyhlopnoy/' . $filename;

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

function miauto_vyhlopnaya_get_fallback_id() {
	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'meta_key'    => '_miauto_demo_source',
		'meta_value'  => 'svc-exhaust.png',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	return ! empty( $existing ) ? $existing[0] : 0;
}

function miauto_vyhlopnaya_fill_fields( $post_id, $imgs ) {
	$fallback = miauto_vyhlopnaya_get_fallback_id();

	$hero  = $imgs['hero']  ?: $fallback;
	$sym_1 = $imgs['sym_1'] ?: $fallback;
	$sym_2 = $imgs['sym_2'] ?: $fallback;
	$sym_3 = $imgs['sym_3'] ?: $fallback;
	$sym_4 = $imgs['sym_4'] ?: $fallback;
	$sym_5 = $imgs['sym_5'] ?: $fallback;
	$sym_6 = $imgs['sym_6'] ?: $fallback;

	// ── SC-HERO ──────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_subtitle',
		'Ремонт и замена элементов выхлопной системы Mitsubishi — устраняем шум, запах и Check Engine с гарантией'
	);

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_features', array(
		array( 'feature_text' => 'Диагностика на подъёмнике бесплатно' ),
		array( 'feature_text' => 'Сварочные работы без замены узла в сборе' ),
		array( 'feature_text' => 'Оригинальные и OEM запчасти в наличии' ),
		array( 'feature_text' => 'Гарантия на работы и запчасти' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_primary_text',   'Записаться на диагностику выхлопной' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_secondary_text', 'Рассчитать стоимость' );

	if ( $hero ) {
		miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_image', $hero );
	}

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_stats', array(
		array( 'stat_value' => '5,0',    'stat_label' => 'Рейтинг на картах' ),
		array( 'stat_value' => '500+',   'stat_label' => 'Отзывов на картах' ),
		array( 'stat_value' => 'с 2005', 'stat_label' => 'Опыт работы' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_examples_title', 'Примеры работ по выхлопной системе' );

	// ── SYMPTOMS ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_title',    'Когда нужен ремонт выхлопной' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_subtitle', 'Запах выхлопа в салоне — опасность для здоровья. Не откладывайте проверку' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cards', array(
		array(
			'sym_image'     => $sym_1,
			'symptom_title' => 'Громкий рёв двигателя',
			'symptom_desc'  => 'Нарастающий рёв или «выстрелы» из-под кузова — прогорела гофра, прокладка коллектора или корпус глушителя.',
		),
		array(
			'sym_image'     => $sym_2,
			'symptom_title' => 'Запах выхлопа в салоне',
			'symptom_desc'  => 'Запах отработавших газов внутри автомобиля — критически опасный симптом. Нарушена герметичность системы, требуется немедленная проверка.',
		),
		array(
			'sym_image'     => $sym_3,
			'symptom_title' => 'Потеря мощности и тяги',
			'symptom_desc'  => 'Двигатель хуже тянет, вырос расход топлива — возможно разрушение катализатора или засор сажевого фильтра DPF.',
		),
		array(
			'sym_image'     => $sym_4,
			'symptom_title' => 'Горит Check Engine',
			'symptom_desc'  => 'Ошибки P0420/P0430 и P0130–P0167 — признак неисправного катализатора или лямбда-зонда. Диагностика выявит точную причину.',
		),
		array(
			'sym_image'     => $sym_5,
			'symptom_title' => 'Вибрация кузова',
			'symptom_desc'  => 'Кузов вибрирует на холостом ходу или при разгоне — лопнула гофра или оторвалась подвеска глушителя.',
		),
		array(
			'sym_image'     => $sym_6,
			'symptom_title' => 'Ржавые пятна под машиной',
			'symptom_desc'  => 'Ржавые разводы или хлопья под автомобилем — сквозная коррозия глушителя или трубы, требует замены или сварки.',
		),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_text',     'Запишитесь — осмотрим выхлопную систему и устраним проблему' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_btn_text', 'Записаться на диагностику выхлопной' );

	// ── SVC-LIST ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_title', 'Какие работы выполняем' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_items', array(
		array(
			'svc_title' => 'Диагностика выхлопной системы',
			'svc_desc'  => 'Осматриваем все элементы на подъёмнике: коллектор, гофра, катализатор, резонатор, глушитель — определяем место утечки и степень износа.',
		),
		array(
			'svc_title' => 'Замена гофры',
			'svc_desc'  => 'Меняем гибкую вставку (гофру) — наиболее часто изнашиваемый элемент. Устанавливаем усиленные гофры из нержавеющей стали.',
		),
		array(
			'svc_title' => 'Замена катализатора',
			'svc_desc'  => 'Устанавливаем оригинальный или равноценный катализатор с проверкой по OBD-диагностике. По запросу — установка пламегасителя.',
		),
		array(
			'svc_title' => 'Замена лямбда-зонда',
			'svc_desc'  => 'Диагностируем датчик осциллографом, заменяем неисправный кислородный датчик с адаптацией топливной смеси по OBD.',
		),
		array(
			'svc_title' => 'Замена глушителя',
			'svc_desc'  => 'Меняем основной глушитель или резонатор — используем оригинальные или сертифицированные аналоги с нержавеющим корпусом.',
		),
		array(
			'svc_title' => 'Сварка и ремонт труб',
			'svc_desc'  => 'Завариваем трещины и свищи в трубах выхлопной системы — экономичная альтернатива полной замене трубы.',
		),
		array(
			'svc_title' => 'Замена прокладки выпускного коллектора',
			'svc_desc'  => 'Устраняем свист и хлопки на холодном двигателе — меняем прогоревшую прокладку коллектора.',
		),
		array(
			'svc_title' => 'Чистка и регенерация сажевого фильтра',
			'svc_desc'  => 'Проводим принудительную регенерацию DPF или химическую промывку — восстанавливаем пропускную способность без замены.',
		),
	) );

	// ── SC-PRICES ────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_title',    'Стоимость ремонта выхлопной системы' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_subtitle', 'Точная стоимость — после осмотра на подъёмнике' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_rows', array(
		array( 'service_name' => 'Диагностика выхлопной системы',    'sc_service_price' => 'бесплатно' ),
		array( 'service_name' => 'Замена гофры (работа)',            'sc_service_price' => 'от 1 500 ₽' ),
		array( 'service_name' => 'Замена лямбда-зонда (работа)',     'sc_service_price' => 'от 1 200 ₽' ),
		array( 'service_name' => 'Замена глушителя (работа)',        'sc_service_price' => 'от 2 000 ₽' ),
		array( 'service_name' => 'Замена катализатора (работа)',     'sc_service_price' => 'от 3 000 ₽' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_heading',  'Итоговая стоимость — после осмотра и согласования' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_desc',     'Все работы согласуем до начала ремонта' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_btn_text', 'Записаться на диагностику выхлопной' );

	// ── WARRANTY ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_title',    'Гарантия и ответственность' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_subtitle', 'Используем нержавеющие компоненты — служат в 2–3 раза дольше обычных' );

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
			'warranty_text' => 'Фото дефектных узлов (по запросу)',
		),
	) );

	// ── FAQ ──────────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_heading', 'FAQ' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_entries', array(
		array(
			'faq_entry_active'   => true,
			'faq_entry_question' => 'Чем опасен запах выхлопа в салоне?',
			'faq_entry_answer'   => 'Угарный газ CO — бесцветный и без запаха яд. Даже небольшая концентрация вызывает головную боль, потерю концентрации и может привести к потере сознания за рулём. Любой запах выхлопа в салоне — повод немедленно остановиться и проверить систему.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Можно ли заварить глушитель или нужно менять?',
			'faq_entry_answer'   => 'Небольшие трещины и свищи в трубах и корпусе глушителя поддаются сварке. Если корпус сгнил насквозь или деформирован — замена выгоднее. Решение принимаем после осмотра.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Что такое гофра и почему она часто рвётся?',
			'faq_entry_answer'   => 'Гофра — гибкая металлическая вставка между коллектором и трубой выхлопа, компенсирующая вибрации двигателя. Рвётся из-за усталости металла и коррозии — обычно через 80 000–120 000 км.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Нужно ли менять катализатор или можно удалить?',
			'faq_entry_answer'   => 'Замена оригинальным катализатором — правильное решение. Удаление делает автомобиль экологически несоответствующим нормам. Устанавливаем качественные аналоги по разумной цене.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Как понять, что катализатор разрушен?',
			'faq_entry_answer'   => 'Основные признаки: потеря мощности, увеличение расхода топлива, характерный «дребезжащий» звук из-под машины, ошибка P0420/P0430. Подтверждаем эндоскопом или диагностикой.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Почему лучше выбирать нержавеющие компоненты?',
			'faq_entry_answer'   => 'Обычная сталь служит 2–4 года, нержавейка — 6–10 лет. Переплата за нержавеющий компонент окупается отсутствием повторного ремонта в следующие несколько лет.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Сколько лямбда-зондов в Mitsubishi и зачем их два?',
			'faq_entry_answer'   => 'Большинство Mitsubishi оснащены двумя датчиками: первый управляет топливной смесью, второй контролирует эффективность катализатора. При замене определяем, какой из них неисправен.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Даёте ли гарантию на ремонт выхлопной?',
			'faq_entry_answer'   => 'Да, 1 год на все виды работ. На запчасти — по условиям поставщика. Гарантийный талон и акт выполненных работ выдаём обязательно.',
		),
	) );
}

function miauto_vyhlopnaya_seo_text() {
	return '<p>Технический центр МИ АВТО специализируется на диагностике и ремонте выхлопных систем автомобилей Mitsubishi — от прокладки коллектора до основного глушителя.</p>'
		. '<p>Проводим полный осмотр системы на подъёмнике, устанавливаем нержавеющие компоненты с увеличенным сроком службы и выполняем все сварочные работы на месте.</p>'
		. '<p>При ошибках катализатора и лямбда-зонда используем дилерский сканер для точной диагностики — исключаем ненужные замены запчастей.</p>'
		. '<p>Ремонт выхлопной системы Mitsubishi в МИ АВТО — профессионально, с гарантией 1 год и полным пакетом закрывающих документов.</p>';
}
