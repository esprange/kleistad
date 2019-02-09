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
class Kleistad_Event {

	const ACCESS_TOKEN  = 'kleistad_google_access_token';
	const REFRESH_TOKEN = 'kleistad_google_refresh_token';
	const REDIRECT_URI  = 'kleistad_google_redirect_uri';
	const META_KEY      = 'kleistad_event';

	/**
	 * Het Google event object.
	 *
	 * @var Google_Service_Calendar_Event $event Het event.
	 */
	protected $event;

	/**
	 * De private properties van het event.
	 *
	 * @var array $properties De properties.
	 */
	protected $properties = [];

	/**
	 * De Google kalender service.
	 *
	 * @var Google_Service $service De google service.
	 */
	private static $service = null;

	/**
	 * Het Google kalender id.
	 *
	 * @var string $kalender_id De google kalender id.
	 */
	private static $kalender_id;

	/**
	 * Converteer DateTime object naar Google datetime format, zoals '2015-05-28T09:00:00-07:00'.
	 *
	 * @param \DateTime $datetime Het datetime object.
	 * @return Google_Service_Calendar_EventDateTime De tijd in Google datetime format.
	 */
	private function to_google_dt( \DateTime $datetime ) {
		$google_datetime = new Google_Service_Calendar_EventDateTime();
		$google_datetime->setDateTime( $datetime->format( DateTime::RFC3339 ) );
		$google_datetime->setTimeZone( $datetime->getTimeZone()->getName() );
		return $google_datetime;
	}

	/**
	 * Converteer Google datetime object, zoals '2015-05-28T09:00:00-07:00' naar DateTime object.
	 *
	 * @param Google_Service_Calendar_EventDateTime $google_datetime Het datetime object.
	 * @return DateTime Het php DateTime object.
	 */
	private function from_google_dt( $google_datetime ) {
		if ( ! empty( $google_datetime->getTimeZone() ) ) {
			$datetime = new DateTime( $google_datetime->getDateTime(), new DateTimeZone( $google_datetime->getTimeZone() ) );
		} else {
			$datetime = new DateTime( $google_datetime->getDateTime() );
		}
		return $datetime;
	}

