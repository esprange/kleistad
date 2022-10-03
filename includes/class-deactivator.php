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

namespace Kleistad;

/**
 * De deactivator class.
 */
class Deactivator {

	/**
	 * Deactiveer de plugin.
	 */
	public static function deactivate() : void {
		wp_clear_scheduled_hook( 'kleistad_rcv_email' );
	}

}
