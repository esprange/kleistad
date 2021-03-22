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

use DateTimeZone;
use DateTime;
use Exception;

/**
 * Kleistad workshop class.
 *
 * @since 5.0.0
 *
 * @property int    id
 * @property string naam
 * @property int    start_tijd
 * @property int    eind_tijd
 * @property string docent
 * @property array  technieken
 * @property string organisatie
 * @property string organisatie_adres
 * @property stirng organisatie_email
 * @property string contact
 * @property string email
 * @property string telnr
 * @property string programma
 * @property bool   vervallen
 * @property float  kosten
 * @property int    aantal
 * @property bool   definitief
 * @property bool   betaling_email
 * @property string event_id
 * @property int    aanvraag_id
 */
class Workshop extends Artikel {

	public const DEFINITIE = [
		'prefix' => 'W',
		'naam'   => 'workshop',
		'pcount' => 1,
	];
	public const META_KEY  = 'kleistad_workshop';

	private const EMAIL_SUBJECT = [
		'_bevestiging'    => 'Bevestiging van ',
		'_herbevestiging' => 'Bevestiging na correctie van ',
		'_betaling'       => 'Betaling van ',
		'_ideal'          => 'Betaling van ',
		'_afzegging'      => 'Annulering van ',
	];

	/**
	 * Het actie object
	 *
	 * @var WorkshopActie $actie De acties.
	 */
	public WorkshopActie $actie;

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
		$this->betalen = new Betalen();
		$options       = opties();
		if ( is_null( $workshop_id ) ) {
			$this->data = [
				'id'                => null,
				'naam'              => '',
				'datum'             => date( 'Y-m-d' ),
				'start_tijd'        => '10:00',
				'eind_tijd'         => '12:00',
				'docent'            => '',
				'technieken'        => wp_json_encode( [] ),
				'organisatie'       => '',
				'organisatie_adres' => '',
				'organisatie_email' => '',
				'contact'           => '',
				'email'             => '',
				'telefoon'          => '',
				'programma'         => '',
				'vervallen'         => 0,
				'kosten'            => $options['workshopprijs'],
				'aantal'            => 6,
				'definitief'        => 0,
				'betaling_email'    => 0,
				'aanvraag_id'       => 0,
			];
			return;
		}
		$this->data  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_workshops WHERE id = %d", $workshop_id ), ARRAY_A );
		$this->actie = new WorkshopActie( $this );
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
		if ( preg_match( '~(datum|start_tijd|eind_tijd)~', $attribuut ) ) {
			return strtotime( $this->data[ $attribuut ] );
		}
		if ( preg_match( '~(vervallen|definitief|betaling_email)~', $attribuut ) ) {
			return boolval( $this->data[ $attribuut ] );
		}
		switch ( $attribuut ) {
			case 'technieken':
				return json_decode( $this->data['technieken'], true );
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
			case 'telnr':
				$this->data['telefoon'] = $waarde;
				break;
			default:
				$this->data[ $attribuut ] = is_string( $waarde ) ? trim( $waarde ) : ( is_bool( $waarde ) ? (int) $waarde : $waarde );
		}
	}

	/**
	 * Erase de workshop
	 */
	public function erase() : bool {
		global $wpdb;
		$wpdb->delete( "{$wpdb->prefix}kleistad_workshops", [ 'id' => $this->id ] );
		return true;
	}

	/**
	 * Geef de artikel naam.
	 *
	 * @return string
	 */
	public function geef_artikelnaam() : string {
		return $this->naam;
	}

	/**
	 * Betaal de workshop met iDeal.
	 *
	 * @since        5.0.0
	 *
	 * @param  string $bericht    Het bericht bij succesvolle betaling.
	 * @param  float  $openstaand Het bedrag dat openstaat.
	 * @return string|bool De redirect url ingeval van een ideal betaling of false als het mislukt.
	 */
	public function doe_idealbetaling( string $bericht, float $openstaand = null ) {
		return $this->betalen->order(
			[
				'naam'     => $this->contact,
				'email'    => $this->email,
				'order_id' => $this->code,
			],
			$this->geef_referentie(),
			$openstaand ?? $this->kosten,
			'Kleistad workshop ' . $this->code . ' op ' . strftime( '%d-%m-%Y', $this->datum ),
			$bericht,
			false
		);
	}

	/**
	 * Geef de code terug.
	 *
	 * @return string
	 */
	public function geef_referentie() : string {
		return $this->code;
	}

	/**
	 * De contact gegevens van de klant, bij een workshop afwijkend.
	 *
	 * @return array De contact info.
	 */
	public function naw_klant() : array {
		if ( $this->organisatie ) {
			return [
				'naam'  => $this->organisatie,
				'adres' => $this->organisatie_adres,
				'email' => $this->organisatie_email ?: $this->email,
			];
		}
		return [
			'naam'  => $this->contact,
			'adres' => '',
			'email' => $this->email,
		];
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
		$this->id         = $wpdb->insert_id;
		$timezone         = new DateTimeZone( get_option( 'timezone_string' ) ?: 'Europe/Amsterdam' );
		$workshopaanvraag = new WorkshopAanvraag();
		$workshopaanvraag->gepland( $this->aanvraag_id, $this->id );

		try {
			$event             = new Event( $this->event_id );
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
	 * Geef de workshop status in tekstvorm terug.
	 *
	 * @param bool $uitgebreid Of er een uitgebreide versie geleverd moet worden.
	 * @suppressWarnings(PHPMD.BooleanArgumentFlag)
	 */
	public function geef_statustekst( bool $uitgebreid = false ) : string {
		$status = $this->vervallen ? 'vervallen' : ( ( $this->definitief ? 'definitief ' : 'concept' ) . ( $this->is_betaald() ? 'betaald' : '' ) );
		return $uitgebreid ? "$this->naam $status" : $status;
	}

	/**
	 * De regels voor de factuur.
	 *
	 * @return Orderregel De regel.
	 */
	protected function geef_factuurregels() {
		return new Orderregel( "{$this->naam} op " . strftime( '%A %d-%m-%y', $this->datum ) . ", {$this->aantal} deelnemers", 1, $this->kosten );
	}

	/**
	 * Geef aan of de workshop betaald is.
	 *
	 * @return bool True als betaald.
	 */
	public function is_betaald() {
		$order = new Order( $this->geef_referentie() );
		return $order->gesloten;
	}

	/**
	 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
	 *
	 * @since        5.0.0
	 *
	 * @param int    $order_id     De order id, als deze al bestaat.
	 * @param float  $bedrag       Het betaalde bedrag.
	 * @param bool   $betaald      Of er werkelijk betaald is.
	 * @param string $type         Type betaling, ideal , directdebit of bank.
	 * @param string $transactie_id De betaling id.
	 */
	public function verwerk_betaling( $order_id, $bedrag, $betaald, $type, $transactie_id = '' ) {
		if ( $betaald && $order_id ) {
			/**
			 * Bij workshops is er altijd eerst een factuur verstuurd
			 */
			$this->ontvang_order( $order_id, $bedrag, $transactie_id );
			if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting.
				$this->verzend_email( '_ideal' );
			}
		}
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
	public function verzend_email( $type, $factuur = '' ) {
		$emailer          = new Email();
		$email_parameters = [
			'to'          => "{$this->contact} <{$this->email}>",
			'attachments' => $factuur ?: [],
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
				'workshop_link'       => $this->betaal_link,
			],
			'slug'        => "workshop$type",
			'subject'     => self::EMAIL_SUBJECT( $type ) . $this->naam,
		];
		if ( $factuur && $this->organisatie_email ) {
			$email_parameters['to'] .= ", {$this->organisatie} <{$this->organisatie_email}>";
		}
		if ( false !== strpos( $type, 'bevestiging' ) ) {
				$email_parameters['auto']     = false;
				$email_parameters['slug']     = 'workshop_bevestiging';
				$email_parameters['from']     = "{$emailer->info}{$emailer->verzend_domein}";
				$email_parameters['reply-to'] = "{$emailer->info}{$emailer->domein}";
		}
		return $emailer->send( $email_parameters );
	}

}
