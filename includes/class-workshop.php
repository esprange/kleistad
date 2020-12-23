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
 * @property bool   betaald
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
				'betaald'           => 0,
				'definitief'        => 0,
				'betaling_email'    => 0,
				'aanvraag_id'       => 0,
			];
			return;
		}
		$this->data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_workshops WHERE id = %d", $workshop_id ), ARRAY_A );
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
		if ( preg_match( '~(vervallen|betaald|definitief|betaling_email)~', $attribuut ) ) {
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
	 * Zeg de gemaakte afspraak voor de workshop af.
	 *
	 * @since 5.0.0
	 */
	public function afzeggen() : bool {
		if ( ! $this->vervallen ) {
			$this->vervallen = true;
			$this->save();
			try {
				$event = new Event( $this->event_id );
				$event->delete();
			} catch ( Exception $e ) {
				unset( $e ); // phpcs:ignore
			}
		}
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
	 * @param  string $referentie De referentie van het artikel.
	 * @param  float  $openstaand Het bedrag dat openstaat.
	 * @return string|bool De redirect url ingeval van een ideal betaling of false als het mislukt.
	 */
	public function doe_idealbetaling( $bericht, $referentie, $openstaand = null ) {
		return $this->betalen->order(
			[
				'naam'     => $this->contact,
				'email'    => $this->email,
				'order_id' => $this->code,
			],
			$referentie,
			$openstaand ?? $this->kosten,
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
			return $this->verzend_email( '_bevestiging' );
		}
		$order = new Order( $this->geef_referentie() );
		if ( $order->id ) { // Als er al een factuur is aangemaakt, pas dan de order en factuur aan.
			$factuur = $this->wijzig_order( $order->id );
			if ( false === $factuur ) { // De factuur is aangemaakt in een periode die boekhoudkundig geblokkeerd is, correctie is niet mogelijk.
				return false;
			} elseif ( ! empty( $factuur ) ) { // Er was al een factuur die nog gecorrigeerd mag worden.
				return $this->verzend_email( '_betaling', $factuur );
			}
		}
		return $this->verzend_email( '_herbevestiging' );
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
		];

		$email_parameters['slug'] = "workshop$type";
		if ( $factuur && $this->organisatie_email ) {
			$email_parameters['to'] .= ", {$this->organisatie} <{$this->organisatie_email}>";
		}
		switch ( $type ) {
			case '_bevestiging':
			case '_herbevestiging':
				$email_parameters['subject']  = 'Bevestiging ' . $this->naam . ( '_herbevestiging' === $type ? ' (correctie)' : '' );
				$email_parameters['auto']     = false;
				$email_parameters['slug']     = 'workshop_bevestiging';
				$email_parameters['from']     = "{$emailer->info}{$emailer->verzend_domein}";
				$email_parameters['reply-to'] = "{$emailer->info}{$emailer->domein}";
				break;
			case '_betaling':
			case '_ideal':
				$email_parameters['subject'] = 'Betaling ' . $this->naam;
				break;
			case '_afzegging':
				$email_parameters['subject'] = 'Annulering ' . $this->naam;
				break;
			default:
				return false;
		}
		return $emailer->send( $email_parameters );
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
		$this->id = $wpdb->insert_id;
		$timezone = new DateTimeZone( get_option( 'timezone_string' ) ?: 'Europe/Amsterdam' );
		WorkshopAanvraag::gepland( $this->aanvraag_id, $this->id );

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
	 */
	public function geef_statustekst( bool $uitgebreid = false ) : string {
		$status = $this->vervallen ? 'vervallen' : ( ( $this->definitief ? 'definitief ' : 'concept' ) . ( $this->betaald ? 'betaald' : '' ) );
		return $uitgebreid ? 'workshop ' . $status : $status;
	}

	/**
	 * Check of er een indeling moet plaatsvinden ivm betaling inschrijfgeld.
	 *
	 * @param float $bedrag Het betaalde bedrag.
	 */
	protected function betaalactie( $bedrag ) {
		if ( ! $this->betaald && $bedrag >= ( $this->kosten - 0.01 ) ) {
			$this->betaald = true;
		}
		$this->save();
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
	 * Controleer of er betalingsverzoeken verzonden moeten worden.
	 *
	 * @since 6.1.0
	 */
	public static function doe_dagelijks() {
		$workshops = new Workshops();
		foreach ( $workshops as $workshop ) {
			if (
				! $workshop->definitief ||
				$workshop->betaald ||
				$workshop->vervallen ||
				$workshop->betaling_email ||
				strtotime( '+7 days 00:00' ) < $workshop->datum
				) {
				continue;
			}
			$workshop->betaling_email = true;
			$workshop->save();
			$workshop->verzend_email( '_betaling', $workshop->bestel_order( 0.0, $workshop->datum ) );
		}
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

}
