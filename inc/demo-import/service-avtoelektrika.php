<?php
/**
 * Content installer: Автоэлектрика (miauto_service).
 *
 * Triggered by visiting: /wp-admin/?miauto_fill_avtoelektrika=1
 * Safe to run multiple times — fills only empty fields.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set Yoast SEO meta tags for a post if not already set.
 *
 * @param int    $post_id     Post ID.
 * @param string $title       SEO title (plain text, no %%variables%%).
 * @param string $description Meta description (max ~155 chars).
 * @param string $focus_kw    Focus keyphrase.
 */
function miauto_set_yoast_meta_if_empty( $post_id, $title, $description, $focus_kw = '' ) {
	if ( ! get_post_meta( $post_id, '_yoast_wpseo_title', true ) ) {
		update_post_meta( $post_id, '_yoast_wpseo_title', $title );
	}
	if ( ! get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ) ) {
		update_post_meta( $post_id, '_yoast_wpseo_metadesc', $description );
	}
	if ( $focus_kw && ! get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ) ) {
		update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_kw );
	}
}

/**
 * Hook into admin_init to check for the GET parameter.
 */
function miauto_avtoelektrika_init() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_GET['miauto_fill_avtoelektrika'] ) || '1' !== $_GET['miauto_fill_avtoelektrika'] ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_fill_avtoelektrika' ) ) {
		add_action( 'admin_notices', function () {
			$url = wp_nonce_url( admin_url( '?miauto_fill_avtoelektrika=1' ), 'miauto_fill_avtoelektrika' );
			echo '<div class="notice notice-info"><p>MI-AUTO: Для заполнения Автоэлектрики перейдите по ссылке: <a href="' . esc_url( $url ) . '">Заполнить контент Автоэлектрики</a></p></div>';
		} );
		return;
	}

	$result = miauto_run_fill_avtoelektrika();

	if ( is_wp_error( $result ) ) {
		$msg = $result->get_error_message();
		add_action( 'admin_notices', function () use ( $msg ) {
			echo '<div class="notice notice-error"><p>MI-AUTO Автоэлектрика Error: ' . esc_html( $msg ) . '</p></div>';
		} );
		return;
	}

	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-success"><p>MI-AUTO: Контент страницы «Автоэлектрика» успешно заполнен! Заполнены только пустые поля.</p></div>';
	} );
}
add_action( 'admin_init', 'miauto_avtoelektrika_init' );

/**
 * Main runner.
 *
 * @return true|WP_Error
 */
function miauto_run_fill_avtoelektrika() {
	set_time_limit( 300 );
	wp_raise_memory_limit( 'admin' );

	// 1. Find the service post.
	$post_id = miauto_avtoelektrika_get_post_id();
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	// 2. Upload AI-generated images to media library.
	$imgs = miauto_avtoelektrika_upload_images();

	// 3. Fill all Carbon Fields.
	miauto_avtoelektrika_fill_fields( $post_id, $imgs );

	// 4. Set SEO post_content if empty.
	if ( '' === get_post_field( 'post_content', $post_id ) ) {
		wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => miauto_avtoelektrika_seo_text(),
		) );
	}

	// 5. Set Yoast SEO meta tags if empty.
	miauto_set_yoast_meta_if_empty( $post_id,
		'Автоэлектрика Mitsubishi в Москве — диагностика и ремонт | MI-AUTO',
		'Профессиональный ремонт автоэлектрики Mitsubishi: компьютерная диагностика, ремонт стартера, генератора, ABS, SRS, проводки. Гарантия 1 год. Запись онлайн.',
		'автоэлектрика mitsubishi'
	);

	return true;
}

// ─── Helper: find or bail ────────────────────────────────────────────

function miauto_avtoelektrika_get_post_id() {
	// Try by slug first.
	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'name'        => 'avtoelektrika',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	// Try by title.
	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'title'       => 'Автоэлектрика',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	return new WP_Error( 'post_not_found', 'Запись «Автоэлектрика» не найдена. Сначала запустите demo-import.' );
}

