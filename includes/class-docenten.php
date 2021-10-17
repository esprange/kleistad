<?php
/**
 * De definitie van de docenten class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.20.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Countable;
use Iterator;

/**
 * Kleistad Docenten class.
 *
 * @since 6.20.0
 */
class Docenten implements Countable, Iterator {

	/**
	 * De gebruikers
	 *
	 * @var array $docenten De gebruikers.
	 */
	protected array $docenten = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	protected int $current_index = 0;

	/**
	 * De constructor
	 */
	public function __construct() {
		$docenten = get_users(
			[
				'fields'  => [ 'ID' ],
				'role'    => [ DOCENT ],
				'orderby' => 'display_name',
			]
		);
		foreach ( $docenten as $docent ) {
			$this->docenten[] = new Docent( $docent->ID );
		}
	}

	/**
	 * Geef de huidige gebruiker terug.
	 *
	 * @return Docent De gebruiker.
	 */
	public function current(): Docent {
		return $this->docenten[ $this->current_index ];
	}

	/**
	 * Geef het aantal docenten terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->docenten );
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
		return isset( $this->docenten[ $this->current_index ] );
	}
}
