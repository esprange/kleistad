<?php
/**
 * De definitie van de events class.
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
 * Kleistad events class.
 *
 * @since 6.11.0
 */
class Events implements Countable, Iterator {

	/**
	 * De events
	 *
	 * @var array $events De events.
	 */
	private array $events = [];

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
		$googleconnect = new Googleconnect();
		$kalender_id   = setup()['google_kalender_id'];
		$default_query = [
			'calendarId'   => $kalender_id,
			'orderBy'      => 'startTime',
			'singleEvents' => true,
			'timeMin'      => date( 'c', mktime( 0, 0, 0, 1, 1, 2018 ) ),
		];
		$results       = $googleconnect->calendar_service()->events->listEvents( $kalender_id, array_merge( $default_query, $query ) );
		$events        = $results->getItems();
		foreach ( $events as $event ) {
			if ( ! empty( $event->start->dateTime ) ) { // Skip events die de hele dag duren, zoals verjaardagen en vakanties.
				$this->events[] = new Event( $event->getId() );
			}
		}
	}

	/**
	 * Geef het aantal events terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->events );
	}

	/**
	 * Geef de huidige event terug.
	 *
	 * @return Event Het event.
	 */
	public function current(): Event {
		return $this->events[ $this->current_index ];
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
		return isset( $this->events[ $this->current_index ] );
	}
}
