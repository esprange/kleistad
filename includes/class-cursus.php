<?php
/**
 * Definieer de cursus class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Kleistad Cursus class.
 */
class Cursus {

	use WerkplekReservering;

	public const AFSPRAAK_PREFIX = 'kleistadcursus';

	/**
	 * Cursus ident
	 *
	 * @var int Het id van de cursus.
	 */
	public int $id = 0;

	/**
	 * Cursus titel
	 *
	 * @var string Cursus naam.
	 */
	public string $naam = '';

	/**
	 * Cursus code
	 *
	 * @var string Cursus code.
	 */
	public string $code = '';

	/**
	 * Start van de cursus
	 *
	 * @var int Start datum.
	 */
	public int $start_datum;

	/**
	 * Eind van de cursus
	 *
	 * @var int Eind datum.
	 */
	public int $eind_datum;

	/**
	 * Datum nadat er ruimte is ontstaan in de cursus
	 *
	 * @var int Ruimte datum.
	 */
	public int $ruimte_datum = 0;

	/**
	 * De lesdatums van de cursus
	 *
	 * @var array Les datums.
	 */
	public array $lesdatums = [];

	/**
	 * Aanvangstijd cursus
	 *
	 * @var int Start tijd.
	 */
	public int $start_tijd;

	/**
	 * Eindtijd cursus
	 *
	 * @var int Eind tijd.
	 */
	public int $eind_tijd;

	/**
	 * Docent van de cursus
	 *
	 * @var string Docent of docenten.
	 */
	public string $docent = '';

	/**
	 * Gebruikte technieken
	 *
	 * @var array Technieken in de cursus.
	 */
	public array $technieken = [];

	/**
	 * Cursus status
	 *
	 * @var bool True als vervallen.
	 */
	public bool $vervallen = false;

	/**
	 * Cursus ruimte status
	 *
	 * @var bool True als vol
	 */
	public bool $vol = false;

	/**
	 * Cursist techniek keuze
	 *
	 * @var bool True als keuze verplicht.
	 */
	public bool $techniekkeuze = false;

	/**
	 * Benodigde werkplekken voor cursus
	 *
	 * @var array De te reserveren werkplekken.
	 */
	public array $werkplekken = [];

	/**
	 * Inschrijfkosten cursus
	 *
	 * @var float Inschrijfkosten of 0.0
	 */
	public float $inschrijfkosten = 0.0;

	/**
	 * Cursus kosten
	 *
	 * @var float Cursuskosten exclusief de inschrijfkosten
	 */
	public float $cursuskosten = 0.0;

	/**
	 * Inschrijf email
	 *
	 * @var string Email slug voor inschrijving.
	 */
	public string $inschrijfslug = 'cursus_aanvraag';

	/**
	 * Indeling email
	 *
	 * @var string Email slug voor indeling.
	 */
	public string $indelingslug = 'cursus_ingedeeld';

	/**
	 * Aantal cursisten
	 *
	 * @var int Maximum aantal cursisten.
	 */
	public int $maximum = 12;

	/**
	 * Meervoudige inschrijving
	 *
	 * @var bool True als inschrijving voor meer cursisten mogelijk.
	 */
	public bool $meer = true;

	/**
	 * Cursus publicatie status
	 *
	 * @var bool True als gepubliceerd.
	 */
	public bool $tonen = false;

	/**
	 * Constructor
	 *
	 * @global object $wpdb WordPress database.
	 * @param int|null   $cursus_id (optioneel) cursus welke geladen moet worden.
	 * @param array|null $load      (optioneel) data waarmee het object geladen kan worden (ivm performance).
	 */
	public function __construct( ?int $cursus_id = null, ?array $load = null ) {
		global $wpdb;
		$this->start_datum     = strtotime( 'today' );
		$this->lesdatums       = [ $this->start_datum ];
		$this->eind_datum      = strtotime( 'today' );
		$this->start_tijd      = strtotime( 'today 09:30' );
		$this->eind_tijd       = strtotime( 'today 12:00' );
		$this->inschrijfkosten = opties()['cursusinschrijfprijs'];
		$this->cursuskosten    = opties()['cursusprijs'];
		if ( ! is_null( $cursus_id ) ) {
			$data = $load ?? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_cursussen WHERE id = %d", $cursus_id ), ARRAY_A );
			if ( ! is_null( $data ) ) {
				$this->id              = intval( $data['id'] );
				$this->naam            = htmlspecialchars_decode( $data['naam'] );
				$this->code            = Inschrijving::DEFINITIE['prefix'] . $cursus_id;
				$this->start_datum     = strtotime( $data['start_datum'] );
				$this->eind_datum      = strtotime( $data['eind_datum'] );
				$this->ruimte_datum    = strtotime( $data['ruimte_datum'] );
				$this->start_tijd      = strtotime( $data['start_tijd'] );
				$this->eind_tijd       = strtotime( $data['eind_tijd'] );
				$this->lesdatums       = array_map( 'strtotime', json_decode( $data['lesdatums'], true ) );
				$this->technieken      = json_decode( $data['technieken'] ?: '[]', true );
				$this->werkplekken     = json_decode( $data['werkplekken'] ?: '[]', true );
				$this->docent          = htmlspecialchars_decode( $data['docent'] );
				$this->maximum         = intval( $data['maximum'] );
				$this->vol             = boolval( $data['vol'] );
				$this->meer            = boolval( $data['meer'] );
				$this->techniekkeuze   = boolval( $data['techniekkeuze'] );
				$this->vervallen       = boolval( $data['vervallen'] );
				$this->tonen           = boolval( $data['tonen'] );
				$this->inschrijfslug   = $data['inschrijfslug'];
				$this->indelingslug    = $data['indelingslug'];
				$this->inschrijfkosten = floatval( $data['inschrijfkosten'] );
				$this->cursuskosten    = floatval( $data['cursuskosten'] );
				if ( empty( $this->lesdatums ) ) {
					$this->lesdatums = $this->start_datum === $this->eind_datum ? [ $this->start_datum ] : [ $this->start_datum, $this->eind_datum ];
				}
			}
		}
	}

