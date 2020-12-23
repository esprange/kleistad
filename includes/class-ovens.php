<?php
/**
 * De definitie van de ovens class.
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
 * Kleistad Ovens class.
 *
 * @since 6.11.0
 */
class Ovens implements Countable, Iterator {

	/**
	 * De ovens
	 *
	 * @var array $ovens De ovens.
	 */
	private $ovens = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 */
	public function __construct() {
		global $wpdb;
		$oven_ids = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}kleistad_ovens", ARRAY_A );
		foreach ( array_column( $oven_ids, 'id' ) as $oven_id ) {
			$this->ovens[] = new Oven( $oven_id );
		}
	}

	/**
	 * Voeg een oven toe.
	 *
	 * @param Oven $oventoetevoegen Toe te voegen oven.
	 */
	public function toevoegen( Oven $oventoetevoegen ) {
		$oventoetevoegen->save();
		$this->ovens[] = $oventoetevoegen;
	}

	/**
	 * Geef het aantal ovens terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->ovens );
	}

	/**
	 * Geef de huidige oven terug.
	 *
	 * @return Oven De oven.
	 */
	public function current(): Oven {
		return $this->ovens[ $this->current_index ];
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
		return isset( $this->ovens[ $this->current_index ] );
	}
}
