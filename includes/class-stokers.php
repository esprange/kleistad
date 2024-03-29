<?php
/**
 * De definitie van de stokers class.
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
 * Kleistad Stokers class.
 *
 * @since 6.11.0
 */
class Stokers implements Countable, Iterator {

	/**
	 * De gebruikers
	 *
	 * @var array $stokers De gebruikers.
	 */
	protected array $stokers = [];

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
		$stokers = get_users(
			[
				'fields'       => [ 'ID' ],
				'meta_key'     => Saldo::META_KEY,
				'meta_compare' => '!==',
				'meta_value'   => '',
				'orderby'      => 'display_name',
			]
		);
		foreach ( $stokers as $stoker ) {
			$this->stokers[] = new Stoker( $stoker->ID );
		}
	}

	/**
	 * Geef de huidige gebruiker terug.
	 *
	 * @return Stoker De gebruiker.
	 */
	public function current(): Stoker {
		return $this->stokers[ $this->current_index ];
	}

	/**
	 * Geef het aantal stokers terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->stokers );
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
		return isset( $this->stokers[ $this->current_index ] );
	}
}