	/**
	 * Berekent de nog beschikbare ruimte voor een cursus a.d.h.v. de inschrijvingen.
	 *
	 * @return int nog beschikbare ruimte.
	 */
	public function get_ruimte() : int {
		static $ruimte = [];
		if ( ! defined( 'KLEISTAD_TEST' ) && isset( $ruimte[ $this->id ] ) ) {
			return $ruimte[ $this->id ];
		}
		$aantal = $this->maximum;
		if ( 0 < $this->id ) {
			foreach ( new Inschrijvingen( $this->id, true ) as $inschrijving ) {
				if ( $inschrijving->ingedeeld ) {
					$aantal -= $inschrijving->aantal;
				}
			}
		}
		$ruimte[ $this->id ] = max( $aantal, 0 );
		return $ruimte [ $this->id ];
	}

	/**
	 * Erase de cursus. Eerst op vervallen zetten zodat de afspraak ook geannuleerd wordt.
	 */
	public function erase() :bool {
		$this->vervallen = true;
		$this->save();
		global $wpdb;
		$wpdb->delete( "{$wpdb->prefix}kleistad_cursussen", [ 'id' => $this->id ] );
		return true;
	}

	/**
	 * Start de cursus binnenkort ?
	 *
	 * @return bool
	 */
	public function is_binnenkort() : bool {
		return strtotime( '+7 days 0:00' ) >= $this->start_datum;
	}

	/**
	 * Kan er op een wachtlijst worden ingeschreven ?
	 *
	 * @return bool
	 */
	public function is_wachtbaar() : bool {
		return $this->start_datum > strtotime( 'tomorrow + 1 day' );
	}

	/**
	 * Is de cursus actief ?
	 *
	 * @return bool
	 */
	public function is_lopend() : bool {
		return $this->start_datum < strtotime( 'today' );
	}

	/**
	 * Is de cursus open voor inschrijvingen ?
	 *
	 * @return bool
	 */
	public function is_open() : bool {
		return ! $this->vervallen && ( ! $this->vol || $this->is_wachtbaar() );
	}

	/**
	 * Bereken het bedrag om ingedeeld te worden bij de cursus.
	 *
	 * @return float
	 */
	public function get_bedrag() : float {
		if ( $this->is_binnenkort() ) {
			if ( 0.01 < $this->inschrijfkosten ) {
				return $this->inschrijfkosten + $this->cursuskosten;
			}
		} elseif ( 0 < $this->inschrijfkosten ) {
			return $this->inschrijfkosten;
		}
		return $this->cursuskosten;
	}

	/**
	 * Hulp functie voor de oudere cursussen (voor 6.1.1 werd de naam ingevuld, nu het nummer ).
	 *
	 * @param bool $volledig True als volledige naam, anders alleen de voornaam.
	 * @return string De naam van de docent.
	 */
	public function get_docent_naam( bool $volledig = true ) : string {
		if ( is_numeric( $this->docent ) ) {
			return $volledig ?
				get_user_by( 'id', intval( $this->docent ) )->display_name :
				get_user_by( 'id', intval( $this->docent ) )->first_name;
		}
		return $this->docent;
	}

	/**
	 * Geef de status van de cursus terug.
	 *
	 * @return string
	 */
	public function get_statustekst() : string {
		$vandaag = strtotime( 'today' );
		return $this->vervallen ? 'vervallen' :
			( $this->eind_datum < $vandaag ? 'voltooid' :
				( $this->start_datum < $vandaag ? 'actief' : 'nieuw' ) );
	}

