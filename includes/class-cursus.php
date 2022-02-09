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
 *
 * @property int    id
 * @property string naam
 * @property string code
 * @property int    start_datum
 * @property int    eind_datum
 * @property int    ruimte_datum
 * @property array  lesdatums
 * @property int    start_tijd
 * @property int    eind_tijd
 * @property string docent
 * @property array  technieken
 * @property bool   vervallen
 * @property bool   vol
 * @property bool   techniekkeuze
 * @property float  inschrijfkosten
 * @property float  cursuskosten
 * @property string inschrijfslug
 * @property string indelingslug
 * @property int    maximum
 * @property bool   meer
 * @property bool   tonen
 */
class Cursus {

	public const AFSPRAAK_PREFIX = 'kleistadcursus';

	/**
	 * De cursusdata
	 *
	 * @var array $data De ruwe data.
	 */
	private array $data;

	/**
	 * Constructor
	 *
	 * @global object $wpdb WordPress database.
	 * @param int|null   $cursus_id (optioneel) cursus welke geladen moet worden.
	 * @param array|null $load      (optioneel) data waarmee het object geladen kan worden (ivm performance).
	 */
	public function __construct( int $cursus_id = null, ?array $load = null ) {
		global $wpdb;
		$this->data = [
			'id'              => null,
			'naam'            => '',
			'start_datum'     => date( 'Y-m-d' ),
			'eind_datum'      => date( 'Y-m-d' ),
			'ruimte_datum'    => '',
			'lesdatums'       => wp_json_encode( [ date( 'Y-m-d' ) ] ),
			'start_tijd'      => '09:30',
			'eind_tijd'       => '12:00',
			'docent'          => '',
			'technieken'      => wp_json_encode( [] ),
			'vervallen'       => 0,
			'vol'             => 0,
			'techniekkeuze'   => 0,
			'inschrijfkosten' => opties()['cursusinschrijfprijs'],
			'cursuskosten'    => opties()['cursusprijs'],
			'inschrijfslug'   => 'cursus_aanvraag',
			'indelingslug'    => 'cursus_ingedeeld',
			'maximum'         => 12,
			'meer'            => 0,
			'tonen'           => 0,
		];
		if ( ! is_null( $cursus_id ) ) {
			$data = $load ?? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_cursussen WHERE id = %d", $cursus_id ), ARRAY_A );
			if ( ! is_null( $data ) ) {
				$this->data = $data;
				if ( empty( $this->data['lesdatums'] ) ) {
					$this->data['lesdatums'] = wp_json_encode(
						( $this->data['start_datum'] === $this->data['eind_datum'] ) ?
						[ $this->data['start_datum'] ] :
						[ $this->data['start_datum'], $this->data['eind_datum'] ]
					);
				}
			}
		}
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( string $attribuut ) {
		if ( in_array( $attribuut, [ 'start_datum', 'eind_datum', 'ruimte_datum', 'start_tijd', 'eind_tijd' ], true ) ) {
			return strtotime( $this->data[ $attribuut ] );
		}
		if ( in_array( $attribuut, [ 'vol', 'vervallen', 'techniekkeuze', 'meer', 'tonen' ], true ) ) {
			return boolval( $this->data[ $attribuut ] );
		}
		if ( in_array( $attribuut, [ 'inschrijfkosten', 'cursuskosten' ], true ) ) {
			return floatval( $this->data[ $attribuut ] );
		}

		return match ( $attribuut ) {
			'technieken' => json_decode( $this->data[ $attribuut ], true ),
			'lesdatums'  => array_map(
				function ( $item ) {
					return strtotime( $item );
				},
				json_decode( $this->data[ $attribuut ], true )
			),
			'code'       => "C{$this->data['id']}",
			default      => is_numeric( $this->data[ $attribuut ] ) ? intval( $this->data[ $attribuut ] ) :
				( is_string( $this->data[ $attribuut ] ) ? htmlspecialchars_decode( $this->data[ $attribuut ] ) : $this->data[ $attribuut ] ),
		};
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( string $attribuut, mixed $waarde ) {
		$this->data[ $attribuut ] = match ( $attribuut ) {
			'technieken' => wp_json_encode( $waarde ),
			'lesdatums'  => wp_json_encode(
				array_map(
					function ( $item ) {
						return date( 'Y-m-d', $item );
					},
					$waarde
				)
			),
			'start_datum',
			'eind_datum'   => date( 'Y-m-d', $waarde ),
			'ruimte_datum' => date( 'Y-m-d H:i:s', $waarde ),
			'start_tijd',
			'eind_tijd'    => date( 'H:i', $waarde ),
			default        => is_string( $waarde ) ? trim( $waarde ) : ( is_bool( $waarde ) ? (int) $waarde : $waarde ),
		};
	}

	/**
	 * Berekent de nog beschikbare ruimte voor een cursus a.d.h.v. de inschrijvingen.
	 *
	 * @return int nog beschikbare ruimte.
	 */
	public function ruimte() : int {
		$aantal = $this->maximum;
		if ( 0 < $this->id ) {
			foreach ( new Inschrijvingen( $this->id, true ) as $inschrijving ) {
				if ( $inschrijving->ingedeeld ) {
					$aantal -= $inschrijving->aantal;
				}
			}
		}
		return max( $aantal, 0 );
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
	 * Bereken het bedrag om ingedeeld te worden bij de cursus.
	 *
	 * @return float
	 */
	public function bedrag() : float {
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
	 * @return string De naam van de docent.
	 */
	public function docent_naam() : string {
		if ( is_numeric( $this->docent ) ) {
			return get_user_by( 'id', intval( $this->docent ) )->display_name;
		}
		return $this->docent;
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
		$wpdb->replace( "{$wpdb->prefix}kleistad_cursussen", $this->data );
		$this->id = $wpdb->insert_id;
		$timezone = new DateTimeZone( get_option( 'timezone_string' ) ?: 'Europe/Amsterdam' );

		try {
			$afspraak             = new Afspraak( sprintf( '%s%06d', self::AFSPRAAK_PREFIX, $this->id ) );
			$afspraak->titel      = $this->naam;
			$afspraak->definitief = $this->tonen;
			$afspraak->vervallen  = $this->vervallen;
			$afspraak->start      = new DateTime( $this->data['start_datum'] . ' ' . $this->data['start_tijd'], $timezone );
			$afspraak->eind       = new DateTime( $this->data['start_datum'] . ' ' . $this->data['eind_tijd'], $timezone );
			if ( 1 < count( $this->lesdatums ) ) {
				$datums = [];
				foreach ( $this->lesdatums as $lesdatum ) {
					$datums[] = new DateTime( date( 'Y-m-d', $lesdatum ) . ' ' . $this->data['start_tijd'], $timezone );
				}
				sort( $datums );
				$afspraak->patroon( $datums );
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
	public function registreer_vol() {
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
	public function registreer_ruimte() {
		$this->ruimte_datum = time();
		$this->vol          = false;
		$this->save();
	}
}
