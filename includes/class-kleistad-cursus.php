<?php
/**
 * The file that defines the cursus related classes
 *
 * Several class definitions for cursus related classes: the cursus, a collection of cursussen,
 * a inschrijving, a collection of inschrijvingen.
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
		$options = get_option( 'kleistad-opties' );
		$default_data = [
			'id' => null,
			'naam' => 'nog te definiëren cursus',
			'start_datum' => '',
			'eind_datum' => '',
			'start_tijd' => '',
			'eind_tijd' => '',
			'docent' => '',
			'technieken' => wp_json_encode( [] ),
			'vervallen' => 0,
			'vol' => 0,
			'techniekkeuze' => 0,
			'inschrijfkosten' => $options['cursusinschrijfprijs'],
			'cursuskosten' => $options['cursusprijs'],
			'inschrijfslug' => 'kleistad_email_cursus_aanvraag',
			'indelingslug' => 'kleistad_email_cursus_ingedeeld',
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
			case 'vervallen':
			case 'vol':
			case 'techniekkeuze':
				return 1 === intval( $this->_data[ $attribuut ] );
			case 'array':
				return $this->_data;
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
		$this->_data['id'] = $wpdb->insert_id;
		return $this->_data['id'];
	}
}

/**
   * Collection of Cursus
   *
   * Collection of Cursus, loaded from the database.
   *
   * @since 4.0.87
   *
   * @see class Kleistad_Cursus
   * @link URL
    */
class Kleistad_Cursussen extends Kleistad_EntityStore {

	/**
	 * Constructor
	 *
	 * Loads the data from the database.
	 *
	 * @since 4.0.87
	 *
	 * @global object $wpdb WordPress database.
	 * @return null.
	 */
	public function __construct() {
		global $wpdb;
		$cursussen = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_cursussen ORDER BY start_datum DESC, start_tijd ASC", ARRAY_A ); // WPCS: unprepared SQL OK.
		foreach ( $cursussen as $cursus ) {
			$this->_data[ $cursus['id'] ] = new Kleistad_Cursus( $cursus['id'] );
		}
	}
}

/**
 * Kleistad Inschrijving class.
 *
 * A class definition that define the attributes of a inschrijving class.
 *
 * @since 4.0.87
 *
 * @see n.a.
 * @link URL
 */
class Kleistad_Inschrijving extends Kleistad_Entity {

	/**
	 * Store the cursist id
	 *
	 * @since 4.0.87
	 * @access private
	 * @var int $_cursist_id the wp user id the of cursist.
	 */
	private $_cursist_id;

	/**
	 * Store the cursus id
	 *
	 * @since 4.0.87
	 * @access private
	 * @var int $_cursus_id the id of the cursus in the database.
	 */
	private $_cursus_id;

	/**
	 * Constructor
	 *
	 * Create the inschrijving object for cursus to be provided to cursist.
	 *
	 * @since 4.0.87
	 *
	 * @param int $cursist_id id of the cursist.
	 * @param int $cursus_id id of the cursus.
	 */
	public function __construct( $cursist_id, $cursus_id ) {
		$cursus = new Kleistad_Cursus( $cursus_id );
		$this->_cursist_id = $cursist_id;
		$this->_cursus_id = $cursus_id;

		$default_data = [
			'code' => "C$cursus_id-$cursist_id-" . strftime( '%y%m%d', $cursus->start_datum ),
			'datum' => date( 'Y-m-d' ),
			'technieken' => [],
			'i_betaald' => 0,
			'c_betaald' => 0,
			'ingedeeld' => 0,
			'bericht' => 0,
			'geannuleerd' => 0,
			'opmerking' => '',
		];

		$inschrijvingen = get_user_meta( $this->_cursist_id, 'kleistad_cursus', true );
		if ( is_array( $inschrijvingen ) && ( isset( $inschrijvingen[ $this->_cursus_id ] ) ) ) {
			$this->_data = $inschrijvingen[ $this->_cursus_id ];
			if ( ! isset( $this->_data['geannuleerd'] ) ) {
				$this->_data['geannuleerd'] = 0; // voor oude inschrijvingen.
			}
		} else {
			$this->_data = $default_data;
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
				return ( is_array( $this->_data[ $attribuut ] ) ) ? $this->_data[ $attribuut ] : [];
			case 'datum':
				return strtotime( $this->_data[ $attribuut ] );
			case 'i_betaald':
			case 'c_betaald':
			case 'geannuleerd':
			case 'bericht':
				return 1 === intval( $this->_data[ $attribuut ] );
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
				$this->_data[ $attribuut ] = is_array( $waarde ) ? $waarde : [];
				break;
			case 'datum':
				$this->_data[ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			case 'i_betaald':
			case 'c_betaald':
			case 'geannuleerd':
			case 'bericht':
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
	 */
	public function save() {
		$inschrijvingen = get_user_meta( $this->_cursist_id, 'kleistad_cursus', true );
		$inschrijvingen[ $this->_cursus_id ] = $this->_data;
		update_user_meta( $this->_cursist_id, 'kleistad_cursus', $inschrijvingen );
	}

}

/**
   * Collection of Inschrijving
   *
   * Collection of Inschrijvingen, loaded from the database.
   *
   * @since 4.0.87
   *
   * @see class Kleistad_Inschrijving
   * @link URL
    */
class Kleistad_Inschrijvingen extends Kleistad_EntityStore {

	/**
	 * Constructor
	 *
	 * Loads the data from the database.
	 *
	 * @since 4.0.87
	 *
	 * @return null.
	 */
	public function __construct() {
		$cursisten = get_users(
			[
				'meta_key' => 'kleistad_cursus',
			]
		);
		foreach ( $cursisten as $cursist ) {
			$inschrijvingen = get_user_meta( $cursist->ID, 'kleistad_cursus', true );
			foreach ( $inschrijvingen as $cursus_id => $inschrijving ) {
				$this->_data[ $cursist->ID ][ $cursus_id ] = new Kleistad_Inschrijving( $cursist->ID, $cursus_id );
				$this->_data[ $cursist->ID ][ $cursus_id ]->load( $inschrijving );
			}
		}
	}
}
