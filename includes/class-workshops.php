<?php
/**
 * De definitie van de workshops class.
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
 * Kleistad Workshops class.
 *
 * @since 6.11.0
 */
class Workshops implements Countable, Iterator {

	/**
	 * De workshops
	 *
	 * @var array $workshops De workshops.
	 */
	private array $workshops = [];

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
		$data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_workshops", ARRAY_A );
		foreach ( $data as $row ) {
			$this->workshops[] = new Workshop( $row['id'], $row );
		}
	}

	/**
	 * Voeg een workshop toe.
	 *
	 * @param Workshop $workshoptoetevoegen Toe te voegen workshop.
	 */
	public function toevoegen( Workshop $workshoptoetevoegen ) {
		$workshoptoetevoegen->save();
		$this->workshops[] = $workshoptoetevoegen;
	}

	/**
	 * Geef het aantal workshops terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->workshops );
	}

	/**
	 * Geef de huidige workshop terug.
	 *
	 * @return Workshop De workshop.
	 */
	public function current(): Workshop {
		return $this->workshops[ $this->current_index ];
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
		return isset( $this->workshops[ $this->current_index ] );
	}

	/**
	 * Controleer of er betalingsverzoeken verzonden moeten worden.
	 *
	 * @since 6.1.0
	 */
	public static function doe_dagelijks() {
		foreach ( new self() as $workshop ) {
			if (
				! $workshop->definitief ||
				$workshop->vervallen ||
				$workshop->betaling_email ||
				strtotime( '+7 days 00:00' ) < $workshop->datum ||
				$workshop->is_betaald()
				) {
				continue;
			}
			$workshop->actie->vraag_betaling();
		}
	}

}
