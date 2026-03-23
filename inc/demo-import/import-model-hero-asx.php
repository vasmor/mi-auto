<?php
/**
 * Import hero content and SEO text for Mitsubishi ASX model page.
 *
 * Triggered by visiting: /wp-admin/?miauto_import_model_hero_asx=1
 * Re-runnable: overwrites fields each time.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hook into admin_init to check for the import GET parameter.
 */
function miauto_import_model_hero_asx_init() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_GET['miauto_import_model_hero_asx'] ) || '1' !== $_GET['miauto_import_model_hero_asx'] ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_import_model_hero_asx' ) ) {
		add_action( 'admin_notices', function () {
			$url = wp_nonce_url( admin_url( '?miauto_import_model_hero_asx=1' ), 'miauto_import_model_hero_asx' );
			echo '<div class="notice notice-info"><p>MI-AUTO: Для импорта hero-контента ASX перейдите по ссылке: <a href="' . esc_url( $url ) . '">Импортировать hero ASX</a></p></div>';
		} );
		return;
	}

	set_time_limit( 300 );
	wp_raise_memory_limit( 'admin' );

	$result = miauto_run_model_hero_asx_import();

	if ( is_wp_error( $result ) ) {
		$msg = $result->get_error_message();
		add_action( 'admin_notices', function () use ( $msg ) {
			echo '<div class="notice notice-error"><p>MI-AUTO: ' . esc_html( $msg ) . '</p></div>';
		} );
		return;
	}

	$log = $result;
	add_action( 'admin_notices', function () use ( $log ) {
		echo '<div class="notice notice-success"><p>MI-AUTO: Импорт hero ASX завершён.</p><pre style="background:#f9f9f9;padding:10px;max-height:400px;overflow:auto">' . esc_html( $log ) . '</pre></div>';
	} );
}
add_action( 'admin_init', 'miauto_import_model_hero_asx_init' );

/**
 * Main import runner for ASX hero + SEO text.
 *
 * @return string|WP_Error  Log on success, WP_Error on failure.
 */
function miauto_run_model_hero_asx_import() {
	if ( ! function_exists( 'carbon_set_post_meta' ) ) {
		return new WP_Error( 'cf_missing', 'Carbon Fields API не доступна.' );
	}

	$posts = get_posts( array(
		'post_type'      => 'miauto_model',
		'name'           => 'mitsubishi-asx',
		'posts_per_page' => 1,
		'post_status'    => 'any',
	) );

	if ( empty( $posts ) ) {
		return new WP_Error( 'no_post', 'Запись Mitsubishi ASX (slug: mitsubishi-asx) не найдена в CPT miauto_model.' );
	}

	$post_id = $posts[0]->ID;
	$title   = $posts[0]->post_title;
	$log     = "=== Импорт hero + SEO для {$title} (ID: {$post_id}) ===\n\n";

	// ── Hero subtitle ─────────────────────────────────────────────────
	$subtitle = 'Полное обслуживание и ремонт Mitsubishi ASX — дилерское оборудование, оригинальные запчасти, гарантия по договору';
	carbon_set_post_meta( $post_id, 'miauto_md_hero_subtitle', $subtitle );
	$log .= "OK: miauto_md_hero_subtitle\n";

	// ── Hero features ─────────────────────────────────────────────────
	$features = array(
		array( 'md_feature_text' => 'Специализация на Mitsubishi ASX с 2010 года' ),
		array( 'md_feature_text' => 'Дилерское диагностическое оборудование MUT-III' ),
		array( 'md_feature_text' => 'Оригинальные и аналоговые запчасти в наличии' ),
		array( 'md_feature_text' => 'Гарантия на работы и запчасти по договору' ),
	);
	carbon_set_post_meta( $post_id, 'miauto_md_hero_features', $features );
	$log .= "OK: miauto_md_hero_features (" . count( $features ) . " пунктов)\n";

	// ── CTA buttons ───────────────────────────────────────────────────
	carbon_set_post_meta( $post_id, 'miauto_md_hero_cta_primary_text', 'Записаться на диагностику' );
	carbon_set_post_meta( $post_id, 'miauto_md_hero_cta_secondary_text', 'Рассчитать стоимость' );
	$log .= "OK: miauto_md_hero_cta_primary_text\n";
	$log .= "OK: miauto_md_hero_cta_secondary_text\n";

	// ── Hero image (featured image of the post) ───────────────────────
	$thumb_id = get_post_thumbnail_id( $post_id );
	if ( $thumb_id ) {
		carbon_set_post_meta( $post_id, 'miauto_md_hero_image', $thumb_id );
		$log .= "OK: miauto_md_hero_image = attachment #{$thumb_id}\n";
	} else {
		$log .= "SKIP: miauto_md_hero_image — у записи нет миниатюры\n";
	}

	// ── Hero stats ────────────────────────────────────────────────────
	$stats = array(
		array( 'md_stat_value' => '5,0',    'md_stat_label' => 'Рейтинг на картах' ),
		array( 'md_stat_value' => '500+',   'md_stat_label' => 'Отзывов на картах' ),
		array( 'md_stat_value' => 'с 2005', 'md_stat_label' => 'Опыт работы' ),
	);
	carbon_set_post_meta( $post_id, 'miauto_md_hero_stats', $stats );
	$log .= "OK: miauto_md_hero_stats (" . count( $stats ) . " элементов)\n";

	// ── SEO post_content ──────────────────────────────────────────────
	$seo_text = miauto_asx_hero_seo_text();
	wp_update_post( array(
		'ID'           => $post_id,
		'post_content' => $seo_text,
	) );
	$log .= "OK: post_content (" . mb_strlen( $seo_text ) . " символов)\n";

	$log .= "\n=== Импорт завершён ===\n";
	return $log;
}

