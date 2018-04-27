<?php
/**
 * The file that defines the oven class
 *
 * A class definition including the ovens, reserveringen and regelingen
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
		$default_data    = [
			'id'                 => null,
			'naam'               => '',
			'kosten'             => 0,
			'beschikbaarheid'    => wp_json_encode( [] ),
		];
		$this->_data     = $default_data;
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
				$this->_data[ $attribuut ]     = wp_json_encode( $waarde );
				break;
			default:
				$this->_data[ $attribuut ]     = $waarde;
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
		$this->_data['id'] = $wpdb->insert_id;
		return $this->_data['id'];
	}

}

/**
   * Collection of Oven
   *
   * Collection of Oven, loaded from the database.
   *
   * @since 4.0.87
   *
   * @see class Kleistad_Oven
   * @link URL
    */
class Kleistad_Ovens extends Kleistad_EntityStore {

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
		$ovens = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_ovens", ARRAY_A ); // WPCS: unprepared SQL OK.
		foreach ( $ovens as $oven ) {
			$this->_data[ $oven['id'] ] = new Kleistad_Oven();
			$this->_data[ $oven['id'] ]->load( $oven );
		}
	}

}

/**
 * Kleistad Reservering class.
 *
 * A class definition that define the attributes of a single reservering class.
 *
 * @since 4.0.87
 *
 * @see n.a.
 * @link URL
 */
class Kleistad_Reservering extends Kleistad_Entity {

	/**
	 * Constructor
	 *
	 * Constructor, Long description.
	 *
	 * @since 4.0.87
	 *
	 * @param int $oven_id (optional) reservering to load.
	 * @return null.
	 */
	public function __construct( $oven_id ) {
		$default_data    = [
			'id'             => null,
			'oven_id'        => $oven_id,
			'jaar'           => 0,
			'maand'          => 0,
			'dag'            => 0,
			'gebruiker_id'   => 0,
			'temperatuur'    => 0,
			'soortstook'     => '',
			'programma'      => 0,
			'gemeld'         => 0,
			'verwerkt'       => 0,
			'verdeling'      => wp_json_encode( [] ),
			'opmerking'      => '',
		];
		$this->_data     = $default_data;
	}

