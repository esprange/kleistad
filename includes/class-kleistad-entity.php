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
abstract class Kleistad_Entity {

	/**
	 * Store the cursus data
	 *
	 * @since 4.0.87
	 * @access private
	 * @var array $_data contains cursus attributes.
	 */
	protected $_data = [];

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
	abstract public function save();

	/**
	 * Load the data
	 *
	 * Loads the data from the database.
	 *
	 * @since 4.0.87
	 *
	 * @param array $data oven attribute values.
	 */
	public function load( $data ) {
		$this->_data = $data;
	}

	/**
	 * Return privacy data
	 *
	 * @param int $gebruiker_id Het gebruiker_id.
	 * @return array De persoonlijke data.
	 */
	public static function export( $gebruiker_id ) {
		return [];
	}

	/**
	 * Erase privacy data
	 *
	 * @param int $gebruiker_id Het gebruiker_id.
	 * @return mixed De persoonlijke data.
	 */
	public static function erase( $gebruiker_id ) {
		return 0;
	}
}
