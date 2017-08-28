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
	
	require dirname( dirname( __FILE__ ) ) . '/kleistad.php';

	// Add your theme …
	switch_theme('kleistad');
	
	// Update array with plugins to include ...
	$plugins_to_active = ['kleistad/kleistad.php'];
	update_option( 'active_plugins', $plugins_to_active );

}

tests_add_filter( 'muplugins_loaded', '_manually_load_environment' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
