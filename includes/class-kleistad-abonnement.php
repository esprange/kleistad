<?php
/**
 * The file that defines the cursus class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Description of class-kleistad-abonnement
 *
 * @author espra
 */
class Kleistad_Abonnement extends Kleistad_Entity {

	/**
	 * Store the cursist id
	 *
	 * @since 4.0.87
	 * @access private
	 * @var int $_cursist_id the wp user id the of cursist.
	 */
	private $_abonnee_id;

	/**
	 * Constructor
	 *
	 * Create the abonnee object .
	 *
	 * @since 4.0.87
	 *
	 * @param int $abonnee_id id of the abonnee.
	 */
	public function __construct( $abonnee_id ) {
		$default_data = [
			'code' => "A$abonnee_id",
			'datum' => date( 'Y-m-d' ),
			'start_datum' => '',
			'dag' => '',
			'geannuleerd' => 0,
			'opmerking' => '',
			'soort' => 'onbeperkt',
		];

		$this->_abonnee_id = $abonnee_id;
		$abonnement = get_user_meta( $this->_abonnee_id, 'kleistad_abonnement', true );
		if ( is_array( $abonnement ) ) {
			$this->_data = $abonnement;
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
			case 'datum':
			case 'start_datum':
				return strtotime( $this->_data[ $attribuut ] );
			case 'geannuleerd':
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
			case 'datum':
			case 'start_datum':
				$this->_data[ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			case 'geannuleerd':
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
		update_user_meta( $this->_abonnee_id, 'kleistad_abonnement', $this->_data );
	}

	/**
	 * Verzenden van de welkomst email.
	 *
	 * @param string $type Welke email er verstuurd moet worden.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type ) {
		$options = get_option( 'kleistad-opties' );
		$abonnee   = get_userdata( $this->_abonnee_id );
		$to        = "$abonnee->first_name $abonnee->last_name <$abonnee->user_email>";
		return Kleistad_public::compose_email(
			$to, 'Welkom bij Kleistad', 'kleistad_email_abonnement' . $type, [
				'voornaam'             => $abonnee->first_name,
				'achternaam'           => $abonnee->last_name,
				'start_datum'          => strftime( '%A %d-%m-%y', strtotime( $this->_data['start_datum'] ) ),
				'abonnement'           => $this->_data['soort'],
				'abonnement_code'      => $this->_data['code'],
				'abonnement_dag'       => $this->_data['dag'],
				'abonnement_opmerking' => $this->_data['opmerking'],
				'abonnement_startgeld' => number_format( 3 * $options[ $this->_data['soort'] . '_abonnement' ], 2, ',', '' ),
				'abonnement_maandgeld' => number_format( $options[ $this->_data['soort'] . '_abonnement' ], 2, ',', '' ),
			]
		);
	}

	/**
	 * Betaal het abonnement met iDeal.
	 *
	 * @param float  $bedrag   Het te storten bedrag.
	 * @param string $bank    De bank.
	 * @param string $bericht Het succes bericht.
	 */
	public function betalen( $bedrag, $bank, $bericht ) {
		$betaling = new Kleistad_Betalen();
		$betaling->order(
			$this->_abonnee_id,
			$this->_data['code'],
			$bedrag,
			$bank,
			'Kleistad abonnement ' . $this->_data['code'],
			$bericht
		);
	}

}

/**
   * Collection of Abonnement
   *
   * Collection of Abonnementen, loaded from the database.
   *
   * @since 4.0.87
   *
   * @see class Kleistad_Abonnement
   * @link URL
    */
class Kleistad_Abonnementen extends Kleistad_EntityStore {

	/**
	 * Constructor
	 *
	 * Loads the data from the database.
	 *
	 * @since 4.0.91
	 *
	 * @return null.
	 */
	public function __construct() {
		$abonnees = get_users(
			[
				'meta_key' => 'kleistad_abonnement',
			]
		);
		foreach ( $abonnees as $abonnee ) {
			$abonnement = get_user_meta( $abonnee->ID, 'kleistad_abonnement', true );
			$this->_data[ $abonnee->ID ] = new Kleistad_Abonnement( $abonnee->ID );
			$this->_data[ $abonnee->ID ]->load( $abonnement );
		}
	}


}
