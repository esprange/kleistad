<?php
/**
 * Definieer de reservering class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Reservering class.
 *
 * @property int    id
 * @property int    oven_id
 * @property int    jaar
 * @property int    maand
 * @property int    dag
 * @property int    datum
 * @property int    gebruiker_id
 * @property int    temperatuur
 * @property string soortstook
 * @property int    programma
 * @property bool   gemeld
 * @property bool   verwerkt
 * @property bool   actief
 * @property bool   gereserveerd
 * @property int    status
 * @property array  verdeling
 * @property string opmerking
 */
class Reservering extends Entity {

	/**
	 * Soorten stook
	 */
	const ONDERHOUD = 'Onderhoud';
	const GLAZUUR   = 'Glazuur';
	const BISCUIT   = 'Biscuit';
	const OVERIG    = 'Overig';

	/**
	 * Opklimmende status
	 */
	const ONGEBRUIKT    = 'ongebruikt';
	const RESERVEERBAAR = 'reserveerbaar';
	const VERWIJDERBAAR = 'verwijderbaar';
	const ALLEENLEZEN   = 'alleenlezen';
	const WIJZIGBAAR    = 'wijzigbaar';
	const DEFINITIEF    = 'definitief';

	/**
	 * Constructor
	 *
	 * @since 4.0.87
	 *
	 * @param int $oven_id Oven waar de reservering op van toepassing is.
	 * @param int $datum   De unix datum van de reservering.
	 */
	public function __construct( $oven_id, $datum = null ) {
		global $wpdb;

		$this->data = [
			'id'           => null,
			'oven_id'      => $oven_id,
			'jaar'         => 0,
			'maand'        => 0,
			'dag'          => 0,
			'gebruiker_id' => 0,
			'temperatuur'  => 0,
			'soortstook'   => '',
			'programma'    => 0,
			'gemeld'       => 0,
			'verwerkt'     => 0,
			'verdeling'    => wp_json_encode( [ [ 'id' => 0, 'perc' => 100 ] ] ), // phpcs:ignore
			'opmerking'    => '',
		];
		if ( ! is_null( $datum ) ) {
			$resultaat = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}kleistad_reserveringen WHERE oven_id = %d AND jaar= %d AND maand = %d AND dag = %d",
					$oven_id,
					date( 'Y', $datum ),
					date( 'm', $datum ),
					date( 'd', $datum )
				),
				ARRAY_A
			); // phpcs:ignore
			if ( $resultaat ) {
				$this->data = $resultaat;
			} else {
				$this->data['jaar']  = date( 'Y', $datum );
				$this->data['maand'] = date( 'm', $datum );
				$this->data['dag']   = date( 'd', $datum );
			}
		}
	}

	/**
	 * Geef de status terug van de reservering.
	 */
	public function status() {
		if ( ! $this->gereserveerd ) {
			if ( strtotime( "$this->jaar-$this->maand-$this->dag 23:59" ) >= strtotime( 'today' ) || is_super_admin() ) {
				return self::RESERVEERBAAR;
			} else {
				return self::ONGEBRUIKT;
			}
		} else {
			if ( ! $this->verwerkt ) {
				if ( get_current_user_id() === $this->verdeling[0]['id'] || \Kleistad\Roles::override() ) {
					if ( ! $this->datum < strtotime( 'today midnight' ) ) {
						return self::VERWIJDERBAAR;
					} else {
						return self::WIJZIGBAAR;
					}
				} else {
					return self::ALLEENLEZEN;
				}
			}
			return self::DEFINITIEF;
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
			case 'verdeling':
				$verdeling = json_decode( $this->data['verdeling'], true );
				return is_array( $verdeling ) ? $verdeling : [
					[
						'id'   => $this->data['gebruiker_id'],
						'perc' => 100,
					],
				];
			case 'datum':
				return strtotime( $this->data['jaar'] . '-' . $this->data['maand'] . '-' . $this->data['dag'] . ' 00:00' );
			case 'gemeld':
			case 'verwerkt':
				return boolval( $this->data[ $attribuut ] );
			case 'actief':
				return strtotime( $this->data['jaar'] . '-' . $this->data['maand'] . '-' . $this->data['dag'] . ' 00:00' ) < time();
			case 'gereserveerd':
				return ( ! is_null( $this->data['id'] ) );
			case 'jaar':
			case 'maand':
			case 'dag':
			case 'oven_id':
			case 'gebruiker_id':
			case 'temperatuur':
			case 'programma':
				return intval( $this->data[ $attribuut ] );
			default:
				return $this->data[ $attribuut ];
		}
	}

	/**
	 * Registreer de prijs van de stook op een stookdeel.
	 *
	 * @param int   $medestoker_id Het id van de medestoker.
	 * @param float $prijs         De prijs van het stoken.
	 */
	public function prijs( $medestoker_id, $prijs ) {
		$verdeling = json_decode( $this->data['verdeling'], true );
		foreach ( $verdeling as &$stookdeel ) {
			if ( $medestoker_id === $stookdeel['id'] ) {
				$stookdeel['prijs'] = $prijs;
			}
		};
		$this->data['verdeling'] = wp_json_encode( $verdeling );
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
			case 'verdeling':
				$verdeling = [];
				foreach ( $waarde as $stookdeel ) {
					$index = array_search( intval( $stookdeel['id'] ), array_column( $verdeling, 'id' ), true );
					if ( false === $index ) {
						$verdeling[] = [
							'id'   => intval( $stookdeel['id'] ),
							'perc' => intval( $stookdeel['perc'] ),
						];
					} else {
						$verdeling[ $index ]['perc'] += intval( $stookdeel['perc'] );
					}
				}
				$this->data[ $attribuut ] = wp_json_encode( $verdeling );
				break;
			case 'datum':
				$this->data['jaar']  = date( 'Y', $waarde );
				$this->data['maand'] = date( 'm', $waarde );
				$this->data['dag']   = date( 'd', $waarde );
				break;
			case 'gemeld':
			case 'verwerkt':
				$this->data[ $attribuut ] = (int) $waarde;
				break;
			default:
				$this->data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Bewaar de reservering in de database.
	 *
	 * @since 4.0.87
	 *
	 * @global object $wpdb WordPress database.
	 * @return int Het id van de reservering.
	 */
	public function save() {
		global $wpdb;
		if ( false !== $wpdb->replace( "{$wpdb->prefix}kleistad_reserveringen", $this->data ) ) {
			$this->id = $wpdb->insert_id;
			return $this->id;
		}
		return 0;
	}

	/**
	 * Verwijder de reservering.
	 *
	 * @global object $wpdb wp database.
	 */
	public function delete() {
		global $wpdb;
		if ( $wpdb->delete(
			"{$wpdb->prefix}kleistad_reserveringen",
			[ 'id' => $this->id ],
			[ '%d' ]
		) ) {
			$this->id = null;
		}
	}

	/**
	 * Return alle reserveringen.
	 *
	 * @param array|bool $selecties Veld/value combinaties om de query nog te verfijnen (optioneel) of true als alleen onverwerkte reserveringen.
	 * @return array reserveringen.
	 */
	public static function all( $selecties = [] ) {
		global $wpdb;
		$arr   = [];
		$where = 'WHERE 1 = 1';
		if ( is_array( $selecties ) ) {
			foreach ( $selecties as $veld => $selectie ) {
				$where .= " AND $veld = '$selectie'";
			}
		} else {
			$where .= ( $selecties ) ? ' AND verwerkt = 0' : '';
		}

		$reserveringen = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_reserveringen $where ORDER BY jaar DESC, maand DESC, dag DESC", ARRAY_A ); // phpcs:ignore
		foreach ( $reserveringen as $reservering_id => $reservering ) {
			$arr[ $reservering_id ] = new \Kleistad\Reservering( $reservering['oven_id'] );
			$arr[ $reservering_id ]->load( $reservering );
		}
		return $arr;
	}

}
