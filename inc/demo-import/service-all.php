<?php
/**
 * Master content installer: все страницы услуг (miauto_service).
 *
 * Triggered by visiting: /wp-admin/?miauto_fill_all_services=1
 * Safe to run multiple times — fills only empty fields.
 *
 * @package miauto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function miauto_fill_all_services_init() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_GET['miauto_fill_all_services'] ) || '1' !== $_GET['miauto_fill_all_services'] ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'miauto_fill_all_services' ) ) {
		add_action( 'admin_notices', function () {
			$url = wp_nonce_url( admin_url( '?miauto_fill_all_services=1' ), 'miauto_fill_all_services' );
			echo '<div class="notice notice-info"><p>MI-AUTO: Для заполнения всех страниц услуг перейдите по ссылке: <a href="' . esc_url( $url ) . '">Заполнить все страницы услуг</a></p></div>';
		} );
		return;
	}

	set_time_limit( 600 );
	wp_raise_memory_limit( 'admin' );

	$runners = array(
		'Автоэлектрика'               => 'miauto_run_fill_avtoelektrika',
		'Сход-развал'                 => 'miauto_run_fill_scod_razval',
		'Шиномонтаж'                  => 'miauto_run_fill_shinomontazh',
		'Тормозная система'           => 'miauto_run_fill_tormoznaya',
		'Ремонт подвески'             => 'miauto_run_fill_podveska',
		'Рулевое управление'          => 'miauto_run_fill_rulevoe',
		'Выхлопная система'           => 'miauto_run_fill_vyhlopnaya',
		'Компьютерная диагностика'    => 'miauto_run_fill_diagnostika',
		'Замена ремня ГРМ'            => 'miauto_run_fill_grm',
		'Заправка кондиционера'       => 'miauto_run_fill_konditsioner',
	);

	$results = array();

	foreach ( $runners as $label => $fn ) {
		if ( ! function_exists( $fn ) ) {
			$results[] = '⚠ ' . $label . ': функция ' . $fn . '() не найдена';
			continue;
		}
		$result = call_user_func( $fn );
		if ( is_wp_error( $result ) ) {
			$results[] = '✗ ' . $label . ': ' . $result->get_error_message();
		} else {
			$results[] = '✓ ' . $label;
		}
	}

	$output = $results;
	add_action( 'admin_notices', function () use ( $output ) {
		$lines = implode( '<br>', array_map( 'esc_html', $output ) );
		echo '<div class="notice notice-success"><p><strong>MI-AUTO: Результат заполнения всех страниц услуг:</strong><br>' . $lines . '</p></div>';
	} );
}
add_action( 'admin_init', 'miauto_fill_all_services_init' );