// ─── Helper: upload all avtoelektrika images ─────────────────────────

/**
 * Upload images from img/avtoelektrika/ to the media library.
 * Uses _miauto_source meta key to skip already-uploaded files.
 *
 * @return array Map of key => attachment_id.
 */
function miauto_avtoelektrika_upload_images() {
	$files = array(
		'hero'         => 'hero-main.jpg',
		'sym_check'    => 'sym-check-engine.jpg',
		'sym_start'    => 'sym-engine-start.jpg',
		'sym_battery'  => 'sym-battery.jpg',
		'sym_abs'      => 'sym-abs-srs.jpg',
		'sym_ac'       => 'sym-ac.jpg',
		'sym_alarm'    => 'sym-alarm.jpg',
		'ex_diag'      => 'example-diagnostics.jpg',
		'ex_wiring'    => 'example-wiring.jpg',
		'ex_starter'   => 'example-starter.jpg',
		'ex_ecu'       => 'example-ecu.jpg',
	);

	$ids = array();

	foreach ( $files as $key => $filename ) {
		$ids[ $key ] = miauto_avtoelektrika_upload_single( $filename );
	}

	return $ids;
}

/**
 * Upload a single file from img/avtoelektrika/ if not already in media library.
 *
 * @param string $filename
 * @return int Attachment ID (0 on failure).
 */
