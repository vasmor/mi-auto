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

    $already_imported = get_option( 'miauto_demo_imported' );

    // Run the import (safe: заполняет только пустые поля).
    $result = miauto_run_demo_import();

    if ( is_wp_error( $result ) ) {
        $msg = $result->get_error_message();
        add_action( 'admin_notices', function () use ( $msg ) {
            echo '<div class="notice notice-error"><p>MI-AUTO Demo Import Error: ' . esc_html( $msg ) . '</p></div>';
        } );
        return;
    }

    if ( ! $already_imported ) {
        update_option( 'miauto_demo_imported', true );
    }

    $notice_text = $already_imported
        ? 'MI-AUTO: Повторный импорт завершён. Заполнены только пустые поля, существующие данные сохранены.'
        : 'MI-AUTO: Демо-контент успешно установлен! Все страницы, записи, CPT и настройки созданы.';

    add_action( 'admin_notices', function () use ( $notice_text ) {
        echo '<div class="notice notice-success"><p>' . esc_html( $notice_text ) . '</p></div>';
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
    set_time_limit( 300 );
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

    // 11. Fill reviews demo data.
    miauto_demo_fill_reviews( $cpt );

    return true;
}

// ─── Helper: Safe setters (skip if value already exists) ────────────

/**
 * Установить Carbon Fields post meta только если поле пустое.
 *
 * @param int    $post_id ID записи.
 * @param string $key     Ключ поля CF.
 * @param mixed  $value   Значение для установки.
 */
function miauto_demo_set_post_meta_if_empty( $post_id, $key, $value ) {
    if ( ! function_exists( 'carbon_get_post_meta' ) ) {
        return;
    }
    $current = carbon_get_post_meta( $post_id, $key );
    if ( ! empty( $current ) ) {
        return;
    }
    carbon_set_post_meta( $post_id, $key, $value );
}

/**
 * Установить Carbon Fields theme option только если опция пустая.
 *
 * @param string $key   Ключ опции CF.
 * @param mixed  $value Значение для установки.
 */
function miauto_demo_set_theme_option_if_empty( $key, $value ) {
    if ( ! function_exists( 'carbon_get_theme_option' ) ) {
        return;
    }
    $current = carbon_get_theme_option( $key );
    if ( ! empty( $current ) ) {
        return;
    }
    carbon_set_theme_option( $key, $value );
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

    if ( ! copy( $file_path, $target ) ) {
        return new WP_Error( 'copy_failed', 'Failed to copy image: ' . $filename );
    }

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
    // Top Bar.
    miauto_demo_set_theme_option_if_empty( 'miauto_top_bar_enabled', true );
    miauto_demo_set_theme_option_if_empty( 'miauto_top_bar_label', 'Только до 24 февраля' );
    miauto_demo_set_theme_option_if_empty( 'miauto_top_bar_text', 'Скидка -50% на Сход-развал. Записывайтесь прямо сейчас по телефону и получите скидку!' );

    // Header.
    miauto_demo_set_theme_option_if_empty( 'miauto_logo_text', 'MI-AUTO.ru' );
    miauto_demo_set_theme_option_if_empty( 'miauto_slogan', 'Ремонт Mitsubishi всех моделей в одном центре' );
    miauto_demo_set_theme_option_if_empty( 'miauto_online_text', 'Задайте вопрос, мы сейчас онлайн' );
    miauto_demo_set_theme_option_if_empty( 'miauto_callback_text', 'Обратный звонок' );

    // Contacts.
    miauto_demo_set_theme_option_if_empty( 'miauto_contacts_section_title', 'Наши контакты' );
    if ( ! empty( $images['contacts-decoration'] ) ) {
        miauto_demo_set_theme_option_if_empty( 'miauto_contacts_decoration', $images['contacts-decoration'] );
    }
    miauto_demo_set_theme_option_if_empty( 'miauto_contacts_map_embed', '<iframe src="https://api-maps.yandex.ru/frame/v1/-/CZwJQDL7" width="100%" height="100%" frameborder="0" style="width: 100%; height: 100%;"></iframe>' );
    miauto_demo_set_theme_option_if_empty( 'miauto_address', 'г. Москва, ул. Остаповский проезд 1, д. 10, стр. 1' );
    miauto_demo_set_theme_option_if_empty( 'miauto_hours', 'Понедельник-Воскресенье с 10:00 до 21:00' );
    miauto_demo_set_theme_option_if_empty( 'miauto_hours_short', 'Пн-Вс с 10:00 до 21:00' );
    miauto_demo_set_theme_option_if_empty( 'miauto_email', 'info@mi-auto.ru' );

    miauto_demo_set_theme_option_if_empty( 'miauto_phones', array(
        array(
            'phone_number' => '+7 (926) 338-39-29',
            'phone_raw'    => '+79263383929',
        ),
        array(
            'phone_number' => '+7 (495) 632-73-68',
            'phone_raw'    => '+74956327368',
        ),
    ) );

    miauto_demo_set_theme_option_if_empty( 'miauto_telegram_url', 'https://t.me/miauto' );
    miauto_demo_set_theme_option_if_empty( 'miauto_vk_url', 'https://vk.com/miauto' );

    // Rating.
    miauto_demo_set_theme_option_if_empty( 'miauto_rating_stars', 5 );
    miauto_demo_set_theme_option_if_empty( 'miauto_rating_reviews', '(500+ отзывов)' );
    miauto_demo_set_theme_option_if_empty( 'miauto_rating_source', 'Рейтинг организации в Яндексе' );

    // Footer.
    miauto_demo_set_theme_option_if_empty( 'miauto_footer_privacy_text', 'Политика конфиденциальности данных' );
    miauto_demo_set_theme_option_if_empty( 'miauto_footer_privacy_url', '/privacy/' );
    miauto_demo_set_theme_option_if_empty( 'miauto_footer_developer_text', 'Разработка сайта Dynamic IT' );

    // Form.
    miauto_demo_set_theme_option_if_empty( 'miauto_form_title', 'Запишитесь на ТО или бесплатный осмотр!' );
    if ( $cf7_id ) {
        miauto_demo_set_theme_option_if_empty( 'miauto_form_cf7_id', (string) $cf7_id );
    }
    if ( ! empty( $images['hero-bg'] ) ) {
        miauto_demo_set_theme_option_if_empty( 'miauto_form_bg', $images['hero-bg'] );
    }

    // Partners.
    miauto_demo_set_theme_option_if_empty( 'miauto_partners_title', 'Наши партнеры' );
    miauto_demo_set_theme_option_if_empty( 'miauto_partners_items', array(
        array( 'pitem_image' => $images['partner-1'] ?? 0, 'pitem_title' => 'Клуб Mitsubishi ASX', 'pitem_url' => 'http://www.mitsubishi-asx.net/' ),
        array( 'pitem_image' => $images['partner-2'] ?? 0, 'pitem_title' => 'Pajero4-club',         'pitem_url' => 'http://www.pajero4-club.ru/' ),
        array( 'pitem_image' => $images['partner-3'] ?? 0, 'pitem_title' => 'Pajero 4x4 Club',      'pitem_url' => 'http://www.pajeroclub.ru/' ),
        array( 'pitem_image' => $images['partner-4'] ?? 0, 'pitem_title' => 'Club-L200.ru',          'pitem_url' => 'http://club-l200.ru/forum/index.php' ),
    ) );

    // Work Process.
    miauto_demo_set_theme_option_if_empty( 'miauto_work_process_title', 'Как мы работаем' );
    miauto_demo_set_theme_option_if_empty( 'miauto_work_process_subtitle', 'Вы не платите за лишнее — только согласованные работы' );
    miauto_demo_set_theme_option_if_empty( 'miauto_work_process_steps', array(
        array(
            'step_svg'   => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M2.82812 0.76529C3.09057 0.503212 3.40572 0.299907 3.75268 0.168848C4.09965 0.0377897 4.4705 -0.0180293 4.84066 0.00509005C5.21083 0.0282094 5.57185 0.129739 5.89982 0.302951C6.22778 0.476164 6.51519 0.717103 6.743 1.0098L9.43541 4.46896C9.9289 5.10349 10.1029 5.93003 9.9079 6.71006L9.08742 9.99521C9.0453 10.1654 9.04774 10.3435 9.0945 10.5125C9.14127 10.6814 9.23079 10.8355 9.35441 10.9598L13.0398 14.6454C13.1642 14.7693 13.3185 14.859 13.4877 14.9058C13.657 14.9525 13.8354 14.9548 14.0058 14.9124L17.2892 14.0919C17.6741 13.9962 18.0757 13.989 18.4639 14.0709C18.852 14.1527 19.2165 14.3214 19.5301 14.5644L22.989 17.2555C24.2324 18.2231 24.3464 20.0607 23.2335 21.1722L21.6825 22.7233C20.5726 23.8333 18.9136 24.3209 17.3672 23.7763C13.4083 22.3852 9.81421 20.1188 6.85249 17.146C3.88014 14.1845 1.61398 10.5907 0.222699 6.63206C-0.320284 5.08699 0.167201 3.42641 1.27717 2.31636L2.82812 0.76529Z" fill="#EA3423"/></svg>',
            'step_title' => 'Заявка или звонок',
            'step_text'  => 'Свяжитесь с нами для консультации по проблеме.',
        ),
        array(
            'step_svg'   => '<svg viewBox="0 0 24 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.85935 0.895962C6.85935 0.658338 6.76377 0.430446 6.59365 0.262421C6.42352 0.0943957 6.19278 0 5.95219 0C5.71159 0 5.48085 0.0943957 5.31073 0.262421C5.1406 0.430446 5.04503 0.658338 5.04503 0.895962V2.78345C3.30328 2.92083 2.16146 3.25772 1.32204 4.08797C0.4814 4.91704 0.140308 6.04595 0 7.765H23.9999C23.8595 6.04475 23.5185 4.91704 22.6778 4.08797C21.8384 3.25772 20.6954 2.92083 18.9548 2.78226V0.895962C18.9548 0.658338 18.8593 0.430446 18.6891 0.262421C18.519 0.0943957 18.2883 0 18.0477 0C17.8071 0 17.5763 0.0943957 17.4062 0.262421C17.2361 0.430446 17.1405 0.658338 17.1405 0.895962V2.70341C16.3362 2.68788 15.4338 2.68788 14.419 2.68788H9.58083C8.56602 2.68788 7.6637 2.68788 6.85935 2.70341V0.895962ZM0 9.765C0 10.4327 0 11.1327 0 11.1327V18.1327C0 19.4327 0.448616 20.6 1.24716 21.4587C2.0457 22.3173 3.12876 22.7659 4.25806 22.7659H19.7419C20.8712 22.7659 21.9543 22.3173 22.7528 21.4587C23.5514 20.6 24 19.4327 24 18.1327V11.1327C24 11.1327 24 9.765 23.9844 9.765H0.0156Z" fill="#EA3423"/></svg>',
            'step_title' => 'Запись',
            'step_text'  => 'Подберем удобные дату и время для визита.',
        ),
        array(
            'step_svg'   => '<svg viewBox="0 0 24 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.7568 1.6104C0 3.223 0 5.8146 0 11C0 16.1854 0 18.7781 1.7568 20.3885C3.516 22 6.3432 22 12 22C17.6568 22 20.4852 22 22.242 20.3885C24 18.7792 24 16.1854 24 11C24 5.8146 24 3.2219 22.242 1.6104C20.4864 0 17.6568 0 12 0C6.3432 0 3.5148 0 1.7568 1.6104ZM15.7188 11.1034C15.498 10.7712 15.2808 10.4467 15.0696 10.2157C14.8452 9.9693 14.4516 9.6305 13.8492 9.6525C13.2468 9.6745 12.8844 10.0408 12.6816 10.3026C12.4908 10.5501 12.3036 10.8878 12.1116 11.2354L9.726 15.5474C9.638 15.7087 9.5608 15.847 9.4944 15.9621L9.2088 15.576L8.9976 15.2834C8.78874 14.9856 8.56573 14.6964 8.3292 14.4166C8.10821 14.1508 7.82956 13.9298 7.5108 13.7676C7.18644 13.6144 6.83096 13.5245 6.4668 13.5036C6.1404 13.475 5.7504 13.475 5.31 13.475H3.6C3.36131 13.475 3.13239 13.5619 2.9636 13.7166C2.79482 13.8714 2.7 14.0812 2.7 14.3C2.7 14.5188 2.79482 14.7286 2.9636 14.8834C3.13239 15.0381 3.36131 15.125 3.6 15.125H5.2728C5.7612 15.125 6.066 15.125 6.3 15.1459C6.516 15.1646 6.6048 15.1943 6.6612 15.2229C6.7176 15.2504 6.7944 15.2999 6.9312 15.455C7.0788 15.6222 7.2492 15.8543 7.5192 16.2272L7.7436 16.5341C7.9692 16.8454 8.1912 17.1501 8.4072 17.369C8.6376 17.6033 9.0336 17.9157 9.6192 17.8838C10.2024 17.8519 10.5552 17.4988 10.7556 17.2414C10.9404 17.0005 11.1216 16.6727 11.3064 16.3394L13.6908 12.0296C13.7844 11.8609 13.8656 11.7161 13.9344 11.5951C14.0144 11.711 14.1076 11.8492 14.214 12.0098L14.9988 13.1846C15.2388 13.5443 15.4524 13.8622 15.6588 14.113C15.882 14.3847 16.1376 14.6289 16.4892 14.8093C16.8408 14.9897 17.2008 15.0623 17.5704 15.0953C17.9112 15.125 18.318 15.125 18.7776 15.125H20.4C20.6387 15.125 20.8676 15.0381 21.0364 14.8834C21.2052 14.7286 21.3 14.5188 21.3 14.3C21.3 14.0812 21.2052 13.8714 21.0364 13.7166C20.8676 13.5619 20.6387 13.475 20.4 13.475H18.816C18.3072 13.475 17.988 13.475 17.7432 13.453C17.5176 13.4321 17.4264 13.3991 17.3688 13.3694C17.31 13.3397 17.232 13.2858 17.0964 13.1197C16.9476 12.9404 16.7808 12.6907 16.5144 12.2925L15.7188 11.1034Z" fill="#EA3423"/></svg>',
            'step_title' => 'Диагностика',
            'step_text'  => 'Полная дефектовка и выявление неисправностей.',
        ),
        array(
            'step_svg'   => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.4839 0.307692C15.4839 0.226087 15.4431 0.147825 15.3705 0.0901211C15.2979 0.0324176 15.1994 0 15.0968 0H4.25806C3.12876 0 2.0457 0.356592 1.24716 0.991331C0.448616 1.62607 0 2.48696 0 3.38462V20.6154C0 21.513 0.448616 22.3739 1.24716 23.0087C2.0457 23.6434 3.12876 24 4.25806 24H19.7419C20.8712 24 21.9543 23.6434 22.7528 23.0087C23.5514 22.3739 24 21.513 24 20.6154V8.48862C24 8.40701 23.9592 8.32875 23.8866 8.27104C23.814 8.21334 23.7156 8.18092 23.6129 8.18092H16.6452C16.3372 8.18092 16.0418 8.08367 15.824 7.91056C15.6062 7.73745 15.4839 7.50266 15.4839 7.25785V0.307692ZM16.6452 12.3077C16.9532 12.3077 17.2485 12.4049 17.4663 12.5781C17.6841 12.7512 17.8065 12.986 17.8065 13.2308C17.8065 13.4756 17.6841 13.7104 17.4663 13.8835C17.2485 14.0566 16.9532 14.1538 16.6452 14.1538H7.35484C7.04684 14.1538 6.75147 14.0566 6.53368 13.8835C6.3159 13.7104 6.19355 13.4756 6.19355 13.2308C6.19355 12.986 6.3159 12.7512 6.53368 12.5781C6.75147 12.4049 7.04684 12.3077 7.35484 12.3077H16.6452ZM16.6452 17.2308C16.9532 17.2308 17.2485 17.328 17.4663 17.5011C17.6841 17.6742 17.8065 17.909 17.8065 18.1538C17.8065 18.3987 17.6841 18.6335 17.4663 18.8066C17.2485 18.9797 16.9532 19.0769 16.6452 19.0769H7.35484C7.04684 19.0769 6.75147 18.9797 6.53368 18.8066C6.3159 18.6335 6.19355 18.3987 6.19355 18.1538C6.19355 17.909 6.3159 17.6742 6.53368 17.5011C6.75147 17.328 7.04684 17.2308 7.35484 17.2308H16.6452Z" fill="#EA3423"/></svg>',
            'step_title' => 'Согласование',
            'step_text'  => 'Утверждаем смету и варианты решения проблемы.',
        ),
        array(
            'step_svg'   => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.6 0H2.4C1.2 0 0 1.08 0 2.4V6.012C0 6.876 0.516 7.62 1.2 8.04V21.6C1.2 22.92 2.52 24 3.6 24H20.4C21.48 24 22.8 22.92 22.8 21.6V8.04C23.484 7.62 24 6.876 24 6.012V2.4C24 1.08 22.8 0 21.6 0ZM15.6 14.4H8.4V12H15.6V14.4ZM21.6 6H2.4V2.4L21.6 2.376V6Z" fill="#EA3423"/></svg>',
            'step_title' => 'Ремонт',
            'step_text'  => 'Заказ запчастей и выполнение ремонтных работ.',
        ),
        array(
            'step_svg'   => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 0C5.376 0 0 5.376 0 12C0 18.624 5.376 24 12 24C18.624 24 24 18.624 24 12C24 5.376 18.624 0 12 0ZM9.6 18L3.6 12L5.292 10.308L9.6 14.604L18.708 5.496L20.4 7.2L9.6 18Z" fill="#EA3423"/></svg>',
            'step_title' => 'Тест и выдача',
            'step_text'  => 'Проверка исправности и возврат авто.',
        ),
        array(
            'step_svg'   => '<svg viewBox="0 0 24 27" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 0L0 4.90909V12.2727C0 19.0841 5.12 25.4536 12 27C18.88 25.4536 24 19.0841 24 12.2727V4.90909L12 0ZM9.33333 19.6364L4.79935 15.463C4.369 15.0669 4.369 14.3876 4.79935 13.9915L5.20407 13.619C5.58629 13.2672 6.17423 13.2666 6.55713 13.6177L9.33333 16.1632L17.4404 8.70103C17.8241 8.3478 18.4149 8.34884 18.7974 8.70342L19.2061 9.08221C19.634 9.47889 19.6328 10.1562 19.2035 10.5514L9.33333 19.6364Z" fill="#EA3423"/></svg>',
            'step_title' => 'Гарантия',
            'step_text'  => 'Выдаем гарантийный талон и консультируем.',
        ),
    ) );
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
                $current_tpl = get_post_meta( $existing->ID, '_wp_page_template', true );
                if ( empty( $current_tpl ) || 'default' === $current_tpl ) {
                    update_post_meta( $existing->ID, '_wp_page_template', $data['template'] );
                }
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
    if ( 'page' !== get_option( 'show_on_front' ) ) {
        update_option( 'show_on_front', 'page' );
    }

    if ( ! empty( $pages['home'] ) && ! get_option( 'page_on_front' ) ) {
        update_option( 'page_on_front', $pages['home'] );
    }
    if ( ! empty( $pages['blog'] ) && ! get_option( 'page_for_posts' ) ) {
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
        if ( $thumb && ! has_post_thumbnail( $id ) ) {
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
        if ( $thumb && ! has_post_thumbnail( $id ) ) {
            set_post_thumbnail( $id, $thumb );
        }

        $current_order = (int) get_post_field( 'menu_order', $id );
        if ( 0 === $current_order ) {
            wp_update_post( array( 'ID' => $id, 'menu_order' => $svc_order ) );
        }
        $svc_order++;

        miauto_demo_set_post_meta_if_empty( $id, 'miauto_service_price', $svc['price'] );

        $result['services'][ $title ] = $id;
    }

    // Fill detailed fields for "Ремонт двигателя" (demo service).
    if ( ! empty( $result['services']['Ремонт двигателя'] ) ) {
        $sid = $result['services']['Ремонт двигателя'];
        miauto_demo_fill_service_fields( $sid, $images );

        // SEO text (post_content) — only if empty.
        if ( '' === get_post_field( 'post_content', $sid ) ) {
            wp_update_post( array(
                'ID'           => $sid,
                'post_content' => '<p>Технический центр МИ АВТО специализируется на ремонте и техническом обслуживании всего модельного ряда автомобилей Mitsubishi, в Москве и Московской области. Все работы проводятся в соответствии с техническими требованиями заводов изготовителей, с применением специального инструмента и оборудования, а также технической документации. Многолетний опыт, высокая квалификация сотрудников, наличие большой ремонтной базы, склада оригинальных, аналоговых и бывших в употреблении запчастей. Послегарантийное обслуживание и ремонт Mitsubishi — это наша специализация.</p>',
            ) );
        }
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

    // Gallery image mapping per work post.
    $work_galleries = array(
        'Капитальный ремонт ДВС Outlander XL' => array( 'car-outlander-xl', 'car-outlander-3', 'car-outlander-new' ),
        'Замена цепи ГРМ Pajero IV'            => array( 'car-pajero-sport-3', 'car-pajero-sport-2', 'car-lancer-10' ),
        'Ремонт ГБЦ ASX'                       => array( 'car-asx', 'car-lancer-10', 'car-outlander-3' ),
    );

    foreach ( $works as $w ) {
        $id = miauto_demo_get_or_create_post( 'miauto_work', $w['title'] );

        miauto_demo_set_post_meta_if_empty( $id, 'miauto_work_model', $w['model'] );
        miauto_demo_set_post_meta_if_empty( $id, 'miauto_work_mileage', $w['mileage'] );
        miauto_demo_set_post_meta_if_empty( $id, 'miauto_work_issue', $w['issue'] );
        miauto_demo_set_post_meta_if_empty( $id, 'miauto_work_price', $w['price'] );
        miauto_demo_set_post_meta_if_empty( $id, 'miauto_work_duration', $w['duration'] );

        $defects = array();
        foreach ( $w['defects'] as $d ) {
            $defects[] = array( 'defect_text' => $d );
        }
        miauto_demo_set_post_meta_if_empty( $id, 'miauto_work_defects', $defects );

        $done = array();
        foreach ( $w['done'] as $d ) {
            $done[] = array( 'done_text' => $d );
        }
        miauto_demo_set_post_meta_if_empty( $id, 'miauto_work_done', $done );

        // Gallery images.
        if ( isset( $work_galleries[ $w['title'] ] ) ) {
            $gallery_ids = array();
            foreach ( $work_galleries[ $w['title'] ] as $img_key ) {
                if ( ! empty( $images[ $img_key ] ) ) {
                    $gallery_ids[] = $images[ $img_key ];
                }
            }
            if ( ! empty( $gallery_ids ) ) {
                miauto_demo_set_post_meta_if_empty( $id, 'miauto_work_gallery', $gallery_ids );
            }
        }

        $result['works'][] = $id;
    }

    // Associate all works with "Ремонт двигателя" service.
    if ( ! empty( $result['services']['Ремонт двигателя'] ) ) {
        $engine_svc_id = $result['services']['Ремонт двигателя'];
        foreach ( $result['works'] as $work_id ) {
            miauto_demo_set_post_meta_if_empty( $work_id, 'miauto_work_services', array(
                array(
                    'id'      => $engine_svc_id,
                    'type'    => 'post',
                    'subtype' => 'miauto_service',
                    'value'   => 'post:miauto_service:' . $engine_svc_id,
                ),
            ) );
        }
    }

    return $result;
}

// ─── Fill service card fields (demo) ────────────────────────────────

function miauto_demo_fill_service_fields( $post_id, $images ) {
    // SC Hero.
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_subtitle', 'Капремонт / замена / устранение масложора / стук / перегрев — с гарантией по договору' );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_features', array(
        array( 'feature_text' => 'Честная дефектовка и согласование работ до начала' ),
        array( 'feature_text' => 'Фото/видео отчёт по этапам (по запросу)' ),
        array( 'feature_text' => 'Сроки от 1 дня (в зависимости от поломки)' ),
        array( 'feature_text' => 'Гарантия на работы и запчасти' ),
    ) );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_primary_text', 'Записаться на диагностику' );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_cta_secondary_text', 'Рассчитать стоимость' );

    if ( ! empty( $images['svc-engine'] ) ) {
        miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_image', $images['svc-engine'] );
    }

    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_hero_stats', array(
        array( 'stat_value' => '5,0',    'stat_label' => 'Рейтинг на картах' ),
        array( 'stat_value' => '500+',   'stat_label' => 'Отзывов на картах' ),
        array( 'stat_value' => 'с 2005', 'stat_label' => 'Опыт работы' ),
    ) );

    // SC-Examples title.
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_examples_title', 'Примеры ремонта двигателя' );

    // Symptoms.
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_title', 'Когда нужен ремонт двигателя' );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_subtitle', 'Вы не платите за лишнее — только согласованные работы' );
    $sym_img = $images['hero-bg'] ?? '';
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cards', array(
        array( 'sym_image' => $sym_img, 'symptom_title' => 'Дым из выхлопной',        'symptom_desc' => 'Синий, белый или черный дым — признак серьезных неисправностей.' ),
        array( 'sym_image' => $sym_img, 'symptom_title' => 'Масложор/подтеки масла',   'symptom_desc' => 'Постоянное доливание масла, лужи под машиной или следы потеков.' ),
        array( 'sym_image' => $sym_img, 'symptom_title' => 'Потеря мощности/троение',  'symptom_desc' => 'Двигатель не тянет, машина дергается, плохо разгоняется.' ),
        array( 'sym_image' => $sym_img, 'symptom_title' => 'Стук/шум/вибрации',        'symptom_desc' => 'Постоянные звуки при работе двигателя или повышенная вибрация.' ),
        array( 'sym_image' => $sym_img, 'symptom_title' => 'Перегрев/эмульсия',        'symptom_desc' => 'Стрелка температуры выше нормы, «пена» на крышке маслозаливной горловины.' ),
        array( 'sym_image' => $sym_img, 'symptom_title' => 'Ошибки Check Engine',      'symptom_desc' => 'Горящий индикатор «Check Engine» на приборной панели.' ),
    ) );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_text', 'Запишитесь — проверим причину и предложим варианты решения' );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_symptoms_cta_btn_text', 'Записаться на диагностику' );

    // Svc List.
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_title', 'Какие работы выполняем' );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_svc_list_items', array(
        array( 'svc_title' => 'Компьютерная диагностика, эндоскопия цилиндров', 'svc_desc' => 'Считывание кодов ошибок сканером и осмотр состояния цилиндров через эндоскоп без разборки двигателя.' ),
        array( 'svc_title' => 'Замер компрессии/давления масла',                'svc_desc' => 'Инструментальная проверка давления в цилиндрах и масляной системе для оценки износа деталей.' ),
        array( 'svc_title' => 'Дефектовка двигателя',                          'svc_desc' => 'Разборка, чистка и выявление всех скрытых дефектов с составлением точной ведомости.' ),
        array( 'svc_title' => 'Замена прокладок/сальников',                     'svc_desc' => 'Восстановление герметичности двигателя, устранение течей масла и технических жидкостей.' ),
        array( 'svc_title' => 'Замена цепи/ремня ГРМ',                         'svc_desc' => 'Обслуживание механизма газораспределения: замена цепи или ремня с комплектующими.' ),
        array( 'svc_title' => 'Шлифовка ГБЦ, притирка клапанов',               'svc_desc' => 'Механическая обработка головки блока, восстановление плотности прилегания клапанов.' ),
        array( 'svc_title' => 'Замена вкладышей/колец/поршней',                 'svc_desc' => 'Капитальный ремонт цилиндро-поршневой группы для восстановления компрессии и устранения масложора.' ),
        array( 'svc_title' => 'Сборка, запуск и настройка ЭБУ',                'svc_desc' => 'Окончательная сборка двигателя с соблюдением моментов затяжки, первый запуск, контрольная диагностика и программные настройки.' ),
    ) );

    // SC Prices.
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_title', 'Стоимость ремонта двигателя' );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_subtitle', 'Вы не платите за лишнее — только согласованные работы' );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_rows', array(
        array( 'service_name' => 'Диагностика двигателя',  'sc_service_price' => 'от 5 000 ₽' ),
        array( 'service_name' => 'Эндоскопия цилиндров',   'sc_service_price' => 'от 4 000 ₽' ),
        array( 'service_name' => 'Замена прокладки ГБЦ',    'sc_service_price' => 'от 8 000 ₽' ),
        array( 'service_name' => 'Замена цепи ГРМ',         'sc_service_price' => 'от 14 000 ₽' ),
        array( 'service_name' => 'Капитальный ремонт',      'sc_service_price' => 'от 40 000 ₽' ),
    ) );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_heading', 'Получите точную смету после дефектовки' );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_desc', 'До начала работ всё согласуем' );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_prices_footer_btn_text', 'Записаться на диагностику' );

    // Warranty.
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_title', 'Гарантия и ответственность' );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_subtitle', 'Если выявим, что ремонт нецелесообразен — предложим альтернативы' );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_warranty_cards', array(
        array(
            'war_svg'          => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path d="M9 0L0 3.636V9.091C0 14.136 3.84 18.855 9 20c5.16-1.145 9-5.864 9-10.909V3.636L9 0zM7 14.546L3.773 11.611a.82.82 0 010-1.404.826.826 0 011.275-.002L7 11.973l5.948-5.407a.826.826 0 011.282.003.82.82 0 01-.003 1.406L7 14.546z" fill="white"/></svg>',
            'warranty_text' => 'Гарантия на работы — 1 год',
        ),
        array(
            'war_svg'          => '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M18 0H2C1 0 0 .9 0 2v3.01c0 .72.43 1.34 1 1.69V18c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V6.7c.57-.35 1-.97 1-1.69V2c0-1.1-1-.9-2 0zM13 12H7v-2h6v2zM18 5H2V2l16-.02V5z" fill="white"/></svg>',
            'warranty_text' => 'Запчасти — по условиям поставщика',
        ),
        array(
            'war_svg'          => '<svg viewBox="0 0 18 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.613.256a.29.29 0 00-.29-.256H3.194C2.347 0 1.534.297.935.826.336 1.355 0 2.072 0 2.82v14.36c0 .748.336 1.465.935 1.994.6.529 1.412.826 2.259.826h11.612c.847 0 1.66-.297 2.258-.826.6-.529.936-1.246.936-1.994V7.074a.29.29 0 00-.29-.257h-5.226a.87.87 0 01-.612-.226.77.77 0 01-.259-.543V.256zM12.484 10.256c.231 0 .452.081.616.226a.77.77 0 01.255.543.77.77 0 01-.255.544.87.87 0 01-.616.226H5.516a.87.87 0 01-.616-.226.77.77 0 01-.255-.544c0-.208.092-.404.255-.543a.87.87 0 01.616-.226h6.968zm0 4.103c.231 0 .452.081.616.225a.77.77 0 01.255.544.77.77 0 01-.255.544.87.87 0 01-.616.225H5.516a.87.87 0 01-.616-.225.77.77 0 01-.255-.544c0-.208.092-.404.255-.544a.87.87 0 01.616-.225h6.968z" fill="white"/><path d="M13.356.59c0-.19.224-.31.39-.192.14.1.266.217.375.35l3.499 4.305c.079.098-.007.226-.144.226h-3.83a.29.29 0 01-.29-.257V.59z" fill="white"/></svg>',
            'warranty_text' => 'Документы: заказ-наряд, акт выполненных работ',
        ),
        array(
            'war_svg'          => '<svg viewBox="0 0 20 18" xmlns="http://www.w3.org/2000/svg"><path d="M10 13.2c1.77 0 3.2-1.43 3.2-3.2 0-1.77-1.43-3.2-3.2-3.2-1.77 0-3.2 1.43-3.2 3.2 0 1.77 1.43 3.2 3.2 3.2zM7 0L5.17 2H2C.9 2 0 2.9 0 4v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2h-3.17L13 0H7zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z" fill="white"/></svg>',
            'warranty_text' => 'Фото/видео дефектовки (по запросу)',
        ),
    ) );

    // FAQ.
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_heading', 'FAQ' );
    miauto_demo_set_post_meta_if_empty( $post_id, 'miauto_sc_faq_entries', array(
        array(
            'faq_entry_active'   => true,
            'faq_entry_question' => 'Сколько занимает ремонт двигателя?',
            'faq_entry_answer'   => 'Сроки зависят от сложности работ. Замена цепи ГРМ — от 1 до 3 дней. Капитальный ремонт двигателя — от 5 до 10 рабочих дней. Точные сроки сообщим после диагностики.',
        ),
        array(
            'faq_entry_active'   => false,
            'faq_entry_question' => 'Можно ли ездить с этой проблемой?',
            'faq_entry_answer'   => 'Это зависит от типа поломки. Если проблема серьезная и может привести к дальнейшему повреждению двигателя, не рекомендуется ездить на автомобиле. Мы советуем проконсультироваться с нашими специалистами для оценки состояния двигателя.',
        ),
        array(
            'faq_entry_active'   => false,
            'faq_entry_question' => 'Как понять, нужен капремонт или можно ограничиться заменой?',
            'faq_entry_answer'   => 'Решение принимается по результатам дефектовки. Мы разбираем двигатель, проводим замеры износа деталей и составляем ведомость. После этого предлагаем оптимальный вариант ремонта.',
        ),
        array(
            'faq_entry_active'   => false,
            'faq_entry_question' => 'Что если после вскрытия цена изменится?',
            'faq_entry_answer'   => 'Все работы согласуются с вами до начала выполнения. Если при дефектовке выявятся дополнительные неисправности, мы обязательно свяжемся с вами и согласуем изменения в смете.',
        ),
        array(
            'faq_entry_active'   => false,
            'faq_entry_question' => 'Какие запчасти ставите?',
            'faq_entry_answer'   => 'Используем оригинальные запчасти и проверенные аналоги от ведущих производителей. Выбор всегда согласуется с клиентом по цене и качеству.',
        ),
        array(
            'faq_entry_active'   => false,
            'faq_entry_question' => 'Даете ли гарантию?',
            'faq_entry_answer'   => 'Да, на все выполненные работы предоставляется гарантия 1 год. На запчасти — гарантия по условиям поставщика. Выдаём гарантийный талон.',
        ),
        array(
            'faq_entry_active'   => false,
            'faq_entry_question' => 'Можно ли привезти свои запчасти?',
            'faq_entry_answer'   => 'Да, вы можете привезти свои запчасти. Однако в этом случае гарантия на запчасти не распространяется. Гарантия на выполненные работы сохраняется.',
        ),
        array(
            'faq_entry_active'   => false,
            'faq_entry_question' => 'Как происходит согласование?',
            'faq_entry_answer'   => 'После диагностики составляем смету с перечнем работ и стоимостью. Отправляем её вам. Работы начинаем только после вашего согласия.',
        ),
        array(
            'faq_entry_active'   => false,
            'faq_entry_question' => 'Какие причины могут привести к поломке двигателя?',
            'faq_entry_answer'   => 'Основные причины: несвоевременная замена масла, перегрев двигателя, использование некачественного топлива, естественный износ деталей, пробег свыше 100 000 км без обслуживания.',
        ),
    ) );
}