	/**
	 * Constructor
	 *
	 * @since 5.0.0
	 *
	 * @param string|Google_Service_Calendar_Event $event event welke geladen moet worden.
	 * @throws Exception Er is geen connectie.
	 * @phan-suppress PhanUnusedVariableCaughtException, PhanUndeclaredProperty
	 */
	public function __construct( $event ) {
		if ( ! self::maak_service() ) {
			throw new Exception( 'Google Kalender Service disconnect' );
		};

		if ( is_string( $event ) ) {
			try {
				$this->event        = self::$service->events->get( self::$kalender_id, $event );
				$extendedproperties = $this->event->getExtendedProperties();
				$this->properties   = $extendedproperties->getPrivate();
			} catch ( Google_Service_exception $e ) {
				$organizer = new Google_Service_Calendar_EventOrganizer();
				$organizer->setDisplayName( wp_get_current_user()->display_name );
				$organizer->setEmail( wp_get_current_user()->user_email );
				$this->event             = new Google_Service_Calendar_Event(
					[
						'Id'        => $event,
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
		} else {
			$this->event        = $event;
			$extendedproperties = $this->event->getExtendedProperties();
			$this->properties   = ! is_null( $extendedproperties ) ? $extendedproperties->getPrivate() : [];
		}
	}

	/**
	 * Wijzig het event naar een herhalend event
	 *
	 * @param DateTime $eind      Einddatum in unix timestamp.
	 * @param bool     $wekelijks Wekelijks herhalen indien waar.
	 */
	public function herhalen( $eind, $wekelijks = true ) {
		$freq  = $wekelijks ? 'WEEKLY' : 'DAILY';
		$until = $eind->format( 'Ymd\THis\Z' );
		$this->event->setRecurrence( [ "RRULE:FREQ=$freq;UNTIL=$until" ] );
	}

	/**
	 * Maak een Google API client aan.
	 *
	 * @return Google_Client|bool $client of false.
	 * @suppress PhanTypeArraySuspicious, PhanTypeMismatchArgument, PhanTypeVoidAssignment
	 */
	private static function maak_client() {
		$redirect_uri = get_option( self::REDIRECT_URI );
		if ( false === $redirect_uri ) {
			return false;
		}
		$refresh_token = get_option( self::REFRESH_TOKEN );
		if ( false === $refresh_token ) {
			return false;
		}
		$options           = get_option( 'kleistad-opties' );
		self::$kalender_id = $options['google_kalender_id'];
		$client            = new Google_Client();
		$client->setApplicationName( 'Kleistad_Calendar' );
		$client->setAccessType( 'offline' );
		$client->setClientId( $options['google_client_id'] );
		$client->setClientSecret( $options['google_sleutel'] );
		$client->setIncludeGrantedScopes( true );
		$client->addScope( 'email' );
		$client->addScope( Google_Service_Calendar::CALENDAR_EVENTS );
		$client->setRedirectUri( $redirect_uri );
		$client->refreshToken( $refresh_token );
		$token = $client->getAccessToken();
		if ( ! empty( $token ) ) {
			update_option( self::ACCESS_TOKEN, $token );
			$client->setAccessToken( $token );
		}
		return $client;
	}

	/**
	 * Vraag koppeling met google service aan.
	 *
	 * @since 5.0.0
	 * @param  string $redirect_url De url welke gebruikt moet worden na authenticatie.
	 */
	public static function vraag_google_service_aan( $redirect_url ) {
		update_option( self::REDIRECT_URI, $redirect_url );
		$client = self::maak_client();
		if ( false === $client ) {
			return;
		}
		wp_redirect( $client->createAuthUrl() ); // phpcs:ignore
		die();
	}

	/**
	 * Koppel met google service.
	 *
	 * @since 5.0.0
	 * @return \WP_ERROR|bool Succes of error(s).
	 */
	public static function koppel_google_service() {
		$error = new WP_Error();

		$authorization_code = filter_input( INPUT_GET, 'code' );
		if ( ! empty( $authorization_code ) ) {
			delete_option( self::REFRESH_TOKEN );
			delete_option( self::ACCESS_TOKEN );

			$client = self::maak_client();
			if ( false === $client ) {
				$error->add( 'google', 'Client service is niet aangemaakt' );
			}
			$token = $client->fetchAccessTokenWithAuthCode( $authorization_code );
			if ( isset( $token['error'] ) && ! empty( $token['error'] ) ) {
				$error->add( 'google', 'Authenticatie fout, Google meldt ' . $token['error_description'] . ' : ' . $token['error'] );
				return $error;
			} else {
				$client->setAccessToken( $token );
				if ( isset( $token['refresh_token'] ) ) {
					update_option( self::REFRESH_TOKEN, $token['refresh_token'] );
				} else {
					$error->add( 'google', 'Google heeft niet het juiste token gestuurd, trek de toestemming voor de app in via: https://myaccount.google.com/u/0/permissions' );
					return $error;
				}
				if ( isset( $token['access_token'] ) ) {
					update_option( self::ACCESS_TOKEN, $token );
				} else {
					delete_option( self::ACCESS_TOKEN );
					$error->add( 'google', 'Google heeft niet het juiste token gestuurd, onduidelijk wat er aan de hand is....' );
					return $error;
				}
			}
		}
		return true;
	}

	/**
	 * Maak de Google Calendar service aan.
	 *
	 * @since 5.0.0
	 * @return bool succes of falen.
	 */
	public static function maak_service() {
		if ( ! is_null( self::$service ) ) {
			return true;
		}
		$client = self::maak_client();
		if ( false === $client ) {
			error_log( '!!! Google maak client failure' ); //phpcs:ignore
			return false;
		}
		if ( $client->isAccessTokenExpired() ) {
			if ( $client->getRefreshToken() ) {
				$client->fetchAccessTokenWithRefreshToken( $client->getRefreshToken() );
				update_option( self::ACCESS_TOKEN, $client->getAccessToken() );
			} else {
				error_log( '!!! Google refresh token failure' ); //phpcs:ignore
				delete_option( self::ACCESS_TOKEN );
				return false;
			}
		}
		self::$service = new Google_Service_Calendar( $client );
		return true;
	}

	/**
	 * Bepaal of er connectie is met Google.
	 *
	 * @since 5.0.0
	 * @return bool succes of falen
	 */
	public static function is_authorized() {
		if ( false !== get_option( self::ACCESS_TOKEN ) ) {
			return self::maak_service();
		}
		return false;
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @since 5.0.0
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( $attribuut ) {
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
				} else {
					return [];
				}
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
	public function __set( $attribuut, $waarde ) {
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
	 *
	 * param string Het event id.
	 */
	public function save() {
		$extendedproperties = $this->event->getExtendedProperties();
		$extendedproperties->setPrivate( $this->properties );
		$this->event->setExtendedProperties( $extendedproperties );
		if ( is_null( $this->event->getCreated() ) ) {
			$this->event = self::$service->events->insert( self::$kalender_id, $this->event );
		} else {
			$this->event = self::$service->events->update( self::$kalender_id, $this->event->getId(), $this->event );
		}
	}

	/**
	 * Return alle events.
	 *
	 * @param array $query De query.
	 * @return array events.
	 */
	public static function query( $query = [] ) {
		if ( ! self::maak_service() ) {
			return [];
		};
		$default_query = [
			'calendarId'   => self::$kalender_id,
			'orderBy'      => 'startTime',
			'singleEvents' => true,
			'timeMin'      => date( 'c', mktime( 0, 0, 0, 1, 1, 2018 ) ),
			// phpcs:ignore 'privateExtendedProperty' => 'key=' . self::META_KEY,
		];
		$results = self::$service->events->listEvents( self::$kalender_id, array_merge( $default_query, $query ) );
		$events  = $results->getItems();
		$arr     = [];
		foreach ( $events as $event ) {
			if ( ! empty( $event->start->dateTime ) ) { // Skip events die de hele dag duren, zoals verjaardagen en vakanties.
				$arr[ $event->getId() ] = new Kleistad_Event( $event );
			}
		}
		return $arr;
	}
}
