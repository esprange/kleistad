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
 * @property string code
 */
class Workshop extends Artikel {

	use WerkplekReservering;

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
	public const VERVALT         = 'vervalt';
	public const DEFINITIEF      = 'definitief';
	public const VERVALLEN       = 'vervallen';
	public const CONCEPT         = 'concept';

	/**
	 * Het actie object
	 *
	 * @var WorkshopActie $actie De acties.
	 */
	public WorkshopActie $actie;

	/**
	 * Workshop identificatie.
	 *
	 * @var int Het id.
	 */
	public int $id = 0;

	/**
	 * Workshop identificatie code.
	 *
	 * @var string De code.
	 */
	public string $code = '';

	/**
	 * Naam van de workshop.
	 *
	 * @var string De naam, workshop of kinderfeest etc.
	 */
	public string $naam = '';

	/**
	 * Workshop datum.
	 *
	 * @var int Datum waarop de workshop plaatsvindt.
	 */
	public int $datum = 0;

	/**
	 * Workshap aanvraag datum.
	 *
	 * @var int Datum waarop de workshop is aangevraagd.
	 */
	public int $aanvraagdatum = 0;

	/**
	 * Workshop start.
	 *
	 * @var int Start tijd van de workshop.
	 */
	public int $start_tijd = 0;

	/**
	 * Workshop eind.
	 *
	 * @var int Eind tijd van de workshop.
	 */
	public int $eind_tijd = 0;

	/**
	 * Docent(en) van de workshop.
	 *
	 * @var string Docent of docenten.
	 */
	public string $docent = '';

	/**
	 * Technieken.
	 *
	 * @var array De technieken die gebruikt worden.
	 */
	public array $technieken = [];

	/**
	 * Ingeval van zakelijk, de organisatie naam.
	 *
	 * @var string Organisatie naam.
	 */
	public string $organisatie = '';

	/**
	 * Ingeval van zakelijk, de organisatie adres.
	 *
	 * @var string Organisatie adres.
	 */
	public string $organisatie_adres = '';

	/**
	 * Ingeval van zakelijk, de organisatie email.
	 *
	 * @var string Organisatie email.
	 */
	public string $organisatie_email = '';

	/**
	 * Workshop contactpersoon.
	 *
	 * @var string De naam van het contact.
	 */
	public string $contact = '';

	/**
	 * Workshop contactpersoon email.
	 *
	 * @var string De email van het contact.
	 */
	public string $email = '';

	/**
	 * Workshop contactpersoon telefoonnummer.
	 *
	 * @var string Het telefoonnummer van het contact.
	 */
	public string $telnr = '';

	/**
	 * Workshop programma.
	 *
	 * @var string Het afgesproken programma.
	 */
	public string $programma = '';

	/**
	 * Werkplek reservering.
	 *
	 * @var array Aantal te reserveren werkplekken.
	 */
	public array $werkplekken = [];

	/**
	 * Vervallen status workshop.
	 *
	 * @var bool True als vervallen.
	 */
	public bool $vervallen = false;

	/**
	 * Workshop kosten.
	 *
	 * @var float De bruto kosten.
	 */
	public float $kosten = 0.0;

	/**
	 * Workshop deelnemer aantal.
	 *
	 * @var int Aantal deelnemers.
	 */
	public int $aantal = 6;

	/**
	 * Definitieve status workshop.
	 *
	 * @var bool True als definitief.
	 */
	public bool $definitief = false;

	/**
	 * Betaal email status.
	 *
	 * @var bool True als betaling email verstuurd.
	 */
	public bool $betaling_email = false;

	/**
	 * Aanvraag id. Is alleen nog nodig voor oude workshops, voor 21 mei 2022.
	 *
	 * @var int Backwards compatibility, id van de aanvraag.
	 */
	public int $aanvraag_id = 0;

