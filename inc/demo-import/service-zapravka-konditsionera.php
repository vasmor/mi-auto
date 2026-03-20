<?php
/**
 * Content installer: Заправка кондиционера (miauto_service).
 *
 * Triggered by visiting: /wp-admin/?miauto_fill_konditsioner=1
 * Safe to run multiple times — fills only empty fields.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function miauto_konditsioner_init() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_GET['miauto_fill_konditsioner'] ) || '1' !== $_GET['miauto_fill_konditsioner'] ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_fill_konditsioner' ) ) {
		add_action( 'admin_notices', function () {
			$url = wp_nonce_url( admin_url( '?miauto_fill_konditsioner=1' ), 'miauto_fill_konditsioner' );
			echo '<div class="notice notice-info"><p>MI-AUTO: Для заполнения Заправки кондиционера перейдите по ссылке: <a href="' . esc_url( $url ) . '">Заполнить контент Заправки кондиционера</a></p></div>';
		} );
		return;
	}

	$result = miauto_run_fill_konditsioner();

	if ( is_wp_error( $result ) ) {
		$msg = $result->get_error_message();
		add_action( 'admin_notices', function () use ( $msg ) {
			echo '<div class="notice notice-error"><p>MI-AUTO Заправка кондиционера Error: ' . esc_html( $msg ) . '</p></div>';
		} );
		return;
	}

	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-success"><p>MI-AUTO: Контент страницы «Заправка кондиционера» успешно заполнен! Заполнены только пустые поля.</p></div>';
	} );
}
add_action( 'admin_init', 'miauto_konditsioner_init' );

function miauto_run_fill_konditsioner() {
	set_time_limit( 300 );
	wp_raise_memory_limit( 'admin' );

	$post_id = miauto_konditsioner_get_post_id();
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$imgs = miauto_konditsioner_upload_images();

	miauto_konditsioner_fill_fields( $post_id, $imgs );

	if ( '' === get_post_field( 'post_content', $post_id ) ) {
		wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => miauto_konditsioner_seo_text(),
		) );
	}

	miauto_set_yoast_meta_if_empty( $post_id,
		'Заправка кондиционера Mitsubishi в Москве — фреон R134a и R1234yf | MI-AUTO',
		'Профессиональная заправка кондиционера Mitsubishi: диагностика утечек, вакуумирование, заправка фреоном. Холодит как новый. Гарантия результата.',
		'заправка кондиционера mitsubishi'
	);

	return true;
}

function miauto_konditsioner_get_post_id() {
	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'name'        => 'zapravka-konditsionera',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'title'       => 'Заправка кондиционера',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	return new WP_Error( 'post_not_found', 'Запись «Заправка кондиционера» не найдена. Сначала запустите demo-import.' );
}

function miauto_konditsioner_upload_images() {
	$files = array(
		'hero'  => 'hero-main.jpg',
		'sym_1' => 'sym-ne-holodaet.jpg',
		'sym_2' => 'sym-zapah.jpg',
		'sym_3' => 'sym-shum.jpg',
		'sym_4' => 'sym-ne-vkl.jpg',
		'sym_5' => 'sym-zimoy.jpg',
		'sym_6' => 'sym-dva-goda.jpg',
	);

	$ids = array();

	foreach ( $files as $key => $filename ) {
		$ids[ $key ] = miauto_konditsioner_upload_single( $filename );
	}

	return $ids;
}

function miauto_konditsioner_upload_single( $filename ) {
	$source_key = 'zapravka-konditsionera/' . $filename;

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

	$file_path = get_template_directory() . '/img/zapravka-konditsionera/' . $filename;

	if ( ! file_exists( $file_path ) ) {
		return 0;
	}

	$upload_dir      = wp_upload_dir();
	$upload_filename = 'konditsioner-' . $filename;
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

function miauto_konditsioner_get_fallback_id() {
	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'meta_key'    => '_miauto_demo_source',
		'meta_value'  => 'svc-ac.png',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	return ! empty( $existing ) ? $existing[0] : 0;
}

function miauto_konditsioner_fill_fields( $post_id, $imgs ) {
	$fallback = miauto_konditsioner_get_fallback_id();

	$hero  = $imgs['hero']  ?: $fallback;
	$sym_1 = $imgs['sym_1'] ?: $fallback;
	$sym_2 = $imgs['sym_2'] ?: $fallback;
	$sym_3 = $imgs['sym_3'] ?: $fallback;
	$sym_4 = $imgs['sym_4'] ?: $fallback;
	$sym_5 = $imgs['sym_5'] ?: $fallback;
	$sym_6 = $imgs['sym_6'] ?: $fallback;

	// ── SC-HERO ──────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_subtitle',
		'Заправка кондиционера Mitsubishi с диагностикой утечек — восстанавливаем охлаждение до заводского уровня'
	);

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_features', array(
		array( 'feature_text' => 'Проверка на утечки перед заправкой' ),
		array( 'feature_text' => 'Вакуумирование системы — обязательный этап' ),
		array( 'feature_text' => 'Фреон R134a и R1234yf в наличии' ),
		array( 'feature_text' => 'Гарантия на заправку' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_primary_text',   'Записаться на заправку кондиционера' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_secondary_text', 'Рассчитать стоимость' );

	if ( $hero ) {
		miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_image', $hero );
	}

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_stats', array(
		array( 'stat_value' => '5,0',    'stat_label' => 'Рейтинг на картах' ),
		array( 'stat_value' => '500+',   'stat_label' => 'Отзывов на картах' ),
		array( 'stat_value' => 'с 2005', 'stat_label' => 'Опыт работы' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_examples_title', 'Примеры работ по кондиционеру' );

	// ── SYMPTOMS ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_title',    'Когда нужна заправка кондиционера' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_subtitle', 'Не терпите жару — заправка кондиционера восстанавливает эффективность за 1 час' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cards', array(
		array(
			'sym_image'     => $sym_1,
			'symptom_title' => 'Кондиционер не охлаждает салон',
			'symptom_desc'  => 'Воздух из дефлекторов не холодный или холодит слабо — уровень фреона упал ниже нормы, требуется диагностика и заправка.',
		),
		array(
			'sym_image'     => $sym_2,
			'symptom_title' => 'Неприятный запах из вентиляции',
			'symptom_desc'  => 'Затхлый или кислый запах при включении AC — признак размножения бактерий на испарителе. Требуется антибактериальная обработка системы.',
		),
		array(
			'sym_image'     => $sym_3,
			'symptom_title' => 'Шум при включении компрессора',
			'symptom_desc'  => 'Стук или щелчки при включении кондиционера — работа компрессора на недостаточном количестве масла в системе.',
		),
		array(
			'sym_image'     => $sym_4,
			'symptom_title' => 'Компрессор не включается',
			'symptom_desc'  => 'Муфта компрессора не срабатывает — система защиты отключила компрессор из-за критически низкого давления фреона.',
		),
		array(
			'sym_image'     => $sym_5,
			'symptom_title' => 'Плохо размораживает стекло зимой',
			'symptom_desc'  => 'Кондиционер помогает осушить воздух и ускорить размораживание лобового стекла. При недостатке фреона эта функция снижается.',
		),
		array(
			'sym_image'     => $sym_6,
			'symptom_title' => 'Прошло более 2 лет',
			'symptom_desc'  => 'Даже без видимых проблем система теряет до 15% фреона в год. Профилактическая заправка каждые 2 года сохраняет эффективность кондиционера.',
		),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_text',     'Запишитесь — проверим систему и заправим кондиционер' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_btn_text', 'Записаться на заправку кондиционера' );

	// ── SVC-LIST ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_title', 'Какие работы выполняем' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_items', array(
		array(
			'svc_title' => 'Диагностика кондиционера',
			'svc_desc'  => 'Проверяем давление в системе, работу компрессора и электрической части — определяем причину неисправности перед заправкой.',
		),
		array(
			'svc_title' => 'Поиск утечек фреона',
			'svc_desc'  => 'Проверяем систему электронным течеискателем и ультрафиолетовым красителем — находим даже микроскопические утечки в трубках и фитингах.',
		),
		array(
			'svc_title' => 'Заправка фреоном',
			'svc_desc'  => 'Вакуумируем систему, заправляем фреон R134a или R1234yf строго по норме завода с добавлением компрессорного масла.',
		),
		array(
			'svc_title' => 'Антибактериальная обработка',
			'svc_desc'  => 'Обрабатываем испаритель антибактериальным составом — устраняем неприятный запах и предотвращаем образование грибка.',
		),
		array(
			'svc_title' => 'Замена фильтра-осушителя',
			'svc_desc'  => 'Рекомендуем замену при каждом вскрытии контура — абсорбент впитывает влагу и защищает компрессор от гидроудара.',
		),
		array(
			'svc_title' => 'Замена компрессора',
			'svc_desc'  => 'При неисправности компрессора кондиционера — дефектовка, подбор запчасти и замена с полной промывкой контура.',
		),
		array(
			'svc_title' => 'Ремонт трубок и радиатора конденсора',
			'svc_desc'  => 'Устраняем утечки в трубках пайкой или заменой, восстанавливаем или меняем конденсор при механических повреждениях.',
		),
		array(
			'svc_title' => 'Замена салонного фильтра',
			'svc_desc'  => 'При заправке проверяем и при необходимости меняем салонный фильтр — это влияет на качество воздуха и нагрузку на испаритель.',
		),
	) );

	// ── SC-PRICES ────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_title',    'Стоимость заправки кондиционера' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_subtitle', 'Называем точную цену до начала работ — после проверки системы' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_rows', array(
		array( 'service_name' => 'Диагностика кондиционера',                'sc_service_price' => 'от 500 ₽' ),
		array( 'service_name' => 'Заправка фреоном R134a (работа + фреон)', 'sc_service_price' => 'от 2 500 ₽' ),
		array( 'service_name' => 'Заправка фреоном R1234yf',                'sc_service_price' => 'от 4 000 ₽' ),
		array( 'service_name' => 'Антибактериальная обработка',             'sc_service_price' => 'от 800 ₽' ),
		array( 'service_name' => 'Поиск утечек (течеискатель + УФ)',        'sc_service_price' => 'от 1 000 ₽' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_heading',  'Точная стоимость — после проверки давления в системе' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_desc',     'Всё согласуем до начала работ' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_btn_text', 'Записаться на заправку кондиционера' );

	// ── WARRANTY ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_title',    'Гарантия и ответственность' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_subtitle', 'Не заправляем при необнаруженных утечках — только надёжный результат' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_cards', array(
		array(
			'war_svg'       => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 0L0 3.636V9.091C0 14.136 3.84 18.855 9 20c5.16-1.145 9-5.864 9-10.909V3.636L9 0zM7 14.546L3.773 11.611a.82.82 0 010-1.404.826.826 0 011.275-.002L7 11.973l5.948-5.407a.826.826 0 011.282.003.82.82 0 01-.003 1.406L7 14.546z" fill="white"/></svg>',
			'warranty_text' => 'Гарантия на заправку — 1 год',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M18 0H2C1 0 0 .9 0 2v3.01c0 .72.43 1.34 1 1.69V18c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V6.7c.57-.35 1-.97 1-1.69V2c0-1.1-1-.9-2 0zM13 12H7v-2h6v2zM18 5H2V2l16-.02V5z" fill="white"/></svg>',
			'warranty_text' => 'Проверка утечек перед каждой заправкой',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.613.256a.29.29 0 00-.29-.256H3.194C2.347 0 1.534.297.935.826.336 1.355 0 2.072 0 2.82v14.36c0 .748.336 1.465.935 1.994.6.529 1.412.826 2.259.826h11.612c.847 0 1.66-.297 2.258-.826.6-.529.936-1.246.936-1.994V7.074a.29.29 0 00-.29-.257h-5.226a.87.87 0 01-.612-.226.77.77 0 01-.259-.543V.256zM12.484 10.256c.231 0 .452.081.616.226a.77.77 0 01.255.543.77.77 0 01-.255.544.87.87 0 01-.616.226H5.516a.87.87 0 01-.616-.226.77.77 0 01-.255-.544c0-.208.092-.404.255-.543a.87.87 0 01.616-.226h6.968zm0 4.103c.231 0 .452.081.616.225a.77.77 0 01.255.544.77.77 0 01-.255.544.87.87 0 01-.616.225H5.516a.87.87 0 01-.616-.225.77.77 0 01-.255-.544c0-.208.092-.404.255-.544a.87.87 0 01.616-.225h6.968z" fill="white"/><path d="M13.356.59c0-.19.224-.31.39-.192.14.1.266.217.375.35l3.499 4.305c.079.098-.007.226-.144.226h-3.83a.29.29 0 01-.29-.257V.59z" fill="white"/></svg>',
			'warranty_text' => 'Документы: заказ-наряд, акт выполненных работ',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 20 18" xmlns="http://www.w3.org/2000/svg"><path d="M10 13.2c1.77 0 3.2-1.43 3.2-3.2 0-1.77-1.43-3.2-3.2-3.2-1.77 0-3.2 1.43-3.2 3.2 0 1.77 1.43 3.2 3.2 3.2zM7 0L5.17 2H2C.9 2 0 2.9 0 4v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2h-3.17L13 0H7zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z" fill="white"/></svg>',
			'warranty_text' => 'Замер давления в системе до и после заправки',
		),
	) );

	// ── FAQ ──────────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_heading', 'FAQ' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_entries', array(
		array(
			'faq_entry_active'   => true,
			'faq_entry_question' => 'Как часто нужно заправлять кондиционер?',
			'faq_entry_answer'   => 'В норме — раз в 2–3 года. Система теряет до 15% фреона в год через микроуплотнения. При исправной системе без утечек заправка раз в 2 года сохраняет оптимальную эффективность.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Нужно ли искать утечку перед заправкой?',
			'faq_entry_answer'   => 'Да — обязательно. Заправка без проверки утечек — трата денег. Если фреон вышел за год, значит есть утечка: сначала устраняем причину, затем заправляем.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Какой фреон подходит для моего Mitsubishi?',
			'faq_entry_answer'   => 'Модели до 2017 года — R134a. Часть новых моделей — R1234yf (экологически более чистый). Тип фреона указан на наклейке под капотом. Уточним при записи по VIN.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Почему кондиционер плохо холодит после заправки в другом месте?',
			'faq_entry_answer'   => 'Частые причины: заправка без вакуумирования (влага в системе), избыток или недостаток фреона, не добавлено компрессорное масло. Мы всегда соблюдаем полный протокол заправки.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Что такое вакуумирование и зачем оно нужно?',
			'faq_entry_answer'   => 'Вакуумирование — откачка воздуха и влаги из системы перед заправкой. Влага в системе образует кислоту, которая разрушает компрессор. Пропуск этого этапа — грубая ошибка.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Почему неприятный запах из кондиционера и как убрать?',
			'faq_entry_answer'   => 'На испарителе размножаются бактерии и грибок. Антибактериальная обработка ликвидирует запах. Профилактика — не выключать кондиционер за несколько минут до остановки, чтобы испаритель просох.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Можно ли ездить с неработающим кондиционером?',
			'faq_entry_answer'   => 'Да, если не доливать фреон при запрещающем давлении — компрессор защитит себя сам. Но при полной потере фреона резинотехнические уплотнения пересыхают и начинают течь сильнее.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Даёте ли гарантию на заправку?',
			'faq_entry_answer'   => 'Да, 1 год при условии отсутствия выявленных утечек до заправки или после устранения всех найденных утечек. Гарантийный талон и акт выдаём обязательно.',
		),
	) );
}

function miauto_konditsioner_seo_text() {
	return '<p>Технический центр МИ АВТО выполняет диагностику и заправку кондиционеров всех моделей Mitsubishi в Москве — с обязательной проверкой утечек и вакуумированием системы.</p>'
		. '<p>Работаем с фреоном R134a и R1234yf, добавляем компрессорное масло по норме завода — это продлевает ресурс компрессора и гарантирует стабильное охлаждение.</p>'
		. '<p>Не заправляем систему без поиска утечек: такая заправка будет неэффективна уже через несколько месяцев. Все найденные утечки устраняем перед заправкой.</p>'
		. '<p>Заправка кондиционера Mitsubishi в МИ АВТО — правильно, с гарантией 1 год и полным пакетом закрывающих документов.</p>';
}
