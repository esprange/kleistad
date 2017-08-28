<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Kleistad
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_environment() {

	$plugin_file = dirname( dirname( __FILE__ ) ) . '/kleistad.php';
	require $plugin_file;

	$plugins_to_activate = [ $plugin_file ];
	update_option( 'active_plugins', $plugins_to_activate );

	switch_theme( 'kleistad' );
	
}

tests_add_filter( 'muplugins_loaded', '_manually_load_environment' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
