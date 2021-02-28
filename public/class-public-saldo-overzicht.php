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
		$stokers         = new Stokers();
		$data['stokers'] = [];
		foreach ( $stokers as $stoker ) {
			$data['stokers'][] = [
				'naam'  => $stoker->display_name,
				'saldo' => number_format_i18n( $stoker->saldo->bedrag, 2 ),
			];
		}
		return true;
	}


}
