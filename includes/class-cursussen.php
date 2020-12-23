<?php
/**
 * De definitie van de cursussen class.
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
 * Kleistad Cursussen class.
 *
 * @since 6.11.0
 */
class Cursussen implements Countable, Iterator {

	/**
	 * De cursussen
	 *
	 * @var array $cursussen De cursussen.
	 */
	private $cursussen = [];

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
		$cursus_ids = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}kleistad_cursussen", ARRAY_A );
		foreach ( array_column( $cursus_ids, 'id' ) as $cursus_id ) {
			$this->cursussen[] = new Cursus( $cursus_id );
		}
	}

	/**
	 * Geef het aantal cursussen terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->cursussen );
	}

	/**
	 * Geef de huidige cursus terug.
	 *
	 * @return Cursus De cursus.
	 */
	public function current(): Cursus {
		return $this->cursussen[ $this->current_index ];
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
		return isset( $this->cursussen[ $this->current_index ] );
	}
}
