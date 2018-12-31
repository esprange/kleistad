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

/**
 * Kleistad Cursus class.
 *
 * @since 4.0.87
 */
class Kleistad_Cursus extends Kleistad_Entity {

	/**
	 * Berekent de nog beschikbare ruimte voor een cursus a.d.h.v. de inschrijvingen.
	 *
	 * @return int nog beschikbare ruimte.
	 */
	private function ruimte() {
		$inschrijvingen = Kleistad_Inschrijving::all();

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
	 * @since 4.0.87
	 *
	 * @global object $wpdb WordPress database.
	 * @param int $cursus_id (optional) cursus welke geladen moet worden.
	 */
	public function __construct( $cursus_id = null ) {
		global $wpdb;
		$options      = Kleistad::get_options();
		$default_data = [
			'id'              => null,
			'naam'            => 'nog te definiëren cursus',
			'start_datum'     => '',
			'eind_datum'      => '',
			'start_tijd'      => '',
			'eind_tijd'       => '',
			'docent'          => '',
			'technieken'      => wp_json_encode( [] ),
			'vervallen'       => 0,
			'vol'             => 0,
			'techniekkeuze'   => 0,
			'inschrijfkosten' => $options['cursusinschrijfprijs'],
			'cursuskosten'    => $options['cursusprijs'],
			'inschrijfslug'   => 'kleistad_email_cursus_aanvraag',
			'indelingslug'    => 'kleistad_email_cursus_ingedeeld',
			'maximum'         => 12,
			'meer'            => 0,
			'tonen'           => 0,
		];
		if ( is_null( $cursus_id ) ) {
			$this->_data = $default_data;
		} else {
			$this->_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_cursussen WHERE id = %d", $cursus_id ), ARRAY_A );
		}
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( $attribuut ) {
		switch ( $attribuut ) {
			case 'technieken':
				return ( 'null' === $this->_data['technieken'] ) ? [] : json_decode( $this->_data['technieken'], true );
			case 'start_datum':
			case 'eind_datum':
			case 'start_tijd':
			case 'eind_tijd':
				return strtotime( $this->_data[ $attribuut ] );
			case 'vol':
				return 1 === intval( $this->_data['vol'] ) || 0 === $this->ruimte();
			case 'vervallen':
			case 'techniekkeuze':
			case 'meer':
			case 'tonen':
				return 1 === intval( $this->_data[ $attribuut ] );
			case 'array':
				return $this->_data;
			case 'ruimte':
				return $this->ruimte();
			case 'code':
				return "C{$this->_data['id']}";
			case 'event_id':
				return sprintf( 'kleistadcursus%06d', $this->_data['id'] );
			default:
				return $this->_data[ $attribuut ];
		}
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( $attribuut, $waarde ) {
		switch ( $attribuut ) {
			case 'technieken':
				$this->_data[ $attribuut ] = wp_json_encode( $waarde );
				break;
			case 'start_datum':
			case 'eind_datum':
				$this->_data[ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			case 'start_tijd':
			case 'eind_tijd':
				$this->_data[ $attribuut ] = date( 'H:i', $waarde );
				break;
			case 'vervallen':
			case 'vol':
			case 'techniekkeuze':
			case 'meer':
			case 'tonen':
				$this->_data[ $attribuut ] = $waarde ? 1 : 0;
				break;
			default:
				$this->_data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Bewaarde de cursus in de database.
	 *
	 * @since 4.0.87
	 *
	 * @global object $wpdb WordPress database.
	 * @return int Het cursus id.
	 */
	public function save() {
		global $wpdb;
		$wpdb->replace( "{$wpdb->prefix}kleistad_cursussen", $this->_data );
		$this->id = $wpdb->insert_id;

		try {
			$event             = new Kleistad_Event( $this->event_id );
			$event->properties = [
				'docent'     => $this->docent,
				'technieken' => $this->technieken,
				'code'       => "C$this->id",
				'id'         => $this->id,
				'class'      => __CLASS__,
			];
			$event->titel      = 'cursus';
			$event->definitief = $this->tonen;
			$event->vervallen  = $this->vervallen;
			$timezone          = new DateTimeZone( get_option( 'timezone_string' ) );
			$event->start      = new DateTime( $this->_data['start_datum'] . ' ' . $this->_data['start_tijd'], $timezone );
			$event->eind       = new DateTime( $this->_data['start_datum'] . ' ' . $this->_data['eind_tijd'], $timezone );
			if ( $this->start_datum !== $this->eind_datum ) {
				$event->herhalen( new DateTime( $this->_data['eind_datum'] . ' ' . $this->_data['eind_tijd'], $timezone ) );
			}
			$event->save();
		} catch ( Exception $e ) {
			error_log ( $e->getMessage() ); // phpcs:ignore
		}

		return $this->id;
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
		$filter          = $open ? ' WHERE tonen = 1 AND eind_datum > CURRENT_DATE' : '';
		$cursussen_tabel = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_cursussen $filter ORDER BY start_datum DESC, start_tijd ASC", ARRAY_A ); // WPCS: unprepared SQL OK.
		foreach ( $cursussen_tabel as $cursus ) {
			$arr[ $cursus['id'] ] = new Kleistad_Cursus( $cursus['id'] );
		}
		return $arr;
	}
}
