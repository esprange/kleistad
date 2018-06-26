<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * De deactivator class.
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
