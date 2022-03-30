<?php
/**
 * PHPUnit bootstrap file
 *
 * Bootstrap voor uitvoering van unit testen van de kleistad plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      6.16.6
 *
 * @package    Kleistad
 * @file bootstrap.php
 */

/**
 * Een aantal opstart acties.
 */
const KLEISTAD_TEST = true;

// disable xdebug backtrace.
if ( function_exists( 'xdebug_disable' ) ) {
	xdebug_disable();
}

if ( false !== getenv( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', getenv( 'WP_PLUGIN_DIR' ) );
}

if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	/**
	 * Suppress de phpstorm foutmelding
	 *
	 * @noinspection PhpIncludeInspection
	 */
	require getenv( 'WP_DEVELOP_DIR' ) . 'tests/phpunit/includes/bootstrap.php';
}

/**
 * Voor Mollie simulatie, starten met schone database.
 */
$mollie_sim = sys_get_temp_dir() . '/mollie.db';
if ( file_exists( $mollie_sim ) ) {
	unlink( $mollie_sim );
}

require dirname( __FILE__, 2 ) . '/kleistad.php';

tests_add_filter(
	'plugins_loaded',
	function() {
		if ( ! is_plugin_active( 'kleistad/kleistad.php' ) ) {
			exit( 'Some Plugin must be active to run the tests.' . PHP_EOL );
		}
	}
);

do_action( 'init' );
