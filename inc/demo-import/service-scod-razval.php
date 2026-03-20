<?php
/**
 * Content installer: Сход-развал (miauto_service).
 *
 * Triggered by visiting: /wp-admin/?miauto_fill_scod_razval=1
 * Safe to run multiple times — fills only empty fields.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function miauto_scod_razval_init() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_GET['miauto_fill_scod_razval'] ) || '1' !== $_GET['miauto_fill_scod_razval'] ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_fill_scod_razval' ) ) {
		add_action( 'admin_notices', function () {
			$url = wp_nonce_url( admin_url( '?miauto_fill_scod_razval=1' ), 'miauto_fill_scod_razval' );
			echo '<div class="notice notice-info"><p>MI-AUTO: Для заполнения Сход-развала перейдите по ссылке: <a href="' . esc_url( $url ) . '">Заполнить контент Сход-развала</a></p></div>';
		} );
		return;
	}

	$result = miauto_run_fill_scod_razval();

	if ( is_wp_error( $result ) ) {
		$msg = $result->get_error_message();
		add_action( 'admin_notices', function () use ( $msg ) {
			echo '<div class="notice notice-error"><p>MI-AUTO Сход-развал Error: ' . esc_html( $msg ) . '</p></div>';
		} );
		return;
	}

	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-success"><p>MI-AUTO: Контент страницы «Сход-развал» успешно заполнен! Заполнены только пустые поля.</p></div>';
	} );
}
add_action( 'admin_init', 'miauto_scod_razval_init' );

function miauto_run_fill_scod_razval() {
	set_time_limit( 300 );
	wp_raise_memory_limit( 'admin' );

	$post_id = miauto_scod_razval_get_post_id();
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$imgs = miauto_scod_razval_upload_images();

	miauto_scod_razval_fill_fields( $post_id, $imgs );

	if ( '' === get_post_field( 'post_content', $post_id ) ) {
		wp_update_post( array(
			'ID'           => $post_id,
			'post_content' => miauto_scod_razval_seo_text(),
		) );
	}

	miauto_set_yoast_meta_if_empty( $post_id,
		'Сход-развал Mitsubishi в Москве — регулировка углов установки колёс | MI-AUTO',
		'Профессиональный сход-развал Mitsubishi на 3D-стенде: устраняем увод, вибрацию руля и неравномерный износ шин. Гарантия результата. Запись онлайн.',
		'сход-развал mitsubishi'
	);

	return true;
}

function miauto_scod_razval_get_post_id() {
	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'name'        => 'scod-razval',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	$posts = get_posts( array(
		'post_type'   => 'miauto_service',
		'title'       => 'Сход-развал',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	if ( ! empty( $posts ) ) {
		return $posts[0];
	}

	return new WP_Error( 'post_not_found', 'Запись «Сход-развал» не найдена. Сначала запустите demo-import.' );
}

function miauto_scod_razval_upload_images() {
	$files = array(
		'hero'  => 'hero-main.jpg',
		'sym_1' => 'sym-uvod.jpg',
		'sym_2' => 'sym-iznos.jpg',
		'sym_3' => 'sym-vibraciya.jpg',
		'sym_4' => 'sym-vozvrat.jpg',
		'sym_5' => 'sym-skrip.jpg',
		'sym_6' => 'sym-podveska.jpg',
	);

	$ids = array();

	foreach ( $files as $key => $filename ) {
		$ids[ $key ] = miauto_scod_razval_upload_single( $filename );
	}

	return $ids;
}

function miauto_scod_razval_upload_single( $filename ) {
	$source_key = 'scod-razval/' . $filename;

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

	$file_path = get_template_directory() . '/img/scod-razval/' . $filename;

	if ( ! file_exists( $file_path ) ) {
		return 0;
	}

	$upload_dir      = wp_upload_dir();
	$upload_filename = 'scod-razval-' . $filename;
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

function miauto_scod_razval_get_fallback_id() {
	$existing = get_posts( array(
		'post_type'   => 'attachment',
		'meta_key'    => '_miauto_demo_source',
		'meta_value'  => 'svc-wheel-alignment.png',
		'numberposts' => 1,
		'fields'      => 'ids',
	) );

	return ! empty( $existing ) ? $existing[0] : 0;
}

function miauto_scod_razval_fill_fields( $post_id, $imgs ) {
	$fallback = miauto_scod_razval_get_fallback_id();

	$hero  = $imgs['hero']  ?: $fallback;
	$sym_1 = $imgs['sym_1'] ?: $fallback;
	$sym_2 = $imgs['sym_2'] ?: $fallback;
	$sym_3 = $imgs['sym_3'] ?: $fallback;
	$sym_4 = $imgs['sym_4'] ?: $fallback;
	$sym_5 = $imgs['sym_5'] ?: $fallback;
	$sym_6 = $imgs['sym_6'] ?: $fallback;

	// ── SC-HERO ──────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_subtitle',
		'Точная регулировка углов установки колёс Mitsubishi на 3D-стенде — устраняем увод, вибрацию и неравномерный износ шин'
	);

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_features', array(
		array( 'feature_text' => '3D-стенд с точностью до угловой минуты' ),
		array( 'feature_text' => 'Регулировка по заводским допускам Mitsubishi' ),
		array( 'feature_text' => 'Работа без записи — от 30 минут' ),
		array( 'feature_text' => 'Гарантия на регулировку' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_primary_text',   'Записаться на сход-развал' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_secondary_text', 'Рассчитать стоимость' );

	if ( $hero ) {
		miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_image', $hero );
	}

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_stats', array(
		array( 'stat_value' => '5,0',    'stat_label' => 'Рейтинг на картах' ),
		array( 'stat_value' => '500+',   'stat_label' => 'Отзывов на картах' ),
		array( 'stat_value' => 'с 2005', 'stat_label' => 'Опыт работы' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_examples_title', 'Примеры работ по сход-развалу' );

	// ── SYMPTOMS ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_title',    'Когда нужен сход-развал' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_subtitle', 'Своевременная регулировка экономит шины и топливо — не откладывайте проверку' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cards', array(
		array(
			'sym_image'     => $sym_1,
			'symptom_title' => 'Автомобиль уводит в сторону',
			'symptom_desc'  => 'Руль нужно постоянно подправлять — машина не едет прямо на ровной дороге без удержания рулевого колеса.',
		),
		array(
			'sym_image'     => $sym_2,
			'symptom_title' => 'Неравномерный износ шин',
			'symptom_desc'  => 'Протектор стирается с одной стороны или только по краям — явный признак нарушенных углов установки колёс.',
		),
		array(
			'sym_image'     => $sym_3,
			'symptom_title' => 'Вибрация руля на скорости',
			'symptom_desc'  => 'Руль бьёт на скорости 80–120 км/ч — возможен дисбаланс или нарушение схождения передних колёс.',
		),
		array(
			'sym_image'     => $sym_4,
			'symptom_title' => 'Руль не возвращается в центр',
			'symptom_desc'  => 'После поворота рулевое колесо не стремится вернуться в прямое положение — нарушен кастер или развал.',
		),
		array(
			'sym_image'     => $sym_5,
			'symptom_title' => 'Скрипы при повороте',
			'symptom_desc'  => 'Скрип или шорох при вывороте руля — признак износа ШРУСа или изменения углов установки на крайних значениях.',
		),
		array(
			'sym_image'     => $sym_6,
			'symptom_title' => 'После ремонта подвески',
			'symptom_desc'  => 'Замена рычагов, наконечников, стоек или дисков — обязательный повод проверить и отрегулировать углы установки колёс.',
		),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_text',     'Запишитесь — проверим углы и устраним отклонения' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_btn_text', 'Записаться на сход-развал' );

	// ── SVC-LIST ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_title', 'Какие работы выполняем' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_items', array(
		array(
			'svc_title' => 'Диагностика углов установки колёс',
			'svc_desc'  => 'Измеряем все углы — схождение, развал, кастер — на калиброванном 3D-стенде с выводом отчёта до и после регулировки.',
		),
		array(
			'svc_title' => 'Регулировка схождения',
			'svc_desc'  => 'Выставляем схождение передних и задних колёс по заводским допускам Mitsubishi с точностью до 1 угловой минуты.',
		),
		array(
			'svc_title' => 'Регулировка развала',
			'svc_desc'  => 'Корректируем развал через эксцентриковые болты или регулировочные шайбы — там, где конструкция автомобиля это позволяет.',
		),
		array(
			'svc_title' => 'Регулировка кастера',
			'svc_desc'  => 'Восстанавливаем угол наклона поворотной оси для правильного самовозврата руля и устойчивости на прямой.',
		),
		array(
			'svc_title' => 'Балансировка колёс',
			'svc_desc'  => 'Устраняем дисбаланс грузиками на станке — ликвидируем вибрацию руля и кузова на скорости.',
		),
		array(
			'svc_title' => 'Замена наконечников рулевых тяг',
			'svc_desc'  => 'Заменяем изношенные наконечники — частая причина невозможности выставить схождение в норму.',
		),
		array(
			'svc_title' => 'Замена шаровых опор',
			'svc_desc'  => 'Восстанавливаем нормальное положение ступицы и устраняем стуки после замены шаровых опор.',
		),
		array(
			'svc_title' => 'Комплексная проверка ходовой',
			'svc_desc'  => 'При сход-развале проверяем состояние рулевых тяг, шаровых, ступичных подшипников и шин — докладываем об износе.',
		),
	) );

	// ── SC-PRICES ────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_title',    'Стоимость сход-развала' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_subtitle', 'Вы видите итоговую цену до начала работ — без скрытых доплат' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_rows', array(
		array( 'service_name' => 'Диагностика углов (без регулировки)',   'sc_service_price' => 'от 500 ₽' ),
		array( 'service_name' => 'Сход-развал 2D (передняя ось)',         'sc_service_price' => 'от 1 000 ₽' ),
		array( 'service_name' => 'Сход-развал 3D (все 4 колеса)',         'sc_service_price' => 'от 2 000 ₽' ),
		array( 'service_name' => 'Балансировка 1 колеса',                 'sc_service_price' => 'от 300 ₽' ),
		array( 'service_name' => 'Замена наконечника рулевой тяги (1 шт)', 'sc_service_price' => 'от 800 ₽' ),
	) );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_heading',  'Точная стоимость — после диагностики углов' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_desc',     'Согласуем всё до начала работ' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_btn_text', 'Записаться на сход-развал' );

	// ── WARRANTY ─────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_title',    'Гарантия и ответственность' );
	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_subtitle', 'Выдаём распечатку с углами до и после — прозрачный результат' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_cards', array(
		array(
			'war_svg'       => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 0L0 3.636V9.091C0 14.136 3.84 18.855 9 20c5.16-1.145 9-5.864 9-10.909V3.636L9 0zM7 14.546L3.773 11.611a.82.82 0 010-1.404.826.826 0 011.275-.002L7 11.973l5.948-5.407a.826.826 0 011.282.003.82.82 0 01-.003 1.406L7 14.546z" fill="white"/></svg>',
			'warranty_text' => 'Гарантия на регулировку — 3 месяца или 10 000 км',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M18 0H2C1 0 0 .9 0 2v3.01c0 .72.43 1.34 1 1.69V18c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V6.7c.57-.35 1-.97 1-1.69V2c0-1.1-1-.9-2 0zM13 12H7v-2h6v2zM18 5H2V2l16-.02V5z" fill="white"/></svg>',
			'warranty_text' => 'Протокол углов до и после регулировки',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.613.256a.29.29 0 00-.29-.256H3.194C2.347 0 1.534.297.935.826.336 1.355 0 2.072 0 2.82v14.36c0 .748.336 1.465.935 1.994.6.529 1.412.826 2.259.826h11.612c.847 0 1.66-.297 2.258-.826.6-.529.936-1.246.936-1.994V7.074a.29.29 0 00-.29-.257h-5.226a.87.87 0 01-.612-.226.77.77 0 01-.259-.543V.256zM12.484 10.256c.231 0 .452.081.616.226a.77.77 0 01.255.543.77.77 0 01-.255.544.87.87 0 01-.616.226H5.516a.87.87 0 01-.616-.226.77.77 0 01-.255-.544c0-.208.092-.404.255-.543a.87.87 0 01.616-.226h6.968zm0 4.103c.231 0 .452.081.616.225a.77.77 0 01.255.544.77.77 0 01-.255.544.87.87 0 01-.616.225H5.516a.87.87 0 01-.616-.225.77.77 0 01-.255-.544c0-.208.092-.404.255-.544a.87.87 0 01.616-.225h6.968z" fill="white"/><path d="M13.356.59c0-.19.224-.31.39-.192.14.1.266.217.375.35l3.499 4.305c.079.098-.007.226-.144.226h-3.83a.29.29 0 01-.29-.257V.59z" fill="white"/></svg>',
			'warranty_text' => 'Документы: заказ-наряд, акт выполненных работ',
		),
		array(
			'war_svg'       => '<svg viewBox="0 0 20 18" xmlns="http://www.w3.org/2000/svg"><path d="M10 13.2c1.77 0 3.2-1.43 3.2-3.2 0-1.77-1.43-3.2-3.2-3.2-1.77 0-3.2 1.43-3.2 3.2 0 1.77 1.43 3.2 3.2 3.2zM7 0L5.17 2H2C.9 2 0 2.9 0 4v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2h-3.17L13 0H7zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z" fill="white"/></svg>',
			'warranty_text' => 'Повторная проверка бесплатно в течение гарантийного срока',
		),
	) );

	// ── FAQ ──────────────────────────────────────────────────────────

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_heading', 'FAQ' );

	miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_entries', array(
		array(
			'faq_entry_active'   => true,
			'faq_entry_question' => 'Как часто нужно делать сход-развал?',
			'faq_entry_answer'   => 'Рекомендуем каждые 15 000–20 000 км или раз в год. Обязательно — после замены любых элементов подвески, рулевого управления или при ударе о бордюр.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Сколько времени занимает сход-развал?',
			'faq_entry_answer'   => 'Диагностика и регулировка на 3D-стенде занимает 30–60 минут. Если требуется замена изношенных деталей — дополнительное время на дефектовку и ремонт.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Нужна ли балансировка при сход-развале?',
			'faq_entry_answer'   => 'Это разные процедуры. Балансировка устраняет вибрацию от дисбаланса колеса, сход-развал регулирует углы установки. При вибрации руля рекомендуем сделать оба.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Почему после сход-развала машину снова уводит?',
			'faq_entry_answer'   => 'Причина — изношенные наконечники рулевых тяг, шаровые опоры или сайлент-блоки, которые не держат регулировку. Сначала нужно устранить люфт в этих соединениях.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Можно ли сделать сход-развал без записи?',
			'faq_entry_answer'   => 'Да, принимаем без записи при наличии свободного стенда. Чтобы не ждать, рекомендуем позвонить заранее — особенно в утренние и вечерние часы.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Влияет ли давление в шинах на результат?',
			'faq_entry_answer'   => 'Да. Перед регулировкой обязательно доводим давление до нормы по заводскому регламенту — неправильное давление искажает показания стенда.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Какой стенд используете?',
			'faq_entry_answer'   => 'Работаем на 3D-стенде с камерами — он измеряет все четыре угла одновременно и учитывает геометрию кузова. Точность — до 1 угловой минуты.',
		),
		array(
			'faq_entry_active'   => false,
			'faq_entry_question' => 'Дают ли гарантию на сход-развал?',
			'faq_entry_answer'   => 'Даём гарантию 3 месяца или 10 000 км при условии исправности элементов подвески и рулевого. Если машину снова поведёт в гарантийный период — проверим и устраним бесплатно.',
		),
	) );
}

function miauto_scod_razval_seo_text() {
	return '<p>Технический центр МИ АВТО выполняет сход-развал автомобилей Mitsubishi в Москве на профессиональном 3D-стенде с точностью до угловой минуты.</p>'
		. '<p>Правильно выставленные углы установки колёс снижают износ шин, уменьшают расход топлива и обеспечивают предсказуемое поведение автомобиля на дороге.</p>'
		. '<p>После регулировки выдаём протокол с фактическими значениями углов до и после — вы видите конкретный результат работы.</p>'
		. '<p>Сход-развал Mitsubishi в МИ АВТО — быстро, точно, с гарантией и полным пакетом закрывающих документов.</p>';
}
