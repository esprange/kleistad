<?php
/**
 * De definitie van de dagdelenkaarten class.
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
 * Kleistad Dagdelenkaarten class.
 *
 * @since 6.11.0
 */
class Dagdelenkaarten implements Countable, Iterator {

	/**
	 * De dagdelenkaarten
	 *
	 * @var array $dagdelenkaarten De dagdelenkaarten.
	 */
	private $dagdelenkaarten = [];

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
		$gebruikers = get_users(
			[
				'fields'       => [ 'ID' ],
				'meta_key'     => Dagdelenkaart::META_KEY,
				'meta_compare' => '!==',
				'meta_value'   => '',
				'orderby'      => 'display_name',
			]
		);
		foreach ( $gebruikers as $gebruiker ) {
			$this->dagdelenkaarten[] = new Dagdelenkaart( $gebruiker->ID );
		}
	}

	/**
	 * Geef het aantal dagdelenkaarten terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->dagdelenkaarten );
	}

	/**
	 * Geef de huidige abonnement terug.
	 *
	 * @return Dagdelenkaart De abonnement.
	 */
	public function current(): Dagdelenkaart {
		return $this->dagdelenkaarten[ $this->current_index ];
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
		return isset( $this->dagdelenkaarten[ $this->current_index ] );
	}

	/**
	 * Dagelijkse handelingen.
	 */
	public static function doe_dagelijks() {
		// Geen functionaliteit vooralsnog.
	}

}
