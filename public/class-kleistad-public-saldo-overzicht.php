<?php
/**
 * Shortcode saldo overzicht (voor bestuur).
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * De kleistad saldo overzicht class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Saldo_Overzicht extends Kleistad_Shortcode {

	/**
	 * Prepareer 'saldo_overzicht' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data = null ) {
		$gebruikers      = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => [ 'nicename' ],
			]
		);
		$data['stokers'] = [];
		foreach ( $gebruikers as $gebruiker ) {
			if ( Kleistad_Roles::reserveer( $gebruiker->ID ) ) {
				$saldo             = new Kleistad_Saldo( $gebruiker->ID );
				$data['stokers'][] = [
					'naam'  => $gebruiker->display_name,
					'saldo' => number_format_i18n( $saldo->bedrag, 2 ),
				];
			}
		}
		return true;
	}


}
