<?php
/**
 * De definitie van de artikel afboeking class
 *
 * @link       https://www.kleistad.nl
 * @since      6.10.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Klasse voor het beheren van afboeking dubieuze debiteuren.
 */
class Afboeking extends LosArtikel {

	public const DEFINITIE = [
		'prefix' => '@',
		'naam'   => 'dubieuze debiteuren',
		'pcount' => 1,
	];

	/**
	 * Geef de artikel naam.
	 *
	 * @return string
	 */
	public function geef_artikelnaam() : string {
		return 'afboeking';
	}

}
