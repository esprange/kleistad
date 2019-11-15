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

namespace Kleistad;

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
 * @property bool   betaling_email
 * @property string event_id
 * @property int    aanvraag_id
 * @property string code
 */
class Workshop extends Artikel {

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
		$options = \Kleistad\Kleistad::get_options();
		if ( is_null( $workshop_id ) ) {
			$this->data = [
				'id'             => null,
				'naam'           => '',
				'datum'          => date( 'Y-m-d' ),
				'start_tijd'     => '10:00',
				'eind_tijd'      => '12:00',
				'docent'         => '',
				'technieken'     => wp_json_encode( [] ),
				'organisatie'    => '',
				'contact'        => '',
				'email'          => '',
				'telefoon'       => '',
				'programma'      => '',
				'vervallen'      => 0,
				'kosten'         => $options['workshopprijs'],
				'aantal'         => 6,
				'betaald'        => 0,
				'definitief'     => 0,
				'betaling_email' => 0,
				'aanvraag_id'    => 0,
			];
		} else {
			$this->data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_workshops WHERE id = %d", $workshop_id ), ARRAY_A );
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
				return json_decode( $this->data['technieken'], true );
			case 'datum':
			case 'start_tijd':
			case 'eind_tijd':
				return strtotime( $this->data[ $attribuut ] );
			case 'vervallen':
			case 'betaald':
			case 'definitief':
			case 'betaling_email':
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
			case 'betaling_email':
				$this->data[ $attribuut ] = (int) $waarde;
				break;
			case 'telnr':
				$this->data['telefoon'] = $waarde;
				break;
			default:
				$this->data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Zeg de gemaakte afspraak voor de workshop af.
	 *
	 * @since 5.0.0
	 */
	public function annuleren() {
		if ( ! $this->vervallen ) {
			$this->vervallen = true;
			$this->save();
			try {
				$event = new \Kleistad\Event( $this->event_id );
				$event->delete();
			} catch ( \Exception $e ) {
				unset( $e ); // phpcs:ignore
			}
		}
	}

	/**
	 * Geef de artikel naam.
	 *
	 * @return string
	 */
	public function artikel_naam() {
		return $this->naam;
	}

	/**
	 * Betaal de workshop met iDeal.
	 *
	 * @since        5.0.0
	 *
	 * @param string $bericht      Het bericht bij succesvolle betaling.
	 * @return string|bool De redirect url ingeval van een ideal betaling of false als het mislukt.
	 */
	public function betalen( $bericht ) {
		$betalen = new \Kleistad\Betalen();
		return $betalen->order(
			[
				'naam'     => $this->contact,
				'email'    => $this->email,
				'order_id' => $this->code,
			],
			__CLASS__ . '-' . $this->code . '-workshop',
			$this->kosten,
			'Kleistad workshop ' . $this->code . ' op ' . strftime( '%d-%m-%Y', $this->datum ),
			$bericht
		);
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
			$this->email( 'bevestiging' );
		} else {
			if ( \Kleistad\Order::zoek_order( $this->code ) ) { // Als er al een factuur is aangemaakt, pas dan de order en factuur aan.
				$this->email( 'betaling', $this->wijzig_order( \Kleistad\Order::zoek_order( $this->code ) ) );
			} else {
				$this->email( 'correctie bevestiging' );
			}
		}
	}

	/**
	 * Geef de code terug.
	 *
	 * @return string
	 */
	public function code() {
		return $this->code;
	}

