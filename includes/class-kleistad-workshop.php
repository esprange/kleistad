<?php
/**
 * Definieer de workshop class
 *
 * @link       https://www.kleistad.nl
 * @since      5.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Kleistad workshop class.
 *
 * @since 5.0.0
 *
 * @property int    id
 * @property string naam
 * @property int    datum
 * @property int    start_tijd
 * @property int    eind_tijd
 * @property string docent
 * @property array  technieken
 * @property string organisatie
 * @property string contact
 * @property string email
 * @property string telnr
 * @property string programma
 * @property bool   vervallen
 * @property float  kosten
 * @property int    aantal
 * @property bool   betaald
 * @property bool   definitief
 * @property string event_id
 * @property int    aanvraag_id
 * @property string code
 */
class Kleistad_Workshop extends Kleistad_Entity {

	const META_KEY   = 'kleistad_workshop';
	const EMAIL_TIJD = 9; // 9:00 uur.

	/**
	 * Constructor
	 *
	 * @since 5.0.0
	 *
	 * @global object $wpdb WordPress database.
	 * @param int $workshop_id (optional) workshop welke geladen moet worden.
	 */
	public function __construct( $workshop_id = null ) {
		global $wpdb;
		$options       = Kleistad::get_options();
		$this->emailer = new Kleistad_Email();
		if ( is_null( $workshop_id ) ) {
			$this->data = [
				'id'          => null,
				'naam'        => '',
				'datum'       => date( 'Y-m-d' ),
				'start_tijd'  => '10:00',
				'eind_tijd'   => '12:00',
				'docent'      => '',
				'technieken'  => wp_json_encode( [] ),
				'organisatie' => '',
				'contact'     => '',
				'email'       => '',
				'telefoon'    => '',
				'programma'   => '',
				'vervallen'   => 0,
				'kosten'      => $options['workshopprijs'],
				'aantal'      => 6,
				'betaald'     => 0,
				'definitief'  => 0,
				'aanvraag_id' => 0,
			];
		} else {
			$this->data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_workshops WHERE id = %d", $workshop_id ), ARRAY_A );
		}
	}