	/**
	 * Find the reservering
	 *
	 * @global object $wpdb wp database
	 * @param int $jaar jaar.
	 * @param int $maand maand.
	 * @param int $dag dag.
	 * @return boolean
	 */
	public function find( $jaar, $maand, $dag ) {
		global $wpdb;

		$resultaat = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}kleistad_reserveringen WHERE oven_id = %d AND jaar= %d AND maand = %d AND dag = %d", $this->_data['oven_id'], $jaar, $maand, $dag
			), ARRAY_A
		); // WPCS: unprepared SQL OK.
		if ( $resultaat ) {
			$this->_data = $resultaat;
			return true;
		}
		return false;
	}

	/**
	 * Delete the current object
	 *
	 * @global object $wpdb wp database.
	 */
	public function delete() {
		global $wpdb;
		if ( $wpdb->delete(
			"{$wpdb->prefix}kleistad_reserveringen", [
				'id' => $this->_data['id'],
			], [ '%d' ]
		) ) {
			$this->_data['id'] = null;
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
			case 'verdeling':
				$verdeling = json_decode( $this->_data['verdeling'], true );
				if ( is_array( $verdeling ) ) {
					return $verdeling;
				} else {
					return [
						[
							'id'     => $this->_data['gebruiker_id'],
							'perc'   => 100,
						],
						[
							'id'     => 0,
							'perc'   => 0,
						],
						[
							'id'     => 0,
							'perc'   => 0,
						],
						[
							'id'     => 0,
							'perc'   => 0,
						],
						[
							'id'     => 0,
							'perc'   => 0,
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
				return intval( $this->_data[ $attribuut ] );
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
			case 'verdeling':
				if ( is_array( $waarde ) ) {
					$this->_data[ $attribuut ] = wp_json_encode( $waarde );
				} else {
					$this->_data[ $attribuut ] = $waarde;
				}
				break;
			case 'datum':
				$this->_data['jaar']         = date( 'Y', $waarde );
				$this->_data['maand']        = date( 'm', $waarde );
				$this->_data['dag']          = date( 'd', $waarde );
				break;
			case 'gemeld':
			case 'verwerkt':
				$this->_data[ $attribuut ]     = $waarde ? 1 : 0;
				break;
			default:
				$this->_data[ $attribuut ]     = $waarde;
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
		$wpdb->replace( "{$wpdb->prefix}kleistad_reserveringen", $this->_data );
		$this->_data['id'] = $wpdb->insert_id;
		return $this->_data['id'];
	}

	/**
	 * Verwijder reservering van gebruiker
	 *
	 * Get attribuut from the object.
	 *
	 * @since 4.0.87
	 *
	 * @global object $wpdb WordPress db
	 * @param int $gebruiker_id Gebruiker id.
	 * @return mixed Attribute value.
	 */
	public static function verwijder( $gebruiker_id ) {
		// to do, alleen reserveringen in de toekomst verwijderen ?.
		return $gebruiker_id;
	}

}

/**
   * Collection of Reservering
   *
   * Collection of Oven, loaded from the database.
   *
   * @since 4.0.87
   *
   * @see class Kleistad_Reservering
   * @link URL
 */
class Kleistad_Reserveringen extends Kleistad_EntityStore {

	/**
	 * Constructor
	 *
	 * Loads the data from the database.
	 *
	 * @since 4.0.87
	 *
	 * @global object $wpdb WordPress database.
	 * @param int $oven_id the oven id.
	 * @return null.
	 */
	public function __construct( $oven_id = null ) {
		global $wpdb;
		if ( is_null( $oven_id ) ) {
			$reserveringen = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_reserveringen ORDER BY jaar DESC, maand DESC, dag DESC", ARRAY_A ); // WPCS: unprepared SQL OK.
		} else {
			$reserveringen = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_reserveringen WHERE oven_id = %d ORDER BY jaar DESC, maand DESC, dag DESC", $oven_id ), ARRAY_A ); // WPCS: unprepared SQL OK.
		}
		foreach ( $reserveringen as $reservering_id => $reservering ) {
			$this->_data[ $reservering_id ] = new Kleistad_Reservering( $reservering['oven_id'] );
			$this->_data[ $reservering_id ]->load( $reservering );
		}
	}

}

/**
 * Class entity regelingen
 */
class Kleistad_Regelingen {

	/**
	 * Store the regeling data
	 *
	 * @since 4.0.87
	 * @access private
	 * @var array $_data contains regeling attributes.
	 */
	private $_data = [];

	/**
	 * Constructor
	 *
	 * @since 4.0.87
	 */
	public function __construct() {
		$gebruikers = get_users(
			[
				'meta_key' => 'kleistad_regeling',
			]
		);
		foreach ( $gebruikers as $gebruiker ) {
			$regelingen = get_user_meta( $gebruiker->ID, 'kleistad_regeling', true );
			$this->_data[ $gebruiker->ID ] = $regelingen;
		}
	}

	/**
	 * Getter,
	 *
	 * Get regeling from the object.
	 *
	 * @since 4.0.87
	 *
	 * @param int $gebruiker_id wp user id.
	 * @param int $oven_id oven id.
	 * @return float kosten or null if unknown regeling.
	 */
	public function get( $gebruiker_id, $oven_id = null ) {
		if ( array_key_exists( $gebruiker_id, $this->_data ) ) {
			if ( is_null( $oven_id ) ) {
				return $this->_data[ $gebruiker_id ];
			} else {
				if ( array_key_exists( $oven_id, $this->_data[ $gebruiker_id ] ) ) {
					return $this->_data[ $gebruiker_id ][ $oven_id ];
				}
			}
		}
		return null;
	}

	/**
	 * Setter
	 *
	 * Set the regeling and store it to the database.
	 *
	 * @since 4.0.87
	 *
	 * @param int   $gebruiker_id wp user id.
	 * @param int   $oven_id oven id.
	 * @param float $kosten kostenregeling.
	 */
	public function set_and_save( $gebruiker_id, $oven_id, $kosten ) {
		$this->_data[ $gebruiker_id ][ $oven_id ] = $kosten;
		return update_user_meta( $gebruiker_id, 'kleistad_regeling', $this->_data[ $gebruiker_id ] );
	}

	/**
	 * Deleter
	 *
	 * Cancel the regeling and remove it from the database.
	 *
	 * @since 4.0.87
	 *
	 * @param int $gebruiker_id wp user id.
	 * @param int $oven_id oven id.
	 */
	public function delete_and_save( $gebruiker_id, $oven_id ) {
		unset( $this->_data[ $gebruiker_id ][ $oven_id ] );
		if ( 0 === count( $this->_data[ $gebruiker_id ] ) ) {
			return delete_user_meta( $gebruiker_id, 'kleistad_regeling' );
		} else {
			return update_user_meta( $gebruiker_id, 'kleistad_regeling', $this->_data[ $gebruiker_id ] );
		}
	}

}

/**
 * Klasse voor het beheren van de stook saldo.
 */
class Kleistad_Saldo {

	/**
	 * De attributen van het saldo
	 *
	 * @var array De attributen van het saldo.
	 */
	private $_data;

	/**
	 * De gebruiker identificatie
	 *
	 * @var int Het gebruiker_id.
	 */
	private $_gebruiker_id;

	/**
	 * Private functie welke de update van stooksaldo.log doet.
	 *
	 * @param string $tekst Toe te voegen tekst aan log.
	 */
	private static function write_log( $tekst ) {
		$upload_dir      = wp_upload_dir();
		$f               = fopen( $upload_dir['basedir'] . '/stooksaldo.log', 'a' );
		fwrite( $f, date( 'c' ) . " : $tekst\n" );
		fclose( $f );
	}

	/**
	 * Private functie om de huidige saldo stand op te vragen
	 *
	 * @return float De huidige saldo stand.
	 */
	private function huidig_saldo() {
		$huidig_saldo = get_user_meta( $this->_gebruiker_id, 'stooksaldo', true );
		return ( '' === $huidig_saldo ) ? 0.0 : (float) $huidig_saldo;
	}

	/**
	 * Functie om algemene teksten toe te voegen aan de log
	 *
	 * @param tekst $reden De te loggen tekst.
	 */
	public static function log( $reden ) {
		self::write_log( $reden );
	}

	/**
	 * De constructor
	 *
	 * @param int $gebruiker_id De gebruiker waarvoor het saldo wordt gemaakt.
	 */
	public function __construct( $gebruiker_id ) {
		$this->_gebruiker_id   = $gebruiker_id;
		$this->_data['bedrag'] = $this->huidig_saldo();
	}

	/**
	 * Setter magic functie
	 *
	 * @param string $attribuut Het attribuut waarvan de waarde wordt aangepast.
	 * @param mixed  $waarde De nieuwe waarde.
	 */
	public function __set( $attribuut, $waarde ) {
		$this->_data[ $attribuut ] = $waarde;
	}

	/**
	 * Getter magic functie
	 *
	 * @param string $attribuut Het attribuut waarvan de waarde wordt opgevraagd.
	 * @return mixed De waarde.
	 */
	public function __get( $attribuut ) {
		return $this->_data[ $attribuut ];
	}

	/**
	 * Bewaar het aangepaste saldo
	 *
	 * @param string $reden De reden waarom het saldo wordt aangepast.
	 * @return bool True als saldo is aangepast.
	 */
	public function save( $reden ) {
		$huidig_saldo = $this->huidig_saldo();

		if ( $huidig_saldo !== $this->_data['bedrag'] ) {
			update_user_meta( $this->_gebruiker_id, 'stooksaldo', $this->_data['bedrag'] );
			$gebruiker = get_userdata( $this->_gebruiker_id );
			self::write_log( "$gebruiker->display_name nu: € $huidig_saldo naar: € " . $this->_data['bedrag'] . " vanwege $reden\n" );
			return true;
		}
		return false;
	}

	/**
	 * Betaal de inschrijving met iDeal.
	 *
	 * @param float  $bedrag  Het te betalen bedrag.
	 * @param string $bank    De bank.
	 * @param string $bericht Het bericht bij succesvolle betaling.
	 */
	public function betalen( $bedrag, $bank, $bericht ) {
		$betaling = new Kleistad_Betalen();
		$code = "S$this->_gebruiker_id-" . strftime( '%y%m%d' );
		$betaling->order(
			$this->_gebruiker_id,
			$code,
			$bedrag,
			$bank,
			'Kleistad stooksaldo ' . $code,
			$bericht
		);
	}

	/**
	 * Verzenden van de saldo verhoging email.
	 *
	 * @param string $type   direct betaald of melding van storting.
	 * @param float  $bedrag het saldo dat toegevoegd is.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type, $bedrag ) {
		$gebruiker = get_userdata( $this->_gebruiker_id );
		$to        = "$gebruiker->first_name $gebruiker->last_name <$gebruiker->user_email>";
		return Kleistad_public::compose_email(
			$to, 'Bijstorting stooksaldo', ( 'ideal' === $type ) ? 'kleistad_email_saldo_gewijzigd' : 'kleistad_email_saldo_wijziging', [
				'voornaam'   => $gebruiker->first_name,
				'achternaam' => $gebruiker->last_name,
				'bedrag'     => $bedrag,
				'saldo'      => $this->_data['bedrag'],
			]
		);
	}

}
