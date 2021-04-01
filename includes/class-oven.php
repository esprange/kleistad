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

namespace Kleistad;

/**
 * Kleistad Oven class.
 *
 * @since 4.0.87
 *
 * @property int    id
 * @property string naam
 * @property float  kosten_laag
 * @property float  kosten_midden
 * @property float  kosten_hoog
 * @property array  beschikbaarheid
 */
class Oven {

	const REGELING = 'kleistad_regeling';

	/**
	 * De ovendata
	 *
	 * @var array $data De ruwe data.
	 */
	private array $data;

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
			'kosten_laag'     => 0,
			'kosten_midden'   => 0,
			'kosten_hoog'     => 0,
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
	public function __get( string $attribuut ) {
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
	public function __set( string $attribuut, $waarde ) {
		switch ( $attribuut ) {
			case 'beschikbaarheid':
				$this->data[ $attribuut ] = wp_json_encode( $waarde );
				break;
			default:
				$this->data[ $attribuut ] = is_string( $waarde ) ? trim( $waarde ) : $waarde;
		}
	}

	/**
	 * Bepaal de kosten van het stoken van de oven en pas een eventuele regeling toe.
	 *
	 * @param  int   $stoker_id   De stoker.
	 * @param  float $percentage  Het percentage van de stook.
	 * @param  int   $temperatuur De temperatuur waarbij gestookt wordt.
	 * @return float De kosten.
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 */
	public function stookkosten( int $stoker_id, float $percentage, int $temperatuur ) : float {
		$regelingen = get_user_meta( $stoker_id, self::REGELING, true );
		if ( 0 === $stoker_id ) {
			$kosten = 0;
		} elseif ( isset( $regelingen[ $this->data['id'] ] ) ) {
			$kosten = $regelingen[ $this->data['id'] ];
		} elseif ( $temperatuur < opties()['oven_midden'] ) {
			$kosten = $this->data['kosten_laag'];
		} elseif ( $temperatuur < opties()['oven_hoog'] ) {
			$kosten = $this->data['kosten_midden'];
		} else {
			$kosten = $this->data['kosten_hoog'];
		}
		return round( $percentage * $kosten / 100, 2 );
	}

	/**
	 * Sla de oven op in de database.
	 *
	 * @since 4.0.87
	 *
	 * @global object $wpdb WordPress database.
	 * @return int Het id van de oven.
	 */
	public function save() : int {
		global $wpdb;
		$wpdb->replace( "{$wpdb->prefix}kleistad_ovens", $this->data );
		$this->id = $wpdb->insert_id;
		return $this->id;
	}

}
