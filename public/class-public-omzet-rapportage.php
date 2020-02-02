<?php
/**
 * Shortcode omzet rapportage (voor bestuur).
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad stookbestand class.
 */
class Public_Omzet_Rapportage extends Shortcode {

	/**
	 *
	 * Prepareer 'omzet_rapportage' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   6.1.0
	 */
	protected function prepare( &$data ) {
		if ( 'details' === $data['actie'] ) {
			list( $data['jaar'], $data['maand'], $data['artikelcode'] ) = explode( '-', $data['id'] );
			$data['artikel']      = \Kleistad\Artikel::$artikelen[ $data['artikelcode'] ]['naam'];
			$data['periode']      = strtotime( "{$data['jaar']}-{$data['maand']}-1 00:00" );
			$data['omzetdetails'] = \Kleistad\Orderrapportage::maanddetails( $data['maand'], $data['jaar'], $data['artikelcode'] );
			return true;
		} else {
			if ( empty( $data['id'] ) ) {
				$data['periode'] = strtotime( 'first day of this month 00:00' );
				$data['jaar']    = date( 'Y', $data['periode'] );
				$data['maand']   = date( 'm', $data['periode'] );
			} else {
				list( $data['jaar'], $data['maand'] ) = explode( '-', $data['id'] );
				$data['periode']                      = strtotime( "{$data['id']}-1 00:00" );
			}
			$data['omzet'] = \Kleistad\Orderrapportage::maandrapportage( $data['maand'], $data['jaar'] );
			return true;
		}
	}

	/**
	 * Maak een presentielijst aan.
	 */
	protected function omzetrapport() {
		$maand   = filter_input( INPUT_GET, 'maand', FILTER_SANITIZE_STRING );
		$jaar    = filter_input( INPUT_GET, 'jaar', FILTER_SANITIZE_STRING );
		$rapport = new \Kleistad\OmzetRapport();
		return $rapport->run( $maand, $jaar );
	}
}
