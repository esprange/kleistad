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
	private array $regels = [];

	/**
	 * Interne index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De verval datum (dat er betaald moet worden) die bij de orderregels hoort.
	 *
	 * @var int Datum.
	 */
	public int $verval_datum;

	/**
	 * De constructor
	 *
	 * @param string|null $json_string Een optionele string om de regels mee te laden.
	 */
	public function __construct( string $json_string = null ) {
		if ( is_string( $json_string ) ) {
			foreach ( json_decode( $json_string, true ) as $regel ) {
				$this->regels[] = new Orderregel( $regel['artikel'], floatval( $regel['aantal'] ), floatval( $regel['prijs'] ), floatval( $regel['btw'] ) );
			}
		}
		$this->verval_datum = strtotime( '+14 days  0:00' );
	}

	/**
	 * Deep clone
	 */
	public function __clone() {
		foreach ( array_keys( $this->regels ) as $key ) {
			$this->regels[ $key ] = clone $this->regels[ $key ];
		}
	}

	/**
	 * Verwijder alle regels die geen korting betreffen.
	 *
	 * @return void
	 */
	public function reset() : void {
		foreach ( $this->regels as $key => $regel ) {
			if ( Orderregel::KORTING !== $regel->artikel ) {
				unset( $this->regels[ $key ] );
			}
		}
	}

	/**
	 * Voeg een regel toe.
	 *
	 * @param Orderregel $regeltoetevoegen Toe te voegen regel of regels.
	 */
	public function toevoegen( Orderregel $regeltoetevoegen ) {
		$this->regels[] = $regeltoetevoegen;
		$korting        = false;
		$kortingkey     = 0;
		// Eventuele kortingsregels samenvoegen.
		foreach ( $this->regels as $key => $regel ) {
			if ( Orderregel::KORTING === $regel->artikel ) {
				if ( false === $korting ) {
					$korting    = true;
					$kortingkey = $key;
					continue;
				}
				$this->regels[ $kortingkey ]->prijs += $regel->prijs;
				$this->regels[ $kortingkey ]->btw   += $regel->btw;
				unset( $this->regels[ $key ] );
			}
		}
	}

	/**
	 * Bepaal het totaal bedrag van de order.
	 *
	 * @return float
	 */
	public function get_bruto() : float {
		return round( $this->get_netto() + $this->get_btw(), 2 );
	}

	/**
	 * Bepaal het totaal bedrag exclusief BTW.
	 *
	 * @return float
	 */
	public function get_netto() : float {
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
	public function get_btw() : float {
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
	public function get_json_export() : string {
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
		return isset( $this->regels[ $this->current_index ] );
	}
}
