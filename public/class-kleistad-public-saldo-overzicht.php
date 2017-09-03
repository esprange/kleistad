<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_Saldo_Overzicht extends Kleistad_Public_Shortcode {

	/**
	 * Prepareer 'saldo_overzicht' form
	 *
	 * @param array $data data to prepare.
	 * @return array
	 *
	 * @since   4.0.0
	 */
	public function prepare( &$data = null ) {
		$gebruikers = get_users(
			[
				'fields' => [ 'id', 'display_name' ],
				'orderby' => [ 'nicename' ],
			]
		);
		$stokers = [];
		foreach ( $gebruikers as $gebruiker ) {
			if ( Kleistad_Roles::reserveer( $gebruiker->id ) ) {
				$stokers[] = [
					'naam' => $gebruiker->display_name,
					'saldo' => number_format( (float) get_user_meta( $gebruiker->id, 'stooksaldo', true ), 2, ',', '' ),
				];
			}
		}
		$data = [
			'stokers' => $stokers,
		];
		return true;
	}


}
