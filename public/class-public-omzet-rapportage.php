<?php
/**
 * Shortcode stookbestand (voor bestuur).
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
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
	 * De vanaf datum van het stookbestand
	 *
	 * @var int $vanaf_datum de begindatum van het stookbestand.
	 */
	private $vanaf_datum;

	/**
	 * De tot datum van het stookbestand
	 *
	 * @var int $tot_datum de einddatum van het stookbestand.
	 */
	private $tot_datum;

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
		if ( empty( $data['id'] ) ) {
			$data['periode'] = strtotime( 'first day of last month' );
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
