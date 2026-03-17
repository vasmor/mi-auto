<?php
/**
 * Demo Content Installer.
 *
 * Triggered by visiting: /wp-admin/?miauto_setup=1
 * One-time execution, stores flag in wp_options.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hook into admin_init to check for the setup GET parameter.
 */
function miauto_demo_import_init() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( empty( $_GET['miauto_setup'] ) || '1' !== $_GET['miauto_setup'] ) {
        return;
    }

    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_demo_import' ) ) {
        add_action( 'admin_notices', function () {
            $url = wp_nonce_url( admin_url( '?miauto_setup=1' ), 'miauto_demo_import' );
            echo '<div class="notice notice-info"><p>MI-AUTO: Для запуска импорта перейдите по ссылке: <a href="' . esc_url( $url ) . '">Установить демо-контент</a></p></div>';
        } );
        return;
    }

    if ( get_option( 'miauto_demo_imported' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-warning"><p>MI-AUTO: Демо-контент уже был установлен ранее. Для повторной установки удалите опцию <code>miauto_demo_imported</code> из БД.</p></div>';
        } );
        return;
    }

    // Run the import.
    $result = miauto_run_demo_import();

    if ( is_wp_error( $result ) ) {
        $msg = $result->get_error_message();
        add_action( 'admin_notices', function () use ( $msg ) {
            echo '<div class="notice notice-error"><p>MI-AUTO Demo Import Error: ' . esc_html( $msg ) . '</p></div>';
        } );
        return;
    }

    update_option( 'miauto_demo_imported', true );

    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-success"><p>MI-AUTO: Демо-контент успешно установлен! Все страницы, записи, CPT и настройки созданы.</p></div>';
    } );
}
add_action( 'admin_init', 'miauto_demo_import_init' );

/**
 * Main import runner.
 *
 * @return true|WP_Error
 */
function miauto_run_demo_import() {
    // Increase limits for import.
    @set_time_limit( 300 );
    wp_raise_memory_limit( 'admin' );

    // 1. Upload images.
    $images = miauto_demo_upload_images();
    if ( is_wp_error( $images ) ) {
        return $images;
    }

    // 2. Create CF7 form.
    $cf7_id = miauto_demo_create_cf7_form();

    // 3. Set theme options.
    miauto_demo_set_theme_options( $images, $cf7_id );

    // 4. Create pages.
    $pages = miauto_demo_create_pages();

    // 5. Set homepage and blog page.
    miauto_demo_set_reading_settings( $pages );

    // 6. Create CPT posts.
    $cpt = miauto_demo_create_cpt_posts( $images );

    // 7. Set page meta fields.
    miauto_demo_set_page_meta( $pages, $images );

    // 8. Create blog posts.
    miauto_demo_create_blog_posts( $images );

    // 9. Create nav menu.
    miauto_demo_create_menu( $pages );

    // 10. Set permalinks.
    miauto_demo_set_permalinks();

    return true;
}

// ─── Helper: Upload a single image ──────────────────────────────────

/**
 * Upload image from theme img/ folder to media library.
 *
 * @param string $filename Filename in img/ dir.
 * @return int|WP_Error Attachment ID.
 */
function miauto_demo_upload_image( $filename ) {
    $file_path = MIAUTO_DIR . '/img/' . $filename;

    if ( ! file_exists( $file_path ) ) {
        return new WP_Error( 'file_missing', 'Image not found: ' . $filename );
    }

    // Check if already uploaded.
    $existing = get_posts( array(
        'post_type'   => 'attachment',
        'meta_key'    => '_miauto_demo_source',
        'meta_value'  => $filename,
        'numberposts' => 1,
        'fields'      => 'ids',
    ) );

    if ( ! empty( $existing ) ) {
        return $existing[0];
    }

    $upload_dir = wp_upload_dir();
    $target     = $upload_dir['path'] . '/' . $filename;

    copy( $file_path, $target );

    $filetype   = wp_check_filetype( $filename );
    $attachment = array(
        'guid'           => $upload_dir['url'] . '/' . $filename,
        'post_mime_type' => $filetype['type'],
        'post_title'     => pathinfo( $filename, PATHINFO_FILENAME ),
        'post_content'   => '',
        'post_status'    => 'inherit',
    );

    $attach_id = wp_insert_attachment( $attachment, $target );

    if ( is_wp_error( $attach_id ) ) {
        return $attach_id;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $metadata = wp_generate_attachment_metadata( $attach_id, $target );
    wp_update_attachment_metadata( $attach_id, $metadata );

    update_post_meta( $attach_id, '_miauto_demo_source', $filename );

    return $attach_id;
}

// ─── 1. Upload all images ───────────────────────────────────────────

function miauto_demo_upload_images() {
    $files = array(
        'hero-bg',
        'about-us',
        'article-1',
        'article-2',
        'car-asx',
        'car-l200',
        'car-lancer-10',
        'car-outlander-3',
        'car-outlander-new',
        'car-outlander-xl',
        'car-pajero-sport-2',
        'car-pajero-sport-3',
        'contacts-decoration',
        'contacts-map',
        'partner-1',
        'partner-2',
        'partner-3',
        'partner-4',
        'svc-air-conditioning',
        'svc-auto-electric',
        'svc-brake-system',
        'svc-diagnostics',
        'svc-engine',
        'svc-exhaust',
        'svc-steering',
        'svc-suspension',
        'svc-timing-belt',
        'svc-tire-service',
        'svc-wheel-alignment',
    );

    $images = array();
    foreach ( $files as $key ) {
        $ext      = ( 'hero-bg' === $key ) ? 'jpg' : 'png';
        $filename = $key . '.' . $ext;
        $id       = miauto_demo_upload_image( $filename );

        if ( is_wp_error( $id ) ) {
            return $id;
        }

        $images[ $key ] = $id;
    }

    return $images;
}

// ─── 2. Create CF7 form ─────────────────────────────────────────────

function miauto_demo_create_cf7_form() {
    if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
        return 0;
    }

    $existing = get_posts( array(
        'post_type'   => 'wpcf7_contact_form',
        'meta_key'    => '_miauto_demo_form',
        'meta_value'  => '1',
        'numberposts' => 1,
        'fields'      => 'ids',
    ) );

    if ( ! empty( $existing ) ) {
        return $existing[0];
    }

    $form = WPCF7_ContactForm::get_template();
    $form->set_title( 'Запись на обслуживание' );
    $form->set_properties( array(
        'form' => '<div class="form-section__fields">'
            . '[text* your-name placeholder "Ваше имя"]'
            . '[tel* your-phone placeholder "+7 (___) ___-__-__"]'
            . '[select your-service "Выберите услугу" "Техническое обслуживание" "Ремонт двигателя" "Ремонт подвески" "Диагностика" "Другое"]'
            . '</div>'
            . '[submit "Записаться"]',
        'mail' => array(
            'active'            => true,
            'subject'           => 'MI-AUTO: Новая заявка от [your-name]',
            'sender'            => '[your-name] <wordpress@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '>',
            'recipient'         => get_option( 'admin_email' ),
            'body'              => "Имя: [your-name]\nТелефон: [your-phone]\nУслуга: [your-service]",
            'additional_headers' => '',
            'attachments'       => '',
            'use_html'          => false,
        ),
    ) );
    $form->save();

    update_post_meta( $form->id(), '_miauto_demo_form', '1' );

    return $form->id();
}