// ─── 7. Set page meta fields ────────────────────────────────────────

function miauto_demo_set_page_meta( $pages, $images ) {
    // --- Homepage ---
    $home_id = $pages['home'] ?? 0;
    if ( $home_id ) {
        // Hero slides.
        miauto_demo_set_post_meta_if_empty( $home_id, 'miauto_hero_slides', array(
            array(
                'slide_image'      => $images['hero-bg'] ?? '',
                'slide_image_alt'  => 'Ремонт Mitsubishi',
                'slide_title'      => 'Ремонт автомобилей Mitsubishi с гарантией качества',
                'slide_desc' => 'Надежность и точность в каждой детали. Ремонт автомобилей Mitsubishi с использованием оригинальных запчастей и лучших технологий. Доверяйте профессионалам!',
                'slide_cta_text'   => 'Записаться на ремонт',
                'slide_cta_url'    => '#form-section',
            ),
            array(
                'slide_image'      => $images['hero-bg'] ?? '',
                'slide_image_alt'  => 'Диагностика Mitsubishi',
                'slide_title'      => 'Компьютерная диагностика всех систем автомобиля',
                'slide_desc' => 'Выявим любую неисправность с помощью дилерского оборудования Mitsubishi. Точная диагностика — залог качественного ремонта.',
                'slide_cta_text'   => 'Записаться на диагностику',
                'slide_cta_url'    => '#form-section',
            ),
            array(
                'slide_image'      => $images['hero-bg'] ?? '',
                'slide_image_alt'  => 'ТО Mitsubishi',
                'slide_title'      => 'Техническое обслуживание по регламенту производителя',
                'slide_desc' => 'Проводим ТО в полном соответствии с рекомендациями Mitsubishi. Сохраняем гарантию дилера и продлеваем ресурс вашего автомобиля.',
                'slide_cta_text'   => 'Подробнее об услугах',
                'slide_cta_url'    => '/services/',
            ),
            array(
                'slide_image'      => $images['hero-bg'] ?? '',
                'slide_image_alt'  => 'Акция сход-развал',
                'slide_title'      => 'Скидка 50% на сход-развал до конца февраля',
                'slide_desc' => 'Успейте записаться по акции! Профессиональный сход-развал на стенде Hunter с гарантией точности. Звоните прямо сейчас.',
                'slide_cta_text'   => 'Позвонить нам',
                'slide_cta_url'    => 'tel:+79263383929',
            ),
        ) );

        // Hero features.
        miauto_demo_set_post_meta_if_empty( $home_id, 'miauto_hero_features', array(
            array(
                'feat_text' => 'Работаем с 10:00 до 21:00',
                'feat_svg'  => '<svg viewBox="0 0 14 14"><circle cx="7" cy="7" r="6" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M7 4V7.5L9.5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>',
            ),
            array(
                'feat_text' => 'Оригинальные запчасти',
                'feat_svg'  => '<svg viewBox="0 0 14 14"><path d="M7 1L8.5 3.5H11.5L10 6L11.5 8.5H8.5L7 11L5.5 8.5H2.5L4 6L2.5 3.5H5.5L7 1Z" stroke="currentColor" stroke-width="1.2" fill="none"/><circle cx="7" cy="6" r="1.5" fill="currentColor"/></svg>',
            ),
            array(
                'feat_text' => 'Бесплатная консультация',
                'feat_svg'  => '<svg viewBox="0 0 14 14"><path d="M2 2H12C12.55 2 13 2.45 13 3V9C13 9.55 12.55 10 12 10H8L5 13V10H2C1.45 10 1 9.55 1 9V3C1 2.45 1.45 2 2 2Z" stroke="currentColor" stroke-width="1.3" fill="none"/></svg>',
            ),
        ) );

        // Homepage sections.
        miauto_demo_set_post_meta_if_empty( $home_id, 'miauto_car_models_title', 'Модели авто' );
        miauto_demo_set_post_meta_if_empty( $home_id, 'miauto_services_title', 'Категории услуг' );
        miauto_demo_set_post_meta_if_empty( $home_id, 'miauto_services_more_text', 'Смотреть еще' );

        miauto_demo_set_post_meta_if_empty( $home_id, 'miauto_about_title', 'О нас' );
        miauto_demo_set_post_meta_if_empty( $home_id, 'miauto_about_text', 'Все работы в Техническом центре "Ми-Авто" проводятся в соответствии с техническими требованиями заводов изготовителей с применением специального инструмента и оборудования, а также технической документации.<br><br>Сотрудники технического центра имеют высокую квалификацию и специальную подготовку, для проведения обслуживания и ремонта автомобилей Мицубиси (Mitsubishi), выполняют слесарный и агрегатный ремонт автомобилей Мицубиси (Mitsubishi).' );

        if ( ! empty( $images['about-us'] ) ) {
            miauto_demo_set_post_meta_if_empty( $home_id, 'miauto_about_image', $images['about-us'] );
        }

        miauto_demo_set_post_meta_if_empty( $home_id, 'miauto_articles_title', 'Полезные статьи' );
        miauto_demo_set_post_meta_if_empty( $home_id, 'miauto_articles_link_text', 'Узнать больше' );
        miauto_demo_set_post_meta_if_empty( $home_id, 'miauto_articles_count', 2 );

        // Service details tabs.
        miauto_demo_set_post_meta_if_empty( $home_id, 'miauto_svc_details_title', 'Услуги СТО' );
        miauto_demo_set_post_meta_if_empty( $home_id, 'miauto_svc_details_tabs', array(
            array(
                'tab_id'      => 'to',
                'tab_icon'    => '<svg viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M15.8766 9.882C15.9136 9.594 15.9414 9.306 15.9414 9C15.9414 8.694 15.9136 8.406 15.8766 8.118L17.8289 6.633C18.0047 6.498 18.051 6.255 17.9399 6.057L16.0894 2.943C15.9784 2.745 15.7286 2.673 15.525 2.745L13.2212 3.645C12.74 3.285 12.2219 2.988 11.6575 2.763L11.3059 0.378C11.2782 0.162 11.0839 0 10.8525 0H7.15155C6.92024 0 6.72593 0.162 6.69818 0.378L6.34658 2.763C5.78218 2.988 5.26404 3.294 4.78291 3.645L2.47905 2.745C2.26624 2.664 2.02567 2.745 1.91464 2.943L0.0641489 6.057C-0.0561333 6.255 -0.000618345 6.498 0.175179 6.633L2.12745 8.118C2.09044 8.406 2.06268 8.703 2.06268 9C2.06268 9.297 2.09044 9.594 2.12745 9.882L0.175179 11.367C-0.000618345 11.502 -0.0468809 11.745 0.0641489 11.943L1.91464 15.057C2.02567 15.255 2.27549 15.327 2.47905 15.255L4.78291 14.355C5.26404 14.715 5.78218 15.012 6.34658 15.237L6.69818 17.622C6.72593 17.838 6.92024 18 7.15155 18H10.8525C11.0839 18 11.2782 17.838 11.3059 17.622L11.6575 15.237C12.2219 15.012 12.74 14.706 13.2212 14.355L15.525 15.255C15.7378 15.336 15.9784 15.255 16.0894 15.057L17.9399 11.943C18.051 11.745 18.0047 11.502 17.8289 11.367L15.8766 9.882ZM9.00204 12.15C7.21632 12.15 5.76368 10.737 5.76368 9C5.76368 7.263 7.21632 5.85 9.00204 5.85C10.7878 5.85 12.2404 7.263 12.2404 9C12.2404 10.737 10.7878 12.15 9.00204 12.15Z" fill="currentColor"/></svg>',
                'tab_title'   => 'Техническое обслуживание',
                'badge'       => 'Рекомендуется каждые 15 000 км',
                'panel_title' => 'Техническое обслуживание Mitsubishi',
                'panel_text'  => '<p>Проводим полное <strong>регламентное и техническое обслуживание</strong> автомобилей марки <strong>MITSUBISHI</strong>.</p><p>Используем как оригинальные так и аналоговые расходные материалы и запчасти, по желанию клиента.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Замена ГРМ' ),
                    array( 'tabfeat_item' => 'Замена масел и жидкостей' ),
                    array( 'tabfeat_item' => 'Замена масляного/воздушного/топливного фильтров' ),
                    array( 'tabfeat_item' => 'Замена фильтра салона' ),
                    array( 'tabfeat_item' => 'Замена тормозной жидкости' ),
                    array( 'tabfeat_item' => 'Замена масла в АКПП/МКПП' ),
                    array( 'tabfeat_item' => 'Замена свечей зажигания' ),
                    array( 'tabfeat_item' => 'Замена жидкости в ГУР' ),
                    array( 'tabfeat_item' => 'Проверка и смазка шарниров в.т.ч карданных' ),
                    array( 'tabfeat_item' => 'Проверка пыльников' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 3 500 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
            array(
                'tab_id'      => 'suspension',
                'tab_icon'    => '<svg viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M0 4.05V2.25C0 1.8315 1.08033e-07 1.6227 0.0380001 1.449C0.115104 1.09966 0.305628 0.778759 0.585458 0.526912C0.865288 0.275065 1.22184 0.103594 1.61 0.0342001C1.803 9.72301e-08 2.035 0 2.5 0C2.965 0 3.197 9.72301e-08 3.39 0.0342001C3.77816 0.103594 4.13471 0.275065 4.41454 0.526912C4.69437 0.778759 4.8849 1.09966 4.962 1.449C5 1.6227 5 1.8315 5 2.25V2.475H13V2.25C13 1.8315 13 1.6227 13.038 1.449C13.1151 1.09966 13.3056 0.778759 13.5855 0.526912C13.8653 0.275065 14.2218 0.103594 14.61 0.0342001C14.803 9.72301e-08 15.035 0 15.5 0C15.965 0 16.197 9.72301e-08 16.39 0.0342001C16.7782 0.103594 17.1347 0.275065 17.4145 0.526912C17.6944 0.778759 17.8849 1.09966 17.962 1.449C18 1.6227 18 1.8315 18 2.25V4.05C18 4.4685 18 4.6773 17.962 4.851C17.8849 5.20034 17.6944 5.52124 17.4145 5.77309C17.1347 6.02493 16.7782 6.19641 16.39 6.2658C16.197 6.3 15.965 6.3 15.5 6.3C15.035 6.3 14.803 6.3 14.61 6.2658C14.2218 6.19641 13.8653 6.02493 13.5855 5.77309C13.3056 5.52124 13.1151 5.20034 13.038 4.851C13 4.6773 13 4.4685 13 4.05V3.825H9.75V14.175H13V13.95C13 13.5315 13 13.3227 13.038 13.149C13.1151 12.7997 13.3056 12.4788 13.5855 12.2269C13.8653 11.9751 14.2218 11.8036 14.61 11.7342C14.803 11.7 15.035 11.7 15.5 11.7C15.965 11.7 16.197 11.7 16.39 11.7342C16.7782 11.8036 17.1347 11.9751 17.4145 12.2269C17.6944 12.4788 17.8849 12.7997 17.962 13.149C18 13.3227 18 13.5315 18 13.95V15.75C18 16.1685 18 16.3773 17.962 16.551C17.8849 16.9003 17.6944 17.2212 17.4145 17.4731C17.1347 17.7249 16.7782 17.8964 16.39 17.9658C16.197 18 15.965 18 15.5 18C15.035 18 14.803 18 14.61 17.9658C14.2218 17.8964 13.8653 17.7249 13.5855 17.4731C13.3056 17.2212 13.1151 16.9003 13.038 16.551C13 16.3773 13 16.1685 13 15.75V15.525H5V15.75C5 16.1685 5 16.3773 4.962 16.551C4.8849 16.9003 4.69437 17.2212 4.41454 17.4731C4.13471 17.7249 3.77816 17.8964 3.39 17.9658C3.197 18 2.965 18 2.5 18C2.035 18 1.803 18 1.61 17.9658C1.22184 17.8964 0.865288 17.7249 0.585458 17.4731C0.305628 17.2212 0.115104 16.9003 0.0380001 16.551C1.08033e-07 16.3773 0 16.1685 0 15.75V13.95C0 13.5315 1.08033e-07 13.3227 0.0380001 13.149C0.115104 12.7997 0.305628 12.4788 0.585458 12.2269C0.865288 11.9751 1.22184 11.8036 1.61 11.7342C1.803 11.7 2.035 11.7 2.5 11.7C2.965 11.7 3.197 11.7 3.39 11.7342C3.77816 11.8036 4.13471 11.9751 4.41454 12.2269C4.69437 12.4788 4.8849 12.7997 4.962 13.149C5 13.3227 5 13.5315 5 13.95V14.175H8.25V3.825H5V4.05C5 4.4685 5 4.6773 4.962 4.851C4.8849 5.20034 4.69437 5.52124 4.41454 5.77309C4.13471 6.02493 3.77816 6.19641 3.39 6.2658C3.197 6.3 2.965 6.3 2.5 6.3C2.035 6.3 1.803 6.3 1.61 6.2658C1.22184 6.19641 0.865288 6.02493 0.585458 5.77309C0.305628 5.52124 0.115104 5.20034 0.0380001 4.851C1.08033e-07 4.6773 0 4.4685 0 4.05Z" fill="currentColor"/></svg>',
                'tab_title'   => 'Подвеска',
                'badge'       => '',
                'panel_title' => 'Ремонт подвески Mitsubishi',
                'panel_text'  => '<p>Производим полный перечень работ по <strong>ремонту и диагностике подвески MITSUBISHI</strong>.</p><p>Опытные мастера проведут осмотр и дефектовку, все работы согласовываются предварительно с клиентом.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Замена амортизаторов' ),
                    array( 'tabfeat_item' => 'Замена рычагов' ),
                    array( 'tabfeat_item' => 'Замена сайлентблоков' ),
                    array( 'tabfeat_item' => 'Замена ступичных подшипников' ),
                    array( 'tabfeat_item' => 'Замена шаровых опор' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 3 500 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
            array(
                'tab_id'      => 'engine',
                'tab_icon'    => '<svg viewBox="0 0 18 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M4.90909 0V2H7.36364V4H4.90909L3.27273 6V9H1.63636V6H0V14H1.63636V11H3.27273V14H5.72727L7.36364 16H13.9091V12H15.5455V15H18V5H15.5455V8H13.9091V4H9V2H11.4545V0H4.90909Z" fill="currentColor"/></svg>',
                'tab_title'   => 'Двигатель',
                'badge'       => '',
                'panel_title' => 'Ремонт двигателя Mitsubishi',
                'panel_text'  => '<p>Производим <strong>ремонт и диагностику бензиновых и дизельных двигателей</strong> автомобилей <strong>MITSUBISHI</strong>.</p><p>Капитальный ремонт, замена ГРМ, устранение масложора — любой сложности.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Замена ГРМ' ),
                    array( 'tabfeat_item' => 'Замена приводных ремней' ),
                    array( 'tabfeat_item' => 'Замена двигателя' ),
                    array( 'tabfeat_item' => 'Капитальный ремонт двигателя' ),
                    array( 'tabfeat_item' => 'Замена прокладки ГБЦ' ),
                    array( 'tabfeat_item' => 'Диагностика двигателя' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 5 000 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
            array(
                'tab_id'      => 'akpp',
                'tab_icon'    => '<svg viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M8.99981 18C13.9148 18 18 13.9235 18 9C18 4.08513 13.9058 0 8.99078 0C4.06752 0 0 4.08513 0 9C0 13.9235 4.07618 18 8.99981 18ZM8.99153 10.3237C8.52356 10.3237 8.26793 10.05 8.24986 9.58203L8.12637 5.22396C8.10868 4.73831 8.47914 4.39459 8.98212 4.39459C9.47644 4.39459 9.85556 4.74734 9.83824 5.23262L9.71438 9.58278C9.69668 10.059 9.43201 10.3241 8.99115 10.3241M8.99115 13.5712C8.47914 13.5712 8.00251 13.1653 8.00251 12.6179C8.00251 12.0705 8.47048 11.6654 8.99115 11.6654C9.50279 11.6654 9.97904 12.0622 9.97904 12.6179C9.97904 13.174 9.49376 13.5712 8.99115 13.5712Z" fill="currentColor"/></svg>',
                'tab_title'   => 'АКПП',
                'badge'       => '',
                'panel_title' => 'Ремонт и обслуживание АКПП Mitsubishi',
                'panel_text'  => '<p><strong>Обслуживание, диагностика и ремонт автоматических коробок передач MITSUBISHI</strong> — одна из наших специализаций.</p><p>Многолетний опыт, большая ремонтная база и умеренные цены.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Диагностика АКПП' ),
                    array( 'tabfeat_item' => 'Замена масла в АКПП' ),
                    array( 'tabfeat_item' => 'Ремонт гидроблока' ),
                    array( 'tabfeat_item' => 'Замена гидротрансформатора' ),
                    array( 'tabfeat_item' => 'Ремонт мехатроника' ),
                    array( 'tabfeat_item' => 'Замена фрикционов' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 5 000 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
            array(
                'tab_id'      => 'mkpp',
                'tab_icon'    => '<svg viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M13 11.5309C14.306 11.5309 15.418 12.3332 15.83 13.4527H17C17.2652 13.4527 17.5196 13.5539 17.7071 13.7341C17.8946 13.9143 18 14.1587 18 14.4136C18 14.6684 17.8946 14.9128 17.7071 15.0931C17.5196 15.2733 17.2652 15.3745 17 15.3745H15.83C15.6234 15.9371 15.2403 16.4243 14.7334 16.769C14.2265 17.1136 13.6209 17.2987 13 17.2987C12.3791 17.2987 11.7735 17.1136 11.2666 16.769C10.7597 16.4243 10.3766 15.9371 10.17 15.3745H1C0.734784 15.3745 0.48043 15.2733 0.292893 15.0931C0.105357 14.9128 0 14.6684 0 14.4136C0 14.1587 0.105357 13.9143 0.292893 13.7341C0.48043 13.5539 0.734784 13.4527 1 13.4527H10.17C10.3769 12.8904 10.7602 12.4036 11.267 12.0594C11.7739 11.7152 12.3793 11.5306 13 11.5309ZM13 13.4527C12.7348 13.4527 12.4804 13.5539 12.2929 13.7341C12.1054 13.9143 12 14.1587 12 14.4136C12 14.6684 12.1054 14.9128 12.2929 15.0931C12.4804 15.2733 12.7348 15.3745 13 15.3745C13.2652 15.3745 13.5196 15.2733 13.7071 15.0931C13.8946 14.9128 14 14.6684 14 14.4136C14 14.1587 13.8946 13.9143 13.7071 13.7341C13.5196 13.5539 13.2652 13.4527 13 13.4527ZM5 5.76544C5.58899 5.76536 6.16497 5.93188 6.65613 6.24424C7.14729 6.55661 7.5319 7.00098 7.762 7.52197L7.829 7.68725H17C17.2549 7.68752 17.5 7.7813 17.6854 7.94943C17.8707 8.11756 17.9822 8.34734 17.9972 8.59184C18.0121 8.83633 17.9293 9.07708 17.7657 9.26489C17.6021 9.45271 17.3701 9.57341 17.117 9.60233L17 9.60906H7.83C7.6284 10.157 7.25917 10.6337 6.77073 10.9765C6.28229 11.3194 5.69744 11.5124 5.09285 11.5303C4.48827 11.5481 3.89217 11.39 3.38273 11.0767C2.87328 10.7633 2.47427 10.3094 2.238 9.77433L2.17 9.60906H1C0.74512 9.60879 0.499968 9.51501 0.314632 9.34688C0.129296 9.17875 0.017765 8.94896 0.00282788 8.70447C-0.0121092 8.45997 0.0706746 8.21922 0.234265 8.03141C0.397855 7.8436 0.629904 7.7229 0.883 7.69397L1 7.68725H2.17C2.37688 7.12497 2.76016 6.63819 3.26702 6.29399C3.77387 5.94979 4.37935 5.76512 5 5.76544ZM5 7.68725C4.73478 7.68725 4.48043 7.78849 4.29289 7.96869C4.10536 8.14889 4 8.3933 4 8.64815C4 8.903 4.10536 9.14741 4.29289 9.32762C4.48043 9.50782 4.73478 9.60906 5 9.60906C5.26522 9.60906 5.51957 9.50782 5.70711 9.32762C5.89464 9.14741 6 8.903 6 8.64815C6 8.3933 5.89464 8.14889 5.70711 7.96869C5.51957 7.78849 5.26522 7.68725 5 7.68725ZM13 3.96348e-07C14.306 3.96348e-07 15.418 0.802357 15.83 1.92181H17C17.2652 1.92181 17.5196 2.02305 17.7071 2.20326C17.8946 2.38346 18 2.62787 18 2.88272C18 3.13757 17.8946 3.38198 17.7071 3.56218C17.5196 3.74239 17.2652 3.84362 17 3.84362H15.83C15.6234 4.40627 15.2403 4.89348 14.7334 5.2381C14.2265 5.58272 13.6209 5.76779 13 5.76779C12.3791 5.76779 11.7735 5.58272 11.2666 5.2381C10.7597 4.89348 10.3766 4.40627 10.17 3.84362H1C0.734784 3.84362 0.48043 3.74239 0.292893 3.56218C0.105357 3.38198 0 3.13757 0 2.88272C0 2.62787 0.105357 2.38346 0.292893 2.20326C0.48043 2.02305 0.734784 1.92181 1 1.92181H10.17C10.3769 1.35953 10.7602 0.87275 11.267 0.528552C11.7739 0.184355 12.3793 -0.000312256 13 3.96348e-07ZM13 1.92181C12.7348 1.92181 12.4804 2.02305 12.2929 2.20326C12.1054 2.38346 12 2.62787 12 2.88272C12 3.13757 12.1054 3.38198 12.2929 3.56218C12.4804 3.74239 12.7348 3.84362 13 3.84362C13.2652 3.84362 13.5196 3.74239 13.7071 3.56218C13.8946 3.38198 14 3.13757 14 2.88272C14 2.62787 13.8946 2.38346 13.7071 2.20326C13.5196 2.02305 13.2652 1.92181 13 1.92181Z" fill="currentColor"/></svg>',
                'tab_title'   => 'МКПП',
                'badge'       => '',
                'panel_title' => 'Ремонт и обслуживание МКПП Mitsubishi',
                'panel_text'  => '<p><strong>Обслуживание, диагностика и ремонт механических коробок передач MITSUBISHI</strong> — одна из наших специализаций.</p><p>Опытные мастера помогут в самых сложных случаях — от замены сальников до полного восстановления.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Диагностика МКПП' ),
                    array( 'tabfeat_item' => 'Замена масла в МКПП' ),
                    array( 'tabfeat_item' => 'Замена сцепления' ),
                    array( 'tabfeat_item' => 'Замена подшипников' ),
                    array( 'tabfeat_item' => 'Ремонт кулисы переключения' ),
                    array( 'tabfeat_item' => 'Замена сальников КПП' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 3 500 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
            array(
                'tab_id'      => 'electrics',
                'tab_icon'    => '<svg viewBox="0 0 14 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M5.59482 18H4.19479L5.59482 11H0.694706C-0.117312 11 -0.103312 10.68 0.162694 10.34C0.4287 10 0.232696 10.26 0.260696 10.22C2.06674 7.94 4.7828 4.54 8.39488 0H9.79491L8.39488 7H13.295C13.981 7 14.079 7.33 13.953 7.51L13.855 7.66C8.33888 14.55 5.59482 18 5.59482 18Z" fill="currentColor"/></svg>',
                'tab_title'   => 'Автоэлектрика',
                'badge'       => '',
                'panel_title' => 'Диагностика и ремонт автоэлектрики Mitsubishi',
                'panel_text'  => '<p>Профессиональная <strong>диагностика и ремонт электрооборудования</strong> автомобилей <strong>MITSUBISHI</strong>.</p><p>Работаем с оригинальным сканером <strong>MUT III</strong> для точного считывания и сброса ошибок.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Диагностика MUT III' ),
                    array( 'tabfeat_item' => 'Установка электрооборудования' ),
                    array( 'tabfeat_item' => 'Демонтаж электрооборудования' ),
                    array( 'tabfeat_item' => 'Ремонт электрооборудования' ),
                    array( 'tabfeat_item' => 'Чтение/сброс ошибок' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 500 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
            array(
                'tab_id'      => 'tyres',
                'tab_icon'    => '<svg viewBox="0 0 19 19" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M9.5 0.256757C4.39502 0.256757 0.256757 4.39502 0.256757 9.5C0.256757 14.605 4.39502 18.7432 9.5 18.7432C14.605 18.7432 18.7432 14.605 18.7432 9.5C18.7432 4.39502 14.605 0.256757 9.5 0.256757ZM9.5 16.4223C5.67714 16.4223 2.57774 13.3229 2.57774 9.5C2.57774 5.67714 5.67747 2.57774 9.5 2.57774C13.3225 2.57774 16.4223 5.67747 16.4223 9.5C16.4223 13.3225 13.3225 16.4223 9.5 16.4223Z" fill="currentColor"/><path d="M9.49934 7.26341C8.90633 7.26341 8.33762 7.49898 7.9183 7.9183C7.49898 8.33762 7.26341 8.90633 7.26341 9.49934C7.26341 10.0923 7.49898 10.6611 7.9183 11.0804C8.33762 11.4997 8.90633 11.7353 9.49934 11.7353C10.0923 11.7353 10.6611 11.4997 11.0804 11.0804C11.4997 10.6611 11.7353 10.0923 11.7353 9.49934C11.7353 8.90633 11.4997 8.33762 11.0804 7.9183C10.6611 7.49898 10.0923 7.26341 9.49934 7.26341ZM9.49934 10.4922C9.23602 10.4922 8.98348 10.3876 8.79728 10.2014C8.61108 10.0152 8.50648 9.76266 8.50648 9.49934C8.50648 9.23602 8.61108 8.98348 8.79728 8.79728C8.98348 8.61108 9.23602 8.50648 9.49934 8.50648C9.76266 8.50648 10.0152 8.61108 10.2014 8.79728C10.3876 8.98348 10.4922 9.23602 10.4922 9.49934C10.4922 9.76266 10.3876 10.0152 10.2014 10.2014C10.0152 10.3876 9.76266 10.4922 9.49934 10.4922Z" fill="currentColor"/><path d="M9.5 18.7432C14.6049 18.7432 18.7432 14.6049 18.7432 9.5C18.7432 4.3951 14.6049 0.256757 9.5 0.256757C4.3951 0.256757 0.256757 4.3951 0.256757 9.5C0.256757 14.6049 4.3951 18.7432 9.5 18.7432Z" stroke="currentColor" stroke-width="0.5" stroke-miterlimit="10"/><path d="M9.4999 16.5486C13.3928 16.5486 16.5486 13.3928 16.5486 9.4999C16.5486 5.60703 13.3928 2.45122 9.4999 2.45122C5.60703 2.45122 2.45122 5.60703 2.45122 9.4999C2.45122 13.3928 5.60703 16.5486 9.4999 16.5486Z" stroke="currentColor" stroke-width="0.5" stroke-miterlimit="10"/><path d="M4.54703 14.4545L7.91979 11.0817M11.3137 16.2675L10.0796 11.6606M16.2675 11.3137L11.6606 10.0796M14.4545 4.54703L11.0817 7.91979M7.68746 2.73406L8.92225 7.34128M2.73406 7.68746L7.34062 8.92192" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"/><path d="M9.49934 11.7353C10.7342 11.7353 11.7353 10.7342 11.7353 9.49934C11.7353 8.26447 10.7342 7.26341 9.49934 7.26341C8.26447 7.26341 7.26341 8.26447 7.26341 9.49934C7.26341 10.7342 8.26447 11.7353 9.49934 11.7353Z" stroke="currentColor" stroke-width="0.5" stroke-miterlimit="10"/><path d="M9.49994 10.4928C10.0483 10.4928 10.4928 10.0483 10.4928 9.49994C10.4928 8.95159 10.0483 8.50707 9.49994 8.50707C8.95159 8.50707 8.50707 8.95159 8.50707 9.49994C8.50707 10.0483 8.95159 10.4928 9.49994 10.4928Z" stroke="currentColor" stroke-width="0.5" stroke-miterlimit="10"/></svg>',
                'tab_title'   => 'Шиномонтаж',
                'badge'       => '',
                'panel_title' => 'Шиномонтаж для Mitsubishi',
                'panel_text'  => '<p>Предоставляем полный спектр <strong>услуг шиномонтажа</strong> для автомобилей <strong>MITSUBISHI</strong>.</p><p>Балансировка колёс, замена шин и дисков — быстро и профессионально.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Снятие/установка колёс' ),
                    array( 'tabfeat_item' => 'Монтаж/демонтаж шин' ),
                    array( 'tabfeat_item' => 'Балансировка колёс' ),
                    array( 'tabfeat_item' => 'Подкачка шин азотом' ),
                    array( 'tabfeat_item' => 'Ремонт проколов' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 500 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
            array(
                'tab_id'      => 'brakes',
                'tab_icon'    => '<svg viewBox="0 0 18 14" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M15.75 14H18V11.6667H15.75V14ZM15.75 4.66667V11.6667H18V4.66667H15.75ZM6.75 14C10.4738 14 13.5 10.8617 13.5 7C13.5 3.13833 10.4738 0 6.75 0C3.02625 0 0 3.13833 0 7C0 10.8617 3.02625 14 6.75 14ZM6.75 2.33333C9.23625 2.33333 11.25 4.42167 11.25 7C11.25 9.57833 9.23625 11.6667 6.75 11.6667C4.26375 11.6667 2.25 9.57833 2.25 7C2.25 4.42167 4.26375 2.33333 6.75 2.33333Z" fill="currentColor"/></svg>',
                'tab_title'   => 'Тормозная система',
                'badge'       => '',
                'panel_title' => 'Ремонт тормозной системы Mitsubishi',
                'panel_text'  => '<p>Полный спектр работ по <strong>обслуживанию и ремонту тормозной системы MITSUBISHI</strong>.</p><p>Гарантируем надёжность и безопасность торможения.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Замена тормозных колодок' ),
                    array( 'tabfeat_item' => 'Замена тормозных дисков' ),
                    array( 'tabfeat_item' => 'Проточка тормозных дисков' ),
                    array( 'tabfeat_item' => 'Ремонт суппортов' ),
                    array( 'tabfeat_item' => 'Замена тормозных шлангов' ),
                    array( 'tabfeat_item' => 'Замена датчиков ABS' ),
                    array( 'tabfeat_item' => 'Замена тормозных цилиндров' ),
                    array( 'tabfeat_item' => 'Прокачка тормозной системы' ),
                    array( 'tabfeat_item' => 'Замена тормозной жидкости' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 1 500 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
            array(
                'tab_id'      => 'alignment',
                'tab_icon'    => '<svg viewBox="0 0 18 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M4.75312 3.05L3.83555 5.71429H14.1645L13.2469 3.05C13.0887 2.59286 12.6633 2.28571 12.1852 2.28571H5.81484C5.33672 2.28571 4.91133 2.59286 4.75312 3.05ZM1.39219 5.88571L2.62969 2.29643C3.1043 0.921429 4.38047 0 5.81484 0H12.1852C13.6195 0 14.8957 0.921429 15.3703 2.29643L16.6078 5.88571C17.4234 6.22857 18 7.04643 18 8V14.8571C18 15.4893 17.4973 16 16.875 16H15.75C15.1277 16 14.625 15.4893 14.625 14.8571V13.7143H3.375V14.8571C3.375 15.4893 2.87227 16 2.25 16H1.125C0.502734 16 0 15.4893 0 14.8571V8C0 7.04643 0.576562 6.22857 1.39219 5.88571ZM4.5 9.71429C4.5 9.08214 3.99727 8.57143 3.375 8.57143C2.75273 8.57143 2.25 9.08214 2.25 9.71429C2.25 10.3464 2.75273 10.8571 3.375 10.8571C3.99727 10.8571 4.5 10.3464 4.5 9.71429ZM14.625 10.8571C15.2473 10.8571 15.75 10.3464 15.75 9.71429C15.75 9.08214 15.2473 8.57143 14.625 8.57143C14.0027 8.57143 13.5 9.08214 13.5 9.71429C13.5 10.3464 14.0027 10.8571 14.625 10.8571Z" fill="currentColor"/></svg>',
                'tab_title'   => 'Сход-Развал',
                'badge'       => '',
                'panel_title' => 'Регулировка развала-схождения Mitsubishi',
                'panel_text'  => '<p>Регулировка <strong>развала-схождения (сход-развал)</strong> для всех моделей <strong>MITSUBISHI</strong>.</p><p>Работы ведутся строго по регламенту завода-изготовителя на профессиональном оборудовании.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Диагностика углов установки колёс' ),
                    array( 'tabfeat_item' => 'Регулировка схождения' ),
                    array( 'tabfeat_item' => 'Регулировка развала' ),
                    array( 'tabfeat_item' => 'Проверка и регулировка кастора' ),
                    array( 'tabfeat_item' => 'Контрольный замер после регулировки' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 2 000 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
            array(
                'tab_id'      => 'fuel',
                'tab_icon'    => '<svg viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M17.2036 4.23L17.2145 4.22L13.1564 0.5L12 1.56L14.3018 3.67C13.2764 4.03 12.5455 4.93 12.5455 6C12.5455 7.38 13.7673 8.5 15.2727 8.5C15.6655 8.5 16.0255 8.42 16.3636 8.29V15.5C16.3636 16.05 15.8727 16.5 15.2727 16.5C14.6727 16.5 14.1818 16.05 14.1818 15.5V11C14.1818 9.9 13.2 9 12 9H10.9091V2C10.9091 0.9 9.92727 0 8.72727 0H2.18182C0.981818 0 0 0.9 0 2V18H10.9091V10.5H12.5455V15.5C12.5455 16.88 13.7673 18 15.2727 18C16.7782 18 18 16.88 18 15.5V6C18 5.31 17.6945 4.68 17.2036 4.23ZM8.72727 7H2.18182V2H8.72727V7ZM15.2727 7C14.6727 7 14.1818 6.55 14.1818 6C14.1818 5.45 14.6727 5 15.2727 5C15.8727 5 16.3636 5.45 16.3636 6C16.3636 6.55 15.8727 7 15.2727 7Z" fill="currentColor"/></svg>',
                'tab_title'   => 'Топливная система',
                'badge'       => '',
                'panel_title' => 'Ремонт топливной системы Mitsubishi',
                'panel_text'  => '<p>Диагностика и <strong>ремонт топливной системы</strong> автомобилей <strong>MITSUBISHI</strong>: форсунки, топливный насос, регулятор давления.</p><p>Восстанавливаем исправную работу двигателя и снижаем расход топлива.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Диагностика топливной системы' ),
                    array( 'tabfeat_item' => 'Чистка и замена форсунок' ),
                    array( 'tabfeat_item' => 'Замена топливного насоса' ),
                    array( 'tabfeat_item' => 'Замена топливного фильтра' ),
                    array( 'tabfeat_item' => 'Ремонт топливной рампы' ),
                    array( 'tabfeat_item' => 'Проверка давления топлива' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 2 000 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
            array(
                'tab_id'      => 'steering',
                'tab_icon'    => '<svg viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M9 0C4.032 0 0 4.032 0 9C0 13.968 4.032 18 9 18C13.968 18 18 13.968 18 9C18 4.032 13.968 0 9 0ZM9 16.2C5.031 16.2 1.8 12.969 1.8 9C1.8 5.031 5.031 1.8 9 1.8C12.969 1.8 16.2 5.031 16.2 9C16.2 12.969 12.969 16.2 9 16.2ZM4.05 13.95L10.809 10.809L13.95 4.05L7.191 7.191L4.05 13.95ZM9 8.01C9.549 8.01 9.99 8.451 9.99 9C9.99 9.549 9.549 9.99 9 9.99C8.451 9.99 8.01 9.549 8.01 9C8.01 8.451 8.451 8.01 9 8.01Z" fill="currentColor"/></svg>',
                'tab_title'   => 'Рулевое управление',
                'badge'       => '',
                'panel_title' => 'Ремонт рулевого управления Mitsubishi',
                'panel_text'  => '<p>Диагностика и <strong>ремонт рулевого управления MITSUBISHI</strong>: рейка, насос ГУР, рулевые тяги и наконечники.</p><p>Устраняем люфт, стуки и тяжёлое управление рулём.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Диагностика рулевого управления' ),
                    array( 'tabfeat_item' => 'Ремонт рулевой рейки' ),
                    array( 'tabfeat_item' => 'Замена рулевых наконечников' ),
                    array( 'tabfeat_item' => 'Замена рулевых тяг' ),
                    array( 'tabfeat_item' => 'Ремонт/замена насоса ГУР' ),
                    array( 'tabfeat_item' => 'Замена жидкости ГУР' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 2 500 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
            array(
                'tab_id'      => 'cooling',
                'tab_icon'    => '<svg viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M18 8.1H14.247L17.163 5.184L15.894 3.906L11.7 8.1H9.9V6.3L14.094 2.106L12.816 0.837L9.9 3.753V0H8.1V3.753L5.184 0.837L3.906 2.106L8.1 6.3V8.1H6.3L2.106 3.906L0.837 5.184L3.753 8.1H0V9.9H3.753L0.837 12.816L2.106 14.094L6.3 9.9H8.1V11.7L3.906 15.894L5.184 17.163L8.1 14.247V18H9.9V14.247L12.816 17.163L14.094 15.894L9.9 11.7V9.9H11.7L15.894 14.094L17.163 12.816L14.247 9.9H18V8.1Z" fill="currentColor"/></svg>',
                'tab_title'   => 'Система охлаждения',
                'badge'       => '',
                'panel_title' => 'Ремонт системы охлаждения Mitsubishi',
                'panel_text'  => '<p>Диагностика и <strong>ремонт системы охлаждения MITSUBISHI</strong>: устраняем перегрев двигателя, течи антифриза и неисправности термостата.</p><p>Промывка и замена охлаждающей жидкости по регламенту.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Диагностика системы охлаждения' ),
                    array( 'tabfeat_item' => 'Замена охлаждающей жидкости' ),
                    array( 'tabfeat_item' => 'Замена термостата' ),
                    array( 'tabfeat_item' => 'Замена водяного насоса' ),
                    array( 'tabfeat_item' => 'Ремонт/замена радиатора' ),
                    array( 'tabfeat_item' => 'Замена патрубков и шлангов' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 2 000 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
            array(
                'tab_id'      => 'diagnostics',
                'tab_icon'    => '<svg viewBox="0 0 18 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M14.4319 0H0V5.938H1.39054L3.25539 2.788L4.92224 6.288L5.64856 5.062H9.45657C9.79171 3.96157 10.4961 3.04728 11.423 2.50963C12.3498 1.97199 13.4277 1.85248 14.4319 2.176V0ZM9.27656 7.063H6.61879L4.75303 10.21L3.08709 6.71L2.36077 7.936H0V13H6.31548V14H4.51003C4.27133 14 4.0424 14.1054 3.87361 14.2929C3.70483 14.4804 3.61 14.7348 3.61 15C3.61 15.2652 3.70483 15.5196 3.87361 15.7071C4.0424 15.8946 4.27133 16 4.51003 16H9.92188C10.1606 16 10.3895 15.8946 10.5583 15.7071C10.7271 15.5196 10.8219 15.2652 10.8219 15C10.8219 14.7348 10.7271 14.4804 10.5583 14.2929C10.3895 14.1054 10.1606 14 9.92188 14H8.11553V13H14.4319V10.824C14.0653 10.9414 13.6857 11.0006 13.3042 11C12.3167 11.0004 11.363 10.6008 10.6216 9.87609C9.88022 9.15139 9.40202 8.15126 9.27656 7.063Z" fill="currentColor"/><path fill-rule="evenodd" clip-rule="evenodd" d="M16.0804 8.166C16.3392 7.63219 16.469 7.03287 16.4569 6.42665C16.4449 5.82044 16.2914 5.22807 16.0116 4.70746C15.7318 4.18686 15.3352 3.75585 14.8606 3.45656C14.3859 3.15728 13.8495 2.99996 13.3038 3C12.8896 2.99948 12.4794 3.08959 12.0966 3.2652C11.7138 3.4408 11.3659 3.69847 11.0727 4.02347C10.7795 4.34848 10.5468 4.73446 10.3879 5.15938C10.229 5.58431 10.147 6.03985 10.1465 6.5C10.1465 8.433 11.5595 10 13.3038 10C13.8285 10.001 14.3452 9.85624 14.8068 9.579L16.7248 11.706L18.0001 10.294L16.0804 8.166ZM13.3038 8C14.0562 8 14.6601 7.324 14.6601 6.5C14.6601 5.676 14.058 5 13.3038 5C12.5496 5 11.9465 5.676 11.9465 6.5C11.9465 7.324 12.5505 8 13.3038 8Z" fill="currentColor"/></svg>',
                'tab_title'   => 'Диагностика',
                'badge'       => '',
                'panel_title' => 'Компьютерная диагностика Mitsubishi',
                'panel_text'  => '<p>Профессиональная <strong>компьютерная диагностика</strong> всех систем <strong>MITSUBISHI</strong> на оригинальном сканере <strong>MUT III</strong>.</p><p>Точно определяем неисправности, считываем и сбрасываем ошибки ЭБУ.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Диагностика MUT III' ),
                    array( 'tabfeat_item' => 'Считывание кодов ошибок' ),
                    array( 'tabfeat_item' => 'Диагностика двигателя' ),
                    array( 'tabfeat_item' => 'Диагностика АКПП/МКПП' ),
                    array( 'tabfeat_item' => 'Диагностика ABS и ESP' ),
                    array( 'tabfeat_item' => 'Диагностика подушек безопасности' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 500 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
            array(
                'tab_id'      => 'transmission',
                'tab_icon'    => '<svg viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M18 12.4616V16.6154C18 16.9826 17.842 17.3348 17.5607 17.5945C17.2794 17.8541 16.8978 18 16.5 18H1.5C1.10218 18 0.720644 17.8541 0.43934 17.5945C0.158035 17.3348 0 16.9826 0 16.6154V12.4616C0 12.0944 0.158035 11.7422 0.43934 11.4825C0.720644 11.2229 1.10218 11.077 1.5 11.077H8.25V6.85308C7.33947 6.68152 6.53038 6.20399 5.9796 5.51307C5.42882 4.82215 5.17559 3.96707 5.26899 3.1136C5.36239 2.26012 5.79578 1.46907 6.48514 0.893791C7.17449 0.318514 8.07069 0 9 0C9.92931 0 10.8255 0.318514 11.5149 0.893791C12.2042 1.46907 12.6376 2.26012 12.731 3.1136C12.8244 3.96707 12.5712 4.82215 12.0204 5.51307C11.4696 6.20399 10.6605 6.68152 9.75 6.85308V11.077H16.5C16.8978 11.077 17.2794 11.2229 17.5607 11.4825C17.842 11.7422 18 12.0944 18 12.4616ZM12 9.00008C12 9.18369 12.079 9.35978 12.2197 9.48961C12.3603 9.61944 12.5511 9.69238 12.75 9.69238H15.75C15.9489 9.69238 16.1397 9.61944 16.2803 9.48961C16.421 9.35978 16.5 9.18369 16.5 9.00008C16.5 8.81647 16.421 8.64038 16.2803 8.51055C16.1397 8.38072 15.9489 8.30778 15.75 8.30778H12.75C12.5511 8.30778 12.3603 8.38072 12.2197 8.51055C12.079 8.64038 12 8.81647 12 9.00008Z" fill="currentColor"/></svg>',
                'tab_title'   => 'Трансмиссия',
                'badge'       => '',
                'panel_title' => 'Ремонт трансмиссии Mitsubishi',
                'panel_text'  => '<p>Диагностика и <strong>ремонт трансмиссии</strong> полноприводных и переднеприводных <strong>MITSUBISHI</strong>: карданный вал, раздаточная коробка, приводные валы (ШРУСы).</p><p>Специализируемся на полном приводе <strong>4WD/AWD</strong>.</p>',
                'tab_features' => array(
                    array( 'tabfeat_item' => 'Диагностика трансмиссии' ),
                    array( 'tabfeat_item' => 'Ремонт/замена карданного вала' ),
                    array( 'tabfeat_item' => 'Ремонт раздаточной коробки' ),
                    array( 'tabfeat_item' => 'Замена ШРУСов и пыльников' ),
                    array( 'tabfeat_item' => 'Замена масла в раздатке' ),
                    array( 'tabfeat_item' => 'Ремонт муфты подключения 4WD' ),
                ),
                'price_label' => 'Стоимость работ от',
                'price_value' => 'от 3 000 ₽',
                'tab_cta_text' => 'Записаться на ремонт',
                'tab_cta_url'  => '#form-section',
            ),
        ) );

    }

    // --- About page ---
    $about_id = $pages['about'] ?? 0;
    if ( $about_id ) {
        miauto_demo_set_post_meta_if_empty( $about_id, 'miauto_about_hero_badge', 'Официальный сервис' );
        miauto_demo_set_post_meta_if_empty( $about_id, 'miauto_about_hero_title', 'Технический центр МИ АВТО' );
        miauto_demo_set_post_meta_if_empty( $about_id, 'miauto_about_hero_accent', 'МИ АВТО' );
        miauto_demo_set_post_meta_if_empty( $about_id, 'miauto_about_hero_texts', array(
            array( 'hero_text' => 'Технический центр «МИ АВТО» — это специализированный сервис по ремонту и обслуживанию автомобилей Mitsubishi в Москве. Мы работаем с 2005 года и за это время накопили огромный опыт в диагностике и ремонте всех моделей Mitsubishi.' ),
            array( 'hero_text' => 'Наша команда — это квалифицированные специалисты, прошедшие обучение и сертификацию. Мы используем только оригинальные запчасти и современное диагностическое оборудование, что позволяет выполнять работы любой сложности в кратчайшие сроки.' ),
        ) );
        if ( ! empty( $images['about-us'] ) ) {
            miauto_demo_set_post_meta_if_empty( $about_id, 'miauto_about_hero_image', $images['about-us'] );
        }

        miauto_demo_set_post_meta_if_empty( $about_id, 'miauto_about_intro_title', 'Профессионализм и опыт' );
        miauto_demo_set_post_meta_if_empty( $about_id, 'miauto_about_intro_texts', array(
            array( 'text' => 'Мы стремимся предоставить каждому клиенту максимально качественный сервис. Наш подход основан на честности, прозрачности и профессионализме. Мы всегда подробно объясняем, какие работы необходимы, и согласовываем стоимость до начала ремонта.' ),
            array( 'text' => 'Технический центр оснащён современным оборудованием, которое позволяет выполнять диагностику и ремонт любой сложности. Мы постоянно инвестируем в обучение персонала и обновление технической базы, чтобы соответствовать самым высоким стандартам обслуживания.' ),
        ) );
        if ( ! empty( $images['about-us'] ) ) {
            miauto_demo_set_post_meta_if_empty( $about_id, 'miauto_about_intro_image', $images['about-us'] );
        }

        // Advantages.
        miauto_demo_set_post_meta_if_empty( $about_id, 'miauto_advantages_title', 'Наши преимущества' );
        miauto_demo_set_post_meta_if_empty( $about_id, 'miauto_advantages_cards', array(
            array(
                'adv_svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'adv_title' => 'Специализация',
                'adv_text'  => 'Работаем только с автомобилями Mitsubishi, что позволяет глубоко разбираться в каждой модели.',
            ),
            array(
                'adv_svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 12l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'adv_title' => 'Оригинальные запчасти',
                'adv_text'  => 'Используем только оригинальные и сертифицированные запасные части для ремонта.',
            ),
            array(
                'adv_svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M12 15l-2 5 2-1 2 1-2-5z" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="10" r="6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'adv_title' => 'Гарантия',
                'adv_text'  => 'Предоставляем гарантию на все виды выполненных работ и установленные запчасти.',
            ),
            array(
                'adv_svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="4" width="16" height="16" rx="2" stroke-linecap="round" stroke-linejoin="round"/><rect x="9" y="9" width="6" height="6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'adv_title' => 'Современное оборудование',
                'adv_text'  => 'Используем профессиональное диагностическое и ремонтное оборудование.',
            ),
            array(
                'adv_svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'adv_title' => 'Опытные мастера',
                'adv_text'  => 'Наши специалисты имеют высокую квалификацию и регулярно проходят обучение.',
            ),
            array(
                'adv_svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" xmlns="http://www.w3.org/2000/svg"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'adv_title' => 'Прозрачные цены',
                'adv_text'  => 'Составляем подробную смету до начала работ. Никаких скрытых доплат.',
            ),
        ) );
    }

    // --- Contacts page ---
    // No post-specific meta needed; contacts section data comes from Theme Options.

    // --- Prices page ---
    $prices_id = $pages['prices'] ?? 0;
    if ( $prices_id ) {
        miauto_demo_set_post_meta_if_empty( $prices_id, 'miauto_prices_title', 'Прайс-лист' );
        miauto_demo_set_post_meta_if_empty( $prices_id, 'miauto_prices_subtitle', 'Актуальные цены на обслуживание и ремонт автомобилей Mitsubishi' );

        $outlander_cats = array(
            array(
                'cat_name' => 'ТО',
                'price_rows' => array(
                    array( 'row_name' => 'ТО-1 (15 000 км)',              'price' => 'от 8 500 ₽' ),
                    array( 'row_name' => 'ТО-2 (30 000 км)',              'price' => 'от 12 000 ₽' ),
                    array( 'row_name' => 'ТО-3 (45 000 км)',              'price' => 'от 8 500 ₽' ),
                    array( 'row_name' => 'ТО-4 (60 000 км)',              'price' => 'от 15 000 ₽' ),
                    array( 'row_name' => 'Замена масла двигателя',         'price' => 'от 2 500 ₽' ),
                    array( 'row_name' => 'Замена масла АКПП',              'price' => 'от 4 500 ₽' ),
                    array( 'row_name' => 'Замена антифриза',               'price' => 'от 3 000 ₽' ),
                    array( 'row_name' => 'Замена тормозной жидкости',      'price' => 'от 2 000 ₽' ),
                ),
            ),
            array(
                'cat_name' => 'Двигатель',
                'price_rows' => array(
                    array( 'row_name' => 'Замена ремня ГРМ',       'price' => 'от 12 000 ₽' ),
                    array( 'row_name' => 'Замена цепи ГРМ',        'price' => 'от 18 000 ₽' ),
                    array( 'row_name' => 'Замена прокладки ГБЦ',   'price' => 'от 25 000 ₽' ),
                    array( 'row_name' => 'Замена сальника коленвала','price' => 'от 8 000 ₽' ),
                    array( 'row_name' => 'Замена помпы',            'price' => 'от 6 500 ₽' ),
                    array( 'row_name' => 'Замена термостата',       'price' => 'от 4 000 ₽' ),
                    array( 'row_name' => 'Диагностика двигателя',   'price' => 'от 2 000 ₽' ),
                ),
            ),
            array(
                'cat_name' => 'Подвеска',
                'price_rows' => array(
                    array( 'row_name' => 'Замена передних амортизаторов',  'price' => 'от 5 000 ₽' ),
                    array( 'row_name' => 'Замена задних амортизаторов',    'price' => 'от 4 500 ₽' ),
                    array( 'row_name' => 'Замена передних рычагов',        'price' => 'от 6 000 ₽' ),
                    array( 'row_name' => 'Замена сайлентблоков',           'price' => 'от 3 500 ₽' ),
                    array( 'row_name' => 'Замена ступичного подшипника',   'price' => 'от 5 500 ₽' ),
                    array( 'row_name' => 'Замена шаровой опоры',           'price' => 'от 3 000 ₽' ),
                    array( 'row_name' => 'Сход-развал',                    'price' => 'от 3 500 ₽' ),
                ),
            ),
            array(
                'cat_name' => 'Тормоза',
                'price_rows' => array(
                    array( 'row_name' => 'Замена передних колодок',         'price' => 'от 2 500 ₽' ),
                    array( 'row_name' => 'Замена задних колодок',           'price' => 'от 2 500 ₽' ),
                    array( 'row_name' => 'Замена передних дисков',          'price' => 'от 4 000 ₽' ),
                    array( 'row_name' => 'Замена задних дисков',            'price' => 'от 3 500 ₽' ),
                    array( 'row_name' => 'Замена тормозных шлангов',        'price' => 'от 2 000 ₽' ),
                    array( 'row_name' => 'Прокачка тормозной системы',      'price' => 'от 2 500 ₽' ),
                ),
            ),
            array(
                'cat_name' => 'Рулевое',
                'price_rows' => array(
                    array( 'row_name' => 'Замена рулевых наконечников', 'price' => 'от 3 000 ₽' ),
                    array( 'row_name' => 'Замена рулевых тяг',          'price' => 'от 4 500 ₽' ),
                    array( 'row_name' => 'Ремонт рулевой рейки',        'price' => 'от 12 000 ₽' ),
                    array( 'row_name' => 'Замена насоса ГУР',           'price' => 'от 8 000 ₽' ),
                    array( 'row_name' => 'Замена жидкости ГУР',         'price' => 'от 2 000 ₽' ),
                ),
            ),
            array(
                'cat_name' => 'Электрика',
                'price_rows' => array(
                    array( 'row_name' => 'Компьютерная диагностика', 'price' => 'от 1 500 ₽' ),
                    array( 'row_name' => 'Замена генератора',         'price' => 'от 5 000 ₽' ),
                    array( 'row_name' => 'Замена стартера',           'price' => 'от 5 500 ₽' ),
                    array( 'row_name' => 'Заправка кондиционера',     'price' => 'от 3 500 ₽' ),
                    array( 'row_name' => 'Замена аккумулятора',       'price' => 'от 1 000 ₽' ),
                    array( 'row_name' => 'Ремонт проводки',           'price' => 'от 3 000 ₽' ),
                ),
            ),
        );

        // Use same categories for all models (simplified demo).
        $price_models = array( 'Outlander', 'ASX', 'Pajero Sport', 'Pajero', 'L200', 'Lancer' );
        $models_data = array();
        foreach ( $price_models as $model_name ) {
            $models_data[] = array(
                'model_name' => $model_name,
                'price_cats' => $outlander_cats,
            );
        }

        miauto_demo_set_post_meta_if_empty( $prices_id, 'miauto_prices_models', $models_data );
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

        if ( $post_id && ! is_wp_error( $post_id ) && $p['thumb'] && ! has_post_thumbnail( $post_id ) ) {
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

        // Если меню уже содержит пункты — не пересоздаём, только привязываем локации.
        $existing_items = wp_get_nav_menu_items( $menu_id );
        if ( ! empty( $existing_items ) ) {
            $locations            = get_theme_mod( 'nav_menu_locations', array() );
            $locations['primary'] = $menu_id;
            $locations['mobile']  = $menu_id;
            set_theme_mod( 'nav_menu_locations', $locations );
            return;
        }
    } else {
        $menu_id = wp_create_nav_menu( $menu_name );
    }

    if ( is_wp_error( $menu_id ) ) {
        return;
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
    if ( '/%postname%/' === get_option( 'permalink_structure' ) ) {
        return;
    }

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

// ─── 11. Fill reviews demo data ─────────────────────────────────────

/**
 * Заполнить демо-отзывы в теме (Общие блоки → Отзывы).
 * Все 5 отзывов привязаны к услуге «Ремонт двигателя».
 *
 * @param array $cpt Результат miauto_demo_create_cpt_posts().
 */
function miauto_demo_fill_reviews( $cpt ) {
    $engine_svc_id = ! empty( $cpt['services']['Ремонт двигателя'] )
        ? (int) $cpt['services']['Ремонт двигателя']
        : 0;

    $svc_assoc = $engine_svc_id
        ? array(
            array(
                'id'      => $engine_svc_id,
                'type'    => 'post',
                'subtype' => 'miauto_service',
                'value'   => 'post:miauto_service:' . $engine_svc_id,
            ),
        )
        : array();

    miauto_demo_set_theme_option_if_empty( 'miauto_reviews', array(
        array(
            'review_author_name'  => 'Андрей Козлов',
            'review_author_car'   => 'Mitsubishi ASX',
            'review_text'         => 'Обратился с проблемой масложора. Сделали диагностику, объяснили причину, согласовали работы. Капиталка прошла быстро, машина после ремонта работает отлично. Рекомендую!',
            'review_source_label' => 'Яндекс.Карты',
            'review_source_url'   => 'https://yandex.ru/maps/',
            'review_rating'       => '5,0',
            'review_services'     => $svc_assoc,
        ),
        array(
            'review_author_name'  => 'Дмитрий Волков',
            'review_author_car'   => 'Mitsubishi Outlander XL',
            'review_text'         => 'Заменили цепь ГРМ и попутно выявили износ натяжителя. Всё согласовали заранее, уложились в срок. Цена адекватная, мастера знают своё дело.',
            'review_source_label' => 'Google Карты',
            'review_source_url'   => 'https://maps.google.com/',
            'review_rating'       => '5,0',
            'review_services'     => $svc_assoc,
        ),
        array(
            'review_author_name'  => 'Сергей Иванов',
            'review_author_car'   => 'Mitsubishi Pajero IV',
            'review_text'         => 'Двигатель троил, горел Check Engine. Провели полную диагностику, нашли пробой прокладки ГБЦ. Сделали шлифовку, заменили прокладку. Результат отличный, ошибок нет.',
            'review_source_label' => 'Google Карты',
            'review_source_url'   => 'https://maps.google.com/',
            'review_rating'       => '5,0',
            'review_services'     => $svc_assoc,
        ),
        array(
            'review_author_name'  => 'Алексей Петров',
            'review_author_car'   => 'Mitsubishi Lancer X',
            'review_text'         => 'Стук в двигателе оказался проблемой вкладышей. Ребята сделали всё по уму, дали гарантию на год. Очень доволен качеством и отношением к клиенту.',
            'review_source_label' => 'Яндекс.Карты',
            'review_source_url'   => 'https://yandex.ru/maps/',
            'review_rating'       => '5,0',
            'review_services'     => $svc_assoc,
        ),
        array(
            'review_author_name'  => 'Михаил Кузнецов',
            'review_author_car'   => 'Mitsubishi L200',
            'review_text'         => 'Перегрев двигателя, эмульсия в масле. Приехал на эвакуаторе. Диагностика показала трещину в ГБЦ. Сделали замену, объяснили причины, дали подробные рекомендации.',
            'review_source_label' => 'Яндекс.Карты',
            'review_source_url'   => 'https://yandex.ru/maps/',
            'review_rating'       => '5,0',
            'review_services'     => $svc_assoc,
        ),
    ) );
}
