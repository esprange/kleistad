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
	 * De gebruikers
	 *
	 * @var array $cursisten De gebruikers.
	 */
	protected array $cursisten = [];

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
		global $wpdb;
		$cursist_ids = $wpdb->get_col( "SELECT DISTINCT(cursist_id) FROM {$wpdb->prefix}kleistad_inschrijvingen" );
		$cursisten   = get_users(
			[
				'fields'  => [ 'ID' ],
				'include' => $cursist_ids,
				'orderby' => 'display_name',
			]
		);
		foreach ( $cursisten as $cursist ) {
			$this->cursisten[] = new Cursist( $cursist->ID );
		}
	}

	/**
	 * Geef de huidige gebruiker terug.
	 *
	 * @return Cursist De gebruiker.
	 */
	public function current(): Cursist {
		return $this->cursisten[ $this->current_index ];
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
		return isset( $this->cursisten[ $this->current_index ] );
	}

	/**
	 * Dagelijkse job, check of de cursist actief is.
	 * Als er nog een saldo openstaat dat kan worden teruggeboekt blijft de rol sowieso open staan.
	 */
	public static function doe_dagelijks() {
		foreach ( new self() as $cursist ) {
			if ( 1.0 < abs( ( new Saldo( $cursist->ID ) )->bedrag ) || $cursist->is_actief() ) {
				continue;
			}
			$cursist->remove_role( CURSIST );
		}
	}

}
