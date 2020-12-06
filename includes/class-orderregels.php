<?php
/**
 * De definitie van de orderregels class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Countable;
use Iterator;

/**
 * Kleistad Orderregels class.
 *
 * @since 6.11.0
 */
class Orderregels implements Countable, Iterator {

	/**
	 * De order regels
	 *
	 * @var array $regels De regels.
	 */
	private $regels = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param string $json_string Een optionele string om de regels mee te laden.
	 */
	public function __construct( string $json_string = null ) {
		if ( is_string( $json_string ) ) {
			foreach ( json_decode( $json_string, true ) as $regel ) {
				$this->regels[] = new Orderregel( $regel['artikel'], floatval( $regel['aantal'] ), floatval( $regel['prijs'] ), floatval( $regel['btw'] ) );
			}
		}
	}

	/**
	 * Voeg een regel toe.
	 *
	 * @param array | Orderregel $regeltoetevoegen Toe te voegen regel of regels.
	 */
	public function toevoegen( $regeltoetevoegen ) {
		if ( is_array( $regeltoetevoegen ) ) {
			$this->regels = array_merge( $this->regels, $regeltoetevoegen );
		} else {
			$this->regels[] = $regeltoetevoegen;
		}
		// Eventuele kortingsregels samenvoegen.
		$korting = false;
		foreach ( $this->regels as $key => $regel ) {
			if ( Orderregel::KORTING === $regel->artikel ) {
				if ( false === $korting ) {
					$korting = $key;
				} else {
					$this->regels[ $korting ]->prijs += $regel->prijs;
					unset( $this->regels[ $key ] );
				}
			}
		}
	}

	/**
	 * Vervang een of meer regels en behoud eventuele korting.
	 *
	 * @param array | Orderregel $regelvervangen Te vervangen regel of regels.
	 */
	public function vervangen( $regelvervangen ) {
		foreach ( $this->regels as $key => $regel ) {
			if ( Orderregel::KORTING !== $regel->artikel ) {
				unset( $this->regels[ $key ] );
			}
		}
		if ( is_array( $regelvervangen ) ) {
			foreach ( $regelvervangen as $regel ) {
				array_unshift( $this->regels, $regel );
			}
		} else {
			array_unshift( $this->regels, $regelvervangen );
		}
		$this->regels = array_values( $this->regels );
	}

	/**
	 * Bepaal het totaal bedrag van de order.
	 *
	 * @return float
	 */
	public function bruto() : float {
		return round( $this->netto() + $this->btw(), 2 );
	}

	/**
	 * Bepaal het totaal bedrag exclusief BTW.
	 *
	 * @return float
	 */
	public function netto() : float {
		$netto = 0.0;
		foreach ( $this->regels as $regel ) {
			$netto = round( $netto + $regel->prijs * $regel->aantal, 2 );
		}
		return round( $netto, 2 );
	}

	/**
	 * Bepaal het totaal bedrag aan BTW.
	 *
	 * @return float
	 */
	public function btw() : float {
		$btw = 0.0;
		foreach ( $this->regels as $regel ) {
			$btw = round( $btw + $regel->btw * $regel->aantal, 2 );
		}
		return round( $btw, 2 );
	}

	/**
	 * Geef de regels terug als een json string
	 *
	 * @return string De tekst.
	 */
	public function export() : string {
		$regels = [];
		foreach ( $this->regels as $regel ) {
			$regels[] = [
				'artikel' => $regel->artikel,
				'aantal'  => number_format( $regel->aantal, 2, '.', '' ),
				'prijs'   => number_format( $regel->prijs, 2, '.', '' ),
				'btw'     => number_format( $regel->btw, 2, '.', '' ),
			];
		}
		return wp_json_encode( $regels ) ?: '[]';
	}

	/**
	 * Geef het aantal regels terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->regels );
	}

	/**
	 * Geef de huidige regel terug.
	 *
	 * @return Orderregel De regel.
	 */
	public function current(): Orderregel {
		return $this->regels[ $this->current_index ];
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
	public function next() {
		$this->current_index++;
	}

	/**
	 * Ga terug naar het begin.
	 */
	public function rewind() {
		$this->current_index = 0;
	}

	/**
	 * Bepaal of het element bestaat.
	 *
	 * @return bool Of het bestaat of niet.
	 */
	public function valid(): bool {
		return isset( $this->regels[ $this->current_index ] );
	}
}
