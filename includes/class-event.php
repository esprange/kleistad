<?php
/**
 * Definieer de event class
 *
 * @link       https://www.kleistad.nl
 * @since      5.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use DateTime;
use DateTimeZone;
use Google;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Calendar_EventOrganizer;
use Google_Service_Calendar_EventExtendedProperties;

/**
 * Kleistad Event class.
 *
 * @since 5.0.0
 *
 * @property bool     vervallen
 * @property bool     definitief
 * @property DateTime start
 * @property DateTime eind
 * @property string   titel
 * @property string   id
 * @property array    properties
 */
class Event {

	const META_KEY = 'kleistad_event';

	/**
	 * Het Google event object.
	 *
	 * @var Google_Service_Calendar_Event $event Het event.
	 */
	protected Google_Service_Calendar_Event $event;

	/**
	 * De private properties van het event.
	 *
	 * @var array $properties De properties.
	 */
	protected array $properties = [];

	/**
	 * De connect naar Google
	 *
	 * @var Googleconnect $googleconnect Het google connectie object.
	 */
	protected Googleconnect $googleconnect;

	/**
	 * Het Google kalender id.
	 *
	 * @var string $kalender_id De google kalender id.
	 */
	protected string $kalender_id = '';

	/**
	 * Constructor
	 *
	 * @since 5.0.0
	 *
	 * @param string $event_id event id welke geladen moet worden.
	 */
	public function __construct( string $event_id ) {
		$this->googleconnect = new Googleconnect();
		$this->kalender_id   = setup()['google_kalender_id'];
		try {
			$this->event        = $this->googleconnect->calendar_service()->events->get( $this->kalender_id, $event_id );
			$extendedproperties = $this->event->getExtendedProperties();
			$this->properties   = ! is_null( $extendedproperties ) ? $extendedproperties->getPrivate() : [];
		} catch ( Google\Service\Exception $e ) {
			$organizer = new Google_Service_Calendar_EventOrganizer();
			$organizer->setDisplayName( wp_get_current_user()->display_name );
			$organizer->setEmail( wp_get_current_user()->user_email );
			$this->event             = new Google_Service_Calendar_Event(
				[
					'Id'        => $event_id,
					'location'  => get_option( 'kleistad_adres', 'Kleistad, Neonweg 12, 3812 RH Amersfoort' ),
					'organizer' => $organizer,
					'status'    => 'tentative',
				]
			);
			$this->properties['key'] = self::META_KEY;
			$extendedproperties      = new Google_Service_Calendar_EventExtendedProperties();
			$extendedproperties->setPrivate( $this->properties );
			$this->event->setExtendedProperties( $extendedproperties );
		}
	}

	/**
	 * Wijzig het event naar een wekelijks herhalend event
	 *
	 * @param DateTime $eind Einddatum.
	 */
	public function herhalen( DateTime $eind ) : void {
		$until = $eind->format( 'Ymd\THis\Z' );
		$this->event->setRecurrence( [ "RRULE:FREQ=WEEKLY;UNTIL=$until" ] );
	}

	/**
	 * Wijzig het event naar een herhalend event
	 *
	 * @param array $datums Datums als DateTime object.
	 */
	public function patroon( array $datums ) : void {
		unset( $datums[0] );
		$datumteksten = array_map(
			function( $datum ) {
				return $datum->format( 'Ymd\THis' );
			},
			$datums
		);
		$this->event->setRecurrence( [ 'RRULE:FREQ=DAILY;INTERVAL=1;COUNT=1', 'RDATE;VALUE=DATE-TIME:' . implode( ',', $datumteksten ) ] );
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @since 5.0.0
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( string $attribuut ) {
		switch ( $attribuut ) {
			case 'vervallen':
				return 'cancelled' === $this->event->getStatus();
			case 'definitief':
				return 'confirmed' === $this->event->getStatus();
			case 'start':
				return $this->from_google_dt( $this->event->getStart() );
			case 'eind':
				return $this->from_google_dt( $this->event->getEnd() );
			case 'titel':
				return $this->event->getSummary();
			case 'id':
				return $this->event->getId();
			case 'properties':
				if ( isset( $this->properties['data'] ) ) {
					return json_decode( $this->properties['data'], true );
				}
				return [];
			default:
				return null;
		}
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @since 5.0.0
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( string $attribuut, $waarde ) {
		switch ( $attribuut ) {
			case 'titel':
				$this->event->setSummary( $waarde );
				break;
			case 'start':
				$this->event->setStart( $this->to_google_dt( $waarde ) );
				break;
			case 'eind':
				$this->event->setEnd( $this->to_google_dt( $waarde ) );
				break;
			case 'definitief':
				if ( $waarde ) {
					$this->event->setStatus( 'confirmed' );
				}
				break;
			case 'vervallen':
				if ( $waarde ) {
					$this->event->setStatus( 'cancelled' );
				}
				break;
			case 'properties':
				$this->properties['data'] = wp_json_encode( $waarde );
				break;
		}
	}

	/**
	 * Bewaar het event in de kalender.
	 *
	 * @since 5.0.0
	 */
	public function save() : void {
		$extendedproperties = $this->event->getExtendedProperties();
		$extendedproperties->setPrivate( $this->properties );
		$this->event->setExtendedProperties( $extendedproperties );
		if ( is_null( $this->event->getCreated() ) ) {
			$this->event = $this->googleconnect->calendar_service()->events->insert( $this->kalender_id, $this->event );
			return;
		}
		$this->event = $this->googleconnect->calendar_service()->events->update( $this->kalender_id, $this->event->getId(), $this->event );
	}

	/**
	 * Delete het event.
	 */
	public function delete() : void {
		$this->googleconnect->calendar_service()->events->delete( $this->kalender_id, $this->event->getId() );
	}

	/**
	 * Converteer DateTime object naar Google datetime format, zoals '2015-05-28T09:00:00-07:00'.
	 *
	 * @param DateTime $datetime Het datetime object.
	 * @return Google_Service_Calendar_EventDateTime De tijd in Google datetime format.
	 */
	private function to_google_dt( DateTime $datetime ) : Google_Service_Calendar_EventDateTime {
		$google_datetime = new Google_Service_Calendar_EventDateTime();
		$google_datetime->setDateTime( $datetime->format( DateTimeÍnterface::RFC3339 ) );
		$google_datetime->setTimeZone( $datetime->getTimeZone()->getName() );
		return $google_datetime;
	}

	/**
	 * Converteer Google datetime object, zoals '2015-05-28T09:00:00-07:00' naar \DateTime object.
	 *
	 * @param Google_Service_Calendar_EventDateTime $google_datetime Het datetime object.
	 * @return DateTime Het php DateTime object.
	 */
	private function from_google_dt( Google_Service_Calendar_EventDateTime $google_datetime ) : DateTime {
		if ( ! empty( $google_datetime->getTimeZone() ) ) {
			return new DateTime( $google_datetime->getDateTime(), new DateTimeZone( $google_datetime->getTimeZone() ) );
		}
		return new DateTime( $google_datetime->getDateTime() );
	}

}
