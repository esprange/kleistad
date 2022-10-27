<?php
/**
 * De Kleistad plugin bootstrap file
 *
 * @link              https://www.kleistad.nl
 * @since             4.0.87
 * @package           Kleistad
 *
 * @wordpress-plugin
 * Plugin Name:       Kleistad
 * Plugin URI:        https://github.com/esprange/kleistad
 * Description:       Een plugin voor vereniging Kleistad. Oven reserveringen, saldo administratie, cursus adminstratie en keramiek recepten.
 * Version:           7.8.6
 * Author:            Eric Sprangers
 * Author URI:        https://www.kleistad.nl
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       kleistad
 * Requires at least: 4.8.0
 * Requires PHP:      8.0
 * GitHub Plugin URI: https://github.com/esprange/kleistad
 */

namespace Kleistad;

if ( ! defined( 'WPINC' ) ) {
	die;
}

const KLEISTAD_API = 'kleistad_api';

/**
 * De autoloader toevoegen.
 *
 * @noinspection PhpIncludeInspection
 */
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * Plugin activering.
 */
register_activation_hook(
	__FILE__,
	function() {
		Activator::activate();
	}
);

/**
 * Plugin deactivering.
 */
register_deactivation_hook(
	__FILE__,
	function() {
		Deactivator::deactivate();
	}
);

/**
 * Start uitvoering van de plugin.
 */
$kleistad_plugin = new Kleistad();
$kleistad_plugin->run();
