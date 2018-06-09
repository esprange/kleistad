<?php
/**
 * The file that defines the oven class
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Kleistad Oven class.
 *
 * A class definition that define the attributes of a single oven class.
 *
 * @since 4.0.87
 *
 * @see n.a.
 * @link URL
 */
class Kleistad_Oven extends Kleistad_Entity {

	/**
	 * Constructor
	 *
	 * Constructor, Long description.
	 *
	 * @since 4.0.87
	 *
	 * @param int $oven_id (optional) oven to load.
	 * @global object $wpdb WordPress database.
	 * @return null.
	 */
	public function __construct( $oven_id = null ) {
		global $wpdb;
		$default_data = [
			'id'              => null,
			'naam'            => '',
			'kosten'          => 0,
			'beschikbaarheid' => wp_json_encode( [] ),
		];
		$this->_data  = $default_data;
		if ( ! is_null( $oven_id ) ) {
			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_ovens WHERE id = %d", $oven_id ), ARRAY_A ); // WPCS: unprepared SQL OK.
			if ( ! is_null( $result ) ) {
				$this->_data = $result;
			}
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
			case 'beschikbaarheid':
				return json_decode( $this->_data[ $attribuut ], true );
			case 'zondag':
			case 'maandag':
			case 'dinsdag':
			case 'woensdag':
			case 'donderdag':
			case 'vrijdag':
			case 'zaterdag':
				return ( array_search( $attribuut, json_decode( $this->_data['beschikbaarheid'], true ), true ) !== false );
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
	 * @return void
	 */
	public function __set( $attribuut, $waarde ) {
		switch ( $attribuut ) {
			case 'beschikbaarheid':
				$this->_data[ $attribuut ] = wp_json_encode( $waarde );
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
	 * @return int The id of the oven.
	 */
	public function save() {
		global $wpdb;
		$wpdb->replace( "{$wpdb->prefix}kleistad_ovens", $this->_data );
		$this->id = $wpdb->insert_id;
		return $this->id;
	}

	/**
	 * Return alle ovens.
	 *
	 * @return array ovens.
	 */
	public static function all() {
		global $wpdb;
		$ovens = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_ovens", ARRAY_A ); // WPCS: unprepared SQL OK.
		foreach ( $ovens as $oven ) {
			$arr[ $oven['id'] ] = new Kleistad_Oven();
			$arr[ $oven['id'] ]->load( $oven );
		}
		return $arr;
	}
}
