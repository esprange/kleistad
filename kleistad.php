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
 * Description:       Een plugin voor vereniging Kleistad. Oven reserveringen, stooksaldo administratie, cursus adminstratie en keramiek recepten.
 * Version:           6.15.4
 * Author:            Eric Sprangers
 * Author URI:        https://www.kleistad.nl
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       kleistad
 * GitHub Plugin URI: https://github.com/esprange/kleistad
 */

namespace Kleistad;

if ( ! defined( 'WPINC' ) ) {
	die;
}

const KLEISTAD_API = 'kleistad_api';

/**
 * De autoloader toevoegen.
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
