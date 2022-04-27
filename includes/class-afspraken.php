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

/**
 * Kleistad afspraken class.
 *
 * @since 6.11.0
 */
class Afspraken {

	/**
	 * De query
	 *
	 * @param array $query De query specificatie.
	 * @return array
	 */
	public function query( array $query ) : array {
		$afspraken = [];
		if ( defined( 'KLEISTAD_TEST' ) ) {
			return $afspraken;
		}
		try {
			$calendar      = ( new Googleconnect() )->calendar_service();
			$calendar_id   = setup()['google_kalender_id'];
			$default_query = [
				'calendarId'   => $calendar_id,
				'orderBy'      => 'startTime',
				'singleEvents' => true,
				'timeMin'      => date( 'c', mktime( 0, 0, 0, 1, 1, 2018 ) ),
			];
			$results       = $calendar->events->listEvents( $calendar_id, array_merge( $default_query, $query ) );
			foreach ( $results->getItems() as $event ) {
				if ( ! empty( $event->start->dateTime ) ) { // Skip events die de hele dag duren, zoals verjaardagen en vakanties.
					$afspraken[] = new Afspraak( $event->getId(), $event );
				}
			}
			return $afspraken;
		} catch ( Kleistad_Exception ) {
			return $afspraken; // Geen afspraken omdat kalender toegang niet mogelijk is.
		}
	}

}
