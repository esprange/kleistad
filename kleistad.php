<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              www.sprako.nl/wordpress/eric
 * @since             4.0.87
 * @package           Kleistad
 *
 * @wordpress-plugin
 * Plugin Name:       Kleistad
 * Plugin URI:        www.sprako.nl/wordpress/kleistad
 * Description:       Een plugin voor vereniging Kleistad. Overreserveringen, stooksaldo administratie, cursus adminstratie en keramiek recepten.
 * Version:           4.3.7
 * Author:            Eric Sprangers
 * Author URI:        www.sprako.nl/wordpress/eric
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       kleistad
 * GitHub Plugin URI: https://github.com/esprange/kleistad
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-kleistad-activator.php
 */
function activate_kleistad() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kleistad-activator.php';
	Kleistad_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-kleistad-deactivator.php
 */
function deactivate_kleistad() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kleistad-deactivator.php';
	Kleistad_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_kleistad' );
register_deactivation_hook( __FILE__, 'deactivate_kleistad' );

/**
 * The core plugin class that is used to define admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-kleistad.php';

/**
 * Include the external libraries.
 */
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * Begins execution of the plugin.
 *
 * @since    4.0.87
 */
function run_kleistad() {

	$plugin = new Kleistad();
	$plugin->run();

}
run_kleistad();
