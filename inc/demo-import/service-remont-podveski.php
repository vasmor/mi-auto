<?php
/**
 * Content installer: Ремонт подвески (miauto_service).
 *
 * Triggered by visiting: /wp-admin/?miauto_fill_podveska=1
 * Safe to run multiple times — fills only empty fields.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function miauto_podveska_init() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_GET['miauto_fill_podveska'] ) || '1' !== $_GET['miauto_fill_podveska'] ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_fill_podveska' ) ) {
		add_action( 'admin_notices', function () {
			$url = wp_nonce_url( admin_url( '?miauto_fill_podveska=1' ), 'miauto_fill_podveska' );
			echo '<div class="notice notice-info"><p>MI-AUTO: Для заполнения Ремонта подвески перейдите по ссылке: <a href="' . esc_url( $url ) . '">Заполнить контент Ремонта подвески</a></p></div>';
		} );
		return;
	}

	$result = miauto_run_fill_podveska();

	if ( is_wp_error( $result ) ) {
		$msg = $result->get_error_message();
		add_action( 'admin_notices', function () use ( $msg ) {
			echo '<div class="notice notice-error"><p>MI-AUTO Ремонт подвески Error: ' . esc_html( $msg ) . '</p></div>';
		} );
		return;
	}

	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-success"><p>MI-AUTO: Контент страницы «Ремонт подвески» успешно заполнен! Заполнены только пустые поля.</p></div>';
	} );
}
add_action( 'admin_init', 'miauto_podveska_init' );

function miauto_run_fill_podveska() {
	set_time_limit( 300 );
	wp_raise_memory_limit( 'admin' );

	$post_id = miauto_podveska_get_post_id();
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$imgs = miauto_podveska_upload_images();

	miauto_podveska_fill_fields( $post_id, $imgs );

	if ( '' === get_post_field( 'post_content', $post_id ) ) {
		wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => miauto_podveska_seo_text(),
		) );
	}

	miauto_set_yoast_meta_if_empty( $post_id,
		'Ремонт подвески Mitsubishi в Москве — замена стоек, рычагов, сайлент-блоков | MI-AUTO',
		'Профессиональный ремонт ходовой части Mitsubishi: замена амортизаторов, рычагов, шаровых, сайлент-блоков. Гарантия 1 год. Диагностика бесплатно.',
		'ремонт подвески mitsubishi'
	);

	return true;
}

function miauto_podveska_get_post_id() {
	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'name'        => 'remont-podveski',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'title'       => 'Ремонт подвески',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	return new WP_Error( 'post_not_found', 'Запись «Ремонт подвески» не найдена. Сначала запустите demo-import.' );
}

function miauto_podveska_upload_images() {
	$files = array(
		'hero'  => 'hero-main.jpg',
		'sym_1' => 'sym-stuk.jpg',
		'sym_2' => 'sym-raskachka.jpg',
		'sym_3' => 'sym-kren.jpg',
		'sym_4' => 'sym-iznos.jpg',
		'sym_5' => 'sym-neust.jpg',
		'sym_6' => 'sym-skrip.jpg',
	);

	$ids = array();

	foreach ( $files as $key => $filename ) {
		$ids[ $key ] = miauto_podveska_upload_single( $filename );
	}

	return $ids;
}

function miauto_podveska_upload_single( $filename ) {
	$source_key = 'remont-podveski/' . $filename;

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

	$file_path = get_template_directory() . '/img/remont-podveski/' . $filename;

	if ( ! file_exists( $file_path ) ) {
		return 0;
	}

	$upload_dir      = wp_upload_dir();
	$upload_filename = 'podveski-' . $filename;
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

function miauto_podveska_get_fallback_id() {
	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'meta_key'    => '_miauto_demo_source',
		'meta_value'  => 'svc-suspension.png',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	return ! empty( $existing ) ? $existing[0] : 0;
}

function miauto_podveska_fill_fields( $post_id, $imgs ) {
	$fallback = miauto_podveska_get_fallback_id();

	$hero  = $imgs['hero']  ?: $fallback;
	$sym_1 = $imgs['sym_1'] ?: $fallback;
	$sym_2 = $imgs['sym_2'] ?: $fallback;
	$sym_3 = $imgs['sym_3'] ?: $fallback;
	$sym_4 = $imgs['sym_4'] ?: $fallback;
	$sym_5 = $imgs['sym_5'] ?: $fallback;
	$sym_6 = $imgs['sym_6'] ?: $fallback;

	// ── SC-HERO ──────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_subtitle',
		'Диагностика и ремонт ходовой части Mitsubishi — устраняем стуки, раскачку и крен с гарантией 1 год'
	);

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_features', array(
		array( 'feature_text' => 'Диагностика на подъёмнике бесплатно при заказе ремонта' ),
		array( 'feature_text' => 'Оригинальные и OEM запчасти в наличии' ),
		array( 'feature_text' => 'Замена сайлент-блоков без замены рычага в сборе' ),
		array( 'feature_text' => 'Гарантия на работы и запчасти' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_primary_text',   'Записаться на диагностику подвески' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_secondary_text', 'Рассчитать стоимость' );

	if ( $hero ) {
		miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_image', $hero );
	}

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_stats', array(
		array( 'stat_value' => '5,0',    'stat_label' => 'Рейтинг на картах' ),
		array( 'stat_value' => '500+',   'stat_label' => 'Отзывов на картах' ),
		array( 'stat_value' => 'с 2005', 'stat_label' => 'Опыт работы' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_examples_title', 'Примеры работ по ремонту подвески' );

	// ── SYMPTOMS ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_title',    'Когда нужен ремонт подвески' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_subtitle', 'Не откладывайте — изношенная подвеска ускоряет износ шин и увеличивает тормозной путь' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cards', array(
		array(
			'sym_image'     => $sym_1,
			'symptom_title' => 'Стуки при езде по неровностям',
			'symptom_desc'  => 'Характерный стук или удар при проезде ям и лежачих полицейских — признак износа сайлент-блоков, шаровых опор или стоек стабилизатора.',
		),
		array(
			'sym_image'     => $sym_2,
			'symptom_title' => 'Раскачка кузова',
			'symptom_desc'  => 'Машина долго раскачивается после проезда неровности — амортизаторы не справляются с гашением колебаний, требуется замена.',
		),
		array(
			'sym_image'     => $sym_3,
			'symptom_title' => 'Сильный крен в поворотах',
			'symptom_desc'  => 'Автомобиль кренится как «корабль» — изношены амортизаторы или сломан стабилизатор поперечной устойчивости.',
		),
		array(
			'sym_image'     => $sym_4,
			'symptom_title' => 'Неравномерный износ шин',
			'symptom_desc'  => 'Шина стёрта по краям или посередине — следствие неисправных элементов подвески, нарушающих геометрию установки колёс.',
		),
		array(
			'sym_image'     => $sym_5,
			'symptom_title' => 'Машина плохо держит курс',
			'symptom_desc'  => 'Автомобиль реагирует на колею, рыскает на трассе — люфт в шаровых опорах или рычагах нарушает кинематику подвески.',
		),
		array(
			'sym_image'     => $sym_6,
			'symptom_title' => 'Скрипы и шорохи при движении',
			'symptom_desc'  => 'Скрип при повороте или трогании — засохший или разрушенный резинометаллический шарнир, требует замены сайлент-блока или шаровой.',
		),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_text',     'Запишитесь — проверим подвеску и устраним неисправность' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_btn_text', 'Записаться на диагностику подвески' );

	// ── SVC-LIST ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_title', 'Какие работы выполняем' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_items', array(
		array(
			'svc_title' => 'Диагностика подвески',
			'svc_desc'  => 'Осматриваем все элементы на подъёмнике: рычаги, шаровые, сайлент-блоки, амортизаторы, стабилизаторы — выдаём письменное заключение.',
		),
		array(
			'svc_title' => 'Замена амортизаторов',
			'svc_desc'  => 'Устанавливаем парно по оси. Используем оригинальные амортизаторы Mitsubishi или сертифицированные аналоги KYB, Bilstein, Monroe.',
		),
		array(
			'svc_title' => 'Замена шаровых опор',
			'svc_desc'  => 'Меняем шаровые опоры отдельно или в составе рычага — по состоянию детали и экономической целесообразности.',
		),
		array(
			'svc_title' => 'Замена сайлент-блоков',
			'svc_desc'  => 'Выпрессовываем изношенные сайлент-блоки и запрессовываем новые — без замены рычага в сборе там, где это технически возможно.',
		),
		array(
			'svc_title' => 'Замена стоек и втулок стабилизатора',
			'svc_desc'  => 'Устраняем стук стабилизатора поперечной устойчивости — быстрая и недорогая замена стоек и резинометаллических втулок.',
		),
		array(
			'svc_title' => 'Замена пружин подвески',
			'svc_desc'  => 'Меняем просевшие или лопнувшие пружины. После замены обязательно выполняем сход-развал для восстановления правильной геометрии.',
		),
		array(
			'svc_title' => 'Замена ступичных подшипников',
			'svc_desc'  => 'Диагностируем гул и хруст при движении, заменяем ступичный подшипник или ступицу в сборе с проверкой биения.',
		),
		array(
			'svc_title' => 'Замена ШРУСов и приводных валов',
			'svc_desc'  => 'Заменяем пыльники и шарниры равных угловых скоростей — устраняем хруст при поворотах и вибрацию на разгоне.',
		),
	) );

	// ── SC-PRICES ────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_title',    'Стоимость ремонта подвески' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_subtitle', 'Точная стоимость — после осмотра на подъёмнике' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_rows', array(
		array( 'service_name' => 'Диагностика подвески на подъёмнике',    'sc_service_price' => 'от 700 ₽' ),
		array( 'service_name' => 'Замена амортизатора (1 шт, работа)',    'sc_service_price' => 'от 1 500 ₽' ),
		array( 'service_name' => 'Замена шаровой опоры (1 шт, работа)',  'sc_service_price' => 'от 1 200 ₽' ),
		array( 'service_name' => 'Замена сайлент-блока (1 шт, работа)',  'sc_service_price' => 'от 800 ₽' ),
		array( 'service_name' => 'Замена стойки стабилизатора (1 шт)',   'sc_service_price' => 'от 600 ₽' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_heading',  'Итоговая стоимость — после осмотра и согласования' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_desc',     'Все работы согласуем до начала ремонта' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_btn_text', 'Записаться на диагностику подвески' );

	// ── WARRANTY ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_title',    'Гарантия и ответственность' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_subtitle', 'После ремонта подвески обязательно выполняем сход-развал' );

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
			'warranty_text' => 'Фото дефектных деталей до и после замены',
		),
	) );

	// ── FAQ ──────────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_heading', 'FAQ' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_entries', array(
		array(
			'faq_entry_active'   => true,
			'faq_entry_question' => 'Как понять, что амортизаторы пора менять?',
			'faq_entry_answer'   => 'Основные признаки: раскачка кузова после неровностей, «клевок» при торможении, масляные подтёки на амортизаторе. Срок службы — 60 000–80 000 км в зависимости от условий эксплуатации.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Можно ли менять амортизатор только с одной стороны?',
			'faq_entry_answer'   => 'Не рекомендуем — разные по жёсткости амортизаторы на одной оси дают неравномерное торможение и крен. Меняем попарно по оси.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Что такое сайлент-блок и зачем его менять?',
			'faq_entry_answer'   => 'Сайлент-блок — резинометаллический шарнир, гасящий вибрации и удары между рычагом подвески и кузовом. Изношенный сайлент-блок вызывает стуки, рыскание и ускоренный износ шин.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Нужен ли сход-развал после ремонта подвески?',
			'faq_entry_answer'   => 'Да, обязательно. Замена рычагов, шаровых, сайлент-блоков и пружин меняет геометрию подвески. Без последующего сход-развала шины будут изнашиваться неравномерно.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Что опасней — стучит подвеска или гудит подшипник?',
			'faq_entry_answer'   => 'Оба симптома требуют срочного ремонта. Разрушившийся подшипник ступицы может привести к потере колеса на ходу — это критическая неисправность.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Почему лучше заменить сайлент-блок, а не рычаг в сборе?',
			'faq_entry_answer'   => 'Сам рычаг обычно служит весь срок жизни автомобиля — изнашиваются только резинометаллические вставки. Замена отдельно сайлент-блока в 2–4 раза дешевле замены рычага в сборе.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Хрустит при поворотах — это ШРУС?',
			'faq_entry_answer'   => 'Скорее всего да — хруст при повороте под нагрузкой характерен для изношенного наружного ШРУСа. Часто помогает замена пыльника с набивкой смазки, если тело шарнира цело.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Даёте ли гарантию на ремонт подвески?',
			'faq_entry_answer'   => 'Да, 1 год на все виды работ. На запчасти — гарантия поставщика. Выдаём гарантийный талон и акт с перечнем замененных деталей.',
		),
	) );
}

function miauto_podveska_seo_text() {
	return '<p>Технический центр МИ АВТО выполняет полный цикл диагностики и ремонта подвески автомобилей Mitsubishi в Москве — от стоек стабилизатора до ступичных подшипников и ШРУСов.</p>'
		. '<p>Все работы проводим на подъёмнике с применением специального инструмента: съёмников шаровых опор, пресса для выпрессовки сайлент-блоков, динамометрических ключей.</p>'
		. '<p>После ремонта подвески в обязательном порядке выполняем сход-развал — это гарантирует правильную геометрию и равномерный износ шин.</p>'
		. '<p>Ремонт подвески Mitsubishi в МИ АВТО — диагностика, ремонт, гарантия и закрывающие документы в одном месте.</p>';
}
