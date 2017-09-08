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
			'beperkt' => '',
			'geannuleerd' => 0,
			'opmerking' => '',
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
				return 1 == $this->_data[ $attribuut ];
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

}
