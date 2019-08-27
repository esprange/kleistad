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
 *
 * @property int    id
 * @property string naam
 * @property float  kosten
 * @property array  beschikbaarheid
 */
class Kleistad_Oven extends Kleistad_Entity {

	const REGELING = 'kleistad_regeling';

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
		$this->data   = $default_data;
		if ( ! is_null( $oven_id ) ) {
			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_ovens WHERE id = %d", $oven_id ), ARRAY_A ); // phpcs:ignore
			if ( ! is_null( $result ) ) {
				$this->data = $result;
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
				return json_decode( $this->data[ $attribuut ], true );
			case 'zondag':
			case 'maandag':
			case 'dinsdag':
			case 'woensdag':
			case 'donderdag':
			case 'vrijdag':
			case 'zaterdag':
				return ( array_search( $attribuut, json_decode( $this->data['beschikbaarheid'], true ), true ) !== false );
			default:
				return $this->data[ $attribuut ];
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
				$this->data[ $attribuut ] = wp_json_encode( $waarde );
				break;
			default:
				$this->data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Bepaal de kosten van het stoken van de oven en pas een eventuele regeling toe.
	 *
	 * @param  int   $stoker_id   De stoker.
	 * @param  float $percentage  Het percentage van de stook.
	 * @return float De kosten.
	 */
	public function stookkosten( $stoker_id, $percentage ) {
		$regelingen = get_user_meta( $stoker_id, self::REGELING, true );
		$kosten     = $percentage * ( $regelingen[ $this->data['id'] ] ?? $this->data['kosten'] ) / 100;
		return round( $kosten, 2 );
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
		$wpdb->replace( "{$wpdb->prefix}kleistad_ovens", $this->data );
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
		$ovens = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_ovens", ARRAY_A ); // phpcs:ignore
		foreach ( $ovens as $oven ) {
			$arr[ $oven['id'] ] = new Kleistad_Oven();
			$arr[ $oven['id'] ]->load( $oven );
		}
		return $arr;
	}
}
