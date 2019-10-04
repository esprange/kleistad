<?php
/**
 * Shortcode rapport (persoonlijke stookgegevens).
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad rapport class.
 */
class Public_Rapport extends Shortcode {

	/**
	 *
	 * Prepareer 'rapport' form inhoud
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data ) {
		$huidige_gebruiker = wp_get_current_user();
		$saldo             = new \Kleistad\Saldo( $huidige_gebruiker->ID );
		$data['naam']      = $huidige_gebruiker->display_name;
		$data['saldo']     = number_format_i18n( $saldo->bedrag, 2 );
		$data['items']     = [];
		$ovens             = \Kleistad\Oven::all();
		$reserveringen     = \Kleistad\Reservering::all();
		foreach ( $reserveringen as $reservering ) {
			foreach ( $reservering->verdeling as $stookdeel ) {
				if ( $stookdeel['id'] === $huidige_gebruiker->ID ) {
					$stoker          = get_userdata( $reservering->gebruiker_id );
					$data['items'][] = [
						'datum'     => $reservering->datum,
						'oven'      => $ovens[ $reservering->oven_id ]->naam,
						'stoker'    => false === $stoker ? 'onbekend' : $stoker->display_name,
						'stook'     => $reservering->soortstook,
						'temp'      => $reservering->temperatuur > 0 ? $reservering->temperatuur : '',
						'prog'      => $reservering->programma > 0 ? $reservering->programma : '',
						'perc'      => $stookdeel['perc'],
						'kosten'    => number_format_i18n(
							$stookdeel['prijs'] ?? $ovens[ $reservering->oven_id ]->stookkosten( $huidige_gebruiker->ID, $stookdeel['perc'] ),
							2
						),
						'voorlopig' => ! $reservering->verwerkt,
					];
				}
			}
		}
		return true;
	}

}
