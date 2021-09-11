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
	 * Overrule de losse verkoop constructor
	 *
	 * @param int|null $verkoop_id Verkoop_id is hier niet gebruikt.
	 *
	 * @noinspection PhpMissingParentConstructorInspection*/
	public function __construct( ?int $verkoop_id = null ) {
	}
}
