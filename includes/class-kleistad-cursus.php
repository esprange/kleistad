<?php
/**
 * The file that defines the cursus class
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Kleistad Cursus class.
 *
 * A class definition that define the attributes of a single cursus class.
 *
 * @since 4.0.87
 *
 * @see n.a.
 * @link URL
 */
class Kleistad_Cursus extends Kleistad_Entity {

	/**
	 * Berekent de nog beschikbare ruimte voor een cursus.
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
		return $aantal;
	}

	/**
	 * Constructor
	 *
	 * Constructor, Long description.
	 *
	 * @since 4.0.87
	 *
	 * @param int $cursus_id (optional) cursus to load.
	 * @return null.
	 */
	public function __construct( $cursus_id = null ) {
		global $wpdb;
		$options      = get_option( 'kleistad-opties' );
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
	 * Getter, using the magic function
	 *
	 * Get attribuut from the object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut name.
	 * @return mixed Attribute value.
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
			default:
				return $this->_data[ $attribuut ];
		}
	}

	/**
	 * Setter, using the magic function
	 *
	 * Set attribuut from the object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut name.
	 * @param mixed  $waarde Attribuut value.
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
	 * Save the data
	 *
	 * Saves the data to the database.
	 *
	 * @since 4.0.87
	 *
	 * @global object $wpdb WordPress database.
	 * @return int The id of the cursus.
	 */
	public function save() {
		global $wpdb;
		$wpdb->replace( "{$wpdb->prefix}kleistad_cursussen", $this->_data );
		$this->id = $wpdb->insert_id;
		return $this->id;
	}

	/**
	 * Return alle cursussen.
	 *
	 * @param bool $open Toon alleen de open cursussen if true.
	 * @return array cursussen.
	 */
	public static function all( $open = false ) {
		global $wpdb;
		$filter          = $open ? ' WHERE tonen = 1 AND eind_datum > CURRENT_DATE' : '';
		$cursussen_tabel = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_cursussen $filter ORDER BY start_datum DESC, start_tijd ASC", ARRAY_A ); // WPCS: unprepared SQL OK.
		foreach ( $cursussen_tabel as $cursus ) {
			$arr[ $cursus['id'] ] = new Kleistad_Cursus( $cursus['id'] );
		}
		return $arr;
	}
}