// ─── 3. Theme Options ───────────────────────────────────────────────

function miauto_demo_set_theme_options( $images, $cf7_id ) {
    if ( ! function_exists( 'carbon_set_theme_option' ) ) {
        return;
    }

    // Top Bar.
    carbon_set_theme_option( 'miauto_top_bar_enabled', true );
    carbon_set_theme_option( 'miauto_top_bar_label', 'Только до 24 февраля' );
    carbon_set_theme_option( 'miauto_top_bar_text', 'Скидка -50% на Сход-развал. Записывайтесь прямо сейчас по телефону и получите скидку!' );

    // Header.
    carbon_set_theme_option( 'miauto_logo_text', 'MI-AUTO.ru' );
    carbon_set_theme_option( 'miauto_slogan', 'Ремонт Mitsubishi всех моделей в одном центре' );
    carbon_set_theme_option( 'miauto_online_text', 'Задайте вопрос, мы сейчас онлайн' );
    carbon_set_theme_option( 'miauto_callback_text', 'Обратный звонок' );

    // Contacts.
    carbon_set_theme_option( 'miauto_address', 'г. Москва, ул. Остаповский проезд 1, д. 10, стр. 1' );
    carbon_set_theme_option( 'miauto_hours', 'Понедельник-Воскресенье с 10:00 до 21:00' );
    carbon_set_theme_option( 'miauto_hours_short', 'Пн-Вс с 10:00 до 21:00' );
    carbon_set_theme_option( 'miauto_email', 'info@mi-auto.ru' );

    carbon_set_theme_option( 'miauto_phones', array(
        array(
            'number' => '+7 (926) 338-39-29',
            'raw'    => '+79263383929',
        ),
        array(
            'number' => '+7 (495) 632-73-68',
            'raw'    => '+74956327368',
        ),
    ) );

    carbon_set_theme_option( 'miauto_telegram_url', 'https://t.me/miauto' );
    carbon_set_theme_option( 'miauto_vk_url', 'https://vk.com/miauto' );

    // Rating.
    carbon_set_theme_option( 'miauto_rating_stars', 5 );
    carbon_set_theme_option( 'miauto_rating_reviews', '(500+ отзывов)' );
    carbon_set_theme_option( 'miauto_rating_source', 'Рейтинг организации в Яндексе' );

    // Footer.
    carbon_set_theme_option( 'miauto_footer_privacy_text', 'Политика конфиденциальности данных' );
    carbon_set_theme_option( 'miauto_footer_privacy_url', '/privacy/' );
    carbon_set_theme_option( 'miauto_footer_developer_text', 'Разработка сайта Dynamic IT' );

    // Form.
    carbon_set_theme_option( 'miauto_form_title', 'Запишитесь на ТО или бесплатный осмотр!' );
    if ( $cf7_id ) {
        carbon_set_theme_option( 'miauto_form_cf7_id', (string) $cf7_id );
    }
    if ( ! empty( $images['hero-bg'] ) ) {
        carbon_set_theme_option( 'miauto_form_bg', $images['hero-bg'] );
    }

    // Partners.
    carbon_set_theme_option( 'miauto_partners_title', 'Наши партнеры' );
    $partners_gallery = array_filter( array(
        $images['partner-1'] ?? 0,
        $images['partner-2'] ?? 0,
        $images['partner-3'] ?? 0,
        $images['partner-4'] ?? 0,
    ) );
    if ( ! empty( $partners_gallery ) ) {
        carbon_set_theme_option( 'miauto_partners_gallery', array_values( $partners_gallery ) );
    }
}

// ─── 4. Create pages ────────────────────────────────────────────────

function miauto_demo_create_pages() {
    $pages_data = array(
        'home'     => array( 'title' => 'Главная',      'template' => 'front-page.php' ),
        'about'    => array( 'title' => 'О компании',    'template' => 'page-about.php' ),
        'services' => array( 'title' => 'Услуги',        'template' => 'page-services.php' ),
        'works'    => array( 'title' => 'Наши работы',   'template' => 'page-works.php' ),
        'prices'   => array( 'title' => 'Цены',          'template' => 'page-prices.php' ),
        'contacts' => array( 'title' => 'Контакты',      'template' => 'page-contacts.php' ),
        'blog'     => array( 'title' => 'Блог',          'template' => '' ),
    );

    $pages = array();

    foreach ( $pages_data as $slug => $data ) {
        $existing = get_page_by_path( $slug );
        if ( $existing ) {
            $pages[ $slug ] = $existing->ID;
            if ( ! empty( $data['template'] ) ) {
                update_post_meta( $existing->ID, '_wp_page_template', $data['template'] );
            }
            continue;
        }

        $page_id = wp_insert_post( array(
            'post_title'   => $data['title'],
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ) );

        if ( ! empty( $data['template'] ) ) {
            update_post_meta( $page_id, '_wp_page_template', $data['template'] );
        }

        $pages[ $slug ] = $page_id;
    }

    return $pages;
}

// ─── 5. Reading settings ────────────────────────────────────────────

function miauto_demo_set_reading_settings( $pages ) {
    update_option( 'show_on_front', 'page' );

    if ( ! empty( $pages['home'] ) ) {
        update_option( 'page_on_front', $pages['home'] );
    }
    if ( ! empty( $pages['blog'] ) ) {
        update_option( 'page_for_posts', $pages['blog'] );
    }
}

// ─── 6. Create CPT posts ───────────────────────────────────────────

