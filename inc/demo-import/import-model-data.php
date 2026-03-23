<?php
/**
 * Import model data into miauto_model CPT meta fields.
 *
 * Triggered by visiting: /wp-admin/?miauto_import_models=1
 * Re-runnable: overwrites model meta each time.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hook into admin_init to check for the import GET parameter.
 */
function miauto_import_models_init() {
    if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( empty( $_GET['miauto_import_models'] ) || '1' !== $_GET['miauto_import_models'] ) {
        return;
    }

    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_import_models' ) ) {
        add_action( 'admin_notices', function () {
            $url = wp_nonce_url( admin_url( '?miauto_import_models=1' ), 'miauto_import_models' );
            echo '<div class="notice notice-info"><p>MI-AUTO: Для запуска импорта данных моделей перейдите по ссылке: <a href="' . esc_url( $url ) . '">Импортировать данные моделей</a></p></div>';
        } );
        return;
    }

    set_time_limit( 300 );
    wp_raise_memory_limit( 'admin' );

    $result = miauto_run_model_import();

    if ( is_wp_error( $result ) ) {
        $msg = $result->get_error_message();
        add_action( 'admin_notices', function () use ( $msg ) {
            echo '<div class="notice notice-error"><p>MI-AUTO Импорт моделей: ' . esc_html( $msg ) . '</p></div>';
        } );
        return;
    }

    $log = $result;
    add_action( 'admin_notices', function () use ( $log ) {
        echo '<div class="notice notice-success"><p>MI-AUTO: Импорт данных моделей завершён.</p><pre style="background:#f9f9f9;padding:10px;max-height:400px;overflow:auto">' . esc_html( $log ) . '</pre></div>';
    } );
}
add_action( 'admin_init', 'miauto_import_models_init' );

/**
 * Main import runner.
 *
 * @return string|WP_Error  Log string on success, WP_Error on failure.
 */
function miauto_run_model_import() {
    if ( ! function_exists( 'carbon_set_post_meta' ) || ! function_exists( 'carbon_set_theme_option' ) ) {
        return new WP_Error( 'cf_missing', 'Carbon Fields API не доступна. Убедитесь что Carbon Fields загружен.' );
    }

    $parsed_file = get_template_directory() . '/tools/parsed-tabs-data.php';

    if ( ! file_exists( $parsed_file ) ) {
        return new WP_Error( 'no_parsed', 'Файл parsed-tabs-data.php не найден: ' . $parsed_file );
    }

    $parsed_data = include $parsed_file;
    $log = "=== Импорт данных моделей ===\n\n";

    // ── Step 1: Save theme options (shared popup content) ───────────
    if ( ! empty( $parsed_data['_karta_to'] ) ) {
        carbon_set_theme_option( 'miauto_karta_to_normal', $parsed_data['_karta_to'] );
        $log .= "OK: Опция темы miauto_karta_to_normal сохранена\n";
    }

    if ( ! empty( $parsed_data['_tyazhelye_usloviya'] ) ) {
        carbon_set_theme_option( 'miauto_karta_to_heavy', $parsed_data['_tyazhelye_usloviya'] );
        $log .= "OK: Опция темы miauto_karta_to_heavy сохранена\n";
    }

    $log .= "\n";

    // ── Step 2: Get all hardcoded data ──────────────────────────────
    $all_repair = miauto_import_get_all_repair_data();
    $all_to     = miauto_import_get_all_to_data();

    // ── Step 3: Model mapping ───────────────────────────────────────
    $model_map = array(
        'mitsubishi-asx'            => 'Ремонт Mitsubishi ASX',
        'mitsubishi-outlander-3'    => 'Ремонт Mitsubishi Outlander NEW',
        'mitsubishi-outlander-xl'   => 'Ремонт Mitsubishi Outlander XL',
        'mitsubishi-pajero-sport-2' => 'Ремонт Mitsubishi Pajero Sport 2',
        'mitsubishi-pajero-sport-3' => null,
        'mitsubishi-l200'           => 'Ремонт Mitsubishi L 200',
        'mitsubishi-lancer-10'      => 'Ремонт Mitsubishi Lancer 10',
        'mitsubishi-outlander-new'  => 'Ремонт Mitsubishi Outlander NEW',
    );

    // ── Step 4: Process each model ──────────────────────────────────
    foreach ( $model_map as $slug => $parsed_key ) {
        $log .= "--- {$slug} ---\n";

        $posts = get_posts( array(
            'post_type'      => 'miauto_model',
            'name'           => $slug,
            'posts_per_page' => 1,
            'post_status'    => 'any',
        ) );

        if ( empty( $posts ) ) {
            $log .= "  SKIP: запись не найдена\n\n";
            continue;
        }

        $post_id = $posts[0]->ID;
        $title   = $posts[0]->post_title;
        $log .= "  Post ID: {$post_id} ({$title})\n";

        // ── Repair rows ──
        if ( ! empty( $all_repair[ $slug ] ) ) {
            $repair_rows = $all_repair[ $slug ];
            carbon_set_post_meta( $post_id, 'miauto_md_repair_rows', $repair_rows );
            $log .= "  Ремонтные работы: " . count( $repair_rows ) . " строк\n";
        } else {
            $log .= "  Ремонтные работы: нет данных\n";
        }

        // ── TO variants ──
        if ( ! empty( $all_to[ $slug ] ) ) {
            $to_variants = $all_to[ $slug ];
            carbon_set_post_meta( $post_id, 'miauto_md_to_variants', $to_variants );
            $log .= "  Стоимость ТО: " . count( $to_variants ) . " вариантов\n";
        } else {
            $log .= "  Стоимость ТО: нет данных\n";
        }

        // ── Cards content ──
        if ( ! empty( $parsed_key ) && ! empty( $parsed_data[ $parsed_key ] ) ) {
            $cards_html = miauto_import_clean_cards_html( $parsed_data[ $parsed_key ] );
            carbon_set_post_meta( $post_id, 'miauto_md_to_cards_content', $cards_html );
            $log .= "  Карты ТО: OK (" . strlen( $cards_html ) . " символов)\n";
        } else {
            $log .= "  Карты ТО: нет данных\n";
        }

        // ── Tabs title ──
        carbon_set_post_meta( $post_id, 'miauto_md_tabs_title', 'Стоимость обслуживания и ремонта' );

        $log .= "\n";
    }

    $log .= "=== Импорт завершён ===\n";
    return $log;
}

