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
use DateTimeInterface;
use Google;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\EventOrganizer;
use Exception;

/**
 * Kleistad Afspraak class.
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
class Afspraak {

	/**
	 * Het Google event object.
	 *
	 * @var Event $event Het event.
	 */
	protected Event $event;

	/**
	 * De private properties van de afspraak.
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
	 * @param string $afspraak_id afspraak id welke geladen moet worden.
	 *
	 * @throws Kleistad_Exception Op hoger nivo af te handelen.
	 * @since 5.0.0
	 *
	 * Het onderstaande omdat PHPStorm hier in de fout gaat
	 * @noinspection PhpRedundantCatchClauseInspection
	 */
	public function __construct( string $afspraak_id ) {
		$organizer = new EventOrganizer();
		$organizer->setDisplayName( wp_get_current_user()->display_name );
		$organizer->setEmail( wp_get_current_user()->user_email );
		$this->event = new Event(
			[
				'Id'        => $afspraak_id,
				'location'  => get_option( 'kleistad_adres', 'Kleistad, Neonweg 12, 3812 RH Amersfoort' ),
				'organizer' => $organizer,
				'status'    => 'tentative',
			]
		);
		if ( ! defined( 'KLEISTAD_TEST' ) ) {
			$this->googleconnect = new Googleconnect();
			$this->kalender_id   = setup()['google_kalender_id'];
			try {
				$bestaand_event = $this->googleconnect->calendar_service()->events->get( $this->kalender_id, $afspraak_id );
				$this->event    = $bestaand_event;
			} catch ( Google\Service\Exception $e ) {
				; // Geen actie nodig.
			}
		}
	}

	/**
	 * Wijzig de afspraak naar een wekelijks herhalende afspraak
	 *
	 * @param DateTime $eind Einddatum.
	 */
	public function herhalen( DateTime $eind ) : void {
		$until = $eind->format( 'Ymd\THis\Z' );
		$this->event->setRecurrence( [ "RRULE:FREQ=WEEKLY;UNTIL=$until" ] );
	}

	/**
	 * Wijzig de afspraak naar een herhalend afspraak
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
	 * @param string $attribuut Attribuut naam.
	 * @throws Kleistad_Exception  Als er een fout optreedt.
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
		}
		return null;
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
		}
	}

	/**
	 * Bewaar de afspraak in de kalender.
	 *
	 * @throws Kleistad_Exception Op hoger nivo af te handelen.
	 * @since 5.0.0
	 */
	public function save() : void {
		if ( ! defined( 'KLEISTAD_TEST' ) ) {
			if ( is_null( $this->event->getCreated() ) ) {
				$this->event = $this->googleconnect->calendar_service()->events->insert( $this->kalender_id, $this->event );
				return;
			}
			$this->event = $this->googleconnect->calendar_service()->events->update( $this->kalender_id, $this->event->getId(), $this->event );
		}
	}

	/**
	 * Delete de afspraak.
	 *
	 * @throws Kleistad_Exception Op hoger nivo af te handelen.
	 */
	public function delete() : void {
		if ( ! defined( 'KLEISTAD_TEST' ) ) {
			$this->googleconnect->calendar_service()->events->delete( $this->kalender_id, $this->event->getId() );
		}
	}

	/**
	 * Converteer DateTime object naar Google datetime format, zoals '2015-05-28T09:00:00-07:00'.
	 *
	 * @param DateTime $datetime Het datetime object.
	 * @return EventDateTime De tijd in Google datetime format.
	 */
	private function to_google_dt( DateTime $datetime ) : EventDateTime {
		$google_datetime = new EventDateTime();
		$google_datetime->setDateTime( $datetime->format( DateTimeInterface::RFC3339 ) );
		$google_datetime->setTimeZone( $datetime->getTimeZone()->getName() );
		return $google_datetime;
	}

	/**
	 * Converteer Google datetime object, zoals '2015-05-28T09:00:00-07:00' naar \DateTime object.
	 *
	 * @param EventDateTime $google_datetime Het datetime object.
	 * @return DateTime Het php DateTime object.
	 * @throws Kleistad_Exception Een fout is opgetreden.
	 */
	private function from_google_dt( EventDateTime $google_datetime ) : DateTime {
		try {
			if ( ! empty( $google_datetime->getTimeZone() ) ) {
				return new DateTime( $google_datetime->getDateTime(), new DateTimeZone( $google_datetime->getTimeZone() ) );
			}
			return new DateTime( $google_datetime->getDateTime() );
		} catch ( Exception $e ) {
			fout( __CLASS__, $e->getMessage() );
			throw new Kleistad_Exception( 'interne fout' );
		}
	}

}
