<?php
/**
 * The file that defines the regelingen class
 *
 * A class definition regelingen
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

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
			$regelingen                    = get_user_meta( $gebruiker->ID, 'kleistad_regeling', true );
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
