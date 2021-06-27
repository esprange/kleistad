<?php
/**
 * De definitie van de abonnees class.
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
 * Kleistad Abonnees class.
 *
 * @since 6.11.0
 */
class Abonnees implements Countable, Iterator {

	/**
	 * De gebruikers
	 *
	 * @var array $abonnees De gebruikers.
	 */
	protected array $abonnees = [];

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
		$abonnees = get_users(
			[
				'fields'       => [ 'ID' ],
				'meta_key'     => Abonnement::META_KEY,
				'meta_compare' => '!==',
				'meta_value'   => '',
				'orderby'      => 'display_name',
			]
		);
		foreach ( $abonnees as $abonnee ) {
			$this->abonnees[] = new Abonnee( $abonnee->ID );
		}
	}

	/**
	 * Geef de huidige gebruiker terug.
	 *
	 * @return Abonnee De gebruiker.
	 * @codeCoverageIgnore
	 */
	public function current(): Abonnee {
		return $this->abonnees[ $this->current_index ];
	}

	/**
	 * Geef het aantal abonnees terug.
	 *
	 * @return int Het aantal.
	 * @codeCoverageIgnore
	 */
	public function count(): int {
		return count( $this->abonnees );
	}

	/**
	 * Geef de sleutel terug.
	 *
	 * @return int De sleutel.
	 * @codeCoverageIgnore
	 */
	public function key(): int {
		return $this->current_index;
	}

	/**
	 * Ga naar de volgende in de lijst.
	 *
	 * @codeCoverageIgnore
	 */
	public function next() {
		$this->current_index++;
	}

	/**
	 * Ga terug naar het begin.
	 *
	 * @codeCoverageIgnore
	 */
	public function rewind() {
		$this->current_index = 0;
	}

	/**
	 * Bepaal of het element bestaat.
	 *
	 * @return bool Of het bestaat of niet.
	 * @codeCoverageIgnore
	 */
	public function valid(): bool {
		return isset( $this->abonnees[ $this->current_index ] );
	}

}
