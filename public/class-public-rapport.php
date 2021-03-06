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
	protected function prepare( array &$data ) {
		$huidige_gebruiker = wp_get_current_user();
		$saldo             = new Saldo( $huidige_gebruiker->ID );
		$data['naam']      = $huidige_gebruiker->display_name;
		$data['saldo']     = number_format_i18n( $saldo->bedrag, 2 );
		$data['items']     = [];
		$ovens             = new Ovens();
		foreach ( $ovens as $oven ) {
			$stoken = new Stoken( $oven->id, 0, time() );
			foreach ( $stoken as $stook ) {
				if ( ! $stook->is_gereserveerd() ) {
					continue;
				}
				foreach ( $stook->stookdelen as $stookdeel ) {
					if ( $stookdeel->medestoker === $huidige_gebruiker->ID ) {
						$stoker          = get_userdata( $stook->hoofdstoker );
						$data['items'][] = [
							'datum'     => $stook->datum,
							'oven'      => $oven->naam,
							'stoker'    => false === $stoker ? 'onbekend' : $stoker->display_name,
							'stook'     => $stook->soort,
							'temp'      => $stook->temperatuur > 0 ? $stook->temperatuur : '',
							'prog'      => $stook->programma > 0 ? $stook->programma : '',
							'perc'      => $stookdeel->percentage,
							'kosten'    => number_format_i18n(
								$stookdeel->prijs ?? $oven->stookkosten( $huidige_gebruiker->ID, $stookdeel->percentage, $stook->temperatuur ),
								2
							),
							'voorlopig' => ! $stook->verwerkt,
						];
					}
				}
			}
		}
		return true;
	}

}
