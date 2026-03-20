<?php
/**
 * Content installer: Шиномонтаж (miauto_service).
 *
 * Triggered by visiting: /wp-admin/?miauto_fill_shinomontazh=1
 * Safe to run multiple times — fills only empty fields.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function miauto_shinomontazh_init() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_GET['miauto_fill_shinomontazh'] ) || '1' !== $_GET['miauto_fill_shinomontazh'] ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_fill_shinomontazh' ) ) {
		add_action( 'admin_notices', function () {
			$url = wp_nonce_url( admin_url( '?miauto_fill_shinomontazh=1' ), 'miauto_fill_shinomontazh' );
			echo '<div class="notice notice-info"><p>MI-AUTO: Для заполнения Шиномонтажа перейдите по ссылке: <a href="' . esc_url( $url ) . '">Заполнить контент Шиномонтажа</a></p></div>';
		} );
		return;
	}

	$result = miauto_run_fill_shinomontazh();

	if ( is_wp_error( $result ) ) {
		$msg = $result->get_error_message();
		add_action( 'admin_notices', function () use ( $msg ) {
			echo '<div class="notice notice-error"><p>MI-AUTO Шиномонтаж Error: ' . esc_html( $msg ) . '</p></div>';
		} );
		return;
	}

	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-success"><p>MI-AUTO: Контент страницы «Шиномонтаж» успешно заполнен! Заполнены только пустые поля.</p></div>';
	} );
}
add_action( 'admin_init', 'miauto_shinomontazh_init' );

function miauto_run_fill_shinomontazh() {
	set_time_limit( 300 );
	wp_raise_memory_limit( 'admin' );

	$post_id = miauto_shinomontazh_get_post_id();
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$imgs = miauto_shinomontazh_upload_images();

	miauto_shinomontazh_fill_fields( $post_id, $imgs );

	if ( '' === get_post_field( 'post_content', $post_id ) ) {
		wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => miauto_shinomontazh_seo_text(),
		) );
	}

	miauto_set_yoast_meta_if_empty( $post_id,
		'Шиномонтаж Mitsubishi в Москве — шиномонтаж и балансировка | MI-AUTO',
		'Профессиональный шиномонтаж Mitsubishi: замена, балансировка, ремонт проколов, сезонное хранение. Работаем без записи. Гарантия качества.',
		'шиномонтаж mitsubishi'
	);

	return true;
}

function miauto_shinomontazh_get_post_id() {
	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'name'        => 'shinomontazh',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'title'       => 'Шиномонтаж',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	return new WP_Error( 'post_not_found', 'Запись «Шиномонтаж» не найдена. Сначала запустите demo-import.' );
}

function miauto_shinomontazh_upload_images() {
	$files = array(
		'hero'  => 'hero-main.jpg',
		'sym_1' => 'sym-prokol.jpg',
		'sym_2' => 'sym-sezonnaya.jpg',
		'sym_3' => 'sym-vibracia.jpg',
		'sym_4' => 'sym-davlenie.jpg',
		'sym_5' => 'sym-iznos.jpg',
		'sym_6' => 'sym-bortovaya.jpg',
	);

	$ids = array();

	foreach ( $files as $key => $filename ) {
		$ids[ $key ] = miauto_shinomontazh_upload_single( $filename );
	}

	return $ids;
}

function miauto_shinomontazh_upload_single( $filename ) {
	$source_key = 'shinomontazh/' . $filename;

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

	$file_path = get_template_directory() . '/img/shinomontazh/' . $filename;

	if ( ! file_exists( $file_path ) ) {
		return 0;
	}

	$upload_dir      = wp_upload_dir();
	$upload_filename = 'shinomontazh-' . $filename;
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

function miauto_shinomontazh_get_fallback_id() {
	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'meta_key'    => '_miauto_demo_source',
		'meta_value'  => 'svc-tire.png',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	return ! empty( $existing ) ? $existing[0] : 0;
}

function miauto_shinomontazh_fill_fields( $post_id, $imgs ) {
	$fallback = miauto_shinomontazh_get_fallback_id();

	$hero  = $imgs['hero']  ?: $fallback;
	$sym_1 = $imgs['sym_1'] ?: $fallback;
	$sym_2 = $imgs['sym_2'] ?: $fallback;
	$sym_3 = $imgs['sym_3'] ?: $fallback;
	$sym_4 = $imgs['sym_4'] ?: $fallback;
	$sym_5 = $imgs['sym_5'] ?: $fallback;
	$sym_6 = $imgs['sym_6'] ?: $fallback;

	// ── SC-HERO ──────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_subtitle',
		'Шиномонтаж и балансировка колёс Mitsubishi — быстро, аккуратно, с гарантией на работы'
	);

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_features', array(
		array( 'feature_text' => 'Работаем без записи — принимаем сразу' ),
		array( 'feature_text' => 'Современное оборудование для любых размеров дисков' ),
		array( 'feature_text' => 'Балансировка на финишном балансировочном стенде' ),
		array( 'feature_text' => 'Гарантия на установку и балансировку' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_primary_text',   'Записаться на шиномонтаж' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_secondary_text', 'Рассчитать стоимость' );

	if ( $hero ) {
		miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_image', $hero );
	}

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_stats', array(
		array( 'stat_value' => '5,0',    'stat_label' => 'Рейтинг на картах' ),
		array( 'stat_value' => '500+',   'stat_label' => 'Отзывов на картах' ),
		array( 'stat_value' => 'с 2005', 'stat_label' => 'Опыт работы' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_examples_title', 'Примеры работ по шиномонтажу' );

	// ── SYMPTOMS ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_title',    'Когда нужен шиномонтаж' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_subtitle', 'Не откладывайте — повреждённая шина опасна на скорости' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cards', array(
		array(
			'sym_image'     => $sym_1,
			'symptom_title' => 'Прокол или порез шины',
			'symptom_desc'  => 'Колесо спускает или полностью потеряло давление — оперативный ремонт прокола сохранит шину и предотвратит повреждение диска.',
		),
		array(
			'sym_image'     => $sym_2,
			'symptom_title' => 'Сезонная смена резины',
			'symptom_desc'  => 'Смена летней резины на зимнюю и обратно — обязательная процедура дважды в год для безопасности и сохранности шин.',
		),
		array(
			'sym_image'     => $sym_3,
			'symptom_title' => 'Вибрация на скорости',
			'symptom_desc'  => 'Руль или кузов вибрирует при движении — признак дисбаланса колёс, требует срочной балансировки.',
		),
		array(
			'sym_image'     => $sym_4,
			'symptom_title' => 'Давление постоянно падает',
			'symptom_desc'  => 'Шина медленно спускает без видимых повреждений — возможна утечка через вентиль, микропорез или бортовое кольцо.',
		),
		array(
			'sym_image'     => $sym_5,
			'symptom_title' => 'Неравномерный износ протектора',
			'symptom_desc'  => 'Шина стёрта с одной стороны — сигнал нарушенных углов установки колёс, нужен шиномонтаж с последующим сход-развалом.',
		),
		array(
			'sym_image'     => $sym_6,
			'symptom_title' => 'Повреждение боковины',
			'symptom_desc'  => 'Грыжа или порыв боковой части шины после удара о бордюр — такая шина не подлежит ремонту и требует замены.',
		),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_text',     'Запишитесь — заменим или отремонтируем шины быстро' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_btn_text', 'Записаться на шиномонтаж' );

	// ── SVC-LIST ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_title', 'Какие работы выполняем' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_items', array(
		array(
			'svc_title' => 'Монтаж и демонтаж шин',
			'svc_desc'  => 'Снимаем и устанавливаем шины на диски любого типа: стальные, литые, кованые. Работаем с низкопрофильными и run-flat шинами.',
		),
		array(
			'svc_title' => 'Балансировка колёс',
			'svc_desc'  => 'Устраняем дисбаланс на компьютерном стенде с точностью до 1 грамма — ликвидируем вибрацию руля и биение кузова.',
		),
		array(
			'svc_title' => 'Ремонт прокола',
			'svc_desc'  => 'Ремонтируем проколы методом вулканизации изнутри — надёжнее жгута, держит скоростной режим шины.',
		),
		array(
			'svc_title' => 'Замена вентилей',
			'svc_desc'  => 'Меняем резиновые и металлические вентили, устанавливаем датчики давления TPMS с программированием под ваш автомобиль.',
		),
		array(
			'svc_title' => 'Сезонное хранение шин',
			'svc_desc'  => 'Принимаем шины на хранение в специализированном стеллажном складе с соблюдением температурного режима.',
		),
		array(
			'svc_title' => 'Подбор шин',
			'svc_desc'  => 'Помогаем выбрать шины под модель Mitsubishi, стиль езды и бюджет — с учётом заводских рекомендаций.',
		),
		array(
			'svc_title' => 'Проверка и подкачка шин',
			'svc_desc'  => 'Проверяем давление во всех колёсах, доводим до нормы по регламенту — обязательно перед каждой сезонной сменой.',
		),
		array(
			'svc_title' => 'Переборка дисков',
			'svc_desc'  => 'Снимаем, очищаем от ржавчины и окислов места посадки шины, наносим герметик для устранения медленных утечек.',
		),
	) );

	// ── SC-PRICES ────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_title',    'Стоимость шиномонтажа' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_subtitle', 'Фиксированные цены без скрытых доплат' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_rows', array(
		array( 'service_name' => 'Монтаж/демонтаж (1 колесо R14–R17)',   'sc_service_price' => 'от 250 ₽' ),
		array( 'service_name' => 'Балансировка (1 колесо)',               'sc_service_price' => 'от 300 ₽' ),
		array( 'service_name' => 'Ремонт прокола (вулканизация)',         'sc_service_price' => 'от 500 ₽' ),
		array( 'service_name' => 'Замена вентиля',                        'sc_service_price' => 'от 100 ₽' ),
		array( 'service_name' => 'Сезонное хранение (4 шины)',            'sc_service_price' => 'от 2 000 ₽/сез.' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_heading',  'Точная стоимость — зависит от размера шины и типа работ' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_desc',     'Называем цену до начала работ' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_btn_text', 'Записаться на шиномонтаж' );

	// ── WARRANTY ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_title',    'Гарантия и ответственность' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_subtitle', 'Работаем аккуратно — царапины на дисках исключены' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_cards', array(
		array(
			'war_svg'       => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 0L0 3.636V9.091C0 14.136 3.84 18.855 9 20c5.16-1.145 9-5.864 9-10.909V3.636L9 0zM7 14.546L3.773 11.611a.82.82 0 010-1.404.826.826 0 011.275-.002L7 11.973l5.948-5.407a.826.826 0 011.282.003.82.82 0 01-.003 1.406L7 14.546z" fill="white"/></svg>',
			'warranty_text' => 'Гарантия на монтаж и балансировку',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M18 0H2C1 0 0 .9 0 2v3.01c0 .72.43 1.34 1 1.69V18c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V6.7c.57-.35 1-.97 1-1.69V2c0-1.1-1-.9-2 0zM13 12H7v-2h6v2zM18 5H2V2l16-.02V5z" fill="white"/></svg>',
			'warranty_text' => 'Защита дисков при монтаже',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.613.256a.29.29 0 00-.29-.256H3.194C2.347 0 1.534.297.935.826.336 1.355 0 2.072 0 2.82v14.36c0 .748.336 1.465.935 1.994.6.529 1.412.826 2.259.826h11.612c.847 0 1.66-.297 2.258-.826.6-.529.936-1.246.936-1.994V7.074a.29.29 0 00-.29-.257h-5.226a.87.87 0 01-.612-.226.77.77 0 01-.259-.543V.256zM12.484 10.256c.231 0 .452.081.616.226a.77.77 0 01.255.543.77.77 0 01-.255.544.87.87 0 01-.616.226H5.516a.87.87 0 01-.616-.226.77.77 0 01-.255-.544c0-.208.092-.404.255-.543a.87.87 0 01.616-.226h6.968zm0 4.103c.231 0 .452.081.616.225a.77.77 0 01.255.544.77.77 0 01-.255.544.87.87 0 01-.616.225H5.516a.87.87 0 01-.616-.225.77.77 0 01-.255-.544c0-.208.092-.404.255-.544a.87.87 0 01.616-.225h6.968z" fill="white"/><path d="M13.356.59c0-.19.224-.31.39-.192.14.1.266.217.375.35l3.499 4.305c.079.098-.007.226-.144.226h-3.83a.29.29 0 01-.29-.257V.59z" fill="white"/></svg>',
			'warranty_text' => 'Документы: заказ-наряд, акт выполненных работ',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 20 18" xmlns="http://www.w3.org/2000/svg"><path d="M10 13.2c1.77 0 3.2-1.43 3.2-3.2 0-1.77-1.43-3.2-3.2-3.2-1.77 0-3.2 1.43-3.2 3.2 0 1.77 1.43 3.2 3.2 3.2zM7 0L5.17 2H2C.9 2 0 2.9 0 4v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2h-3.17L13 0H7zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z" fill="white"/></svg>',
			'warranty_text' => 'Проверка балансировки после первых 500 км бесплатно',
		),
	) );

	// ── FAQ ──────────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_heading', 'FAQ' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_entries', array(
		array(
			'faq_entry_active'   => true,
			'faq_entry_question' => 'Сколько времени занимает замена комплекта шин?',
			'faq_entry_answer'   => 'Замена четырёх шин с балансировкой занимает в среднем 40–60 минут. Без предварительной записи принимаем при наличии свободного подъёмника.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Можно ли отремонтировать прокол боковины?',
			'faq_entry_answer'   => 'Нет — боковина шины при движении постоянно деформируется, поэтому ремонт боковых повреждений ненадёжен и опасен. Требуется замена шины.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Нужна ли балансировка при каждой смене резины?',
			'faq_entry_answer'   => 'Да. При монтаже шина садится на диск в новом положении — дисбаланс меняется. Балансировка обязательна при каждой смене, а также после ремонта прокола.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Вы принимаете шины на хранение?',
			'faq_entry_answer'   => 'Да, принимаем шины и шины на дисках на сезонное хранение в крытом складе. Выдаём квитанцию с описанием состояния каждой шины.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Работаете ли с низкопрофильными шинами?',
			'faq_entry_answer'   => 'Да, используем станок с защитой диска и лопаткой с тефлоновым покрытием — монтируем шины профилем от 25 без риска повредить диск.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Как понять, что шина не подлежит ремонту?',
			'faq_entry_answer'   => 'Шина не ремонтируется при: порыве или грыже боковины, проколе крупным предметом в плечевой зоне, износе до индикатора, расслоении корда.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Устанавливаете датчики давления TPMS?',
			'faq_entry_answer'   => 'Да, устанавливаем и программируем датчики TPMS под ваш автомобиль. Если датчики уже есть — переустанавливаем и перепрограммируем при сезонной смене.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Нужна ли запись или можно приехать сразу?',
			'faq_entry_answer'   => 'Принимаем без записи, но в сезон (октябрь–ноябрь, март–апрель) загруженность высокая. Рекомендуем позвонить заранее — запишем на удобное время без очереди.',
		),
	) );
}

function miauto_shinomontazh_seo_text() {
	return '<p>Технический центр МИ АВТО выполняет шиномонтаж автомобилей Mitsubishi в Москве на профессиональном оборудовании с точной балансировкой и защитой дисков.</p>'
		. '<p>Принимаем без записи, работаем с любыми типами шин и дисков — стальными, литыми и кованными, включая низкопрофильную и run-flat резину.</p>'
		. '<p>В пиковый сезон (весна/осень) рекомендуем приезжать заблаговременно или записываться по телефону — это гарантирует минимальное ожидание.</p>'
		. '<p>Шиномонтаж Mitsubishi в МИ АВТО — оперативно, аккуратно, с документами и гарантией на выполненные работы.</p>';
}
