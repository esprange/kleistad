<?php
/**
 * De definitie van de cursisten class.
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
 * Kleistad Cursisten class.
 *
 * @since 6.11.0
 */
class Cursisten implements Countable, Iterator {

	/**
	 * De cursisten
	 *
	 * @var array $cursisten De cursisten.
	 */
	private $cursisten = [];

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
		$cursisten = get_users(
			[
				'fields'       => [ 'ID' ],
				'meta_key'     => Inschrijving::META_KEY,
				'meta_compare' => '!==',
				'meta_value'   => '',
				'orderby'      => 'display_name',
			]
		);
		foreach ( $cursisten as $cursist ) {
			$this->cursisten[] = new Cursist( $cursist->ID );
		}
	}

	/**
	 * Geef het aantal cursisten terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->cursisten );
	}

	/**
	 * Geef de huidige cursist terug.
	 *
	 * @return Cursist De cursist.
	 */
	public function current(): Cursist {
		return $this->cursisten[ $this->current_index ];
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
		return isset( $this->cursisten[ $this->current_index ] );
	}
}
