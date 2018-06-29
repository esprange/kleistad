<?php
/**
 * De definitie van de oven class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Kleistad Oven class.
 *
 * @since 4.0.87
 */
class Kleistad_Oven extends Kleistad_Entity {

	/**
	 * Constructor
	 *
	 * @since 4.0.87
	 *
	 * @param int $oven_id (optioneel) oven te laden vanuit database.
	 * @global object $wpdb WordPress database.
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
	 * Get attribuut van het object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
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
	 * Set attribuut van het object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
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
	 * Sla de oven op in de database.
	 *
	 * @since 4.0.87
	 *
	 * @global object $wpdb WordPress database.
	 * @return int Het id van de oven.
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
		$arr   = [];
		$ovens = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_ovens", ARRAY_A ); // WPCS: unprepared SQL OK.
		foreach ( $ovens as $oven ) {
			$arr[ $oven['id'] ] = new Kleistad_Oven();
			$arr[ $oven['id'] ]->load( $oven );
		}
		return $arr;
	}
}
