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
 * @property int    datum
 * @property int    aanvraagdatum
 * @property int    start_tijd
 * @property int    eind_tijd
 * @property string docent
 * @property array  technieken
 * @property string organisatie
 * @property string organisatie_adres
 * @property string organisatie_email
 * @property string contact
 * @property string email
 * @property string telnr
 * @property string programma
 * @property bool   vervallen
 * @property float  kosten
 * @property int    aantal
 * @property bool   definitief
 * @property bool   betaling_email
 * @property int    aanvraag_id
 * @property array  communicatie
 */
class Workshop extends Artikel {

	public const DEFINITIE       = [
		'prefix'       => 'W',
		'naam'         => 'workshop',
		'pcount'       => 1,
		'annuleerbaar' => false,
	];
	public const META_KEY        = 'kleistad_workshop';
	public const AFSPRAAK_PREFIX = 'kleistadevent';
	private const EMAIL_SUBJECT  = [
		'_bevestiging'          => '[WS#%08d] Bevestiging van ',
		'_herbevestiging'       => '[WS#%08d] Bevestiging na correctie van ',
		'_betaling'             => 'Betaling van ',
		'_ideal'                => 'Betaling van ',
		'_afzegging'            => '[WS#%08d] Annulering van ',
		'_reactie'              => '[WS#%08d] Reactie op ',
		'_aanvraag_bevestiging' => '[WS#%08d] Bevestiging van aanvraag ',
	];

	/**
	 * Het actie object
	 *
	 * @var WorkshopActie $actie De acties.
	 */
	public WorkshopActie $actie;

	/**
	 * Het door de aanvrager voorgestelde dagdeel van de workshop.
	 *
	 * @var string Het dagdeel.
	 */
	public string $dagdeel = '';

	/**
	 * De door de aanvrager gestelde vraag.
	 *
	 * @var string De vraag.
	 */
	public string $vraag = '';

	/**
	 * Reactie ingeval op een email gereageerd wordt.
	 *
	 * @var string De reactie.
	 */
	public string $reactie = '';