/**
 * SEO-optimized text for Mitsubishi ASX model page.
 *
 * @return string HTML content for the WordPress editor.
 */
function miauto_asx_hero_seo_text() {
	return '<p><strong>Техцентр МИ АВТО</strong> — специализированный автосервис по <strong>обслуживанию и ремонту Mitsubishi ASX</strong> в Москве. Мы работаем с этой моделью с момента её появления на российском рынке в 2010 году и знаем все её конструктивные особенности, типичные неисправности и слабые места. Каждый мастер нашего центра проходил обучение по обслуживанию автомобилей Mitsubishi и имеет многолетний практический опыт.</p>'
		. '<p>Мы выполняем <strong>полный спектр работ по Mitsubishi ASX</strong>, включая:</p>'
		. '<ul>'
		. '<li><strong>плановое ТО</strong> по регламенту завода-изготовителя с сохранением гарантии;</li>'
		. '<li><strong>ремонт подвески</strong> — замена амортизаторов, рычагов, стоек стабилизатора, ступичных подшипников;</li>'
		. '<li><strong>ремонт двигателя</strong> — диагностика, замена приводных ремней, промывка инжекторов и дроссельной заслонки;</li>'
		. '<li><strong>ремонт трансмиссии</strong> — обслуживание вариатора (CVT), замена масла и фильтров, ремонт МКПП;</li>'
		. '<li><strong>ремонт тормозной системы</strong> — замена колодок, дисков, прокачка, чистка и смазка суппортов;</li>'
		. '<li><strong>компьютерную диагностику</strong> всех электронных систем дилерским сканером MUT-III.</li>'
		. '</ul>'
		. '<blockquote><p>Все работы выполняются с применением <strong>дилерского диагностического оборудования</strong>, специального инструмента и технической документации завода-изготовителя. Мы используем оригинальные запчасти Mitsubishi, а также проверенные аналоги ведущих производителей.</p></blockquote>'
		. '<p>Стоимость обслуживания ASX в нашем техцентре <strong>на 20–40% ниже дилерских расценок</strong> при сохранении качества работ. На все выполненные работы и установленные запчасти предоставляется <strong>гарантия по договору</strong>. Запишитесь на диагностику прямо сейчас — мы проведём осмотр и рассчитаем точную стоимость ремонта вашего Mitsubishi ASX.</p>';
}
