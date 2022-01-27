<?php
/**
 * De definitie van de dagdelengebruikers class.
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
 * Kleistad Dagdelengebruikers class.
 *
 * @since 6.11.0
 */
class Dagdelengebruikers implements Countable, Iterator {

	/**
	 * De gebruikers
	 *
	 * @var array $dagdelengebruikers De gebruikers.
	 */
	protected array $dagdelengebruikers = [];

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
		$dagdelengebruikers = get_users(
			[
				'fields'       => [ 'ID' ],
				'meta_key'     => Dagdelenkaart::META_KEY,
				'meta_compare' => '!==',
				'meta_value'   => '',
				'orderby'      => 'display_name',
			]
		);
		foreach ( $dagdelengebruikers as $dagdelengebruiker ) {
			$this->dagdelengebruikers[] = new Dagdelengebruiker( $dagdelengebruiker->ID );
		}
	}

	/**
	 * Geef de huidige gebruiker terug.
	 *
	 * @return Dagdelengebruiker De gebruiker.
	 */
	public function current(): Dagdelengebruiker {
		return $this->dagdelengebruikers[ $this->current_index ];
	}

	/**
	 * Geef het aantal dagdelengebruikers terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->dagdelengebruikers );
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
		return isset( $this->dagdelengebruikers[ $this->current_index ] );
	}

}
