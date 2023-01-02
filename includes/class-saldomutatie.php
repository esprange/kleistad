<?php
/**
 * De definitie van de saldo mutatie class
 *
 * @link       https://www.kleistad.nl
 * @since      7.9.6
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Klasse voor het registreren van een saldo mutatie.
 */
class SaldoMutatie {

	/**
	 * Registratie gewicht voor verbruik materialen.
	 *
	 * @param string $code    Referentie, bijvoorbeeld van de bijbehorende order.
	 * @param float  $bedrag  Bedrag dat het saldo wijzigt.
	 * @param string $status  Status tekst.
	 * @param int    $gewicht Gewicht in gram waarop de verbruiksmutatie gebaseerd is.
	 * @param int    $datum   De mutatie datum.
	 */
	public function __construct(
		public string $code,
		public float $bedrag,
		public string $status = '',
		public int $gewicht = 0,
		public int $datum = 0
	) {
		if ( 0 === $datum ) {
			$this->datum = strtotime( 'today' );
		}
	}
}