	/**
	 * Bereken de kosten van een lopende cursus.
	 *
	 * @param int $vanafdatum De datum vanaf dat de les gevolgd gaat worden.
	 * @return array De advies kosten en het aantal resterende lessen.
	 */
	public function lopend( int $vanafdatum ) : array {
		$aantal_lessen    = count( $this->lesdatums );
		$totaal_kosten    = $this->inschrijfkosten + $this->cursuskosten;
		$aantal_resterend = 0;
		foreach ( $this->lesdatums as $lesdatum ) {
			if ( $lesdatum >= $vanafdatum ) {
				$aantal_resterend++;
			}
		}
		return [
			'lessen'      => $aantal_lessen,
			'lessen_rest' => $aantal_resterend,
			'kosten'      => round( $totaal_kosten * $aantal_resterend / $aantal_lessen * 2 ) / 2,
		];
	}

	/**
	 * Bewaarde de cursus in de database.
	 *
	 * @global object $wpdb WordPress database.
	 * @return int Het cursus id.
	 */
	public function save() : int {
		global $wpdb;
		$wpdb->replace(
			"{$wpdb->prefix}kleistad_cursussen",
			[
				'id'              => $this->id,
				'naam'            => $this->naam,
				'docent'          => $this->docent,
				'maximum'         => $this->maximum,
				'technieken'      => wp_json_encode( $this->technieken ),
				'werkplekken'     => wp_json_encode( $this->werkplekken ),
				'lesdatums'       => wp_json_encode(
					array_map(
						function( $item ) {
							return date( 'Y-m-d', $item );
						},
						$this->lesdatums
					)
				),
				'start_datum'     => date( 'Y-m-d', $this->start_datum ),
				'eind_datum'      => date( 'Y-m-d', $this->eind_datum ),
				'ruimte_datum'    => date( 'Y-m-d H:i:s', $this->ruimte_datum ),
				'start_tijd'      => date( 'H:i', $this->start_tijd ),
				'eind_tijd'       => date( 'H:i', $this->eind_tijd ),
				'vol'             => intval( $this->vol ),
				'meer'            => intval( $this->meer ),
				'vervallen'       => intval( $this->vervallen ),
				'tonen'           => intval( $this->tonen ),
				'techniekkeuze'   => intval( $this->techniekkeuze ),
				'inschrijfslug'   => $this->indelingslug,
				'indelingslug'    => $this->indelingslug,
				'inschrijfkosten' => $this->inschrijfkosten,
				'cursuskosten'    => $this->cursuskosten,
			]
		);
		$this->id = $wpdb->insert_id;
		$timezone = new DateTimeZone( get_option( 'timezone_string' ) ?: 'Europe/Amsterdam' );
		try {
			$afspraak             = new Afspraak( sprintf( '%s%06d', self::AFSPRAAK_PREFIX, $this->id ) );
			$afspraak->titel      = $this->naam;
			$afspraak->definitief = $this->tonen;
			$afspraak->vervallen  = $this->vervallen;
			$afspraak->start      = new DateTime( date( 'Y-m-d', $this->start_datum ) . ' ' . date( 'H:i', $this->start_tijd ), $timezone );
			$afspraak->eind       = new DateTime( date( 'Y-m-d', $this->start_datum ) . ' ' . date( 'H:i', $this->eind_tijd ), $timezone );
			if ( 1 < count( $this->lesdatums ) ) {
				$datums = [];
				foreach ( $this->lesdatums as $lesdatum ) {
					$datums[] = new DateTime( date( 'Y-m-d', $lesdatum ) . ' ' . date( 'H:i', $this->start_tijd ), $timezone );
				}
				sort( $datums );
				$afspraak->set_patroon( $datums );
			}
			$afspraak->save();
		} catch ( Exception $e ) {
			fout( __CLASS__, $e->getMessage() );
		}
		return $this->id;
	}

	/**
	 * Registreer dat de cursus nu vol is. Aanmeldingen die niet ingedeeld / geannuleerd zijn gaan naar de wachtlijst.
	 */
	public function set_vol() : void {
		$this->ruimte_datum = 0;
		$this->vol          = true;
		$this->save();
		foreach ( new Inschrijvingen( $this->id, true ) as $inschrijving ) {
			$inschrijving->actie->naar_wachtlijst();
		}
	}

	/**
	 * Registreer dat er weer ruimte beschikbaar is gekomen.
	 */
	public function set_ruimte() : void {
		$this->ruimte_datum = time();
		$this->vol          = false;
		$this->save();
	}

	/**
	 * Update de werkplek reserveringen
	 *
	 * @return string
	 */
	public function update_werkplekken() : string {
		$this->verwijder_werkplekken( $this->code );
		if ( $this->vervallen ) {
			return '';
		}
		$bericht = '';
		$dagdeel = bepaal_dagdelen( $this->start_tijd, $this->eind_tijd )[0];
		foreach ( $this->lesdatums as $datum ) {
			$result  = $this->reserveer_werkplekken( $this->code, 'cursus', $this->werkplekken, $datum, $dagdeel );
			$bericht = $bericht ?: $result;
		}
		return $bericht;
	}
}
