<?php
/**
 * Content installer: Тормозная система (miauto_service).
 *
 * Triggered by visiting: /wp-admin/?miauto_fill_tormoznaya=1
 * Safe to run multiple times — fills only empty fields.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function miauto_tormoznaya_init() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_GET['miauto_fill_tormoznaya'] ) || '1' !== $_GET['miauto_fill_tormoznaya'] ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_fill_tormoznaya' ) ) {
		add_action( 'admin_notices', function () {
			$url = wp_nonce_url( admin_url( '?miauto_fill_tormoznaya=1' ), 'miauto_fill_tormoznaya' );
			echo '<div class="notice notice-info"><p>MI-AUTO: Для заполнения Тормозной системы перейдите по ссылке: <a href="' . esc_url( $url ) . '">Заполнить контент Тормозной системы</a></p></div>';
		} );
		return;
	}

	$result = miauto_run_fill_tormoznaya();

	if ( is_wp_error( $result ) ) {
		$msg = $result->get_error_message();
		add_action( 'admin_notices', function () use ( $msg ) {
			echo '<div class="notice notice-error"><p>MI-AUTO Тормозная система Error: ' . esc_html( $msg ) . '</p></div>';
		} );
		return;
	}

	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-success"><p>MI-AUTO: Контент страницы «Тормозная система» успешно заполнен! Заполнены только пустые поля.</p></div>';
	} );
}
add_action( 'admin_init', 'miauto_tormoznaya_init' );

function miauto_run_fill_tormoznaya() {
	set_time_limit( 300 );
	wp_raise_memory_limit( 'admin' );

	$post_id = miauto_tormoznaya_get_post_id();
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$imgs = miauto_tormoznaya_upload_images();

	miauto_tormoznaya_fill_fields( $post_id, $imgs );

	if ( '' === get_post_field( 'post_content', $post_id ) ) {
		wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => miauto_tormoznaya_seo_text(),
		) );
	}

	miauto_set_yoast_meta_if_empty( $post_id,
		'Ремонт тормозной системы Mitsubishi в Москве — замена колодок и дисков | MI-AUTO',
		'Профессиональный ремонт тормозов Mitsubishi: замена колодок, дисков, суппортов, тормозной жидкости. Гарантия 1 год. Безопасность на дороге.',
		'тормозная система mitsubishi'
	);

	return true;
}

function miauto_tormoznaya_get_post_id() {
	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'name'        => 'tormoznaya-sistema',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'title'       => 'Тормозная система',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	return new WP_Error( 'post_not_found', 'Запись «Тормозная система» не найдена. Сначала запустите demo-import.' );
}

function miauto_tormoznaya_upload_images() {
	$files = array(
		'hero'  => 'hero-main.jpg',
		'sym_1' => 'sym-skrip.jpg',
		'sym_2' => 'sym-pedal.jpg',
		'sym_3' => 'sym-vibraciya.jpg',
		'sym_4' => 'sym-uvod.jpg',
		'sym_5' => 'sym-abs.jpg',
		'sym_6' => 'sym-put.jpg',
	);

	$ids = array();

	foreach ( $files as $key => $filename ) {
		$ids[ $key ] = miauto_tormoznaya_upload_single( $filename );
	}

	return $ids;
}

function miauto_tormoznaya_upload_single( $filename ) {
	$source_key = 'tormoznaya-sistema/' . $filename;

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

	$file_path = get_template_directory() . '/img/tormoznaya-sistema/' . $filename;

	if ( ! file_exists( $file_path ) ) {
		return 0;
	}

	$upload_dir      = wp_upload_dir();
	$upload_filename = 'tormoznaya-' . $filename;
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

function miauto_tormoznaya_get_fallback_id() {
	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'meta_key'    => '_miauto_demo_source',
		'meta_value'  => 'svc-brakes.png',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	return ! empty( $existing ) ? $existing[0] : 0;
}

function miauto_tormoznaya_fill_fields( $post_id, $imgs ) {
	$fallback = miauto_tormoznaya_get_fallback_id();

	$hero  = $imgs['hero']  ?: $fallback;
	$sym_1 = $imgs['sym_1'] ?: $fallback;
	$sym_2 = $imgs['sym_2'] ?: $fallback;
	$sym_3 = $imgs['sym_3'] ?: $fallback;
	$sym_4 = $imgs['sym_4'] ?: $fallback;
	$sym_5 = $imgs['sym_5'] ?: $fallback;
	$sym_6 = $imgs['sym_6'] ?: $fallback;

	// ── SC-HERO ──────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_subtitle',
		'Диагностика и ремонт тормозной системы Mitsubishi — восстанавливаем надёжность торможения с гарантией 1 год'
	);

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_features', array(
		array( 'feature_text' => 'Замер толщины дисков и колодок бесплатно' ),
		array( 'feature_text' => 'Оригинальные и OEM запчасти в наличии' ),
		array( 'feature_text' => 'Прокачка тормозной системы в стоимости замены жидкости' ),
		array( 'feature_text' => 'Гарантия на работы и запчасти' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_primary_text',   'Записаться на диагностику тормозов' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_secondary_text', 'Рассчитать стоимость' );

	if ( $hero ) {
		miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_image', $hero );
	}

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_stats', array(
		array( 'stat_value' => '5,0',    'stat_label' => 'Рейтинг на картах' ),
		array( 'stat_value' => '500+',   'stat_label' => 'Отзывов на картах' ),
		array( 'stat_value' => 'с 2005', 'stat_label' => 'Опыт работы' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_examples_title', 'Примеры работ по тормозной системе' );

	// ── SYMPTOMS ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_title',    'Когда нужен ремонт тормозов' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_subtitle', 'Тормоза — главная система безопасности. Не откладывайте обслуживание' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cards', array(
		array(
			'sym_image'     => $sym_1,
			'symptom_title' => 'Скрип при торможении',
			'symptom_desc'  => 'Металлический скрип или визг при нажатии на педаль — сигнал износа колодок до индикатора или замасливания тормозных дисков.',
		),
		array(
			'sym_image'     => $sym_2,
			'symptom_title' => 'Педаль тормоза проваливается',
			'symptom_desc'  => 'Педаль уходит вниз до пола или требует нескольких нажатий — признак попадания воздуха в систему или отказа главного тормозного цилиндра.',
		),
		array(
			'sym_image'     => $sym_3,
			'symptom_title' => 'Вибрация при торможении',
			'symptom_desc'  => 'Руль и кузов вибрируют при нажатии на тормоз — деформированные или неравномерно изношенные тормозные диски.',
		),
		array(
			'sym_image'     => $sym_4,
			'symptom_title' => 'Уводит в сторону при торможении',
			'symptom_desc'  => 'Автомобиль уходит влево или вправо при торможении — признак заклинившего суппорта или разной эффективности тормозов по осям.',
		),
		array(
			'sym_image'     => $sym_5,
			'symptom_title' => 'Горит индикатор ABS',
			'symptom_desc'  => 'Постоянно горящий индикатор ABS — неисправность датчика скорости колеса или гидроблока, требует срочной диагностики.',
		),
		array(
			'sym_image'     => $sym_6,
			'symptom_title' => 'Увеличился тормозной путь',
			'symptom_desc'  => 'Автомобиль тормозит хуже, чем раньше — изношенные колодки, диски или деградировавшая тормозная жидкость.',
		),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_text',     'Запишитесь — проверим тормоза и предложим решение' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_btn_text', 'Записаться на диагностику тормозов' );

	// ── SVC-LIST ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_title', 'Какие работы выполняем' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_items', array(
		array(
			'svc_title' => 'Диагностика тормозной системы',
			'svc_desc'  => 'Замеряем толщину дисков и колодок, проверяем давление в системе, состояние суппортов и тормозной жидкости — выдаём заключение.',
		),
		array(
			'svc_title' => 'Замена тормозных колодок',
			'svc_desc'  => 'Меняем передние и задние колодки с центровкой суппорта. Используем оригинальные или сертифицированные аналоги OEM/OES.',
		),
		array(
			'svc_title' => 'Замена тормозных дисков',
			'svc_desc'  => 'Устанавливаем диски по заводским допускам, проверяем биение после монтажа. Парная замена для равномерного торможения.',
		),
		array(
			'svc_title' => 'Ремонт суппортов',
			'svc_desc'  => 'Разбираем, чистим, меняем поршни и уплотнения — восстанавливаем подвижность суппорта. Альтернатива дорогостоящей замене в сборе.',
		),
		array(
			'svc_title' => 'Замена тормозной жидкости',
			'svc_desc'  => 'Полная замена с прокачкой через все контуры. Используем жидкость DOT 4 или DOT 5.1 по рекомендации Mitsubishi.',
		),
		array(
			'svc_title' => 'Ремонт ручного тормоза',
			'svc_desc'  => 'Регулируем трос стояночного тормоза, меняем колодки барабанного механизма задних колёс.',
		),
		array(
			'svc_title' => 'Ремонт ABS и ESP',
			'svc_desc'  => 'Диагностируем датчики скорости колёс, гидроблок и блок управления ABS/ESP — считываем коды ошибок и устраняем неисправность.',
		),
		array(
			'svc_title' => 'Замена тормозных шлангов',
			'svc_desc'  => 'Заменяем потрескавшиеся или вздутые тормозные шланги на новые с опрессовкой всех соединений.',
		),
	) );

	// ── SC-PRICES ────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_title',    'Стоимость ремонта тормозной системы' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_subtitle', 'Точная стоимость — после осмотра и замера дисков и колодок' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_rows', array(
		array( 'service_name' => 'Замена колодок (1 ось, работа)',         'sc_service_price' => 'от 1 500 ₽' ),
		array( 'service_name' => 'Замена дисков (1 ось, работа)',          'sc_service_price' => 'от 2 000 ₽' ),
		array( 'service_name' => 'Ремонт суппорта (1 шт)',                 'sc_service_price' => 'от 2 500 ₽' ),
		array( 'service_name' => 'Замена тормозной жидкости с прокачкой', 'sc_service_price' => 'от 1 500 ₽' ),
		array( 'service_name' => 'Диагностика тормозной системы',         'sc_service_price' => 'от 700 ₽' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_heading',  'Точная стоимость — после осмотра и замеров' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_desc',     'Согласуем до начала работ, без скрытых доплат' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_btn_text', 'Записаться на диагностику тормозов' );

	// ── WARRANTY ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_title',    'Гарантия и ответственность' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_subtitle', 'Работаем только с проверенными запчастями — оригинал или сертифицированный аналог' );

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
			'warranty_text' => 'Фото дефектовки изношенных деталей (по запросу)',
		),
	) );

	// ── FAQ ──────────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_heading', 'FAQ' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_entries', array(
		array(
			'faq_entry_active'   => true,
			'faq_entry_question' => 'Как часто нужно менять тормозную жидкость?',
			'faq_entry_answer'   => 'По регламенту Mitsubishi — каждые 2 года или 40 000 км. Тормозная жидкость гигроскопична: поглощает влагу и со временем снижает температуру кипения, что опасно при интенсивном торможении.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Когда менять тормозные колодки?',
			'faq_entry_answer'   => 'Остаточная толщина фрикционного материала менее 2–3 мм — сигнал к замене. В среднем передние колодки служат 30 000–50 000 км, задние — дольше. При скрипе или вибрации — немедленный осмотр.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Нужно ли менять диски вместе с колодками?',
			'faq_entry_answer'   => 'Не всегда. Если диск не выходит за минимальную толщину и не имеет глубоких борозд — колодки меняем без дисков. Решение принимаем после замера.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Почему после замены колодок появился скрип?',
			'faq_entry_answer'   => 'Первые 200–300 км новые колодки притираются к диску — лёгкий скрип нормален. Если скрип не проходит — причина в несовместимости колодок с диском или неправильной установке суппорта.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Можно ли ездить с тонкими тормозными дисками?',
			'faq_entry_answer'   => 'Нет. Диск ниже минимальной толщины может треснуть при резком торможении. Это критически опасно и является основанием для запрета эксплуатации.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Что такое прокачка тормозов и зачем она нужна?',
			'faq_entry_answer'   => 'Прокачка удаляет воздух из тормозных контуров. Воздух сжимается и делает педаль «ватной». Обязательна при замене жидкости, шлангов, цилиндров и суппортов.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Опасна ли поездка при мягкой педали тормоза?',
			'faq_entry_answer'   => 'Да — это критическая неисправность. Мягкая или проваливающаяся педаль говорит об отказе элементов системы или попадании воздуха. Необходима немедленная диагностика.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Даёте ли гарантию на тормозные работы?',
			'faq_entry_answer'   => 'Да, 1 год на все виды работ по тормозной системе. На запчасти — по условиям поставщика. Гарантийный талон и акт выполненных работ выдаём в обязательном порядке.',
		),
	) );
}

function miauto_tormoznaya_seo_text() {
	return '<p>Технический центр МИ АВТО специализируется на диагностике и ремонте тормозных систем автомобилей Mitsubishi в Москве и Московской области.</p>'
		. '<p>Используем только оригинальные и сертифицированные аналоговые запчасти: Brembo, ATE, TRW, Bosch — с проверкой совместимости по VIN автомобиля.</p>'
		. '<p>Тормозная система — это основа безопасности. Мы не рекомендуем откладывать ремонт при появлении первых симптомов и всегда предупреждаем о критических неисправностях.</p>'
		. '<p>Ремонт тормозной системы Mitsubishi в МИ АВТО — профессионально, с документами и гарантией 1 год на все виды работ.</p>';
}
