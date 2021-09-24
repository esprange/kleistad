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
	protected function prepare( array &$data ) {
		$register = new Artikelregister();
		$rapport  = new Orderrapportage();
		if ( 'details' === $data['actie'] ) {
			sscanf( $data['id'], '%d-%d-%s', $data['jaar'], $data['maand'], $data['artikelcode'] );
			$data['artikel']      = $register->geef_naam( $data['artikelcode'] );
			$data['omzetdetails'] = $rapport->maanddetails( $data['maand'], $data['jaar'], $data['artikelcode'] );
			return true;
		}
		if ( empty( $data['id'] ) ) {
			$data['id'] = date( 'Y-m', strtotime( 'this month 00:00' ) );
		}
		sscanf( $data['id'], '%d-%d', $data['jaar'], $data['maand'] );
		$data['omzet'] = $rapport->maandrapport( $data['maand'], $data['jaar'] );
		return true;
	}

	/**
	 * Maak een presentielijst aan.
	 *
	 * @return string Pad naar het rapport.
	 */
	protected function omzetrapport() : string {
		$maand = filter_input( INPUT_GET, 'maand', FILTER_SANITIZE_STRING );
		$jaar  = filter_input( INPUT_GET, 'jaar', FILTER_SANITIZE_STRING );
		if ( is_null( $maand ) || is_null( $jaar ) ) {
			return '';
		}
		$rapport = new OmzetRapport();
		return $rapport->run( $maand, $jaar );
	}
}