function miauto_demo_create_cpt_posts( $images ) {
    $result = array();

    // --- Car Models ---
    $models = array(
        'Mitsubishi ASX'             => $images['car-asx'] ?? 0,
        'Mitsubishi Outlander NEW'   => $images['car-outlander-new'] ?? 0,
        'Mitsubishi Outlander 3'     => $images['car-outlander-3'] ?? 0,
        'Mitsubishi Outlander XL'    => $images['car-outlander-xl'] ?? 0,
        'Mitsubishi Pajero Sport 3'  => $images['car-pajero-sport-3'] ?? 0,
        'Mitsubishi Pajero Sport 2'  => $images['car-pajero-sport-2'] ?? 0,
        'Mitsubishi L200'            => $images['car-l200'] ?? 0,
        'Mitsubishi Lancer 10'       => $images['car-lancer-10'] ?? 0,
    );

    foreach ( $models as $title => $thumb ) {
        $id = miauto_demo_get_or_create_post( 'miauto_model', $title );
        if ( $thumb ) {
            set_post_thumbnail( $id, $thumb );
        }
        $result['models'][] = $id;
    }

    // --- Services ---
    $services = array(
        'Автоэлектрика'                => array( 'img' => 'svc-auto-electric',    'price' => 'от 3 000 ₽' ),
        'Сход-развал'                   => array( 'img' => 'svc-wheel-alignment',  'price' => 'от 3 500 ₽' ),
        'Шиномонтаж'                    => array( 'img' => 'svc-tire-service',      'price' => 'от 2 000 ₽' ),
        'Тормозная система'             => array( 'img' => 'svc-brake-system',      'price' => 'от 2 500 ₽' ),
        'Ремонт подвески'              => array( 'img' => 'svc-suspension',         'price' => 'от 3 500 ₽' ),
        'Ремонт двигателя'             => array( 'img' => 'svc-engine',             'price' => 'от 5 000 ₽' ),
        'Ремонт рулевого управления'   => array( 'img' => 'svc-steering',           'price' => 'от 3 000 ₽' ),
        'Ремонт выхлопной системы'     => array( 'img' => 'svc-exhaust',             'price' => 'от 4 000 ₽' ),
        'Компьютерная диагностика авто' => array( 'img' => 'svc-diagnostics',        'price' => 'от 1 500 ₽' ),
        'Замена ремня ГРМ'             => array( 'img' => 'svc-timing-belt',         'price' => 'от 12 000 ₽' ),
        'Заправка кондиционера'        => array( 'img' => 'svc-air-conditioning',    'price' => 'от 3 500 ₽' ),
    );

    $svc_order = 0;
    foreach ( $services as $title => $svc ) {
        $id    = miauto_demo_get_or_create_post( 'miauto_service', $title );
        $thumb = $images[ $svc['img'] ] ?? 0;
        if ( $thumb ) {
            set_post_thumbnail( $id, $thumb );
        }
        wp_update_post( array( 'ID' => $id, 'menu_order' => $svc_order++ ) );

        if ( function_exists( 'carbon_set_post_meta' ) ) {
            carbon_set_post_meta( $id, 'miauto_service_price', $svc['price'] );
        }

        $result['services'][ $title ] = $id;
    }

    // Fill detailed fields for "Ремонт двигателя" (demo service).
    if ( function_exists( 'carbon_set_post_meta' ) && ! empty( $result['services']['Ремонт двигателя'] ) ) {
        $sid = $result['services']['Ремонт двигателя'];
        miauto_demo_fill_service_fields( $sid, $images );
    }

    // --- Works ---
    $works = array(
        array(
            'title'    => 'Капитальный ремонт ДВС Outlander XL',
            'model'    => 'Mitsubishi Outlander XL',
            'mileage'  => '128 000 км',
            'issue'    => 'Жор масла, дым',
            'defects'  => array( 'Залегание поршневых колец', 'Износ маслосъемных колпачков' ),
            'done'     => array( 'Капитальный ремонт ДВС', 'Расточка, замена поршневой', 'Замена прокладок, сальников' ),
            'price'    => '85 000 ₽',
            'duration' => '7 дней',
        ),
        array(
            'title'    => 'Замена цепи ГРМ Pajero IV',
            'model'    => 'Mitsubishi Pajero IV',
            'mileage'  => '185 000 км',
            'issue'    => 'Стук двигателя, тряска',
            'defects'  => array( 'Критический износ цепи ГРМ', 'Разрушение натяжителя' ),
            'done'     => array( 'Замена комплекта цепи ГРМ', 'Регулировка клапанов', 'Замена всех жидкостей' ),
            'price'    => '30 000 ₽',
            'duration' => '3 дня',
        ),
        array(
            'title'    => 'Ремонт ГБЦ ASX',
            'model'    => 'Mitsubishi ASX',
            'mileage'  => '95 000 км',
            'issue'    => 'Троение, потеря мощности',
            'defects'  => array( 'Пробой прокладки ГБЦ', 'Дефект плоскости головки' ),
            'done'     => array( 'Шлифовка ГБЦ', 'Замена прокладки ГБЦ', 'Притирка клапанов' ),
            'price'    => '45 000 ₽',
            'duration' => '5 дней',
        ),
    );

    foreach ( $works as $w ) {
        $id = miauto_demo_get_or_create_post( 'miauto_work', $w['title'] );

        if ( function_exists( 'carbon_set_post_meta' ) ) {
            carbon_set_post_meta( $id, 'miauto_work_model', $w['model'] );
            carbon_set_post_meta( $id, 'miauto_work_mileage', $w['mileage'] );
            carbon_set_post_meta( $id, 'miauto_work_issue', $w['issue'] );
            carbon_set_post_meta( $id, 'miauto_work_price', $w['price'] );
            carbon_set_post_meta( $id, 'miauto_work_duration', $w['duration'] );

            $defects = array();
            foreach ( $w['defects'] as $d ) {
                $defects[] = array( 'text' => $d );
            }
            carbon_set_post_meta( $id, 'miauto_work_defects', $defects );

            $done = array();
            foreach ( $w['done'] as $d ) {
                $done[] = array( 'text' => $d );
            }
            carbon_set_post_meta( $id, 'miauto_work_done', $done );
        }

        $result['works'][] = $id;
    }

    return $result;
}

// ─── Fill service card fields (demo) ────────────────────────────────

