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

/**
 * Kleistad Reservering class.
 *
 * @since 4.0.87
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
class Kleistad_Reservering extends Kleistad_Entity {

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
	const ONGEBRUIKT    = 0;
	const RESERVEERBAAR = 1;
	const VERWIJDERBAAR = 2;
	const WIJZIGBAAR    = 3;
	const DEFINITIEF    = 4;

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
			if ( strtotime( "$this->jaar-$this->maand-$this->dag 23:59" ) >= strtotime( 'today' ) ) {
				return self::RESERVEERBAAR;
			} else {
				return self::ONGEBRUIKT;
			}
		} else {
			if ( ! $this->verwerkt ) {
				if ( ! $this->datum < strtotime( 'today midnight' ) ) {
					return self::VERWIJDERBAAR;
				} else {
					return self::WIJZIGBAAR;
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
				if ( is_array( $verdeling ) ) {
					return $verdeling;
				}
				return [
					[
						'id'   => $this->data['gebruiker_id'],
						'perc' => 100,
					],
				];
			case 'datum':
				return strtotime( $this->data['jaar'] . '-' . $this->data['maand'] . '-' . $this->data['dag'] . ' 00:00' );
			case 'gemeld':
			case 'verwerkt':
				return 1 === intval( $this->data[ $attribuut ] );
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
				if ( is_array( $waarde ) ) {
					$verdeling = [];
					array_walk(
						$waarde,
						function( $item, $key ) use ( &$verdeling ) {
							$verdeling[ $key ] = [
								'id'    => intval( $item['id'] ),
								'perc'  => intval( $item['perc'] ),
								'prijs' => isset( $item['prijs'] ) ? $item['prijs'] : 0.0,
							];
						}
					);
					$this->data[ $attribuut ] = wp_json_encode( $verdeling );
				} else {
					$this->data[ $attribuut ] = $waarde;
				}
				break;
			case 'datum':
				$this->data['jaar']  = date( 'Y', $waarde );
				$this->data['maand'] = date( 'm', $waarde );
				$this->data['dag']   = date( 'd', $waarde );
				break;
			case 'gemeld':
			case 'verwerkt':
				$this->data[ $attribuut ] = $waarde ? 1 : 0;
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
		$wpdb->replace( "{$wpdb->prefix}kleistad_reserveringen", $this->data );
		$this->id = $wpdb->insert_id;
		return $this->id;
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
			$arr[ $reservering_id ] = new Kleistad_Reservering( $reservering['oven_id'] );
			$arr[ $reservering_id ]->load( $reservering );
		}
		return $arr;
	}

	/**
	 * Verwerk de reservering. Afhankelijk van de status melden dat er een afboeking gaat plaats vinden of de werkelijke afboeking uitvoeren.
	 *
	 * @since 4.5.1
	 */
	public function meld_en_verwerk() {
		$options    = Kleistad::get_options();
		$regelingen = new Kleistad_Regelingen();
		$ovens      = Kleistad_Oven::all();

		if ( $this->datum <= strtotime( '- ' . $options['termijn'] . ' days 00:00' ) ) {
			if ( self::ONDERHOUD !== $this->soortstook ) {
				$gebruiker = get_userdata( $this->gebruiker_id );
				$verdeling = $this->verdeling;
				foreach ( $verdeling as &$stookdeel ) {
					if ( 0 === intval( $stookdeel['id'] ) ) {
						continue;
					}
					$medestoker         = get_userdata( $stookdeel['id'] );
					$regeling           = $regelingen->get( $stookdeel['id'], $this->oven_id );
					$kosten             = is_float( $regeling ) ? $regeling : $ovens[ $this->oven_id ]->kosten;
					$bedrag             = round( $stookdeel['perc'] / 100 * $kosten, 2 );
					$stookdeel['prijs'] = $bedrag;

					$saldo         = new Kleistad_Saldo( $stookdeel['id'] );
					$saldo->bedrag = $saldo->bedrag - $bedrag;
					if ( $saldo->save( 'stook op ' . date( 'd-m-Y', $this->datum ) . ' door ' . $gebruiker->display_name ) ) {

						$to = "$medestoker->display_name <$medestoker->user_email>";
						Kleistad_email::compose(
							$to,
							'Kleistad kosten zijn verwerkt op het stooksaldo',
							'kleistad_email_stookkosten_verwerkt',
							[
								'voornaam'   => $medestoker->first_name,
								'achternaam' => $medestoker->last_name,
								'stoker'     => $gebruiker->display_name,
								'bedrag'     => number_format_i18n( $bedrag, 2 ),
								'saldo'      => number_format_i18n( $saldo->bedrag, 2 ),
								'stookdeel'  => $stookdeel['perc'],
								'stookdatum' => date( 'd-m-Y', $this->datum ),
								'stookoven'  => $ovens[ $this->oven_id ]->naam,
							]
						);
					}
				}
				$this->verdeling = $verdeling;
			}
			$this->verwerkt = true;
			$this->save();
		} elseif ( ! $this->gemeld && $this->datum < strtotime( 'today' ) ) {
			if ( self::ONDERHOUD !== $this->soortstook ) {
				$regeling  = $regelingen->get( $this->gebruiker_id, $this->oven_id );
				$bedrag    = is_float( $regeling ) ? $regeling : $ovens[ $this->oven_id ]->kosten;
				$gebruiker = get_userdata( $this->gebruiker_id );
				$to        = "$gebruiker->display_name <$gebruiker->user_email>";
				Kleistad_email::compose(
					$to,
					'Kleistad oven gebruik op ' . date( 'd-m-Y', $this->datum ),
					'kleistad_email_stookmelding',
					[
						'voornaam'         => $gebruiker->first_name,
						'achternaam'       => $gebruiker->last_name,
						'bedrag'           => number_format_i18n( $bedrag, 2 ),
						'datum_verwerking' => date( 'd-m-Y', strtotime( '+' . $options['termijn'] . ' day', $this->datum ) ), // datum verwerking.
						'datum_deadline'   => date( 'd-m-Y', strtotime( '+' . $options['termijn'] - 1 . ' day', $this->datum ) ), // datum deadline.
						'stookoven'        => $ovens[ $this->oven_id ]->naam,
					]
				);
			}
			$this->gemeld = true;
			$this->save();
		}
	}
}
