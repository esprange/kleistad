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
 * @property int    gebruiker_id
 * @property int    temperatuur
 * @property string soortstook
 * @property int    programma
 * @property bool   gemeld
 * @property bool   verwerkt
 * @property array  verdeling
 * @property string opmerking
 */
class Kleistad_Reservering extends Kleistad_Entity {

	const ONDERHOUD = 'Onderhoud';

	/**
	 * Constructor
	 *
	 * @since 4.0.87
	 *
	 * @param int $oven_id oven waar de reservering op van toepassing is.
	 */
	public function __construct( $oven_id ) {
		$default_data = [
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
			'verdeling'    => wp_json_encode( [] ),
			'opmerking'    => '',
		];
		$this->_data  = $default_data;
	}

	/**
	 * Export functie privacy gevoelige data.
	 *
	 * @global object $wpdb wp database
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return array De persoonlijke data (stooksaldo).
	 */
	public static function export( $gebruiker_id ) {
		global $wpdb;

		$items         = [];
		$reserveringen = $wpdb->get_results(
			"SELECT dag as dag, maand, jaar, verdeling, naam, R.id as id FROM
				{$wpdb->prefix}kleistad_reserveringen as R, {$wpdb->prefix}kleistad_ovens as O
				WHERE R.oven_id = O.id
				ORDER BY jaar DESC, maand DESC, dag DESC",
			ARRAY_A
		); // WPCS: unprepared SQL OK.

		foreach ( $reserveringen as $reservering ) {
			$verdeling = json_decode( $reservering['verdeling'], true );
			$key       = array_search( $gebruiker_id, array_column( $verdeling, 'id' ), true );
			if ( false !== $key ) {
				$items[] = [
					'group_id'    => 'stook',
					'group_label' => 'Stook informatie',
					'item_id'     => 'stook-' . $reservering['id'],
					'data'        => [
						[
							'name'  => 'Datum',
							'value' => $reservering['dag'] . '-' . $reservering['maand'] . '-' . $reservering['jaar'],
						],
						[
							'name'  => 'Oven',
							'value' => $reservering['naam'],
						],
					],
				];
			}
		}
		return $items;
	}

	/**
	 * Vind de reservering
	 *
	 * @global object $wpdb wp database
	 * @param  int $jaar jaar.
	 * @param  int $maand maand.
	 * @param  int $dag dag.
	 * @return boolean
	 */
	public function find( $jaar, $maand, $dag ) {
		global $wpdb;

		$resultaat = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}kleistad_reserveringen WHERE oven_id = %d AND jaar= %d AND maand = %d AND dag = %d",
				$this->_data['oven_id'],
				$jaar,
				$maand,
				$dag
			),
			ARRAY_A
		); // WPCS: unprepared SQL OK.
		if ( $resultaat ) {
			$this->_data = $resultaat;
			return true;
		}
		return false;
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
				$verdeling = json_decode( $this->_data['verdeling'], true );
				if ( is_array( $verdeling ) ) {
					return $verdeling;
				} else {
					return [
						[
							'id'   => $this->_data['gebruiker_id'],
							'perc' => 100,
						],
						[
							'id'   => 0,
							'perc' => 0,
						],
						[
							'id'   => 0,
							'perc' => 0,
						],
						[
							'id'   => 0,
							'perc' => 0,
						],
						[
							'id'   => 0,
							'perc' => 0,
						],
					];
				}
			case 'datum':
				return strtotime( $this->_data['jaar'] . '-' . $this->_data['maand'] . '-' . $this->_data['dag'] . ' 00:00' );
			case 'gemeld':
			case 'verwerkt':
				return 1 === intval( $this->_data[ $attribuut ] );
			case 'jaar':
			case 'maand':
			case 'dag':
			case 'oven_id':
				return intval( $this->_data[ $attribuut ] );
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
			case 'verdeling':
				if ( is_array( $waarde ) ) {
					$this->_data[ $attribuut ] = wp_json_encode( $waarde );
				} else {
					$this->_data[ $attribuut ] = $waarde;
				}
				break;
			case 'datum':
				$this->_data['jaar']  = date( 'Y', $waarde );
				$this->_data['maand'] = date( 'm', $waarde );
				$this->_data['dag']   = date( 'd', $waarde );
				break;
			case 'gemeld':
			case 'verwerkt':
				$this->_data[ $attribuut ] = $waarde ? 1 : 0;
				break;
			default:
				$this->_data[ $attribuut ] = $waarde;
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
		$wpdb->replace( "{$wpdb->prefix}kleistad_reserveringen", $this->_data );
		$this->id = $wpdb->insert_id;
		return $this->id;
	}

	/**
	 * Verwijder reserveringen van gebruiker
	 *
	 * @since 4.0.87
	 *
	 * @param int $gebruiker_id Gebruiker id.
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public static function verwijder( $gebruiker_id ) {
		// to do, alleen reserveringen in de toekomst verwijderen ?.
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

		$reserveringen = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_reserveringen $where ORDER BY jaar DESC, maand DESC, dag DESC", ARRAY_A ); // WPCS: unprepared SQL OK.
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
					$kosten             = ( is_null( $regeling ) ) ? $ovens[ $this->oven_id ]->kosten : $regeling;
					$prijs              = round( $stookdeel['perc'] / 100 * $kosten, 2 );
					$stookdeel['prijs'] = $prijs;

					$saldo         = new Kleistad_Saldo( $stookdeel['id'] );
					$saldo->bedrag = $saldo->bedrag - $prijs;
					if ( $saldo->save( 'stook op ' . date( 'd-m-Y', $this->datum ) . ' door ' . $gebruiker->display_name ) ) {

						$to = "$medestoker->display_name <$medestoker->user_email>";
						Kleistad_public::compose_email(
							$to,
							'Kleistad kosten zijn verwerkt op het stooksaldo',
							'kleistad_email_stookkosten_verwerkt',
							[
								'voornaam'   => $medestoker->first_name,
								'achternaam' => $medestoker->last_name,
								'stoker'     => $gebruiker->display_name,
								'bedrag'     => number_format_i18n( $prijs, 2 ),
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
				$regeling = $regelingen->get( $this->gebruiker_id, $this->oven_id );

				$gebruiker = get_userdata( $this->gebruiker_id );
				$to        = "$gebruiker->display_name <$gebruiker->user_email>";
				Kleistad_public::compose_email(
					$to,
					'Kleistad oven gebruik op ' . date( 'd-m-Y', $this->datum ),
					'kleistad_email_stookmelding',
					[
						'voornaam'         => $gebruiker->first_name,
						'achternaam'       => $gebruiker->last_name,
						'bedrag'           => number_format_i18n( ( is_null( $regeling ) ) ? $ovens[ $this->oven_id ]->kosten : $regeling, 2 ),
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
