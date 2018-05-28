<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
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
class Kleistad_Public_Rapport extends Kleistad_Shortcode {

	/**
	 *
	 * Prepareer 'rapport' form inhoud
	 *
	 * @param array $data date to be prepared.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$huidige_gebruiker = wp_get_current_user();
		$naam              = $huidige_gebruiker->display_name;
		$saldo             = new Kleistad_Saldo( $huidige_gebruiker->ID );
		$items             = [];

		$oven_store        = new Kleistad_Ovens();
		$ovens             = $oven_store->get();
		$reservering_store = new Kleistad_Reserveringen();
		$reserveringen     = $reservering_store->get();
		$regeling_store    = new Kleistad_Regelingen();

		foreach ( $reserveringen as $reservering ) {
			foreach ( $reservering->verdeling as $stookdeel ) {
				if ( $stookdeel['id'] == $huidige_gebruiker->ID ) {  // WPCS: loose comparison ok.
					if ( isset( $stookdeel['prijs'] ) ) { // Berekening als vastgelegd in transactie.
						$kosten = $stookdeel['prijs'];
					} else { // Voorlopige berekening.
						$regeling = $regeling_store->get( $huidige_gebruiker->ID, $reservering->oven_id );
						$kosten   = round( $stookdeel['perc'] / 100 * ( ( is_null( $regeling ) ) ? $ovens[ $reservering->oven_id ]->kosten : $regeling ), 2 );
					}
					$stoker  = get_userdata( $reservering->gebruiker_id );
					$items[] = [
						'datum'     => $reservering->dag . '-' . $reservering->maand . '-' . $reservering->jaar,
						'sdatum'    => $reservering->datum,
						'oven'      => $ovens[ $reservering->oven_id ]->naam,
						'stoker'    => ! $stoker ? 'onbekend' : $stoker->display_name,
						'stook'     => $reservering->soortstook,
						'temp'      => $reservering->temperatuur > 0 ? $reservering->temperatuur : '',
						'prog'      => $reservering->programma > 0 ? $reservering->programma : '',
						'perc'      => $stookdeel['perc'],
						'kosten'    => number_format( $kosten, 2, ',', '' ),
						'voorlopig' => $reservering->verwerkt ? '' : 'genericon genericon-checkmark',
					];
				}
			}
		}
		usort(
			$items, function ( $a, $b ) {
				return $a['sdatum'] < $b['sdatum'] ? 1 : -1;
			}
		);
		$data = [
			'naam'  => $naam,
			'saldo' => number_format( $saldo->bedrag, 2, ',', '' ),
			'items' => $items,
		];
		return true;
	}

}
