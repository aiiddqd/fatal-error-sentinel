<?php 

namespace FatalErrorSentinel;


if ( defined( 'BETTERSTACK_LOGS_SOURCE_TOKEN' ) ) {
	add_action( 'shutdown', function () {

		$error = error_get_last();

		if ( is_null( $error ) ) {
			return;
		}

		if ( $error['type'] != E_ERROR ) {
			return;
		}

		send_to_betterstack( $error );
	}, 1 );

	add_filter( 'wp_php_error_message', function ($message, $error) {

		send_to_betterstack( $error );

		return $message;
	}, 11, 2 );

}


function send_to_betterstack( $error ) {
	$message = explode( 'Stack trace:', $error['message'] );

	$data = [ 
		'message' => trim( $message[0] ),
		'nested' => [],
	];

	if ( isset( $message[1] ) ) {
		$data['nested']['stack_trace'] = explode( "\n", trim( $message[1] ) );
	}

	if ( $user_id = get_current_user_id() ) {
		$data['nested']['user_id'] = $user_id;
	}

	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$data['nested']['request'] = $_SERVER['REQUEST_URI'];
	}

	if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
		$data['nested']['referer'] = $_SERVER['HTTP_REFERER'];
	} else {
		$data['nested']['referer'] = 'unknown';
	}

	if ( isset( $error['type'] ) ) {
		$data['nested']['type'] = $error['type'];
	}

	$json = json_encode( $data );

	$result = wp_remote_post( 'https://in.logs.betterstack.com', [ 
		'headers' => [ 
			'Authorization' => 'Bearer ' . BETTERSTACK_LOGS_SOURCE_TOKEN,
			'Content-Type' => 'application/json'
		],
		'body' => $json,
	] );

	return $result;
}

/**
 * simple test for check BetterStack
 * 
 * 1. just run {{siteUrl}}/?test_BetterStackLogsIntegration
 * 2. check log https://logs.betterstack.com/
 */
add_action( 'init', function () {

	if ( ! isset( $_GET['test_BetterStackLogsIntegration'] ) ) {
		return;
	}

	test_wrong_function();
} );
