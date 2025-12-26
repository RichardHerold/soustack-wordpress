<?php
/**
 * Plugin Name: Soustack Sidecar Publisher
 * Description: Serve Soustack sidecar JSON, emit discovery links, and validate payloads when publishing.
 * Version: 0.1.0
 * Author: Soustack
 * License: GPL-2.0-or-later
 *
 * @package Soustack
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-soustack-core.php';
require_once __DIR__ . '/includes/class-soustack-settings.php';
require_once __DIR__ . '/includes/class-soustack-publisher.php';

$soustack_settings  = new Soustack_Settings();
$soustack_publisher = new Soustack_Publisher( $soustack_settings );

$soustack_settings->register();
$soustack_publisher->register();

register_activation_hook(
	__FILE__,
	function () use ( $soustack_publisher ) {
		$soustack_publisher->register_rewrite();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);
