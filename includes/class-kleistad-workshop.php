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
 */
class Kleistad_Workshop extends Kleistad_Entity {

	const META_KEY = 'kleistad_workshop';

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
		$default_data = [
			'id'          => null,
			'naam'        => 'nog te definiëren workshop',
			'datum'       => '',
			'start_tijd'  => '',
			'eind_tijd'   => '',
			'docent'      => '',
			'technieken'  => wp_json_encode( [] ),
			'organisatie' => '',
			'contact'     => '',
			'email'       => '',
			'telefoon'    => '',
			'programma'   => '',
			'vervallen'   => 0,
			'kosten'      => 0.0,
			'aantal'      => 0,
			'betaald'     => 0,
			'definitief'  => 0,
		];
		if ( is_null( $workshop_id ) ) {
			$this->_data = $default_data;
		} else {
			$this->_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_workshops WHERE id = %d", $workshop_id ), ARRAY_A );
		}
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
				return ( 'null' === $this->_data['technieken'] ) ? [] : json_decode( $this->_data['technieken'], true );
			case 'datum':
			case 'start_tijd':
			case 'eind_tijd':
				return strtotime( $this->_data[ $attribuut ] );
			case 'vervallen':
			case 'betaald':
			case 'definitief':
				return 1 === intval( $this->_data[ $attribuut ] );
			case 'array':
				return $this->_data;
			case 'code':
				return "W{$this->_data['id']}";
			case 'event_id':
				return sprintf( 'kleistadevent%06d', $this->_data['id'] );
			default:
				return $this->_data[ $attribuut ];
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
				$this->_data[ $attribuut ] = wp_json_encode( $waarde );
				break;
			case 'datum':
			case 'datum_betalen':
				$this->_data[ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			case 'start_tijd':
			case 'eind_tijd':
				$this->_data[ $attribuut ] = date( 'H:i', $waarde );
				break;
			case 'vervallen':
			case 'betaald':
			case 'definitief':
				$this->_data[ $attribuut ] = $waarde ? 1 : 0;
				break;
			default:
				$this->_data[ $attribuut ] = $waarde;
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
		$wpdb->replace( "{$wpdb->prefix}kleistad_workshops", $this->_data );
		$this->id = $wpdb->insert_id;

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
			$timezone          = new DateTimeZone( get_option( 'timezone_string' ) );
			$event->start      = new DateTime( $this->_data['datum'] . ' ' . $this->_data['start_tijd'], $timezone );
			$event->eind       = new DateTime( $this->_data['datum'] . ' ' . $this->_data['eind_tijd'], $timezone );
			$event->save();
		} catch ( Exception $e ) {
			error_log ( $e->getMessage() ); // phpcs:ignore
		}

		return $this->id;
	}

	/**
	 * Bevestig de workshop.
	 *
	 * @since 5.0.0
	 */
	public function bevestig() {
		if ( ! $this->definitief ) {
			$this->definitief = true;
			$this->save();
			wp_schedule_single_event(
				mktime( 0, 0, 0, intval( date( 'n', $this->datum ) ), intval( date( 'j', $this->datum ) ) - 7, intval( date( 'Y', $this->datum ) ) ),
				self::META_KEY,
				[
					$this->id,
					'betaling',
					$this->datum,
				]
			);
			$this->email( 'bevestiging' );
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
			wp_unschedule_event(
				mktime( 0, 0, 0, intval( date( 'n', $this->datum ) ), intval( date( 'j', $this->datum ) ) - 7, intval( date( 'Y', $this->datum ) ) ),
				self::META_KEY,
				[
					$this->id,
					'betaling',
					$this->datum,
				]
			);
			$this->save();
			$this->email( 'afzegging' );
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
		$to        = "{$this->contact} <{$this->email}>";
		$onderwerp = ucfirst( $type ) . ' workshop';

		switch ( $type ) {
			case 'bevestiging':
				$slug = 'kleistad_email_workshop_bevestiging';
				break;
			case 'betaling':
				$slug = 'kleistad_email_workshop_betaling';
				break;
			case 'betaling_ideal':
				$slug = 'kleistad_email_workshop_betaling_ideal';
				break;
			case 'afzegging':
				$slug = 'kleistad_email_workshop_afzegging';
				break;
			default:
				return false;
		}
		return Kleistad_public::compose_email(
			$to,
			$onderwerp,
			$slug,
			[
				'naam'                => $this->contact,
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
			]
		);
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
	 * Service functie voor update abonnee batch job.
	 * Datum wordt apart meegegeven, ondanks dat het de datum heden is.
	 * Omdat de uitvoeringstijd van de batchjob niet vastligt beter om de oorspronkelijke timestamp vast te leggen.
	 *
	 * @since 5.0.0
	 *
	 * @param string $actie De actie die op datum uitgevoerd moet worden.
	 * @param int    $datum De datum / tijdstip waarop de actie nodig is.
	 * @phan-suppress PhanUnusedPublicMethodParameter
	 */
	public function event( $actie, $datum ) {
		switch ( $actie ) {
			case 'betaling':
				$this->email( $actie );
				break;
			default:
				break;
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
	 * @phan-suppress PhanUnusedPublicMethodParameter
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
		$workshops_tabel = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_workshops $filter ORDER BY datum DESC, start_tijd ASC", ARRAY_A ); // WPCS: unprepared SQL OK.
		foreach ( $workshops_tabel as $workshop ) {
			$arr[ $workshop['id'] ] = new Kleistad_Workshop( $workshop['id'] );
		}
		return $arr;
	}

}
