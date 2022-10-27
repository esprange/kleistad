<?php
/**
 * De definitie van de werkplekken class.
 *
 * @link       https://www.kleistad.nl
 * @since      7.8.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Countable;
use Iterator;

/**
 * Kleistad Werkplekken class.
 *
 * @since 7.8.0
 */
class Werkplekken implements Countable, Iterator {

	/**
	 * De werkplekken
	 *
	 * @var array $werkplekken De werkplekken.
	 */
	private array $werkplekken = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param int $vanaf_datum Toon alleen werkplekken vanaf deze datum.
	 * @param int $tot_datum   Toon alleen werkplekken tot aan deze datum.
	 */
	public function __construct( int $vanaf_datum = 0, int $tot_datum = 0 ) {
		global $wpdb;
		$filter = $wpdb->prepare( 'WHERE datum >= %s', date( 'Y-m-d', $vanaf_datum ) );
		if ( $tot_datum ) {
			$filter .= $wpdb->prepare( ' AND datum <= %s', date( 'Y-m-d', $tot_datum ) );
		}
		$data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_werkplekken $filter ORDER BY datum", ARRAY_A ); // phpcs:ignore
		foreach ( $data as $row ) {
			$this->werkplekken[] = new Werkplek( strtotime( $row['datum'] ), $row['gebruik'] );
		}
	}

	/**
	 * Geef het aantal werkplekken terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->werkplekken );
	}

	/**
	 * Geef de huidige werkplek terug.
	 *
	 * @return Werkplek De werkplek.
	 */
	public function current(): Werkplek {
		return $this->werkplekken[ $this->current_index ];
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
		return isset( $this->werkplekken[ $this->current_index ] );
	}

}
