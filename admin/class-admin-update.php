<?php
/**
 * De admin functies van de kleistad plugin voor updates.
 *
 * @link       https://www.kleistad.nl
 * @since      6.19.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * De admin-update functies van de plugin.
 */
class Admin_Update {

	/**
	 * Haal de info bij de update server op.
	 *
	 * @since 4.3.8
	 *
	 * @param  string $action De gevraagde actie.
	 * @return bool|object remote info.
	 */
	public function get_remote( string $action = '' ) {
		$params = [
			'timeout' => 10,
			'body'    => [
				'action' => $action,
			],
		];
		/**
		 * De plugin url heeft vooralsnog geen certificaat.
		 *
		 * @noinspection HttpUrlsUsage
		 */
		$request = wp_remote_get( 'http://plugin.kleistad.nl/update.php', $params );
		if ( ! is_wp_error( $request ) || ( is_array( $request ) && wp_remote_retrieve_response_code( $request ) === 200 ) ) {
			// phpcs:ignore
			return unserialize( $request['body'] );
		}
		return false;
	}

}