	/**
	 * Geef de workshop status in tekstvorm terug.
	 */
	public function status() {
		return $this->vervallen ? 'vervallen' : ( ( $this->definitief ? 'definitief ' : 'concept' ) . ( $this->betaald ? 'betaald' : '' ) );
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
			case 'technieken':
				return json_decode( $this->data['technieken'], true );
			case 'datum':
			case 'start_tijd':
			case 'eind_tijd':
				return strtotime( $this->data[ $attribuut ] );
			case 'vervallen':
			case 'betaald':
			case 'definitief':
				return boolval( $this->data[ $attribuut ] );
			case 'code':
				return "W{$this->data['id']}";
			case 'telnr':
				return $this->data['telefoon'];
			case 'event_id':
				return sprintf( 'kleistadevent%06d', $this->data['id'] );
			default:
				return is_string( $this->data[ $attribuut ] ) ? htmlspecialchars_decode( $this->data[ $attribuut ] ) : $this->data[ $attribuut ];
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
			case 'technieken':
				$this->data[ $attribuut ] = wp_json_encode( $waarde );
				break;
			case 'datum':
			case 'datum_betalen':
				$this->data[ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			case 'start_tijd':
			case 'eind_tijd':
				$this->data[ $attribuut ] = date( 'H:i', $waarde );
				break;
			case 'vervallen':
			case 'betaald':
			case 'definitief':
				$this->data[ $attribuut ] = $waarde ? 1 : 0;
				break;
			case 'telnr':
				$this->data['telefoon'] = $waarde;
				break;
			default:
				$this->data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Bewaar de workshop in de database.
	 *
	 * @since 5.0.0
	 *
	 * @global object $wpdb     WordPress database.
	 * @return int Het workshop id.
	 */
	public function save() {
		global $wpdb;
		$wpdb->replace( "{$wpdb->prefix}kleistad_workshops", $this->data );
		$this->id = $wpdb->insert_id;
		$timezone = new DateTimeZone( get_option( 'timezone_string' ) ?: 'Europe/Amsterdam' );
		Kleistad_WorkshopAanvraag::gepland( $this->aanvraag_id, $this->id );

		try {
			$event             = new Kleistad_Event( $this->event_id );
			$event->properties = [
				'docent'     => $this->docent,
				'technieken' => $this->technieken,
				'code'       => $this->code,
				'id'         => $this->id,
				'class'      => __CLASS__,
			];
			$event->titel      = $this->naam;
			$event->definitief = $this->definitief;
			$event->vervallen  = $this->vervallen;
			$event->start      = new DateTime( $this->data['datum'] . ' ' . $this->data['start_tijd'], $timezone );
			$event->eind       = new DateTime( $this->data['datum'] . ' ' . $this->data['eind_tijd'], $timezone );
			$event->save();
		} catch ( Exception $e ) {
			error_log ( $e->getMessage() ); // phpcs:ignore
		}

		return $this->id;
	}

	/**
	 * Verwijder de workshop.
	 *
	 * @return bool True als de workshop verwijderd kan worden.
	 */
	public function verwijder() {
		global $wpdb;
		if ( $this->definitief || $this->betaald ) {
			return false; // Er is al betaald of de workshop is definitief bevestigd.
		}
		if ( $wpdb->delete( "{$wpdb->prefix}kleistad_workshops", [ 'id' => $this->id ] ) ) {
			try {
				$event = new Kleistad_Event( $this->event_id );
				$event->delete();
			} catch ( Exception $e ) {
				unset( $e ); // phpcs:ignore
			}
		} else {
			return false;
		};
		Kleistad_WorkshopAanvraag::gepland( $this->aanvraag_id, 0 );
		return true;
	}

	/**
	 * Bevestig de workshop.
	 *
	 * @since 5.0.0
	 */
	public function bevestig() {
		$herbevestiging   = $this->definitief;
		$this->definitief = true;
		$this->save();
		if ( ! $herbevestiging ) {
			wp_schedule_single_event(
				mktime( self::EMAIL_TIJD, 0, 0, intval( date( 'n', $this->datum ) ), intval( date( 'j', $this->datum ) ) - 7, intval( date( 'Y', $this->datum ) ) ),
				self::META_KEY,
				[
					$this->id,
					'betaling',
					$this->datum,
				]
			);
			$this->email( 'bevestiging' );
		} else {
			$this->email( 'correctie bevestiging' );
		}
	}

	/**
	 * Zeg de gemaakte afspraak voor de workshop af.
	 *
	 * @since 5.0.0
	 */
	public function afzeggen() {
		if ( ! $this->vervallen ) {
			$this->vervallen = true;
			if ( $this->definitief ) {
				wp_unschedule_event(
					mktime( self::EMAIL_TIJD, 0, 0, intval( date( 'n', $this->datum ) ), intval( date( 'j', $this->datum ) ) - 7, intval( date( 'Y', $this->datum ) ) ),
					self::META_KEY,
					[
						$this->id,
						'betaling',
						$this->datum,
					]
				);
				$this->email( 'afzegging' );
			}
			$this->save();
			try {
				$event = new Kleistad_Event( $this->event_id );
				$event->delete();
			} catch ( Exception $e ) {
				unset( $e ); // phpcs:ignore
			}
		}
	}

	/**
	 * Maak een controle string aan.
	 *
	 * @since        5.0.0
	 *
	 * @return string Hash string.
	 */
	public function controle() {
		return hash( 'sha256', "KlEiStAdW{$this->id}cOnTrOlE" );
	}

	/**
	 * Verzenden van de bevestiging of betalings email.
	 *
	 * @since      5.0.0
	 *
	 * @param string $type bevestiging of betaling.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type ) {
		$email_parameters = [
			'to'         => "{$this->contact} <{$this->email}>",
			'parameters' => [
				'contact'             => $this->contact,
				'naam'                => ( 'workshop' === $this->naam ) ? 'de workshop' : ( 'kinderfeest' === $this->naam ? 'het kinderfeest' : $this->naam ),
				'organisatie'         => $this->organisatie,
				'aantal'              => $this->aantal,
				'workshop_code'       => $this->code,
				'workshop_datum'      => strftime( '%A %d-%m-%y', $this->datum ),
				'workshop_start_tijd' => strftime( '%H:%M', $this->start_tijd ),
				'workshop_eind_tijd'  => strftime( '%H:%M', $this->eind_tijd ),
				'workshop_docent'     => $this->docent,
				'workshop_technieken' => implode( ', ', $this->technieken ),
				'workshop_programma'  => $this->programma,
				'workshop_kosten'     => number_format_i18n( $this->kosten, 2 ),
				'workshop_link'       => '<a href="' . home_url( '/kleistad_workshop_betaling' ) .
										'?wrk=' . $this->id .
										'&hsh=' . $this->controle() . '" >Kleistad pagina</a>',
			],
		];

		switch ( $type ) {
			case 'bevestiging':
			case 'correctie bevestiging':
				$email_parameters['subject']  = 'Bevestiging ' . $this->naam . ( 'bevestiging' === $type ? '' : ' (correctie)' );
				$email_parameters['auto']     = false;
				$email_parameters['slug']     = 'kleistad_email_workshop_bevestiging';
				$email_parameters['from']     = 'info@' . Kleistad_Email::verzend_domein();
				$email_parameters['reply-to'] = 'info@' . Kleistad_Email::domein();
				break;
			case 'betaling':
			case 'betaling_ideal':
				$email_parameters['subject'] = 'Betaling ' . $this->naam;
				$email_parameters['slug']    = "kleistad_email_workshop_$type";
				break;
			case 'afzegging':
				$email_parameters['subject'] = 'Annulering ' . $this->naam;
				$email_parameters['slug']    = 'kleistad_email_workshop_afzegging';
				break;
			default:
				return false;
		}
		return $this->emailer->send( $email_parameters );
	}

	/**
	 * Betaal de workshop met iDeal.
	 *
	 * @since        5.0.0
	 *
	 * @param string $bericht      Het bericht bij succesvolle betaling.
	 */
	public function betalen( $bericht ) {
		$betaling = new Kleistad_Betalen();
		$betaling->order(
			[
				'naam'     => $this->contact,
				'email'    => $this->email,
				'order_id' => $this->code,
			],
			__CLASS__ . '-' . $this->code . '-workshop',
			$this->kosten,
			'Kleistad workshop ' . $this->code . ' op ' . strftime( '%d-%m-%y', $this->datum ),
			$bericht
		);
	}

	/**
	 * Service functie voor update workshop batch job.
	 *
	 * @since 5.0.0
	 *
	 * @param string $actie De actie die op datum uitgevoerd moet worden.
	 */
	public function event( $actie ) {
		if ( 'betaling' === $actie ) {
			$this->email( $actie );
		}
	}

	/**
	 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
	 *
	 * @since        5.0.0
	 *
	 * @param array $parameters De parameters 0: workshop-id.
	 * @param float $bedrag     Het betaalde bedrag, wordt hier niet gebruikt.
	 * @param bool  $betaald    Of er werkelijk betaald is.
	 * @scrutinizer ignore-unused
	 */
	public static function callback( $parameters, $bedrag, $betaald = true ) {
		if ( $betaald ) {
			$workshop          = new static( intval( $parameters[0] ) );
			$workshop->betaald = true;
			$workshop->save();
			$workshop->email( 'betaling_ideal' );
		}
	}

	/**
	 * Return alle workshops.
	 *
	 * @global object $wpdb WordPress database.
	 * @param bool $open Toon alles.
	 * @return array workshops.
	 */
	public static function all( $open = false ) {
		global $wpdb;
		$arr             = [];
		$filter          = $open ? ' WHERE datum > CURRENT_DATE' : '';
		$workshops_tabel = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_workshops $filter ORDER BY datum DESC, start_tijd ASC", ARRAY_A ); // phpcs:ignore
		foreach ( $workshops_tabel as $workshop ) {
			$arr[ $workshop['id'] ] = new Kleistad_Workshop( $workshop['id'] );
		}
		return $arr;
	}

}
