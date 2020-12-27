<?php
/**
 * De definitie van de gebruikers class.
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
 * Kleistad Gebruikers class.
 *
 * @since 6.11.0
 */
class Gebruikers implements Countable, Iterator {

	/**
	 * De gebruikers
	 *
	 * @var array $gebruikers De gebruikers.
	 */
	protected $gebruikers = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	protected int $current_index = 0;

	/**
	 * Geef het aantal gebruikers terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->gebruikers );
	}

	/**
	 * Geef de huidige gebruiker terug.
	 *
	 * @return Gebruiker De gebruiker.
	 */
	public function current(): Gebruiker {
		return $this->gebruikers[ $this->current_index ];
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
		return isset( $this->gebruikers[ $this->current_index ] );
	}
}