	/**
	 * Constructor
	 *
	 * @since 5.0.0
	 *
	 * @global object    $wpdb WordPress database.
	 * @param int|null   $workshop_id (optional) workshop welke geladen moet worden.
	 * @param array|null $load (optioneel) data waarmee het object geladen kan worden (ivm performance).
	 */
	public function __construct( int $workshop_id = null, ?array $load = null ) {
		global $wpdb;
		$this->actie    = new WorkshopActie( $this );
		$this->betaling = new WorkshopBetaling( $this );
		$this->data     = [
			'id'                => null,
			'naam'              => '',
			'datum'             => date( 'Y-m-d' ),
			'aanvraagdatum'     => date( 'Y-m-d' ),
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
			'kosten'            => opties()['workshopprijs'],
			'aantal'            => 6,
			'definitief'        => 0,
			'betaling_email'    => 0,
			'aanvraag_id'       => 0,
			'communicatie'      => maybe_serialize( [] ),
		];
		if ( is_null( $workshop_id ) ) {
			return;
		}
		$workshop = $load ?? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_workshops WHERE id = %d", $workshop_id ), ARRAY_A );
		if ( is_null( $workshop ) ) {
			$workshop = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_workshops WHERE aanvraag_id = %d", $workshop_id ), ARRAY_A );
		}
		if ( ! is_null( $workshop ) ) {
			$this->data = $workshop;
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
	public function __get( string $attribuut ) {
		if ( in_array( $attribuut, [ 'start_tijd', 'eind_tijd' ], true ) ) {
			return strtotime( "{$this->data['datum']} {$this->data[ $attribuut ]}" );
		}
		if ( in_array( $attribuut, [ 'vervallen', 'definitief', 'betaling_email' ], true ) ) {
			return boolval( $this->data[ $attribuut ] );
		}
		return match ( $attribuut ) {
			'datum'         => strtotime( $this->data['datum'] ),
			'aanvraagdatum' => strtotime( $this->data['aanvraagdatum'] ),
			'technieken'    => json_decode( $this->data['technieken'], true ),
			'code'          => "W{$this->data['id']}",
			'telnr'         => $this->data['telefoon'],
			'communicatie'  => maybe_unserialize( $this->data['communicatie'] ) ?: [],
			default         => is_string( $this->data[ $attribuut ] ) ? htmlspecialchars_decode( $this->data[ $attribuut ] ) : $this->data[ $attribuut ],
		};
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @since 5.0.0
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( string $attribuut, mixed $waarde ) {
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
			case 'communicatie':
				$this->data['communicatie'] = maybe_serialize( $waarde );
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
	public function get_artikelnaam() : string {
		return $this->naam;
	}

	/**
	 * Hulp functie voor de oudere workshops (voor 7.0.0 werd de naam ingevuld, nu het nummer of een reeks van nummers ).
	 *
	 * @return string De naam van de docent.
	 */
	public function get_docent_naam() : string {
		$docenten = [];
		foreach ( explode( ';', $this->docent ) as $docent_item ) {
			if ( is_numeric( $docent_item ) ) {
				$docenten[] = get_user_by( 'id', intval( $docent_item ) )->display_name;
				continue;
			}
			$docenten[] = $docent_item;
		}
		return implode( ', ', $docenten );
	}

	/**
	 * Geef de code terug.
	 *
	 * @return string
	 */
	public function get_referentie() : string {
		return $this->code;
	}

	/**
	 * De contact gegevens van de klant, bij een workshop afwijkend.
	 *
	 * @return array De contact info.
	 */
	public function get_naw_klant() : array {
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
	public function save() : int {
		global $wpdb;
		$wpdb->replace( "{$wpdb->prefix}kleistad_workshops", $this->data );
		$this->id = $wpdb->insert_id;
		$timezone = new DateTimeZone( get_option( 'timezone_string' ) ?: 'Europe/Amsterdam' );

		try {
			$afspraak               = new Afspraak( sprintf( '%s%06d', self::AFSPRAAK_PREFIX, $this->id ) );
			$afspraak->titel        = $this->naam;
			$afspraak->definitief   = $this->definitief;
			$afspraak->vervallen    = $this->vervallen;
			$afspraak->start        = new DateTime( $this->data['datum'] . ' ' . $this->data['start_tijd'], $timezone );
			$afspraak->eind         = new DateTime( $this->data['datum'] . ' ' . $this->data['eind_tijd'], $timezone );
			$afspraak->beschrijving = sprintf(
				'<p><strong>%s</strong></p><p>contact: %s, %s</p><p>aantal: %d</p><p>programma: %s</p><p>technieken: %s</p>',
				$this->naam,
				$this->contact,
				$this->telnr,
				$this->aantal,
				$this->programma,
				implode( ', ', $this->technieken )
			);
			if ( $this->docent ) {
				foreach ( explode( ';', $this->docent ) as $docent_item ) {
					$afspraak->deelnemers[] = [ 'email' => get_user_by( 'id', intval( $docent_item ) )->user_email ];
				}
			}
			$afspraak->save();
		} catch ( Exception $e ) {
			fout( __CLASS__, $e->getMessage() );
		}
		return $this->id;
	}

	/**
	 * Geef de workshop status in tekstvorm terug.
	 *
	 * @param bool $uitgebreid Of er een uitgebreide versie geleverd moet worden.
	 * @return string
	 * @suppressWarnings(PHPMD.BooleanArgumentFlag)
	 */
	public function get_statustekst( bool $uitgebreid = false ) : string {
		$status = $this->vervallen ? 'vervallen' : ( ( $this->definitief ? 'definitief ' : 'concept' ) );
		return $uitgebreid ? "$this->naam $status" : $status;
	}

	/**
	 * De regels voor de factuur.
	 *
	 * @return Orderregels De regel.
	 */
	public function get_factuurregels() : Orderregels {
		$orderregels = new Orderregels();
		$orderregels->toevoegen( new Orderregel( "$this->naam op " . strftime( '%A %d-%m-%y', $this->datum ) . ", $this->aantal deelnemers", 1, $this->kosten ) );
		return $orderregels;
	}

	/**
	 * Geef aan of de workshop betaald is.
	 *
	 * @return bool True als betaald.
	 */
	public function is_betaald() : bool {
		$order = new Order( $this->get_referentie() );
		return $order->gesloten;
	}

	/**
	 * Verzenden van de bevestiging of betalings email.
	 *
	 * @since      5.0.0
	 *
	 * @param string $type bevestiging of betaling.
	 * @param string $factuur Een bij te sluiten factuur.
	 * @return bool succes of falen van verzending email.
	 */
	public function verzend_email( string $type, string $factuur = '' ) : bool {
		$emailer          = new Email();
		$email_parameters = [
			'to'          => "$this->contact <$this->email>",
			'attachments' => $factuur ?: [],
			'parameters'  => [
				'contact'             => $this->contact,
				'naam'                => $this->naam,
				'organisatie'         => $this->organisatie,
				'aantal'              => $this->aantal,
				'workshop_code'       => $this->code,
				'workshop_datum'      => strftime( '%A %d-%m-%y', $this->datum ),
				'workshop_start_tijd' => strftime( '%H:%M', $this->start_tijd ),
				'workshop_eind_tijd'  => strftime( '%H:%M', $this->eind_tijd ),
				'workshop_docent'     => str_replace( ', ', ' en ', $this->get_docent_naam() ),
				'workshop_technieken' => implode( ', ', $this->technieken ),
				'workshop_programma'  => $this->programma,
				'workshop_kosten'     => number_format_i18n( $this->kosten, 2 ),
				'workshop_link'       => $this->get_betaal_link(),
				'workshop_dagdeel'    => $this->dagdeel,
				'vraag'               => $this->vraag,
				'reactie'             => $this->reactie,
			],
			'slug'        => "workshop$type",
			'subject'     => sprintf( self::EMAIL_SUBJECT[ $type ], $this->id ) . $this->naam,
		];
		if ( in_array( $type, [ '_aanvraag_bevestiging', '_bevestiging', '_herbevestiging', '_afzegging', '_reactie' ], true ) ) {
			$mbx                          = 'production' === wp_get_environment_type() ? 'workshops@' : ( strtok( get_bloginfo( 'admin_email' ), '@' ) . 'workshops@' );
			$email_parameters['from']     = $mbx . $emailer->verzend_domein;
			$email_parameters['reply-to'] = $mbx . $emailer->domein;
			$email_parameters['auto']     = false;
		}
		if ( in_array( $type, [ '_bevestiging', '_herbevestiging', '_afzegging', '_reactie' ], true ) ) {
			$email_parameters['sign'] = wp_get_current_user()->display_name . ',<br/>Kleistad';
		}
		if ( $factuur && $this->organisatie_email ) {
			$email_parameters['to'] .= ", $this->organisatie <$this->organisatie_email>";
		}
		return $emailer->send( $email_parameters );
	}

}
