<?php
/**
 * Fired during plugin deactivation
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */

/**
 * Fired during plugin deactivation.
 */
class Kleistad_Deactivator {

	/**
	 * Deactiveer de plugin.
	 *
	 * @since    4.0.87
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'kleistad_kosten' );
	}

}
