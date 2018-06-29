<?php
/**
 * De definitie van de regelingen class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Class entity regelingen
 */
class Kleistad_Regelingen {

	const META_KEY = 'kleistad_regeling';

	/**
	 * De regeling data (alle regelingen in één array).
	 *
	 * @since 4.0.87
	 * @access private
	 * @var array $_data de regelingen.
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
				'meta_key' => self::META_KEY,
			]
		);
		foreach ( $gebruikers as $gebruiker ) {
			$regelingen                    = get_user_meta( $gebruiker->ID, self::META_KEY, true );
			$this->_data[ $gebruiker->ID ] = $regelingen;
		}
	}

	/**
	 * Get regeling van het object.
	 *
	 * @since 4.0.87
	 *
	 * @param int $gebruiker_id wp user id.
	 * @param int $oven_id oven id.
	 * @return null|float|array kosten van oven, kosten van ovens of null als de regeling onbekend is.
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
	 * Set en bewaar de regeling in de database.
	 *
	 * @since 4.0.87
	 *
	 * @param int   $gebruiker_id wp user id.
	 * @param int   $oven_id oven id.
	 * @param float $kosten kostenregeling.
	 */
	public function set_and_save( $gebruiker_id, $oven_id, $kosten ) {
		$this->_data[ $gebruiker_id ][ $oven_id ] = $kosten;
		return update_user_meta( $gebruiker_id, self::META_KEY, $this->_data[ $gebruiker_id ] );
	}

	/**
	 * Cancel de regeling en verwijder die vanuit de database.
	 *
	 * @since 4.0.87
	 *
	 * @param int $gebruiker_id wp user id.
	 * @param int $oven_id oven id.
	 */
	public function delete_and_save( $gebruiker_id, $oven_id ) {
		unset( $this->_data[ $gebruiker_id ][ $oven_id ] );
		if ( 0 === count( $this->_data[ $gebruiker_id ] ) ) {
			return delete_user_meta( $gebruiker_id, self::META_KEY );
		} else {
			return update_user_meta( $gebruiker_id, self::META_KEY, $this->_data[ $gebruiker_id ] );
		}
	}

}