function miauto_demo_fill_service_fields( $post_id, $images ) {
    // SC Hero.
    carbon_set_post_meta( $post_id, 'miauto_sc_hero_subtitle', 'Капремонт / замена / устранение масложора / стук / перегрев — с гарантией по договору' );
    carbon_set_post_meta( $post_id, 'miauto_sc_hero_features', array(
        array( 'text' => 'Честная дефектовка и согласование работ до начала' ),
        array( 'text' => 'Фото/видео отчёт по этапам (по запросу)' ),
        array( 'text' => 'Сроки от 1 дня (в зависимости от поломки)' ),
        array( 'text' => 'Гарантия на работы и запчасти' ),
    ) );
    carbon_set_post_meta( $post_id, 'miauto_sc_hero_cta_primary_text', 'Записаться на диагностику' );
    carbon_set_post_meta( $post_id, 'miauto_sc_hero_cta_secondary_text', 'Рассчитать стоимость' );

    if ( ! empty( $images['svc-engine'] ) ) {
        carbon_set_post_meta( $post_id, 'miauto_sc_hero_image', $images['svc-engine'] );
    }

    carbon_set_post_meta( $post_id, 'miauto_sc_hero_stats', array(
        array( 'stat_value' => '5,0',    'stat_label' => 'Рейтинг на картах' ),
        array( 'stat_value' => '500+',   'stat_label' => 'Отзывов на картах' ),
        array( 'stat_value' => 'с 2005', 'stat_label' => 'Опыт работы' ),
    ) );

    // Symptoms.
    carbon_set_post_meta( $post_id, 'miauto_sc_symptoms_title', 'Когда нужен ремонт двигателя' );
    carbon_set_post_meta( $post_id, 'miauto_sc_symptoms_subtitle', 'Вы не платите за лишнее — только согласованные работы' );
    carbon_set_post_meta( $post_id, 'miauto_sc_symptoms_cards', array(
        array( 'image' => '', 'title' => 'Дым из выхлопной',        'desc' => 'Синий, белый или черный дым — признак серьезных неисправностей.' ),
        array( 'image' => '', 'title' => 'Масложор/подтеки масла',   'desc' => 'Постоянное доливание масла, лужи под машиной или следы потеков.' ),
        array( 'image' => '', 'title' => 'Потеря мощности/троение',  'desc' => 'Двигатель не тянет, машина дергается, плохо разгоняется.' ),
        array( 'image' => '', 'title' => 'Стук/шум/вибрации',        'desc' => 'Постоянные звуки при работе двигателя или повышенная вибрация.' ),
        array( 'image' => '', 'title' => 'Перегрев/эмульсия',        'desc' => 'Стрелка температуры выше нормы, «пена» на крышке маслозаливной горловины.' ),
        array( 'image' => '', 'title' => 'Ошибки Check Engine',      'desc' => 'Горящий индикатор «Check Engine» на приборной панели.' ),
    ) );
    carbon_set_post_meta( $post_id, 'miauto_sc_symptoms_cta_text', 'Запишитесь — проверим причину и предложим варианты решения' );
    carbon_set_post_meta( $post_id, 'miauto_sc_symptoms_cta_btn_text', 'Записаться на диагностику' );

    // Svc List.
    carbon_set_post_meta( $post_id, 'miauto_sc_svc_list_title', 'Какие работы выполняем' );
    carbon_set_post_meta( $post_id, 'miauto_sc_svc_list_items', array(
        array( 'title' => 'Компьютерная диагностика, эндоскопия цилиндров', 'desc' => 'Считывание кодов ошибок сканером и осмотр состояния цилиндров через эндоскоп без разборки двигателя.' ),
        array( 'title' => 'Замер компрессии/давления масла',                'desc' => 'Инструментальная проверка давления в цилиндрах и масляной системе для оценки износа деталей.' ),
        array( 'title' => 'Дефектовка двигателя',                          'desc' => 'Разборка, чистка и выявление всех скрытых дефектов с составлением точной ведомости.' ),
        array( 'title' => 'Замена прокладок/сальников',                     'desc' => 'Восстановление герметичности двигателя, устранение течей масла и технических жидкостей.' ),
        array( 'title' => 'Замена цепи/ремня ГРМ',                         'desc' => 'Обслуживание механизма газораспределения: замена цепи или ремня с комплектующими.' ),
        array( 'title' => 'Шлифовка ГБЦ, притирка клапанов',               'desc' => 'Механическая обработка головки блока, восстановление плотности прилегания клапанов.' ),
        array( 'title' => 'Замена вкладышей/колец/поршней',                 'desc' => 'Капитальный ремонт цилиндро-поршневой группы для восстановления компрессии и устранения масложора.' ),
        array( 'title' => 'Сборка, запуск и настройка ЭБУ',                'desc' => 'Окончательная сборка двигателя с соблюдением моментов затяжки, первый запуск, контрольная диагностика и программные настройки.' ),
    ) );

    // SC Prices.
    carbon_set_post_meta( $post_id, 'miauto_sc_prices_title', 'Стоимость ремонта двигателя' );
    carbon_set_post_meta( $post_id, 'miauto_sc_prices_subtitle', 'Вы не платите за лишнее — только согласованные работы' );
    carbon_set_post_meta( $post_id, 'miauto_sc_prices_rows', array(
        array( 'name' => 'Диагностика двигателя',  'price' => 'от 5 000 ₽' ),
        array( 'name' => 'Эндоскопия цилиндров',   'price' => 'от 4 000 ₽' ),
        array( 'name' => 'Замена прокладки ГБЦ',    'price' => 'от 8 000 ₽' ),
        array( 'name' => 'Замена цепи ГРМ',         'price' => 'от 14 000 ₽' ),
        array( 'name' => 'Капитальный ремонт',      'price' => 'от 40 000 ₽' ),
    ) );
    carbon_set_post_meta( $post_id, 'miauto_sc_prices_footer_heading', 'Получите точную смету после дефектовки' );
    carbon_set_post_meta( $post_id, 'miauto_sc_prices_footer_desc', 'До начала работ всё согласуем' );
    carbon_set_post_meta( $post_id, 'miauto_sc_prices_footer_btn_text', 'Записаться на диагностику' );

    // Warranty.
    carbon_set_post_meta( $post_id, 'miauto_sc_warranty_title', 'Гарантия и ответственность' );
    carbon_set_post_meta( $post_id, 'miauto_sc_warranty_subtitle', 'Если выявим, что ремонт нецелесообразен — предложим альтернативы' );
    carbon_set_post_meta( $post_id, 'miauto_sc_warranty_cards', array(
        array(
            'svg'  => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 0L0 3.636V9.091C0 14.136 3.84 18.855 9 20c5.16-1.145 9-5.864 9-10.909V3.636L9 0zM7 14.546L3.773 11.611a.82.82 0 010-1.404.826.826 0 011.275-.002L7 11.973l5.948-5.407a.826.826 0 011.282.003.82.82 0 01-.003 1.406L7 14.546z" fill="white"/></svg>',
            'text' => 'Гарантия на работы — 1 год',
        ),
        array(
            'svg'  => '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M18 0H2C1 0 0 .9 0 2v3.01c0 .72.43 1.34 1 1.69V18c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V6.7c.57-.35 1-.97 1-1.69V2c0-1.1-1-.9-2 0zM13 12H7v-2h6v2zM18 5H2V2l16-.02V5z" fill="white"/></svg>',
            'text' => 'Запчасти — по условиям поставщика',
        ),
        array(
            'svg'  => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.613.256a.29.29 0 00-.29-.256H3.194C2.347 0 1.534.297.935.826.336 1.355 0 2.072 0 2.82v14.36c0 .748.336 1.465.935 1.994.6.529 1.412.826 2.259.826h11.612c.847 0 1.66-.297 2.258-.826.6-.529.936-1.246.936-1.994V7.074a.29.29 0 00-.29-.257h-5.226a.87.87 0 01-.612-.226.77.77 0 01-.259-.543V.256zM12.484 10.256c.231 0 .452.081.616.226a.77.77 0 01.255.543.77.77 0 01-.255.544.87.87 0 01-.616.226H5.516a.87.87 0 01-.616-.226.77.77 0 01-.255-.544c0-.208.092-.404.255-.543a.87.87 0 01.616-.226h6.968zm0 4.103c.231 0 .452.081.616.225a.77.77 0 01.255.544.77.77 0 01-.255.544.87.87 0 01-.616.225H5.516a.87.87 0 01-.616-.225.77.77 0 01-.255-.544c0-.208.092-.404.255-.544a.87.87 0 01.616-.225h6.968z" fill="white"/><path d="M13.356.59c0-.19.224-.31.39-.192.14.1.266.217.375.35l3.499 4.305c.079.098-.007.226-.144.226h-3.83a.29.29 0 01-.29-.257V.59z" fill="white"/></svg>',
            'text' => 'Документы: заказ-наряд, акт выполненных работ',
        ),
        array(
            'svg'  => '<svg viewBox="0 0 20 18" xmlns="http://www.w3.org/2000/svg"><path d="M10 13.2c1.77 0 3.2-1.43 3.2-3.2 0-1.77-1.43-3.2-3.2-3.2-1.77 0-3.2 1.43-3.2 3.2 0 1.77 1.43 3.2 3.2 3.2zM7 0L5.17 2H2C.9 2 0 2.9 0 4v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2h-3.17L13 0H7zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z" fill="white"/></svg>',
            'text' => 'Фото/видео дефектовки (по запросу)',
        ),
    ) );
}

// ─── 7. Set page meta fields ────────────────────────────────────────