	/**
	 * Verzenden van de bevestiging of betalings email.
	 *
	 * @since      5.0.0
	 *
	 * @param string $type bevestiging of betaling.
	 * @param string $factuur Een bij te sluiten factuur.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type, $factuur = '' ) {
		$emailer          = new \Kleistad\Email();
		$email_parameters = [
			'to'          => "{$this->contact} <{$this->email}>",
			'attachments' => $factuur,
			'parameters'  => [
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
				'workshop_link'       => $this->betaal_link(),
			],
		];

		switch ( $type ) {
			case 'bevestiging':
			case 'correctie bevestiging':
				$email_parameters['subject']  = 'Bevestiging ' . $this->naam . ( 'bevestiging' === $type ? '' : ' (correctie)' );
				$email_parameters['auto']     = false;
				$email_parameters['slug']     = 'kleistad_email_workshop_bevestiging';
				$email_parameters['from']     = 'info@' . \Kleistad\Email::verzend_domein();
				$email_parameters['reply-to'] = 'info@' . \Kleistad\Email::domein();
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
		return $emailer->send( $email_parameters );
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
		$timezone = new \DateTimeZone( get_option( 'timezone_string' ) ?: 'Europe/Amsterdam' );
		\Kleistad\WorkshopAanvraag::gepland( $this->aanvraag_id, $this->id );

		try {
			$event             = new \Kleistad\Event( $this->event_id );
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
			$event->start      = new \DateTime( $this->data['datum'] . ' ' . $this->data['start_tijd'], $timezone );
			$event->eind       = new \DateTime( $this->data['datum'] . ' ' . $this->data['eind_tijd'], $timezone );
			$event->save();
		} catch ( \Exception $e ) {
			error_log ( $e->getMessage() ); // phpcs:ignore
		}

		return $this->id;
	}

	/**
	 * Geef de workshop status in tekstvorm terug.
	 */
	public function status() {
		return $this->vervallen ? 'vervallen' : ( ( $this->definitief ? 'definitief ' : 'concept' ) . ( $this->betaald ? 'betaald' : '' ) );
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
				$event = new \Kleistad\Event( $this->event_id );
				$event->delete();
			} catch ( \Exception $e ) {
				unset( $e ); // phpcs:ignore
			}
		} else {
			return false;
		};
		\Kleistad\WorkshopAanvraag::gepland( $this->aanvraag_id, 0 );
		return true;
	}

	/**
	 * Check of er een indeling moet plaatsvinden ivm betaling inschrijfgeld.
	 *
	 * @param float $bedrag Het betaalde bedrag.
	 */
	protected function betaalactie( $bedrag ) {
		if ( ! $this->betaald && 0.1 < abs( $bedrag - $this->kosten ) ) {
			$this->betaald = true;
		}
		$this->save();
	}

	/**
	 * De regels voor de factuur.
	 *
	 * @return array De regels.
	 */
	protected function factuurregels() {
		$prijs = round( $this->kosten / ( 1 + self::BTW ), 2 );
		$btw   = round( $this->kosten - $prijs, 2 );
		return [
			[
				'artikel' => "{$this->naam} op " . strftime( '%A %d-%m-%y', $this->datum ),
				'aantal'  => 1,
				'prijs'   => $prijs,
				'btw'     => $btw,
			],
		];
	}

	/**
	 * De contact gegevens van de klant, bij een workshop afwijkend.
	 *
	 * @return array De contact info.
	 */
	protected function naw_klant() {
		if ( $this->organisatie ) {
			$naam = $this->organisatie . ', ' . $this->contact;
		} else {
			$naam = $this->contact;
		}
		return [
			'naam'  => $naam,
			'adres' => '',
		];
	}

	/**
	 * Controleer of er betalingsverzoeken verzonden moeten worden.
	 *
	 * @since 6.1.0
	 */
	public static function dagelijks() {
		$workshops = self::all();
		$meetdag   = strtotime( '+7 days' );
		foreach ( $workshops as $workshop ) {
			if ( $workshop->definitief && ! $workshop->betaald && ! $workshop->vervallen && ! $workshop->betaling_email && $meetdag >= $workshop->datum ) {
				$workshop->betaling_email = true;
				$workshop->save();
				$workshop->email( 'betaling', $workshop->bestel_order( 0.0 ) );
			}
		}
	}

	/**
	 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
	 *
	 * @since        5.0.0
	 *
	 * @param array $parameters De parameters 0: workshop-id.
	 * @param float $bedrag     Het betaalde bedrag.
	 * @param bool  $betaald    Of er werkelijk betaald is.
	 * @scrutinizer ignore-unused
	 */
	public static function callback( $parameters, $bedrag, $betaald ) {
		if ( $betaald ) {
			$workshop          = new static( intval( $parameters[0] ) );
			$workshop->betaald = true;
			$workshop->save();
			$workshop->ontvang_order( \Kleistad\Order::zoek_order( $workshop->code ), $bedrag );
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
			$arr[ $workshop['id'] ] = new \Kleistad\Workshop( $workshop['id'] );
		}
		return $arr;
	}

}
