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

/**
 * Kleistad Cursus class.
 *
 * @property int    id
 * @property string naam
 * @property string code
 * @property int    start_datum
 * @property int    eind_datum
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
 * @property string event_id
 */
class Cursus extends Entity {

	/**
	 * Berekent de nog beschikbare ruimte voor een cursus a.d.h.v. de inschrijvingen.
	 *
	 * @return int nog beschikbare ruimte.
	 */
	public function ruimte() {
		$inschrijvingen = \Kleistad\Inschrijving::all();

		$aantal = $this->maximum;

		foreach ( $inschrijvingen as $inschrijving ) {

			if ( array_key_exists( $this->id, $inschrijving ) ) {
				if ( $inschrijving[ $this->id ]->geannuleerd ) {
					continue;
				}
				if ( $inschrijving[ $this->id ]->ingedeeld ) {
					$aantal = $aantal - $inschrijving[ $this->id ]->aantal;
				}
			}
		}
		return max( $aantal, 0 );
	}

	/**
	 * Constructor
	 *
	 * @global object $wpdb WordPress database.
	 * @param int $cursus_id (optional) cursus welke geladen moet worden.
	 */
	public function __construct( $cursus_id = null ) {
		global $wpdb;
		$options    = \Kleistad\Kleistad::get_options();
		$this->data = [
			'id'              => null,
			'naam'            => '',
			'start_datum'     => date( 'Y-m-d' ),
			'eind_datum'      => date( 'Y-m-d' ),
			'lesdatums'       => wp_json_encode( [ date( 'Y-m-d' ) ] ),
			'start_tijd'      => '09:30',
			'eind_tijd'       => '12:00',
			'docent'          => '',
			'technieken'      => wp_json_encode( [] ),
			'vervallen'       => 0,
			'vol'             => 0,
			'techniekkeuze'   => 0,
			'inschrijfkosten' => $options['cursusinschrijfprijs'],
			'cursuskosten'    => $options['cursusprijs'],
			'inschrijfslug'   => 'cursus_aanvraag',
			'indelingslug'    => 'cursus_ingedeeld',
			'maximum'         => 12,
			'meer'            => 0,
			'tonen'           => 0,
		];
		if ( ! is_null( $cursus_id ) ) {
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_cursussen WHERE id = %d", $cursus_id ), ARRAY_A );
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
	public function __get( $attribuut ) {
		switch ( $attribuut ) {
			case 'technieken':
				return json_decode( $this->data[ $attribuut ], true );
			case 'lesdatums':
				return array_map(
					function( $item ) {
						return strtotime( $item );
					},
					json_decode( $this->data[ $attribuut ], true )
				);
			case 'start_datum':
			case 'eind_datum':
			case 'start_tijd':
			case 'eind_tijd':
				return strtotime( $this->data[ $attribuut ] );
			case 'vol':
				return boolval( $this->data[ $attribuut ] ) || 0 === $this->ruimte();
			case 'vervallen':
			case 'techniekkeuze':
			case 'meer':
			case 'tonen':
				return boolval( $this->data[ $attribuut ] );
			case 'code':
				return "C{$this->data['id']}";
			case 'event_id':
				return sprintf( 'kleistadcursus%06d', $this->data['id'] );
			default:
				return is_string( $this->data[ $attribuut ] ) ? htmlspecialchars_decode( $this->data[ $attribuut ] ) : $this->data[ $attribuut ];
		}
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( $attribuut, $waarde ) {
		switch ( $attribuut ) {
			case 'technieken':
				$this->data[ $attribuut ] = wp_json_encode( $waarde );
				break;
			case 'lesdatums':
				$this->data[ $attribuut ] = wp_json_encode(
					array_map(
						function( $item ) {
							return date( 'Y-m-d', $item );
						},
						$waarde
					)
				);
				break;
			case 'start_datum':
			case 'eind_datum':
				$this->data[ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			case 'start_tijd':
			case 'eind_tijd':
				$this->data[ $attribuut ] = date( 'H:i', $waarde );
				break;
			default:
				$this->data[ $attribuut ] = is_string( $waarde ) ? trim( $waarde ) : ( is_bool( $waarde ) ? (int) $waarde : $waarde );
		}
	}

	/**
	 * Hulp functie voor de oudere cursussen (voor 6.1.1 werd de naam ingevuld, nu het nummer ).
	 *
	 * @return string De naam van de docent.
	 */
	public function docent_naam() {
		if ( is_numeric( $this->docent ) ) {
			return get_user_by( 'id', intval( $this->docent ) )->display_name;
		} else {
			return $this->docent;
		}
	}

	/**
	 * Bereken de kosten van een lopende cursus.
	 *
	 * @param int $vanafdatum De datum vanaf dat de les gevolgd gaat worden.
	 * @return array De advies kosten en het aantal resterende lessen.
	 */
	public function lopend( $vanafdatum ) {
		$aantal_lessen           = count( $this->lesdatums );
		$totaal_kosten           = $this->inschrijfkosten + $this->cursuskosten;
		$aantal_lessen_resterend = 0;
		foreach ( $this->lesdatums as $lesdatum ) {
			if ( $lesdatum >= $vanafdatum ) {
				$aantal_lessen_resterend++;
			}
		}
		return [
			'lessen'      => $aantal_lessen,
			'lessen_rest' => $aantal_lessen_resterend,
			'kosten'      => round( $totaal_kosten * $aantal_lessen_resterend / $aantal_lessen * 2 ) / 2,
		];
	}

	/**
	 * Bewaarde de cursus in de database.
	 *
	 * @global object $wpdb WordPress database.
	 * @return int Het cursus id.
	 */
	public function save() {
		global $wpdb;
		$wpdb->replace( "{$wpdb->prefix}kleistad_cursussen", $this->data );
		$this->id = $wpdb->insert_id;
		$timezone = new \DateTimeZone( get_option( 'timezone_string' ) ?: 'Europe/Amsterdam' );

		try {
			$event             = new \Kleistad\Event( $this->event_id );
			$event->properties = [
				'docent'     => $this->docent_naam(),
				'technieken' => $this->technieken,
				'code'       => "C$this->id",
				'id'         => $this->id,
				'class'      => __CLASS__,
			];
			$event->titel      = $this->naam;
			$event->definitief = $this->tonen;
			$event->vervallen  = $this->vervallen;
			$event->start      = new \DateTime( $this->data['start_datum'] . ' ' . $this->data['start_tijd'], $timezone );
			$event->eind       = new \DateTime( $this->data['start_datum'] . ' ' . $this->data['eind_tijd'], $timezone );
			switch ( count( $this->lesdatums ) ) {
				case 0: // Oude cursus, er zijn nog geen lesdatums toegevoegd.
					if ( $this->start_datum !== $this->eind_datum ) {
						$event->herhalen( new \DateTime( $this->data['eind_datum'] . ' ' . $this->data['eind_tijd'], $timezone ) );
					}
					break;
				case 1: // Geen recurrence, er is maar één lesdatum.
					break;
				default:
					$datums = [];
					foreach ( $this->lesdatums as $lesdatum ) {
						$datums[] = new \DateTime( date( 'Y-m-d', $lesdatum ) . ' ' . $this->data['start_tijd'], $timezone );
					}
					$event->patroon( $datums );
					break;
			}
			$event->save();
		} catch ( \Exception $e ) {
			error_log ( $e->getMessage() ); // phpcs:ignore
		}
		return $this->id;
	}

	/**
	 * Verwijder de cursus.
	 *
	 * @return bool True als de cursus verwijderd kan worden.
	 */
	public function verwijder() {
		global $wpdb;
		$inschrijvingen = \Kleistad\Inschrijving::all();
		foreach ( $inschrijvingen as $inschrijving ) {
			if ( array_key_exists( $this->id, $inschrijving ) ) {
				return false; // Er is al een inschrijving dus verwijderen is niet meer mogelijk.
			}
		}
		if ( $wpdb->delete( "{$wpdb->prefix}kleistad_cursussen", [ 'id' => $this->id ] ) ) {
			try {
				$event = new \Kleistad\Event( $this->event_id );
				$event->delete();
			} catch ( \Exception $e ) {
				unset( $e ); // phpcs:ignore
			}
		} else {
			return false;
		};
		return true;
	}

	/**
	 * Return alle cursussen.
	 *
	 * @global object $wpdb WordPress database.
	 * @param bool $open Toon alleen de open cursussen if true.
	 * @return array cursussen.
	 */
	public static function all( $open = false ) {
		global $wpdb;
		$arr             = [];
		$filter          = $open ? ' WHERE eind_datum > CURRENT_DATE' : '';
		$cursussen_tabel = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_cursussen $filter ORDER BY start_datum DESC, start_tijd ASC", ARRAY_A ); // phpcs:ignore
		foreach ( $cursussen_tabel as $cursus ) {
			$arr[ $cursus['id'] ] = new \Kleistad\Cursus( $cursus['id'] );
		}
		return $arr;
	}
}
