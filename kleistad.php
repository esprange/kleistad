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
 * Description:       Een plugin voor vereniging Kleistad. Overreserveringen, stooksaldo administratie, cursus adminstratie en keramiek recepten.
 * Version:           4.5.1
 * Author:            Eric Sprangers
 * Author URI:        https://www.kleistad.nl
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       kleistad
 * GitHub Plugin URI: https://github.com/esprange/kleistad
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin activering.
 */
function activate_kleistad() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kleistad-activator.php';
	Kleistad_Activator::activate();
}

/**
 * Plugin deactivering.
 */
function deactivate_kleistad() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kleistad-deactivator.php';
	Kleistad_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_kleistad' );
register_deactivation_hook( __FILE__, 'deactivate_kleistad' );

/**
 * De basis plugin class die de admin en public hooks definieert.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-kleistad.php';

/**
 * Externe libraries toevoegen.
 */
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * Start uitvoering van de plugin.
 *
 * @since    4.0.87
 */
function run_kleistad() {

	$plugin = new Kleistad();
	$plugin->run();

}
run_kleistad();
