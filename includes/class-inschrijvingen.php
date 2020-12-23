<?php
/**
 * De definitie van de inschrijvingen class.
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
 * Kleistad inschrijvingen class.
 *
 * @since 6.11.0
 */
class Inschrijvingen implements Countable, Iterator {

	/**
	 * De inschrijvingen
	 *
	 * @var array $inschrijvingen De inschrijvingen.
	 */
	private $inschrijvingen = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param int $cursus_id De cursus id.
	 */
	public function __construct( int $cursus_id = null ) {
		$cursisten = get_users(
			[
				'fields'       => [ 'ID' ],
				'meta_key'     => Inschrijving::META_KEY,
				'meta_compare' => '!==',
				'meta_value'   => '',
			]
		);
		foreach ( $cursisten as $cursist ) {
			$inschrijvingen = get_user_meta( $cursist->ID, Inschrijving::META_KEY, true );
			if ( ! is_null( $cursus_id ) ) {
				if ( ! isset( $inschrijvingen[ $cursus_id ] ) ) {
					continue;
				}
				$this->inschrijvingen[] = new Inschrijving( $cursus_id, $cursist->ID );
				continue;
			}
			foreach ( array_keys( $inschrijvingen ) as $cursus_id ) {
				$this->inschrijvingen[] = new Inschrijving( $cursus_id, $cursist->ID );
			}
		}
	}

	/**
	 * Geef het aantal inschrijvingen terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->inschrijvingen );
	}

	/**
	 * Geef de huidige inschrijving terug.
	 *
	 * @return inschrijving De inschrijving.
	 */
	public function current(): Inschrijving {
		return $this->inschrijvingen[ $this->current_index ];
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
		return isset( $this->inschrijvingen[ $this->current_index ] );
	}
}
