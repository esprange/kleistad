<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Kleistad
 */

$GLOBALS['wp_tests_options'] = array(

	'active_plugins' => array( 'kleistad/kleistad.php' ),

);

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_environment() {

	$plugin_file = dirname( dirname( __FILE__ ) ) . '/kleistad.php';
	require $plugin_file;

}

tests_add_filter( 'muplugins_loaded', '_manually_load_environment' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

update_option( 'kleistad_opties', [ 'betalen' => 0, 'sleutel_test' => 'test_12345678901234567890123456789' ] );