// ── Repair data ─────────────────────────────────────────────────────────

/**
 * Return all repair data as slug => rows array.
 */
function miauto_import_get_all_repair_data() {
    $asx = array(
        array( 'md_repair_name' => 'Амортизатор задний-замена', 'md_repair_price' => '3960' ),
        array( 'md_repair_name' => 'Амортизатор передний-замена', 'md_repair_price' => '2880' ),
        array( 'md_repair_name' => 'БДЗ-промывка', 'md_repair_price' => '3420' ),
        array( 'md_repair_name' => 'Воздушный фильтр-замена', 'md_repair_price' => '180' ),
        array( 'md_repair_name' => 'Втулки стабилизатора, задней подвески-замена', 'md_repair_price' => '1620' ),
        array( 'md_repair_name' => 'Втулки стабилизатора, передней подвески-замена', 'md_repair_price' => '2520' ),
        array( 'md_repair_name' => 'Диагностика ходовой', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Диагностика электронных систем, считывание ошибки (SRS, ABS, двигатель)', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Задняя опора двигателя-замена', 'md_repair_price' => '3600' ),
        array( 'md_repair_name' => 'Задняя опора двигателя (4WD)-замена', 'md_repair_price' => '8820' ),
        array( 'md_repair_name' => 'Защита двигателя-с/у', 'md_repair_price' => '900' ),
        array( 'md_repair_name' => 'Инжектор-промывка', 'md_repair_price' => '5580' ),
        array( 'md_repair_name' => 'КПП вариатор (4WD)-с/у, замена', 'md_repair_price' => '26640' ),
        array( 'md_repair_name' => 'КПП вариатор-с/у, замена', 'md_repair_price' => '17100' ),
        array( 'md_repair_name' => 'КПП механика-с/у, замена', 'md_repair_price' => '15300' ),
        array( 'md_repair_name' => 'Масло в двигателе-замена', 'md_repair_price' => '1080' ),
        array( 'md_repair_name' => 'Масло в вариаторе-замена', 'md_repair_price' => '1440' ),
        array( 'md_repair_name' => 'Масло в КПП-замена', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Масло в РКП (для 4WD)-замена', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Охлаждающая жидкость (полная)-замена', 'md_repair_price' => '2520' ),
        array( 'md_repair_name' => 'Передняя опора двигателя-замена', 'md_repair_price' => '1800' ),
        array( 'md_repair_name' => 'Подшипник ступицы, передней подвески-замена', 'md_repair_price' => '4680' ),
        array( 'md_repair_name' => 'Приводной ремень с роликами-замена', 'md_repair_price' => '3240' ),
        array( 'md_repair_name' => 'Пыльник рулевой тяги-замена', 'md_repair_price' => '2520' ),
        array( 'md_repair_name' => 'Пыльник шруса-замена', 'md_repair_price' => '4140' ),
        array( 'md_repair_name' => 'Радиатор охлаждения-с/у, замена', 'md_repair_price' => '7200' ),
        array( 'md_repair_name' => 'Рулевая рейка-замена', 'md_repair_price' => '9900' ),
        array( 'md_repair_name' => 'Рулевая тяга-замена', 'md_repair_price' => '2700' ),
        array( 'md_repair_name' => 'Рулевой наконечник-замена', 'md_repair_price' => '1980' ),
        array( 'md_repair_name' => 'Рычаг верхний, задней подвески (прямой)-замена', 'md_repair_price' => '2160' ),
        array( 'md_repair_name' => 'Рычаг верхний, задней подвески (серп)-замена', 'md_repair_price' => '2340' ),
        array( 'md_repair_name' => 'Рычаг нижний, поперечный, задней подвески-замена', 'md_repair_price' => '2520' ),
        array( 'md_repair_name' => 'Рычаг нижний, передней подвески-замена', 'md_repair_price' => '2700' ),
        array( 'md_repair_name' => 'Рычаг нижний, продольный, задней подвески-с/у, замена', 'md_repair_price' => '5220' ),
        array( 'md_repair_name' => 'Рычаг нижний, продольный, задней подвески (4WD)-с/у, замена', 'md_repair_price' => '8100' ),
        array( 'md_repair_name' => 'Сайлентблок нижнего рычага, задней подвески-перепрессовка', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Сайлентблок нижнего, продольного рычага, задней подвески-перепрессовка', 'md_repair_price' => '1800' ),
        array( 'md_repair_name' => 'Сайлентблок нижнего рычага, передней подвески-перепрессовка', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Свечи зажигания-замена', 'md_repair_price' => '900' ),
        array( 'md_repair_name' => 'Стартер-замена', 'md_repair_price' => '5400' ),
        array( 'md_repair_name' => 'Стойка стабилизатора, задней подвески-замена', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Стойка стабилизатора, передней подвески-замена', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Сцепление-замена', 'md_repair_price' => '18360' ),
        array( 'md_repair_name' => 'Топливный фильтр, в баке-замена', 'md_repair_price' => '5400' ),
        array( 'md_repair_name' => 'Тормозная жидкость-замена', 'md_repair_price' => '1800' ),
        array( 'md_repair_name' => 'Тормозные диски, передние-замена', 'md_repair_price' => '3240' ),
        array( 'md_repair_name' => 'Тормозные диски, задние-замена', 'md_repair_price' => '3240' ),
        array( 'md_repair_name' => 'Тормозные колодки, задние-замена', 'md_repair_price' => '1080' ),
        array( 'md_repair_name' => 'Тормозные колодки, передние-замена', 'md_repair_price' => '1080' ),
        array( 'md_repair_name' => 'Трапеция стеклоочистителя-замена', 'md_repair_price' => '4500' ),
        array( 'md_repair_name' => 'Уплотнительное кольцо, приемной трубы глушителя-замена', 'md_repair_price' => '1800' ),
        array( 'md_repair_name' => 'Фильтр вариатора, верхний-замена', 'md_repair_price' => '4860' ),
        array( 'md_repair_name' => 'Фильтр вариатора, нижний-замена', 'md_repair_price' => '2700' ),
        array( 'md_repair_name' => 'Фильтр салона-замена', 'md_repair_price' => '540' ),
        array( 'md_repair_name' => 'Чистка-смазка суппортов, одной оси', 'md_repair_price' => '1080' ),
    );

    // Outlander 3: same as ASX with differences in rows 15-19.
    $outlander3 = $asx;
    $outlander3[14] = array( 'md_repair_name' => 'Масло в заднем редукторе-замена', 'md_repair_price' => '1080' );
    $outlander3[15] = array( 'md_repair_name' => 'Масло в двигателе-замена', 'md_repair_price' => '1080' );
    $outlander3[16] = array( 'md_repair_name' => 'Масло в вариаторе-замена', 'md_repair_price' => '1440' );
    $outlander3[17] = array( 'md_repair_name' => 'Масло в вариаторе-обнуление разложения', 'md_repair_price' => '900' );
    $outlander3[18] = array( 'md_repair_name' => 'Масло в РКП (для 4WD)-замена', 'md_repair_price' => '1260' );
    // Row 43 (index 43): Ступица в сборе вместо Сцепление.
    $outlander3[42] = array( 'md_repair_name' => 'Стойка стабилизатора, передней подвески-замена', 'md_repair_price' => '1260' );
    $outlander3[43] = array( 'md_repair_name' => 'Ступица в сборе, задней подвески-замена', 'md_repair_price' => '5400' );

    // Outlander XL: same as ASX except radiator price.
    $outlander_xl = $asx;
    $outlander_xl[25] = array( 'md_repair_name' => 'Радиатор охлаждения-с/у, замена', 'md_repair_price' => '4860' );

    // Pajero Sport 2 (внедорожник, другой набор работ).
    $psp2 = array(
        array( 'md_repair_name' => 'Диагностика ходовой', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Диагностика двигателя и электронных систем', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Масло в двигателе (бензиновый двигатель)-замена', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Масло в двигателе-замена (дизельный двигатель)', 'md_repair_price' => '1440' ),
        array( 'md_repair_name' => 'Масло в раздаточной коробке или КПП-замена', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Масло в дифференциале-замена', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Масло в АКПП-замена', 'md_repair_price' => '1800' ),
        array( 'md_repair_name' => 'Масло в АКПП (с фильтром)-замена', 'md_repair_price' => '6300' ),
        array( 'md_repair_name' => 'Масло в АКПП-полная замена', 'md_repair_price' => '3240' ),
        array( 'md_repair_name' => 'Тормозная жидкость-замена', 'md_repair_price' => '1800' ),
        array( 'md_repair_name' => 'Охлаждающая жидкость-замена', 'md_repair_price' => '2520' ),
        array( 'md_repair_name' => 'Фильтр салона-замена', 'md_repair_price' => '720' ),
        array( 'md_repair_name' => 'Фильтр топливный внешний-замена', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Сцепление-замена', 'md_repair_price' => '23400' ),
        array( 'md_repair_name' => 'Ремень ГРМ-замена', 'md_repair_price' => '14040' ),
        array( 'md_repair_name' => 'Помпа системы охлаждения-замена', 'md_repair_price' => '18360' ),
        array( 'md_repair_name' => 'Дроссельная заслонка-промывка', 'md_repair_price' => '2160' ),
        array( 'md_repair_name' => 'Радиатор системы охлаждения-замена', 'md_repair_price' => '4680' ),
        array( 'md_repair_name' => 'Амортизатор передний-замена', 'md_repair_price' => '4680' ),
        array( 'md_repair_name' => 'Амортизатор задний-замена', 'md_repair_price' => '2520' ),
        array( 'md_repair_name' => 'Втулки стабилизатора, передней подвески-замена', 'md_repair_price' => '1440' ),
        array( 'md_repair_name' => 'Шприцевание карданов', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Стойка стабилизатора, передней подвески-замена', 'md_repair_price' => '1620' ),
        array( 'md_repair_name' => 'Ступица, передней подвески-замена', 'md_repair_price' => '6480' ),
        array( 'md_repair_name' => 'Шаровая опора, передней подвески, нижняя-замена', 'md_repair_price' => '4860' ),
        array( 'md_repair_name' => 'Шаровая опора, передней подвески, верхняя-замена', 'md_repair_price' => '3060' ),
        array( 'md_repair_name' => 'Пружина, задней подвески-замена', 'md_repair_price' => '3240' ),
        array( 'md_repair_name' => 'Пыльник шруса, внутренний-замена', 'md_repair_price' => '4860' ),
        array( 'md_repair_name' => 'Рулевой наконечник-замена', 'md_repair_price' => '1980' ),
        array( 'md_repair_name' => 'Рулевая тяга-замена', 'md_repair_price' => '2700' ),
        array( 'md_repair_name' => 'Пыльник рулевой тяги-замена', 'md_repair_price' => '2520' ),
        array( 'md_repair_name' => 'Сход-развал (внедорожник)', 'md_repair_price' => '4320' ),
        array( 'md_repair_name' => 'Топливный бак-замена', 'md_repair_price' => '9900' ),
        array( 'md_repair_name' => 'Тормозные колодки передние-замена', 'md_repair_price' => '1080' ),
        array( 'md_repair_name' => 'Тормозные колодки, стояночного тормоза-замена', 'md_repair_price' => '5760' ),
        array( 'md_repair_name' => 'Обслуживание тормозных суппортов (одна ось)', 'md_repair_price' => '1080' ),
        array( 'md_repair_name' => 'Тормозной суппорт, задний-переборка', 'md_repair_price' => '3600' ),
        array( 'md_repair_name' => 'Тормозной суппорт, передний-переборка', 'md_repair_price' => '2700' ),
    );

    // L200: same structure as PSP2 with minor differences.
    $l200 = array(
        array( 'md_repair_name' => 'Диагностика ходовой', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Диагностика двигателя и электронных систем', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Масло в двигателе (бензиновый двигатель)-замена', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Масло в двигателе-замена (дизельный двигатель)', 'md_repair_price' => '1440' ),
        array( 'md_repair_name' => 'Масло в раздаточной коробке или КПП-замена', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Масло в дифференциале-замена', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Масло в АКПП-замена', 'md_repair_price' => '1800' ),
        array( 'md_repair_name' => 'Масло в АКПП (с фильтром)-замена', 'md_repair_price' => '6300' ),
        array( 'md_repair_name' => 'Масло в АКПП-полная замена', 'md_repair_price' => '3240' ),
        array( 'md_repair_name' => 'Тормозная жидкость-замена', 'md_repair_price' => '1800' ),
        array( 'md_repair_name' => 'Охлаждающая жидкость-замена', 'md_repair_price' => '2520' ),
        array( 'md_repair_name' => 'Фильтр салона-замена', 'md_repair_price' => '720' ),
        array( 'md_repair_name' => 'Фильтр топливный внешний-замена', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Сцепление-замена', 'md_repair_price' => '23400' ),
        array( 'md_repair_name' => 'Ремень ГРМ-замена', 'md_repair_price' => '14040' ),
        array( 'md_repair_name' => 'Помпа системы охлаждения-замена', 'md_repair_price' => '18360' ),
        array( 'md_repair_name' => 'Дроссельная заслонка-промывка', 'md_repair_price' => '2160' ),
        array( 'md_repair_name' => 'Радиатор системы охлаждения-замена', 'md_repair_price' => '4680' ),
        array( 'md_repair_name' => 'Амортизатор передний (L200 new)-замена', 'md_repair_price' => '4680' ),
        array( 'md_repair_name' => 'Амортизатор задний-замена', 'md_repair_price' => '2520' ),
        array( 'md_repair_name' => 'Втулки стабилизатора, передней подвески-замена', 'md_repair_price' => '1440' ),
        array( 'md_repair_name' => 'Шприцевание карданов', 'md_repair_price' => '1800' ),
        array( 'md_repair_name' => 'Стойка стабилизатора, передней подвески-замена', 'md_repair_price' => '1620' ),
        array( 'md_repair_name' => 'Ступица, передней подвески-замена', 'md_repair_price' => '6480' ),
        array( 'md_repair_name' => 'Шаровая опора, передней подвески, нижняя-замена', 'md_repair_price' => '4860' ),
        array( 'md_repair_name' => 'Шаровая опора, передней подвески, верхняя-замена', 'md_repair_price' => '3060' ),
        array( 'md_repair_name' => 'Рессора, задней подвески-замена', 'md_repair_price' => '5040' ),
        array( 'md_repair_name' => 'Пыльник шруса, внутренний-замена', 'md_repair_price' => '4860' ),
        array( 'md_repair_name' => 'Рулевой наконечник-замена', 'md_repair_price' => '1980' ),
        array( 'md_repair_name' => 'Рулевая тяга-замена', 'md_repair_price' => '2700' ),
        array( 'md_repair_name' => 'Пыльник рулевой тяги-замена', 'md_repair_price' => '2520' ),
        array( 'md_repair_name' => 'Сход-развал (внедорожник)', 'md_repair_price' => '4320' ),
        array( 'md_repair_name' => 'Топливный бак-замена', 'md_repair_price' => '9900' ),
        array( 'md_repair_name' => 'Тормозные колодки передние-замена', 'md_repair_price' => '1080' ),
        array( 'md_repair_name' => 'Тормозные колодки, барабанные-замена', 'md_repair_price' => '3600' ),
        array( 'md_repair_name' => 'Обслуживание тормозных суппортов (одна ось)', 'md_repair_price' => '1080' ),
        array( 'md_repair_name' => 'Тормозной суппорт, передний (2 поршня)-переборка', 'md_repair_price' => '2700' ),
        array( 'md_repair_name' => 'Турбина (PSP2, L200)-замена', 'md_repair_price' => '12960' ),
    );

    // Lancer 10: same structure as ASX but lower prices (~78%).
    $lancer10 = array(
        array( 'md_repair_name' => 'Амортизатор задний-замена', 'md_repair_price' => '3080' ),
        array( 'md_repair_name' => 'Амортизатор передний-замена', 'md_repair_price' => '2240' ),
        array( 'md_repair_name' => 'БДЗ-промывка', 'md_repair_price' => '2660' ),
        array( 'md_repair_name' => 'Воздушный фильтр-замена', 'md_repair_price' => '140' ),
        array( 'md_repair_name' => 'Втулки стабилизатора, задней подвески-замена', 'md_repair_price' => '1260' ),
        array( 'md_repair_name' => 'Втулки стабилизатора, передней подвески-замена', 'md_repair_price' => '1960' ),
        array( 'md_repair_name' => 'Диагностика ходовой', 'md_repair_price' => '980' ),
        array( 'md_repair_name' => 'Диагностика электронных систем (SRS, ABS, двигатель)', 'md_repair_price' => '980' ),
        array( 'md_repair_name' => 'Задняя опора двигателя-замена', 'md_repair_price' => '2800' ),
        array( 'md_repair_name' => 'Задняя опора двигателя (4WD)-замена', 'md_repair_price' => '5600' ),
        array( 'md_repair_name' => 'Защита двигателя-с/у', 'md_repair_price' => '700' ),
        array( 'md_repair_name' => 'Инжектор-промывка', 'md_repair_price' => '4340' ),
        array( 'md_repair_name' => 'КПП вариатор (4WD)-с/у, замена', 'md_repair_price' => '20720' ),
        array( 'md_repair_name' => 'КПП вариатор-с/у, замена', 'md_repair_price' => '12040' ),
        array( 'md_repair_name' => 'КПП механика-с/у, замена', 'md_repair_price' => '10080' ),
        array( 'md_repair_name' => 'Масло в двигателе-замена', 'md_repair_price' => '840' ),
        array( 'md_repair_name' => 'Масло в вариаторе-замена', 'md_repair_price' => '1120' ),
        array( 'md_repair_name' => 'Масло в КПП-замена', 'md_repair_price' => '980' ),
        array( 'md_repair_name' => 'Масло в РКП (для 4WD)-замена', 'md_repair_price' => '980' ),
        array( 'md_repair_name' => 'Охлаждающая жидкость (полная)-замена', 'md_repair_price' => '1960' ),
        array( 'md_repair_name' => 'Передняя опора двигателя-замена', 'md_repair_price' => '1400' ),
        array( 'md_repair_name' => 'Подшипник ступицы, передней подвески-замена', 'md_repair_price' => '3640' ),
        array( 'md_repair_name' => 'Приводной ремень с роликами-замена', 'md_repair_price' => '2520' ),
        array( 'md_repair_name' => 'Пыльник рулевой тяги-замена', 'md_repair_price' => '1960' ),
        array( 'md_repair_name' => 'Пыльник шруса-замена', 'md_repair_price' => '3220' ),
        array( 'md_repair_name' => 'Радиатор охлаждения-с/у, замена', 'md_repair_price' => '5600' ),
        array( 'md_repair_name' => 'Рулевая рейка-замена', 'md_repair_price' => '7700' ),
        array( 'md_repair_name' => 'Рулевая тяга-замена', 'md_repair_price' => '2100' ),
        array( 'md_repair_name' => 'Рулевой наконечник-замена', 'md_repair_price' => '1540' ),
        array( 'md_repair_name' => 'Рычаг верхний, задней подвески (прямой)-замена', 'md_repair_price' => '1680' ),
        array( 'md_repair_name' => 'Рычаг верхний, задней подвески (серп)-замена', 'md_repair_price' => '1820' ),
        array( 'md_repair_name' => 'Рычаг нижний, поперечный, задней подвески-замена', 'md_repair_price' => '1960' ),
        array( 'md_repair_name' => 'Рычаг нижний, передней подвески-замена', 'md_repair_price' => '2100' ),
        array( 'md_repair_name' => 'Рычаг нижний, продольный, задней подвески-с/у, замена', 'md_repair_price' => '4060' ),
        array( 'md_repair_name' => 'Рычаг нижний, продольный, задней подвески (4WD)-с/у, замена', 'md_repair_price' => '6300' ),
        array( 'md_repair_name' => 'Сайлентблок нижнего рычага, задней подвески-перепрессовка', 'md_repair_price' => '980' ),
        array( 'md_repair_name' => 'Сайлентблок нижнего, продольного рычага, задней подвески-перепрессовка', 'md_repair_price' => '1400' ),
        array( 'md_repair_name' => 'Сайлентблок нижнего рычага, передней подвески-перепрессовка', 'md_repair_price' => '980' ),
        array( 'md_repair_name' => 'Свечи зажигания-замена', 'md_repair_price' => '700' ),
        array( 'md_repair_name' => 'Стартер-замена', 'md_repair_price' => '2100' ),
        array( 'md_repair_name' => 'Стойка стабилизатора, задней подвески-замена', 'md_repair_price' => '980' ),
        array( 'md_repair_name' => 'Стойка стабилизатора, передней подвески-замена', 'md_repair_price' => '980' ),
        array( 'md_repair_name' => 'Сцепление-замена', 'md_repair_price' => '11200' ),
        array( 'md_repair_name' => 'Топливный фильтр, в баке-замена', 'md_repair_price' => '3220' ),
        array( 'md_repair_name' => 'Тормозная жидкость-замена', 'md_repair_price' => '1400' ),
        array( 'md_repair_name' => 'Тормозные диски, передние-замена', 'md_repair_price' => '2520' ),
        array( 'md_repair_name' => 'Тормозные диски, задние-замена', 'md_repair_price' => '2520' ),
        array( 'md_repair_name' => 'Тормозные колодки, задние-замена', 'md_repair_price' => '840' ),
        array( 'md_repair_name' => 'Тормозные колодки, передние-замена', 'md_repair_price' => '840' ),
        array( 'md_repair_name' => 'Трапеция стеклоочистителя-замена', 'md_repair_price' => '2380' ),
        array( 'md_repair_name' => 'Уплотнительное кольцо, приемной трубы глушителя-замена', 'md_repair_price' => '1400' ),
        array( 'md_repair_name' => 'Фильтр вариатора, верхний-замена', 'md_repair_price' => '3780' ),
        array( 'md_repair_name' => 'Фильтр вариатора, нижний-замена', 'md_repair_price' => '2100' ),
        array( 'md_repair_name' => 'Фильтр салона-замена', 'md_repair_price' => '420' ),
        array( 'md_repair_name' => 'Чистка-смазка суппортов, одной оси', 'md_repair_price' => '840' ),
    );

    // Pajero Sport 3: generate from PSP2 with ±10%.
    $psp3 = miauto_import_adjust_repair_prices( $psp2, 90, 110 );

    // Outlander NEW: generate from Outlander 3 with ±5%.
    $outlander_new = miauto_import_adjust_repair_prices( $outlander3, 95, 105 );

    return array(
        'mitsubishi-asx'            => $asx,
        'mitsubishi-outlander-3'    => $outlander3,
        'mitsubishi-outlander-xl'   => $outlander_xl,
        'mitsubishi-pajero-sport-2' => $psp2,
        'mitsubishi-pajero-sport-3' => $psp3,
        'mitsubishi-l200'           => $l200,
        'mitsubishi-lancer-10'      => $lancer10,
        'mitsubishi-outlander-new'  => $outlander_new,
    );
}

/**
 * Adjust repair prices by random percentage.
 */
function miauto_import_adjust_repair_prices( $rows, $min_pct, $max_pct ) {
    $result = array();
    foreach ( $rows as $row ) {
        $price = (int) $row['md_repair_price'];
        $adjusted = (int) round( $price * ( mt_rand( $min_pct, $max_pct ) / 100 ), -1 );
        if ( $adjusted < 100 ) {
            $adjusted = $price;
        }
        $result[] = array(
            'md_repair_name'  => $row['md_repair_name'],
            'md_repair_price' => (string) $adjusted,
        );
    }
    return $result;
}

// ── TO data ─────────────────────────────────────────────────────────────

/**
 * Return all TO data as slug => variants array.
 */
function miauto_import_get_all_to_data() {
    $mileages = array( '15000', '30000', '45000', '60000', '75000', '90000', '105000', '120000', '135000', '150000', '165000', '180000' );

    // ASX: 3 variants.
    $asx = array(
        array(
            'md_to_variant_name' => 'ASX 1.6',
            'md_to_variant_rows' => miauto_import_build_to_rows( $mileages, array(
                array( 4320, 9150 ), array( 6300, 10650 ), array( 4320, 9150 ), array( 7200, 19050 ),
                array( 4320, 9150 ), array( 6300, 10650 ), array( 5580, 15630 ), array( 15120, 37750 ),
                array( 4320, 9150 ), array( 6300, 10650 ), array( 4320, 9150 ), array( 7200, 19050 ),
            ) ),
        ),
        array(
            'md_to_variant_name' => 'ASX 1.8',
            'md_to_variant_rows' => miauto_import_build_to_rows( $mileages, array(
                array( 4320, 9150 ), array( 6300, 10650 ), array( 4320, 9150 ), array( 7200, 19050 ),
                array( 6660, 20900 ), array( 6300, 10650 ), array( 4320, 9150 ), array( 15120, 37750 ),
                array( 4320, 9150 ), array( 8640, 22400 ), array( 4320, 9150 ), array( 7200, 19050 ),
            ) ),
        ),
        array(
            'md_to_variant_name' => 'ASX 2.0',
            'md_to_variant_rows' => miauto_import_build_to_rows( $mileages, array(
                array( 4320, 9150 ), array( 6300, 10650 ), array( 4320, 9150 ), array( 7200, 19050 ),
                array( 7920, 22150 ), array( 7380, 11900 ), array( 4320, 9150 ), array( 15120, 37750 ),
                array( 4320, 9150 ), array( 9900, 23650 ), array( 4320, 9150 ), array( 8280, 20300 ),
            ) ),
        ),
    );

    // Outlander 3: 2 variants.
    $outlander3 = array(
        array(
            'md_to_variant_name' => 'Outlander 3 2.0 2WD',
            'md_to_variant_rows' => miauto_import_build_to_rows( $mileages, array(
                array( 4320, 9150 ), array( 6120, 9550 ), array( 4500, 10250 ), array( 6120, 9550 ),
                array( 6660, 20650 ), array( 7200, 19050 ), array( 4320, 9150 ), array( 6120, 9550 ),
                array( 4500, 10250 ), array( 16380, 38950 ), array( 4320, 9150 ), array( 7200, 19050 ),
            ) ),
        ),
        array(
            'md_to_variant_name' => 'Outlander 3 2.4 4WD',
            'md_to_variant_rows' => miauto_import_build_to_rows( $mileages, array(
                array( 4320, 9150 ), array( 6120, 9550 ), array( 4500, 10250 ), array( 6120, 9550 ),
                array( 9000, 23400 ), array( 7200, 20250 ), array( 4320, 9150 ), array( 6120, 9550 ),
                array( 4500, 10250 ), array( 18720, 41700 ), array( 4320, 9150 ), array( 7200, 20250 ),
            ) ),
        ),
    );

    // Outlander XL: 2 variants.
    $outlander_xl = array(
        array(
            'md_to_variant_name' => 'Outlander XL 2.4/2.0',
            'md_to_variant_rows' => miauto_import_build_to_rows( $mileages, array(
                array( 4320, 9150 ), array( 6300, 10650 ), array( 4320, 9150 ), array( 9720, 23250 ),
                array( 5580, 10400 ), array( 9720, 23400 ), array( 4320, 9150 ), array( 15120, 37750 ),
                array( 4320, 9150 ), array( 7560, 11900 ), array( 4320, 9150 ), array( 13140, 36250 ),
            ) ),
        ),
        array(
            'md_to_variant_name' => 'Outlander XL 3.0',
            'md_to_variant_rows' => miauto_import_build_to_rows( $mileages, array(
                array( 4320, 9150 ), array( 6300, 10650 ), array( 4320, 9150 ), array( 15660, 33150 ),
                array( 5580, 10400 ), array( 20880, 50000 ), array( 4320, 9150 ), array( 21060, 47650 ),
                array( 4320, 9150 ), array( 7560, 11900 ), array( 4320, 9150 ), array( 30240, 64100 ),
            ) ),
        ),
    );

    // Pajero Sport 2: 3 variants.
    $psp2 = array(
        array(
            'md_to_variant_name' => 'Pajero Sport 2 2.5 АКПП',
            'md_to_variant_rows' => miauto_import_build_to_rows( $mileages, array(
                array( 7200, 14250 ), array( 9180, 15750 ), array( 9540, 23750 ), array( 11700, 20650 ),
                array( 7200, 14250 ), array( 28620, 70625 ), array( 7200, 14250 ), array( 11700, 20650 ),
                array( 9540, 23750 ), array( 9180, 15750 ), array( 7200, 14250 ), array( 31140, 75525 ),
            ) ),
        ),
        array(
            'md_to_variant_name' => 'Pajero Sport 2 3.2 АКПП',
            'md_to_variant_rows' => miauto_import_build_to_rows( $mileages, array(
                array( 7200, 16400 ), array( 9180, 17900 ), array( 9540, 25900 ), array( 11700, 22800 ),
                array( 7200, 16400 ), array( 14850, 34550 ), array( 7200, 16400 ), array( 11700, 22800 ),
                array( 9540, 25900 ), array( 9180, 17900 ), array( 7200, 16400 ), array( 17100, 39450 ),
            ) ),
        ),
        array(
            'md_to_variant_name' => 'Pajero Sport 2 3.0 АКПП',
            'md_to_variant_rows' => miauto_import_build_to_rows( $mileages, array(
                array( 5580, 8950 ), array( 7560, 10450 ), array( 7920, 18450 ), array( 18180, 32950 ),
                array( 5580, 8950 ), array( 26280, 54700 ), array( 5580, 8950 ), array( 23580, 44950 ),
                array( 7920, 18450 ), array( 7560, 10450 ), array( 5580, 8950 ), array( 36900, 77200 ),
            ) ),
        ),
    );

    // Pajero Sport 3: 2 variants.
    $psp3 = array(
        array(
            'md_to_variant_name' => 'Pajero Sport 3 2.4',
            'md_to_variant_rows' => miauto_import_build_to_rows( $mileages, array(
                array( 5220, 15950 ), array( 8100, 21650 ), array( 5220, 15950 ), array( 8100, 21650 ),
                array( 8820, 22750 ), array( 10800, 32650 ), array( 5220, 15950 ), array( 8100, 21650 ),
                array( 5220, 15950 ), array( 14580, 33350 ), array( 5220, 15950 ), array( 10800, 32650 ),
            ) ),
        ),
        array(
            'md_to_variant_name' => 'Pajero Sport 3 3.0',
            'md_to_variant_rows' => miauto_import_build_to_rows( $mileages, array(
                array( 5040, 9150 ), array( 6840, 9550 ), array( 5220, 10250 ), array( 6840, 9550 ),
                array( 8640, 15400 ), array( 32040, 67550 ), array( 5040, 9150 ), array( 6840, 9550 ),
                array( 5220, 10250 ), array( 12960, 20700 ), array( 5040, 9150 ), array( 32040, 67550 ),
            ) ),
        ),
    );

    // L200: generate from PSP2 (2 variants with adjusted prices).
    $l200 = miauto_import_generate_to_variants(
        array( $psp2[0], $psp2[2] ),
        array( 'L200 2.4', 'L200 2.5' ),
        90, 110
    );

    // Lancer 10: generate from ASX (2 variants with adjusted prices).
    $lancer10 = miauto_import_generate_to_variants(
        array( $asx[0], $asx[2] ),
        array( 'Lancer 10 1.5', 'Lancer 10 2.0' ),
        85, 100
    );

    // Outlander NEW: generate from Outlander 3 with ±5%.
    $outlander_new = miauto_import_generate_to_variants(
        $outlander3,
        array( 'Outlander NEW 2.0', 'Outlander NEW 2.4' ),
        95, 105
    );

    return array(
        'mitsubishi-asx'            => $asx,
        'mitsubishi-outlander-3'    => $outlander3,
        'mitsubishi-outlander-xl'   => $outlander_xl,
        'mitsubishi-pajero-sport-2' => $psp2,
        'mitsubishi-pajero-sport-3' => $psp3,
        'mitsubishi-l200'           => $l200,
        'mitsubishi-lancer-10'      => $lancer10,
        'mitsubishi-outlander-new'  => $outlander_new,
    );
}

/**
 * Build TO rows from mileages and work/parts price pairs.
 */
function miauto_import_build_to_rows( $mileages, $prices ) {
    $rows = array();
    foreach ( $mileages as $i => $km ) {
        $rows[] = array(
            'md_to_mileage'     => $km,
            'md_to_work_price'  => isset( $prices[ $i ][0] ) ? (string) $prices[ $i ][0] : '',
            'md_to_parts_price' => isset( $prices[ $i ][1] ) ? (string) $prices[ $i ][1] : '',
        );
    }
    return $rows;
}

/**
 * Generate TO variants from a template with adjusted prices and new names.
 */
function miauto_import_generate_to_variants( $template_variants, $new_names, $min_pct, $max_pct ) {
    $result = array();
    foreach ( $template_variants as $vi => $variant ) {
        $new_rows = array();
        foreach ( $variant['md_to_variant_rows'] as $row ) {
            $work  = (int) $row['md_to_work_price'];
            $parts = (int) $row['md_to_parts_price'];

            $new_rows[] = array(
                'md_to_mileage'     => $row['md_to_mileage'],
                'md_to_work_price'  => (string) max( 100, (int) round( $work * ( mt_rand( $min_pct, $max_pct ) / 100 ), -1 ) ),
                'md_to_parts_price' => (string) max( 100, (int) round( $parts * ( mt_rand( $min_pct, $max_pct ) / 100 ), -1 ) ),
            );
        }

        $result[] = array(
            'md_to_variant_name' => isset( $new_names[ $vi ] ) ? $new_names[ $vi ] : $variant['md_to_variant_name'],
            'md_to_variant_rows' => $new_rows,
        );
    }
    return $result;
}

// ── Cards HTML cleaner ──────────────────────────────────────────────────

/**
 * Clean parsed HTML content for "Карты ТО" tab.
 */
function miauto_import_clean_cards_html( $html ) {
    $html = preg_replace( '/<div\s+class="vc_empty_space[^"]*"[^>]*>.*?<\/div>/s', '', $html );

    $html = preg_replace( '/<div\s+class="wpb_text_column[^"]*"[^>]*>\s*<div\s+class="wpb_wrapper">/s', '', $html );
    $html = str_replace( '</div></div>', '', $html );

    $html = preg_replace( '/<img[^>]*src="data:image\/svg\+xml[^"]*"[^>]*>/s', '', $html );

    $html = preg_replace( '/<\/?noscript>/s', '', $html );

    $html = preg_replace(
        '/<a[^>]*href="https?:\/\/www\.mi-auto\.ru\/444-2\/"[^>]*>(.*?)<\/a>/s',
        '<span class="model-tabs__popup-trigger" data-popup="popup-karta-normal">$1</span>',
        $html
    );

    $html = preg_replace(
        '/<a[^>]*href="https?:\/\/www\.mi-auto\.ru\/obsluzhivanie-pri-tyazhelyh-usloviyah\/"[^>]*>(.*?)<\/a>/s',
        '<span class="model-tabs__popup-trigger" data-popup="popup-karta-heavy">$1</span>',
        $html
    );

    $html = preg_replace( '/\n{3,}/', "\n\n", $html );
    $html = trim( $html );

    return $html;
}