function miauto_demo_set_page_meta( $pages, $images ) {
    if ( ! function_exists( 'carbon_set_post_meta' ) ) {
        return;
    }

    // --- Homepage ---
    $home_id = $pages['home'] ?? 0;
    if ( $home_id ) {
        // Hero slides.
        carbon_set_post_meta( $home_id, 'miauto_hero_slides', array(
            array(
                'image'       => $images['hero-bg'] ?? '',
                'image_alt'   => 'Ремонт Mitsubishi',
                'title'       => 'Ремонт автомобилей Mitsubishi с гарантией качества',
                'description' => 'Надежность и точность в каждой детали. Ремонт автомобилей Mitsubishi с использованием оригинальных запчастей и лучших технологий. Доверяйте профессионалам!',
                'cta_text'    => 'Записаться на ремонт',
                'cta_url'     => '#form-section',
            ),
            array(
                'image'       => $images['hero-bg'] ?? '',
                'image_alt'   => 'Диагностика Mitsubishi',
                'title'       => 'Компьютерная диагностика всех систем автомобиля',
                'description' => 'Выявим любую неисправность с помощью дилерского оборудования Mitsubishi. Точная диагностика — залог качественного ремонта.',
                'cta_text'    => 'Записаться на диагностику',
                'cta_url'     => '#form-section',
            ),
            array(
                'image'       => $images['hero-bg'] ?? '',
                'image_alt'   => 'ТО Mitsubishi',
                'title'       => 'Техническое обслуживание по регламенту производителя',
                'description' => 'Проводим ТО в полном соответствии с рекомендациями Mitsubishi. Сохраняем гарантию дилера и продлеваем ресурс вашего автомобиля.',
                'cta_text'    => 'Подробнее об услугах',
                'cta_url'     => '/services/',
            ),
            array(
                'image'       => $images['hero-bg'] ?? '',
                'image_alt'   => 'Акция сход-развал',
                'title'       => 'Скидка 50% на сход-развал до конца февраля',
                'description' => 'Успейте записаться по акции! Профессиональный сход-развал на стенде Hunter с гарантией точности. Звоните прямо сейчас.',
                'cta_text'    => 'Позвонить нам',
                'cta_url'     => 'tel:+79263383929',
            ),
        ) );

        // Hero features.
        carbon_set_post_meta( $home_id, 'miauto_hero_features', array(
            array(
                'text' => 'Работаем с 10:00 до 21:00',
                'svg'  => '<svg viewBox="0 0 14 14"><circle cx="7" cy="7" r="6" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M7 4V7.5L9.5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>',
            ),
            array(
                'text' => 'Оригинальные запчасти',
                'svg'  => '<svg viewBox="0 0 14 14"><path d="M7 1L8.5 3.5H11.5L10 6L11.5 8.5H8.5L7 11L5.5 8.5H2.5L4 6L2.5 3.5H5.5L7 1Z" stroke="currentColor" stroke-width="1.2" fill="none"/><circle cx="7" cy="6" r="1.5" fill="currentColor"/></svg>',
            ),
            array(
                'text' => 'Бесплатная консультация',
                'svg'  => '<svg viewBox="0 0 14 14"><path d="M2 2H12C12.55 2 13 2.45 13 3V9C13 9.55 12.55 10 12 10H8L5 13V10H2C1.45 10 1 9.55 1 9V3C1 2.45 1.45 2 2 2Z" stroke="currentColor" stroke-width="1.3" fill="none"/></svg>',
            ),
        ) );

        // Homepage sections.
        carbon_set_post_meta( $home_id, 'miauto_car_models_title', 'Модели авто' );
        carbon_set_post_meta( $home_id, 'miauto_services_title', 'Категории услуг' );
        carbon_set_post_meta( $home_id, 'miauto_services_more_text', 'Смотреть еще' );

        carbon_set_post_meta( $home_id, 'miauto_about_title', 'О нас' );
        carbon_set_post_meta( $home_id, 'miauto_about_text', 'Все работы в Техническом центре "Ми-Авто" проводятся в соответствии с техническими требованиями заводов изготовителей с применением специального инструмента и оборудования, а также технической документации.<br><br>Сотрудники технического центра имеют высокую квалификацию и специальную подготовку, для проведения обслуживания и ремонта автомобилей Мицубиси (Mitsubishi), выполняют слесарный и агрегатный ремонт автомобилей Мицубиси (Mitsubishi).' );

        if ( ! empty( $images['about-us'] ) ) {
            carbon_set_post_meta( $home_id, 'miauto_about_image', $images['about-us'] );
        }

        carbon_set_post_meta( $home_id, 'miauto_articles_title', 'Полезные статьи' );
        carbon_set_post_meta( $home_id, 'miauto_articles_link_text', 'Узнать больше' );
        carbon_set_post_meta( $home_id, 'miauto_articles_count', 2 );

        // Service details tabs.
        carbon_set_post_meta( $home_id, 'miauto_svc_details_title', 'Услуги CTO' );
        carbon_set_post_meta( $home_id, 'miauto_svc_details_tabs', array(
            array(
                'tab_id'      => 'to',
                'tab_title'   => 'Техническое обслуживание Mitsubishi',
                'badge'       => 'Рекомендуется каждые 15 000 км',
                'panel_title' => 'Техническое обслуживание Mitsubishi',
                'panel_text'  => 'Проводим полное регламентное и техническое обслуживание автомобилей марки MITSUBISHI. Используем как оригинальные так и аналоговые расходные материалы и запчасти, по желанию клиента.',
                'features'    => array(
                    array( 'text' => 'Замена ГРМ' ),
                    array( 'text' => 'Замена масел и жидкостей' ),
                    array( 'text' => 'Замена масляного/воздушного/топливного фильтров' ),
                    array( 'text' => 'Замена фильтра салона' ),
                    array( 'text' => 'Замена тормозной жидкости' ),
                    array( 'text' => 'Замена масла в АКПП/МКПП' ),
                    array( 'text' => 'Замена свечей зажигания' ),
                    array( 'text' => 'Замена жидкости в ГУР' ),
                    array( 'text' => 'Проверка, смазка шарниров в.т.ч карданных' ),
                    array( 'text' => 'Проверка пыльников' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 30 000 ₽',
                'cta_text'    => 'Записаться на ремонт',
                'cta_url'     => '#form-section',
            ),
            array(
                'tab_id'      => 'suspension',
                'tab_title'   => 'Подвеска',
                'badge'       => '',
                'panel_title' => 'Ремонт подвески Mitsubishi',
                'panel_text'  => 'Диагностика и ремонт ходовой части автомобилей Mitsubishi. Замена амортизаторов, рычагов, сайлентблоков, ступичных подшипников.',
                'features'    => array(
                    array( 'text' => 'Замена амортизаторов' ),
                    array( 'text' => 'Замена рычагов' ),
                    array( 'text' => 'Замена сайлентблоков' ),
                    array( 'text' => 'Замена ступичных подшипников' ),
                    array( 'text' => 'Замена шаровых опор' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 3 500 ₽',
                'cta_text'    => 'Записаться на ремонт',
                'cta_url'     => '#form-section',
            ),
            array(
                'tab_id'      => 'engine',
                'tab_title'   => 'Двигатель',
                'badge'       => '',
                'panel_title' => 'Ремонт двигателя Mitsubishi',
                'panel_text'  => 'Капитальный ремонт, замена ГРМ, устранение масложора, диагностика и восстановление двигателей всех моделей Mitsubishi.',
                'features'    => array(
                    array( 'text' => 'Замена ремня/цепи ГРМ' ),
                    array( 'text' => 'Капитальный ремонт ДВС' ),
                    array( 'text' => 'Замена прокладки ГБЦ' ),
                    array( 'text' => 'Замена сальников' ),
                    array( 'text' => 'Диагностика двигателя' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 5 000 ₽',
                'cta_text'    => 'Записаться на ремонт',
                'cta_url'     => '#form-section',
            ),
        ) );

        // Contacts on homepage.
        carbon_set_post_meta( $home_id, 'miauto_contacts_title', 'Наши контакты' );
        if ( ! empty( $images['contacts-decoration'] ) ) {
            carbon_set_post_meta( $home_id, 'miauto_contacts_decoration', $images['contacts-decoration'] );
        }
        if ( ! empty( $images['contacts-map'] ) ) {
            carbon_set_post_meta( $home_id, 'miauto_contacts_map', $images['contacts-map'] );
        }
    }

    // --- About page ---
    $about_id = $pages['about'] ?? 0;
    if ( $about_id ) {
        carbon_set_post_meta( $about_id, 'miauto_about_hero_badge', 'Официальный сервис' );
        carbon_set_post_meta( $about_id, 'miauto_about_hero_title', 'Технический центр МИ АВТО' );
        carbon_set_post_meta( $about_id, 'miauto_about_hero_accent', 'МИ АВТО' );
        carbon_set_post_meta( $about_id, 'miauto_about_hero_texts', array(
            array( 'text' => 'Технический центр «МИ АВТО» — это специализированный сервис по ремонту и обслуживанию автомобилей Mitsubishi в Москве. Мы работаем с 2005 года и за это время накопили огромный опыт в диагностике и ремонте всех моделей Mitsubishi.' ),
            array( 'text' => 'Наша команда — это квалифицированные специалисты, прошедшие обучение и сертификацию. Мы используем только оригинальные запчасти и современное диагностическое оборудование, что позволяет выполнять работы любой сложности в кратчайшие сроки.' ),
        ) );
        if ( ! empty( $images['about-us'] ) ) {
            carbon_set_post_meta( $about_id, 'miauto_about_hero_image', $images['about-us'] );
        }

        carbon_set_post_meta( $about_id, 'miauto_about_intro_title', 'Профессионализм и опыт' );
        carbon_set_post_meta( $about_id, 'miauto_about_intro_texts', array(
            array( 'text' => 'Мы стремимся предоставить каждому клиенту максимально качественный сервис. Наш подход основан на честности, прозрачности и профессионализме. Мы всегда подробно объясняем, какие работы необходимы, и согласовываем стоимость до начала ремонта.' ),
            array( 'text' => 'Технический центр оснащён современным оборудованием, которое позволяет выполнять диагностику и ремонт любой сложности. Мы постоянно инвестируем в обучение персонала и обновление технической базы, чтобы соответствовать самым высоким стандартам обслуживания.' ),
        ) );
        if ( ! empty( $images['about-us'] ) ) {
            carbon_set_post_meta( $about_id, 'miauto_about_intro_image', $images['about-us'] );
        }

        // Work Process.
        carbon_set_post_meta( $about_id, 'miauto_work_process_title', 'Как мы работаем' );
        carbon_set_post_meta( $about_id, 'miauto_work_process_subtitle', 'Вы не платите за лишнее — только согласованные работы' );
        carbon_set_post_meta( $about_id, 'miauto_work_process_steps', array(
            array(
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>',
                'title' => 'Заявка или звонок',
                'text'  => 'Свяжитесь с нами для консультации по проблеме.',
            ),
            array(
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
                'title' => 'Запись',
                'text'  => 'Подберем удобные дату и время для визита.',
            ),
            array(
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><path d="M11 8v6l4 2"/></svg>',
                'title' => 'Диагностика',
                'text'  => 'Полная дефектовка и выявление неисправностей.',
            ),
            array(
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg>',
                'title' => 'Согласование',
                'text'  => 'Утверждаем смету и варианты решения проблемы.',
            ),
            array(
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="12" y2="16"/></svg>',
                'title' => 'Ремонт',
                'text'  => 'Заказ запчастей и выполнение ремонтных работ.',
            ),
            array(
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
                'title' => 'Тест и выдача',
                'text'  => 'Проверка исправности и возврат авто.',
            ),
            array(
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>',
                'title' => 'Гарантия',
                'text'  => 'Выдаем гарантийный талон и консультируем.',
            ),
        ) );

        // Advantages.
        carbon_set_post_meta( $about_id, 'miauto_advantages_title', 'Наши преимущества' );
        carbon_set_post_meta( $about_id, 'miauto_advantages_cards', array(
            array(
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'title' => 'Специализация',
                'text'  => 'Работаем только с автомобилями Mitsubishi, что позволяет глубоко разбираться в каждой модели.',
            ),
            array(
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 12l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'title' => 'Оригинальные запчасти',
                'text'  => 'Используем только оригинальные и сертифицированные запасные части для ремонта.',
            ),
            array(
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M12 15l-2 5 2-1 2 1-2-5z" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="10" r="6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'title' => 'Гарантия',
                'text'  => 'Предоставляем гарантию на все виды выполненных работ и установленные запчасти.',
            ),
            array(
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="4" width="16" height="16" rx="2" stroke-linecap="round" stroke-linejoin="round"/><rect x="9" y="9" width="6" height="6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'title' => 'Современное оборудование',
                'text'  => 'Используем профессиональное диагностическое и ремонтное оборудование.',
            ),
            array(
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'title' => 'Опытные мастера',
                'text'  => 'Наши специалисты имеют высокую квалификацию и регулярно проходят обучение.',
            ),
            array(
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'title' => 'Прозрачные цены',
                'text'  => 'Составляем подробную смету до начала работ. Никаких скрытых доплат.',
            ),
        ) );
    }

    // --- Contacts page ---
    $contacts_id = $pages['contacts'] ?? 0;
    if ( $contacts_id ) {
        carbon_set_post_meta( $contacts_id, 'miauto_contacts_title', 'Наши контакты' );
        if ( ! empty( $images['contacts-decoration'] ) ) {
            carbon_set_post_meta( $contacts_id, 'miauto_contacts_decoration', $images['contacts-decoration'] );
        }
        if ( ! empty( $images['contacts-map'] ) ) {
            carbon_set_post_meta( $contacts_id, 'miauto_contacts_map', $images['contacts-map'] );
        }
    }

    // --- Prices page ---
    $prices_id = $pages['prices'] ?? 0;
    if ( $prices_id ) {
        carbon_set_post_meta( $prices_id, 'miauto_prices_title', 'Прайс-лист' );
        carbon_set_post_meta( $prices_id, 'miauto_prices_subtitle', 'Актуальные цены на обслуживание и ремонт автомобилей Mitsubishi' );

        $outlander_cats = array(
            array(
                'name' => 'ТО',
                'rows' => array(
                    array( 'name' => 'ТО-1 (15 000 км)',              'price' => 'от 8 500 ₽' ),
                    array( 'name' => 'ТО-2 (30 000 км)',              'price' => 'от 12 000 ₽' ),
                    array( 'name' => 'ТО-3 (45 000 км)',              'price' => 'от 8 500 ₽' ),
                    array( 'name' => 'ТО-4 (60 000 км)',              'price' => 'от 15 000 ₽' ),
                    array( 'name' => 'Замена масла двигателя',         'price' => 'от 2 500 ₽' ),
                    array( 'name' => 'Замена масла АКПП',              'price' => 'от 4 500 ₽' ),
                    array( 'name' => 'Замена антифриза',               'price' => 'от 3 000 ₽' ),
                    array( 'name' => 'Замена тормозной жидкости',      'price' => 'от 2 000 ₽' ),
                ),
            ),
            array(
                'name' => 'Двигатель',
                'rows' => array(
                    array( 'name' => 'Замена ремня ГРМ',       'price' => 'от 12 000 ₽' ),
                    array( 'name' => 'Замена цепи ГРМ',        'price' => 'от 18 000 ₽' ),
                    array( 'name' => 'Замена прокладки ГБЦ',   'price' => 'от 25 000 ₽' ),
                    array( 'name' => 'Замена сальника коленвала','price' => 'от 8 000 ₽' ),
                    array( 'name' => 'Замена помпы',            'price' => 'от 6 500 ₽' ),
                    array( 'name' => 'Замена термостата',       'price' => 'от 4 000 ₽' ),
                    array( 'name' => 'Диагностика двигателя',   'price' => 'от 2 000 ₽' ),
                ),
            ),
            array(
                'name' => 'Подвеска',
                'rows' => array(
                    array( 'name' => 'Замена передних амортизаторов',  'price' => 'от 5 000 ₽' ),
                    array( 'name' => 'Замена задних амортизаторов',    'price' => 'от 4 500 ₽' ),
                    array( 'name' => 'Замена передних рычагов',        'price' => 'от 6 000 ₽' ),
                    array( 'name' => 'Замена сайлентблоков',           'price' => 'от 3 500 ₽' ),
                    array( 'name' => 'Замена ступичного подшипника',   'price' => 'от 5 500 ₽' ),
                    array( 'name' => 'Замена шаровой опоры',           'price' => 'от 3 000 ₽' ),
                    array( 'name' => 'Сход-развал',                    'price' => 'от 3 500 ₽' ),
                ),
            ),
            array(
                'name' => 'Тормоза',
                'rows' => array(
                    array( 'name' => 'Замена передних колодок',         'price' => 'от 2 500 ₽' ),
                    array( 'name' => 'Замена задних колодок',           'price' => 'от 2 500 ₽' ),
                    array( 'name' => 'Замена передних дисков',          'price' => 'от 4 000 ₽' ),
                    array( 'name' => 'Замена задних дисков',            'price' => 'от 3 500 ₽' ),
                    array( 'name' => 'Замена тормозных шлангов',        'price' => 'от 2 000 ₽' ),
                    array( 'name' => 'Прокачка тормозной системы',      'price' => 'от 2 500 ₽' ),
                ),
            ),
            array(
                'name' => 'Рулевое',
                'rows' => array(
                    array( 'name' => 'Замена рулевых наконечников', 'price' => 'от 3 000 ₽' ),
                    array( 'name' => 'Замена рулевых тяг',          'price' => 'от 4 500 ₽' ),
                    array( 'name' => 'Ремонт рулевой рейки',        'price' => 'от 12 000 ₽' ),
                    array( 'name' => 'Замена насоса ГУР',           'price' => 'от 8 000 ₽' ),
                    array( 'name' => 'Замена жидкости ГУР',         'price' => 'от 2 000 ₽' ),
                ),
            ),
            array(
                'name' => 'Электрика',
                'rows' => array(
                    array( 'name' => 'Компьютерная диагностика', 'price' => 'от 1 500 ₽' ),
                    array( 'name' => 'Замена генератора',         'price' => 'от 5 000 ₽' ),
                    array( 'name' => 'Замена стартера',           'price' => 'от 5 500 ₽' ),
                    array( 'name' => 'Заправка кондиционера',     'price' => 'от 3 500 ₽' ),
                    array( 'name' => 'Замена аккумулятора',       'price' => 'от 1 000 ₽' ),
                    array( 'name' => 'Ремонт проводки',           'price' => 'от 3 000 ₽' ),
                ),
            ),
        );

        // Use same categories for all models (simplified demo).
        $price_models = array( 'Outlander', 'ASX', 'Pajero Sport', 'Pajero', 'L200', 'Lancer' );
        $models_data = array();
        foreach ( $price_models as $model_name ) {
            $models_data[] = array(
                'name'       => $model_name,
                'categories' => $outlander_cats,
            );
        }

        carbon_set_post_meta( $prices_id, 'miauto_prices_models', $models_data );
    }
}

// ─── 8. Blog posts ──────────────────────────────────────────────────

function miauto_demo_create_blog_posts( $images ) {
    $posts = array(
        array(
            'title'    => 'Как подготовить Mitsubishi к зимнему сезону: советы экспертов',
            'slug'     => 'winter-preparation',
            'category' => 'Зимняя подготовка',
            'thumb'    => $images['article-1'] ?? 0,
            'content'  => '<p>Зима — сложное испытание для любого автомобиля, и Mitsubishi не исключение. Правильная подготовка к холодному сезону поможет избежать неприятных сюрпризов на дороге и продлить срок службы вашего автомобиля. Специалисты технического центра «Ми-Авто» делятся проверенными рекомендациями.</p>

<h2>Проверка и замена технических жидкостей</h2>
<p>Первое, на что стоит обратить внимание — это технические жидкости. Антифриз должен соответствовать температурным условиям вашего региона. Рекомендуется проверить концентрацию охлаждающей жидкости и при необходимости заменить её на свежую.</p>
<p>Моторное масло также играет важную роль. В зимний период лучше использовать масло с низкой вязкостью (например, 0W-30 или 5W-30), которое обеспечит лёгкий запуск двигателя в мороз.</p>

<h2>Аккумулятор и электрика</h2>
<p>Аккумулятор — один из самых уязвимых элементов в зимний период. При температуре ниже -20°C ёмкость батареи может снижаться на 30-40%. Рекомендуем:</p>
<ul>
<li>Проверить уровень заряда и плотность электролита</li>
<li>Очистить клеммы от окисления</li>
<li>При возрасте батареи более 3 лет — рассмотреть замену</li>
<li>Проверить работу генератора и стартера</li>
</ul>

<h2>Шины и тормозная система</h2>
<p>Переход на зимние шины — обязательная процедура. Зимняя резина обеспечивает надёжное сцепление с дорогой при температуре ниже +5°C. Не забудьте проверить глубину протектора — она должна быть не менее 4 мм.</p>

<h2>Система обогрева и видимость</h2>
<p>Убедитесь, что система отопления и кондиционирования работает исправно. Проверьте состояние щёток стеклоочистителя и замените их при необходимости. Залейте незамерзающую жидкость в бачок омывателя.</p>
<p>Специалисты «Ми-Авто» рекомендуют пройти комплексную диагностику перед зимним сезоном. Это позволит выявить и устранить потенциальные проблемы до наступления холодов. Записывайтесь на обслуживание — мы поможем подготовить ваш Mitsubishi к зиме!</p>',
        ),
        array(
            'title'    => 'Регулярное ТО: почему это важно для вашего Mitsubishi',
            'slug'     => 'regular-maintenance',
            'category' => 'Техническое обслуживание',
            'thumb'    => $images['article-2'] ?? 0,
            'content'  => '<p>Регулярное техническое обслуживание — залог долгой и безопасной эксплуатации автомобиля. Разбираемся, какие работы входят в ТО и почему их нельзя откладывать.</p>

<h2>Что входит в регулярное ТО</h2>
<p>Техническое обслуживание Mitsubishi включает комплекс работ, направленных на поддержание автомобиля в исправном состоянии. Основные операции:</p>
<ul>
<li>Замена моторного масла и масляного фильтра</li>
<li>Проверка и замена воздушного фильтра</li>
<li>Замена салонного фильтра</li>
<li>Проверка тормозной системы</li>
<li>Диагностика подвески</li>
<li>Проверка уровня всех технических жидкостей</li>
</ul>

<h2>Периодичность обслуживания</h2>
<p>Производитель рекомендует проходить ТО каждые 15 000 км пробега или раз в год — в зависимости от того, что наступит раньше. При эксплуатации в тяжёлых условиях (городской цикл, пыльные дороги) интервал может быть сокращён.</p>

<p>Не откладывайте техническое обслуживание — это инвестиция в надёжность и безопасность вашего автомобиля. Записывайтесь в «Ми-Авто» — мы проведём ТО качественно и в срок!</p>',
        ),
        array(
            'title'    => 'Признаки износа тормозных колодок: когда менять',
            'slug'     => 'brake-pads-wear',
            'category' => 'Обслуживание',
            'thumb'    => $images['article-1'] ?? 0,
            'content'  => '<p>Тормозные колодки — один из ключевых элементов безопасности автомобиля. Рассказываем, как определить их износ и когда пора менять.</p>

<h2>Основные признаки износа</h2>
<p>Своевременная замена тормозных колодок критически важна для вашей безопасности. Вот основные признаки, на которые стоит обращать внимание:</p>
<ul>
<li>Скрип или визг при торможении</li>
<li>Увеличение тормозного пути</li>
<li>Вибрация педали тормоза</li>
<li>Неравномерный износ шин</li>
<li>Индикатор износа на приборной панели</li>
</ul>

<h2>Когда менять колодки</h2>
<p>Средний ресурс тормозных колодок составляет 30 000-60 000 км в зависимости от стиля вождения и условий эксплуатации. Минимальная допустимая толщина фрикционного материала — 2 мм.</p>

<p>Если вы заметили хотя бы один из перечисленных признаков — не откладывайте визит в сервис. В «Ми-Авто» мы проведём диагностику тормозной системы и при необходимости заменим колодки на качественные аналоги или оригинальные запчасти.</p>',
        ),
    );

    foreach ( $posts as $p ) {
        // Check if exists.
        $existing = get_page_by_path( $p['slug'], OBJECT, 'post' );
        if ( $existing ) {
            continue;
        }

        // Create category.
        $cat = get_term_by( 'name', $p['category'], 'category' );
        if ( ! $cat ) {
            $cat_result = wp_insert_term( $p['category'], 'category' );
            $cat_id     = is_array( $cat_result ) ? $cat_result['term_id'] : 0;
        } else {
            $cat_id = $cat->term_id;
        }

        $post_id = wp_insert_post( array(
            'post_title'   => $p['title'],
            'post_name'    => $p['slug'],
            'post_content' => $p['content'],
            'post_status'  => 'publish',
            'post_type'    => 'post',
            'post_category' => $cat_id ? array( $cat_id ) : array(),
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) && $p['thumb'] ) {
            set_post_thumbnail( $post_id, $p['thumb'] );
        }
    }
}

// ─── 9. Navigation menu ─────────────────────────────────────────────

function miauto_demo_create_menu( $pages ) {
    $menu_name   = 'Главное меню';
    $menu_exists = wp_get_nav_menu_object( $menu_name );

    if ( $menu_exists ) {
        $menu_id = $menu_exists->term_id;
    } else {
        $menu_id = wp_create_nav_menu( $menu_name );
    }

    if ( is_wp_error( $menu_id ) ) {
        return;
    }

    // Clear existing items.
    $existing_items = wp_get_nav_menu_items( $menu_id );
    if ( $existing_items ) {
        foreach ( $existing_items as $item ) {
            wp_delete_post( $item->ID, true );
        }
    }

    $top_level_items = array(
        'home'     => 'Главная',
        'services' => 'Услуги и ремонт',
        'about'    => 'О нас',
        'works'    => 'Наши работы',
        'blog'     => 'Блог',
        'prices'   => 'Цены',
        'contacts' => 'Контакты',
    );

    $order        = 1;
    $services_nav_id = 0;

    foreach ( $top_level_items as $slug => $title ) {
        if ( empty( $pages[ $slug ] ) ) {
            continue;
        }

        $item_id = wp_update_nav_menu_item( $menu_id, 0, array(
            'menu-item-title'     => $title,
            'menu-item-object-id' => $pages[ $slug ],
            'menu-item-object'    => 'page',
            'menu-item-type'      => 'post_type',
            'menu-item-status'    => 'publish',
            'menu-item-position'  => $order++,
        ) );

        if ( 'services' === $slug && ! is_wp_error( $item_id ) ) {
            $services_nav_id = $item_id;
        }
    }

    // Add service CPT posts as children of "Услуги и ремонт".
    if ( $services_nav_id ) {
        $service_posts = get_posts( array(
            'post_type'      => 'miauto_service',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ) );

        $sub_order = 1;
        foreach ( $service_posts as $svc_id ) {
            wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title'     => get_the_title( $svc_id ),
                'menu-item-object-id' => $svc_id,
                'menu-item-object'    => 'miauto_service',
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-position'  => $sub_order++,
                'menu-item-parent-id' => $services_nav_id,
            ) );
        }
    }

    // Assign to primary and mobile theme locations.
    $locations            = get_theme_mod( 'nav_menu_locations', array() );
    $locations['primary'] = $menu_id;
    $locations['mobile']  = $menu_id;
    set_theme_mod( 'nav_menu_locations', $locations );
}

// ─── 10. Permalinks ─────────────────────────────────────────────────

function miauto_demo_set_permalinks() {
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure( '/%postname%/' );
    $wp_rewrite->flush_rules();
}

// ─── Helper: get or create CPT post ─────────────────────────────────

function miauto_demo_get_or_create_post( $post_type, $title ) {
    $existing = get_posts( array(
        'post_type'   => $post_type,
        'title'       => $title,
        'numberposts' => 1,
        'fields'      => 'ids',
    ) );

    if ( ! empty( $existing ) ) {
        return $existing[0];
    }

    return wp_insert_post( array(
        'post_title'  => $title,
        'post_status' => 'publish',
        'post_type'   => $post_type,
    ) );
}
