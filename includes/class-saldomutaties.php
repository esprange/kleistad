<?php
/**
 * De definitie van de saldo mutaties class
 *
 * @link       https://www.kleistad.nl
 * @since      7.9.6
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Countable;
use Iterator;

/**
 * Klasse voor het registreren van een saldo mutatie.
 */
class SaldoMutaties implements Countable, Iterator {

	/**
	 * De mutaties
	 *
	 * @var array $mutaties De mutaties.
	 */
	private array $mutaties = [];

	/**
	 * Interne index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * Voeg mutatie toe.
	 *
	 * @param SaldoMutatie $mutatie Toe te voegen mutatie.
	 */
	public function toevoegen( SaldoMutatie $mutatie ) {
		$this->mutaties[] = $mutatie;
	}

	/**
	 * Geef het aantal mutaties terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->mutaties );
	}

	/**
	 * Geef de huidige mutatie terug.
	 *
	 * @return SaldoMutatie De mutatie.
	 */
	public function current(): SaldoMutatie {
		return $this->mutaties[ $this->current_index ];
	}

	/**
	 * Geef de meest recente mutatie terug.
	 *
	 * @return SaldoMutatie De mutatie.
	 */
	public function end(): SaldoMutatie {
		$this->current_index = count( $this->mutaties ) - 1;
		return $this->current();
	}

	/**
	 * Geef de sleutel terug.
	 *
	 * @return int De sleutel.
	 */
	public function key(): int {
		return $this->current_index;
	}

	/**
	 * Ga naar de volgende in de lijst.
	 */
	public function next(): void {
		$this->current_index++;
	}

	/**
	 * Ga terug naar het begin.
	 */
	public function rewind(): void {
		$this->current_index = 0;
	}

	/**
	 * Bepaal of het element bestaat.
	 *
	 * @return bool Of het bestaat of niet.
	 */
	public function valid(): bool {
		return isset( $this->mutaties[ $this->current_index ] );
	}

	/**
	 * Filter de verzameling op code soort.
	 *
	 * @param string $code_segment Het filter.
	 *
	 * @return SaldoMutaties
	 */
	public function filter_by_code( string $code_segment ) : SaldoMutaties {
		$mutaties = $this->mutaties;
		foreach ( $mutaties as $mutatie ) {
			if ( ! str_contains( $mutatie->code, $code_segment ) ) {
				unset( $this->mutaties );
			}
		}
		$this->mutaties = array_values( $mutaties );
		$this->rewind();
		return $this;
	}

	/**
	 * Sort de verzameling
	 *
	 * @param bool $ascending Als true dan ascending.
	 *
	 * @return SaldoMutaties
	 */
	public function sort_by_date( bool $ascending = true ) : SaldoMutaties {
		usort(
			$this->mutaties,
			function( $links, $rechts ) use ( $ascending ) {
				return $ascending ? ( $links->datum <=> $rechts->datum ) : ( $rechts->datum <=> $links->datum );
			}
		);
		$this->rewind();
		return $this;
	}

}
