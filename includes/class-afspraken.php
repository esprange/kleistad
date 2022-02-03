<?php
/**
 * De definitie van de afspraken class.
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
 * Kleistad afspraken class.
 *
 * @since 6.11.0
 */
class Afspraken implements Countable, Iterator {

	/**
	 * De afspraken
	 *
	 * @var array $afspraken De afspraken.
	 */
	private array $afspraken = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param array $query De query specificatie.
	 */
	public function __construct( array $query ) {
		if ( defined( 'KLEISTAD_TEST' ) ) {
			return;
		}
		$googleconnect = new Googleconnect();
		$kalender_id   = setup()['google_kalender_id'];
		$default_query = [
			'calendarId'   => $kalender_id,
			'orderBy'      => 'startTime',
			'singleEvents' => true,
			'timeMin'      => date( 'c', mktime( 0, 0, 0, 1, 1, 2018 ) ),
		];
		try {
			$results = $googleconnect->calendar_service()->events->listEvents( $kalender_id, array_merge( $default_query, $query ) );
			$events  = $results->getItems();
			foreach ( $events as $event ) {
				if ( ! empty( $event->start->dateTime ) ) { // Skip events die de hele dag duren, zoals verjaardagen en vakanties.
					$this->afspraken[] = new Afspraak( $event->getId() );
				}
			}
		} catch ( Kleistad_Exception ) {
			return; // Geen afspraken omdat kalender toegang niet mogelijk is.
		}
	}

	/**
	 * Geef het aantal afspraken terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->afspraken );
	}

	/**
	 * Geef de huidige afspraak terug.
	 *
	 * @return Afspraak De afspraak.
	 */
	public function current(): Afspraak {
		return $this->afspraken[ $this->current_index ];
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
		return isset( $this->afspraken[ $this->current_index ] );
	}
}
