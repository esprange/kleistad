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
	 */
	protected function prepare() {
		$this->data['stokers'] = [];
		foreach ( new Stokers() as $stoker ) {
			$this->data['stokers'][] = [
				'naam'  => $stoker->display_name,
				'saldo' => number_format_i18n( $stoker->saldo->bedrag, 2 ),
			];
		}
		return true;
	}


}
