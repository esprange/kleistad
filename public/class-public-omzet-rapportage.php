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
	 * Prepareer 'omzet_rapportage' form
	 *
	 * @return string
	 */
	protected function prepare_overzicht() : string {
		$rapport = new Orderrapportage();
		if ( empty( $this->data['id'] ) ) {
			$this->data['id'] = date( 'Y-m', strtotime( 'this month 00:00' ) );
		}
		sscanf( $this->data['id'], '%d-%d', $this->data['jaar'], $this->data['maand'] );
		$this->data['omzet'] = $rapport->maandrapport( $this->data['maand'], $this->data['jaar'] );
		return $this->content();
	}

	/**
	 * Geef de details van een artikel
	 *
	 * @return string
	 */
	protected function prepare_details() : string {
		$register = new Artikelregister();
		$rapport  = new Orderrapportage();
		sscanf( $this->data['id'], '%d-%d-%s', $this->data['jaar'], $this->data['maand'], $this->data['artikelcode'] );
		$this->data['artikel']      = $register->get_naam( $this->data['artikelcode'] );
		$this->data['omzetdetails'] = $rapport->maanddetails( $this->data['maand'], $this->data['jaar'], $this->data['artikelcode'] );
		return $this->content();
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
