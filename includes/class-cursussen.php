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

	/**
	 * Actualiseer de cursus vol status van de nieuw of lopende cursussen.
	 *
	 * @return array Per cursus de datum waarop er weer een plek vrijgekomen is.
	 */
	public function actualiseer_vol() : array {
		$laatste_wacht = [];
		$vandaag       = strtotime( 'today' );
		foreach ( $this->cursussen as $cursus ) {
			$laatste_wacht[ $cursus->id ] = 0;
			$transient                    = "kleistad_wacht_{$cursus->id}";
			if ( $vandaag >= $cursus->eind_datum ) {
				/**
				 * De cursus is al voltooid. Hier hoeven we niets meer mee te doen.
				 */
				continue;
			}
			$laatste_wacht[ $cursus->id ] = time();
			if ( $cursus->ruimte() ) {
				/**
				 * Als er nu ruimte is gekomen, pas dan de status aan.
				 */
				if ( $cursus->vol ) {
					delete_transient( $transient );
					$cursus->vol = false;
					$cursus->save();
					continue;
				}
				/**
				 * Als er als eerder ruimte was dan geven we de eerdere datum door, als die bekend is.
				 */
				$datum = get_transient( $transient );
				if ( false !== $datum ) {
					$laatste_wacht[ $cursus->id ] = $datum;
					continue;
				}
			}
			set_transient( $transient, $laatste_wacht[ $cursus->id ], $cursus->eind_datum - $vandaag );
		}
		return $laatste_wacht;
	}
}
