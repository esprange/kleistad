<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Kleistad
 */

// disable xdebug backtrace.
if ( function_exists( 'xdebug_disable' ) ) {
	xdebug_disable();
}

if ( false !== getenv( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', getenv( 'WP_PLUGIN_DIR' ) );
}

if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	require getenv( 'WP_DEVELOP_DIR' ) . 'tests/phpunit/includes/bootstrap.php';
}

const KLEISTAD_TEST = true;

tests_add_filter(
	'plugins_loaded',
	function() {
		if ( ! is_plugin_active( 'kleistad/kleistad.php' ) ) {
			exit( 'Some Plugin must be active to run the tests.' . PHP_EOL );
		}
	}
);