	/**
	 * Communicatie over de workshop.
	 *
	 * @var array Communicatie teksten.
	 */
	public array $communicatie = [];

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
	 * @param int        $workshop_id (optional) workshop welke geladen moet worden.
	 * @param array|null $load (optioneel) data waarmee het object geladen kan worden (ivm performance).
	 */
	public function __construct( int $workshop_id = 0, ?array $load = null ) {
		global $wpdb;
		$this->actie         = new WorkshopActie( $this );
		$this->betaling      = new WorkshopBetaling( $this );
		$this->datum         = strtotime( 'tomorrow' );
		$this->start_tijd    = strtotime( 'tomorrow 10:00' );
		$this->eind_tijd     = strtotime( 'tomorrow 12:00' );
		$this->aanvraagdatum = time();
		$this->kosten        = opties()['workshopprijs'];
		$this->communicatie  = [
			[
				'type'    => WorkshopActie::NIEUW,
				'from'    => 'Kleistad',
				'subject' => 'Toevoeging door ' . wp_get_current_user()->display_name,
				'tekst'   => '',
				'tijd'    => current_time( 'd-m-Y H:i' ),
			],
		];
		if ( ! $workshop_id ) {
			return;
		}
		$workshop = $load ?? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_workshops WHERE id = %d", $workshop_id ), ARRAY_A );
		if ( is_null( $workshop ) ) {
			$workshop = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_workshops WHERE aanvraag_id = %d", $workshop_id ), ARRAY_A );
		}
		if ( ! is_null( $workshop ) ) {
			$this->id                = intval( $workshop['id'] );
			$this->code              = self::DEFINITIE['prefix'] . $this->id;
			$this->datum             = strtotime( "{$workshop['datum']} 0:00" );
			$this->aanvraagdatum     = strtotime( "{$workshop['aanvraagdatum']} 0:00" );
			$this->start_tijd        = strtotime( "{$workshop['datum']} {$workshop['start_tijd']}" );
			$this->eind_tijd         = strtotime( "{$workshop['datum']} {$workshop['eind_tijd']}" );
			$this->vervallen         = boolval( $workshop['vervallen'] );
			$this->definitief        = boolval( $workshop['definitief'] );
			$this->betaling_email    = boolval( $workshop['betaling_email'] );
			$this->technieken        = json_decode( $workshop['technieken'], true ) ?: [];
			$this->werkplekken       = json_decode( $workshop['werkplekken'], true ) ?: [];
			$this->communicatie      = maybe_unserialize( $workshop['communicatie'] );
			$this->kosten            = floatval( $workshop['kosten'] );
			$this->aantal            = intval( $workshop['aantal'] );
			$this->contact           = htmlspecialchars_decode( $workshop['contact'] );
			$this->email             = htmlspecialchars_decode( $workshop['email'] );
			$this->telnr             = htmlspecialchars_decode( $workshop['telnr'] );
			$this->organisatie       = htmlspecialchars_decode( $workshop['organisatie'] );
			$this->organisatie_email = htmlspecialchars_decode( $workshop['organisatie_email'] );
			$this->organisatie_adres = htmlspecialchars_decode( $workshop['organisatie_adres'] );
			$this->programma         = htmlspecialchars_decode( $workshop['programma'] );
			$this->docent            = $workshop['docent'];
			$this->naam              = $workshop['naam'];
			$this->aanvraag_id       = $workshop['aanvraag_id'];
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
	 * @param bool $volledig Als true dan voornaam en achternaam, anders alleen voornaam.
	 * @return string De naam van de docent.
	 */
	public function get_docent_naam( bool $volledig = true ) : string {
		$docenten = [];
		foreach ( explode( ';', $this->docent ) as $docent_item ) {
			if ( is_numeric( $docent_item ) ) {
				$docenten[] = $volledig ?
					get_user_by( 'id', intval( $docent_item ) )->display_name :
					get_user_by( 'id', intval( $docent_item ) )->first_name;
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
		$wpdb->replace(
			"{$wpdb->prefix}kleistad_workshops",
			[
				'id'                => $this->id,
				'datum'             => date( 'Y-m-d', $this->datum ),
				'aanvraagdatum'     => date( 'Y-m-d', $this->aanvraagdatum ),
				'start_tijd'        => date( 'H:i', $this->start_tijd ),
				'eind_tijd'         => date( 'H:i', $this->eind_tijd ),
				'contact'           => trim( $this->contact ),
				'email'             => trim( $this->email ),
				'telnr'             => trim( $this->telnr ),
				'organisatie'       => trim( $this->organisatie ),
				'organisatie_email' => trim( $this->organisatie_email ),
				'organisatie_adres' => trim( $this->organisatie_adres ),
				'programma'         => trim( $this->programma ),
				'docent'            => $this->docent,
				'naam'              => $this->naam,
				'aanvraag_id'       => $this->aanvraag_id,
				'aantal'            => $this->aantal,
				'definitief'        => intval( $this->definitief ),
				'betaling_email'    => intval( $this->betaling_email ),
				'vervallen'         => intval( $this->vervallen ),
				'communicatie'      => maybe_serialize( $this->communicatie ),
				'technieken'        => wp_json_encode( $this->technieken ),
				'werkplekken'       => wp_json_encode( $this->werkplekken ),
				'kosten'            => $this->kosten,
			]
		);
		$this->id   = $wpdb->insert_id;
		$this->code = self::DEFINITIE['prefix'] . $this->id;
		$timezone   = new DateTimeZone( get_option( 'timezone_string' ) ?: 'Europe/Amsterdam' );

		try {
			$afspraak               = new Afspraak( sprintf( '%s%06d', self::AFSPRAAK_PREFIX, $this->id ) );
			$afspraak->titel        = $this->naam;
			$afspraak->definitief   = $this->definitief;
			$afspraak->vervallen    = $this->vervallen;
			$afspraak->start        = new DateTime( date( 'Y-m-d H:i', $this->start_tijd ), $timezone );
			$afspraak->eind         = new DateTime( date( 'Y-m-d H:i', $this->eind_tijd ), $timezone );
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
	 * @suppressWarnings(PHPMD.BooleanArgumentFlag,PHPMD.ElseExpression)
	 */
	public function get_statustekst( bool $uitgebreid = false ) : string {
		static $verloop = 0;
		if ( ! $verloop ) {
			$verloop = strtotime( 'tomorrow' ) - opties()['verloopaanvraag'] * WEEK_IN_SECONDS;
		}
		if ( $this->vervallen ) {
			$status = self::VERVALLEN;
		} elseif ( $this->definitief ) {
			$status = self::DEFINITIEF;
		} elseif ( $this->aanvraagdatum < $verloop ) {
			$status = self::VERVALT;
		} else {
			$status = self::CONCEPT;
		}
		return $uitgebreid ? "$this->naam $status" : $status;
	}

	/**
	 * De verval datum
	 *
	 * @return int
	 */
	public function get_verval_datum(): int {
		return $this->datum;
	}

	/**
	 * De regels voor de factuur.
	 *
	 * @return Orderregels De regel.
	 */
	public function get_factuurregels() : Orderregels {
		$orderregels = new Orderregels();
		$orderregels->toevoegen( new Orderregel( "$this->naam op " . wp_date( 'l d-m-y', $this->datum ) . ", $this->aantal deelnemers", 1, $this->kosten ) );
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
				'workshop_datum'      => wp_date( 'l d-m-y', $this->datum ),
				'workshop_start_tijd' => date( 'H:i', $this->start_tijd ),
				'workshop_eind_tijd'  => date( 'H:i', $this->eind_tijd ),
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