function miauto_avtoelektrika_upload_single( $filename ) {
	$source_key = 'avtoelektrika/' . $filename;

	// Already uploaded?
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

	$file_path = get_template_directory() . '/img/avtoelektrika/' . $filename;

	if ( ! file_exists( $file_path ) ) {
		return 0;
	}

	$upload_dir = wp_upload_dir();
	$target     = $upload_dir['path'] . '/' . $filename;

	if ( ! copy( $file_path, $target ) ) {
		return 0;
	}

	$filetype   = wp_check_filetype( $filename );
	$attach_id  = wp_insert_attachment( array(
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

// ─── Helper: find image attachment ID (legacy, demo-import images) ───

function miauto_avtoelektrika_get_image_id( $filename ) {
	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'meta_key'    => '_miauto_demo_source',
		'meta_value'  => $filename,
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	return ! empty( $existing ) ? $existing[0] : 0;
}

// ─── Fill all Carbon Fields ──────────────────────────────────────────

function miauto_avtoelektrika_fill_fields( $post_id, $imgs ) {
	// Fallback: если картинка не загрузилась — используем svc-auto-electric из demo-import.
	$fallback = miauto_avtoelektrika_get_image_id( 'svc-auto-electric.png' );

	$hero        = $imgs['hero']        ?: $fallback;
	$sym_check   = $imgs['sym_check']   ?: $fallback;
	$sym_start   = $imgs['sym_start']   ?: $fallback;
	$sym_battery = $imgs['sym_battery'] ?: $fallback;
	$sym_abs     = $imgs['sym_abs']     ?: $fallback;
	$sym_ac      = $imgs['sym_ac']      ?: $fallback;
	$sym_alarm   = $imgs['sym_alarm']   ?: $fallback;

	// ── SC-HERO ──────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_subtitle',
		'Диагностика и ремонт электрооборудования Mitsubishi — точно, быстро, с гарантией по договору'
	);

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_features', array(
		array( 'feature_text' => 'Дилерский сканер — доступ ко всем блокам' ),
		array( 'feature_text' => 'Фото/видео отчёт по этапам (по запросу)' ),
		array( 'feature_text' => 'Сроки от 1 дня (в зависимости от поломки)' ),
		array( 'feature_text' => 'Гарантия на работы и запчасти' ),
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

	// SC-Examples title.
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_examples_title', 'Примеры работ по автоэлектрике' );

	// ── SYMPTOMS ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_title',    'Когда нужна диагностика электрики' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_subtitle', 'Не откладывайте — ранняя диагностика предотвращает дорогостоящий ремонт' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cards', array(
		array(
			'sym_image'     => $sym_check,
			'symptom_title' => 'Горит Check Engine',
			'symptom_desc'  => 'Индикатор не гаснет после перезапуска — блок управления зафиксировал ошибку в одной из систем.',
		),
		array(
			'sym_image'     => $sym_start,
			'symptom_title' => 'Не запускается двигатель',
			'symptom_desc'  => 'Стартер крутит медленно или двигатель не схватывает — возможен отказ стартера, реле или АКБ.',
		),
		array(
			'sym_image'     => $sym_battery,
			'symptom_title' => 'Аккумулятор быстро разряжается',
			'symptom_desc'  => 'Авто не заводится после стоянки — признак неисправного генератора или утечки тока в бортовой сети.',
		),
		array(
			'sym_image'     => $sym_abs,
			'symptom_title' => 'Не работают ABS или подушки',
			'symptom_desc'  => 'Загорелись индикаторы ABS / SRS — критические системы безопасности требуют немедленной проверки.',
		),
		array(
			'sym_image'     => $sym_ac,
			'symptom_title' => 'Проблемы с кондиционером',
			'symptom_desc'  => 'Не охлаждает, вентилятор работает на одной скорости — часто причина в электрической части системы.',
		),
		array(
			'sym_image'     => $sym_alarm,
			'symptom_title' => 'Сбои сигнализации',
			'symptom_desc'  => 'Ложные срабатывания или замок не реагирует на брелок — повреждение проводки или блока управления.',
		),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_text',     'Запишитесь — проверим причину и предложим варианты решения' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_btn_text', 'Записаться на диагностику' );

	// ── SVC-LIST ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_title', 'Какие работы выполняем' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_items', array(
		array(
			'svc_title' => 'Компьютерная диагностика',
			'svc_desc'  => 'Считываем ошибки всех блоков управления дилерским сканером и анализируем параметры в режиме реального времени.',
		),
		array(
			'svc_title' => 'Ремонт электрооборудования',
			'svc_desc'  => 'Проверяем и восстанавливаем электрические цепи: освещение, датчики, реле, исполнительные механизмы и блоки управления.',
		),
		array(
			'svc_title' => 'Ремонт стартера и генератора',
			'svc_desc'  => 'Разбираем, дефектуем, восстанавливаем или заменяем агрегат. Тестируем на стенде по пусковому току и напряжению зарядки.',
		),
		array(
			'svc_title' => 'Ремонт ABS и SRS',
			'svc_desc'  => 'Диагностируем датчики, гидроблок и блок управления подушками. Заменяем неисправные элементы, сбрасываем аварийные коды.',
		),
		array(
			'svc_title' => 'Замена свечей зажигания',
			'svc_desc'  => 'Подбираем свечи по регламенту завода, проверяем катушки зажигания и высоковольтные провода.',
		),
		array(
			'svc_title' => 'Замена лямбда-зонда',
			'svc_desc'  => 'Диагностируем датчик осциллографом, устанавливаем оригинальный или OEM-аналог с адаптацией топливной смеси.',
		),
		array(
			'svc_title' => 'Ремонт кондиционера и вентиляции',
			'svc_desc'  => 'Проверяем электрическую часть системы: реле компрессора, мотор вентилятора, датчики давления, сервоприводы заслонок.',
		),
		array(
			'svc_title' => 'Диагностика и ремонт сигнализации',
			'svc_desc'  => 'Считываем блок охранной системы, устраняем ложные срабатывания, программируем ключи и настраиваем зоны охраны.',
		),
		array(
			'svc_title' => 'Восстановление проводки',
			'svc_desc'  => 'Ищем обрывы и замыкания методом инжекции сигнала, восстанавливаем жгуты, герметизируем соединения термоусадкой.',
		),
	) );

	// ── SC-PRICES ────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_title',    'Стоимость работ по автоэлектрике' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_subtitle', 'Вы не платите за лишнее — только согласованные работы' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_rows', array(
		array( 'service_name' => 'Компьютерная диагностика (все блоки)', 'sc_service_price' => 'от 1 500 ₽' ),
		array( 'service_name' => 'Ремонт стартера',                      'sc_service_price' => 'от 3 500 ₽' ),
		array( 'service_name' => 'Ремонт генератора',                    'sc_service_price' => 'от 4 000 ₽' ),
		array( 'service_name' => 'Ремонт ABS / SRS',                     'sc_service_price' => 'от 5 000 ₽' ),
		array( 'service_name' => 'Восстановление проводки',              'sc_service_price' => 'от 2 500 ₽' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_heading',  'Получите точную смету после дефектовки' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_desc',     'До начала работ всё согласуем' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_btn_text', 'Записаться на диагностику' );

	// ── WARRANTY ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_title',    'Гарантия и ответственность' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_subtitle', 'Если выявим, что ремонт нецелесообразен — предложим альтернативы' );

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
			'warranty_text' => 'Фото/видео дефектовки (по запросу)',
		),
	) );

	// ── FAQ ──────────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_heading', 'FAQ' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_entries', array(
		array(
			'faq_entry_active'   => true,
			'faq_entry_question' => 'Сколько времени занимает диагностика электрики?',
			'faq_entry_answer'   => 'Компьютерная диагностика всех блоков управления занимает 30–60 минут. Если требуется углублённая проверка отдельной системы (ABS, SRS, климат) — до 2 часов. По итогам выдаём письменный отчёт.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Почему постоянно горит Check Engine?',
			'faq_entry_answer'   => 'Причин более 200 — от незакрученной крышки бензобака до отказа форсунки. Сброс ошибки без устранения причины даёт временный эффект. Единственный верный способ — диагностика с расшифровкой кода.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Можно ли ездить с неисправной ABS?',
			'faq_entry_answer'   => 'Автомобиль остаётся на ходу, но при экстренном торможении колёса могут заблокироваться, что резко увеличивает тормозной путь. Не рекомендуем откладывать ремонт.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Как понять, что генератор неисправен?',
			'faq_entry_answer'   => 'Основные признаки: быстрый разряд АКБ, тусклый свет на холостом ходу, индикатор аккумулятора на панели. Напряжение в бортовой сети при заведённом двигателе должно быть 13,8–14,4 В.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Даёте ли гарантию на ремонт?',
			'faq_entry_answer'   => 'На все виды электрических работ предоставляем гарантию 1 год. На запчасти — по условиям поставщика. Выдаём гарантийный талон и акт выполненных работ.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Какие запчасти используете?',
			'faq_entry_answer'   => 'Работаем с оригинальными запчастями Mitsubishi и сертифицированными аналогами OEM/OES — Denso, Bosch, NGK, Delphi. Выбор согласовываем с клиентом.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Почему сигнализация ложно срабатывает?',
			'faq_entry_answer'   => 'Чаще всего причина — окисление датчика удара, плохой контакт в разъёме блока или севший элемент питания в брелоке. Диагностика позволит точно установить и устранить причину за один визит.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Нужна ли предварительная запись?',
			'faq_entry_answer'   => 'Рекомендуем записаться — это позволяет выделить мастера и оборудование под ваш автомобиль. В срочных случаях постараемся принять в день обращения.',
		),
	) );
}

// ─── SEO post_content ────────────────────────────────────────────────

function miauto_avtoelektrika_seo_text() {
	return '<p>Технический центр МИ АВТО специализируется на диагностике и ремонте автоэлектрики всего модельного ряда автомобилей Mitsubishi в Москве и Московской области.</p>'
		. '<p>Все работы проводятся с применением дилерского диагностического оборудования, специального инструмента и технической документации завода-изготовителя.</p>'
		. '<p>Многолетний опыт, высокая квалификация мастеров-электриков и наличие склада оригинальных и аналоговых запчастей позволяют устранять неисправности в минимальные сроки.</p>'
		. '<p>Послегарантийное обслуживание и ремонт автоэлектрики Mitsubishi — наша специализация.</p>';
}
