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

/**
 * De kleistad rapport class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Rapport extends Kleistad_Shortcode {

	/**
	 *
	 * Prepareer 'rapport' form inhoud
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$huidige_gebruiker = wp_get_current_user();
		$naam              = $huidige_gebruiker->display_name;
		$saldo             = new Kleistad_Saldo( $huidige_gebruiker->ID );
		$items             = [];
		$ovens             = Kleistad_Oven::all();
		$reserveringen     = Kleistad_Reservering::all();
		$regeling_store    = new Kleistad_Regelingen();

		foreach ( $reserveringen as $reservering ) {
			foreach ( $reservering->verdeling as $stookdeel ) {
				if ( $stookdeel['id'] == $huidige_gebruiker->ID ) {  // phpcs:ignore
					if ( isset( $stookdeel['prijs'] ) ) { // Berekening als vastgelegd in transactie.
						$kosten = $stookdeel['prijs'];
					} else { // Voorlopige berekening.
						$regeling = $regeling_store->get( $huidige_gebruiker->ID, $reservering->oven_id );
						$kosten   = round( $stookdeel['perc'] / 100 * ( ( is_null( $regeling ) ) ? $ovens[ $reservering->oven_id ]->kosten : $regeling ), 2 );
					}
					$stoker  = get_userdata( $reservering->gebruiker_id );
					$items[] = [
						'datum'     => $reservering->datum,
						'oven'      => $ovens[ $reservering->oven_id ]->naam,
						'stoker'    => ! $stoker ? 'onbekend' : $stoker->display_name,
						'stook'     => $reservering->soortstook,
						'temp'      => $reservering->temperatuur > 0 ? $reservering->temperatuur : '',
						'prog'      => $reservering->programma > 0 ? $reservering->programma : '',
						'perc'      => $stookdeel['perc'],
						'kosten'    => number_format_i18n( $kosten, 2 ),
						'voorlopig' => $reservering->verwerkt ? '' : 'genericon genericon-checkmark',
					];
				}
			}
		}
		$data = [
			'naam'  => $naam,
			'saldo' => number_format_i18n( $saldo->bedrag, 2 ),
			'items' => $items,
		];
		return true;
	}

}
