<?php
/**
 * De definitie van de abonnementen class.
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
 * Kleistad Abonnementen class.
 *
 * @since 6.11.0
 */
class Abonnementen implements Countable, Iterator {

	/**
	 * De abonnementen
	 *
	 * @var array $abonnementen De abonnementen.
	 */
	private $abonnementen = [];

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
		$abonnees = get_users(
			[
				'fields'       => [ 'ID' ],
				'meta_key'     => Abonnement::META_KEY,
				'meta_compare' => '!==',
				'meta_value'   => '',
			]
		);
		foreach ( $abonnees as $abonnee ) {
			$this->abonnementen[] = new Abonnement( $abonnee->ID );
		}
	}

	/**
	 * Geef het aantal abonnementen terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->abonnementen );
	}

	/**
	 * Geef de huidige abonnement terug.
	 *
	 * @return Abonnement De abonnement.
	 */
	public function current(): Abonnement {
		return $this->abonnementen[ $this->current_index ];
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
		return isset( $this->abonnementen[ $this->current_index ] );
	}
}
