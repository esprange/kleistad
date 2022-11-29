<?php
/**
 * De definitie van de show class
 *
 * @link       https://www.kleistad.nl
 * @since      7.9.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Show class.
 */
class Show {

	/**
	 * De datums
	 *
	 * @var array $datums De datums van de shows.
	 */
	private array $datums = [];

	/**
	 * Bepaal de datums van de shows. Vooralsnog starten die op de eerste maandag van maand 1,3,5,7,9 en 11.
	 * Het is bijvoorbeeld een optie om deze datums via een beheer scherm aanpasbaar te maken, als daar behoefte aan is.
	 *
	 * @param int $datum Default huidige datum, aanpasbaar voor testdoeleinden.
	 */
	public function __construct( int $datum = 0 ) {
		$datum      = $datum ?: time();
		$maand      = intval( date( 'n', $datum ) ) - 1;
		$periode    = 0 === $maand % 2 ? -2 : -3;
		$show_count = 0;
		while ( 3 > $show_count ) {
			$start = strtotime( "first monday of $periode month 0:00", $datum );
			$eind  = strtotime( 'first monday of ' . $periode + 2 . ' month 0:00', $datum );
			if ( $datum >= $start && $datum < $eind || $show_count ) {
				$this->datums[] = [
					'start' => $start,
					'eind'  => $eind,
				];
			}
			$periode   += 2;
			$show_count = count( $this->datums );
		}
	}

	/**
	 * Bepaal de datums van de huidige en komende shows.
	 *
	 * @return array
	 */
	public function get_datums() : array {
		return $this->datums;
	}
}
