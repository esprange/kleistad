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

namespace Kleistad;

/**
 * De kleistad saldo overzicht class.
 */
class Public_Saldo_Overzicht extends Shortcode {

	/**
	 * Prepareer 'saldo_overzicht' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data ) {
		$gebruikers      = get_users(
			[
				'fields'   => [ 'ID', 'display_name' ],
				'orderby'  => [ 'display_name' ],
				'role__in' => [ RESERVEER ],
			]
		);
		$data['stokers'] = [];
		foreach ( $gebruikers as $gebruiker ) {
			$saldo             = new Saldo( $gebruiker->ID );
			$data['stokers'][] = [
				'naam'  => $gebruiker->display_name,
				'saldo' => number_format_i18n( $saldo->bedrag, 2 ),
			];
		}
		return true;
	}


}
