<?php
/**
 * De abstract entity class.
 *
 * Een class definitie als basis voor de Kleistad entiteiten.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Kleistad Entity class.
 *
 * Een class definitie, basis voor overige classes.
 *
 * @since 4.0.87
 *
 * @see n.a.
 * @link URL
 */
abstract class Kleistad_Entity {

	/**
	 * De object data
	 *
	 * @since 4.0.87
	 * @access private
	 * @var array $_data welke de attributen van het object bevat.
	 */
	protected $_data = [];

	/**
	 * Getter, de PHP magic function.
	 *
	 * Lees het attribuut van het object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribute waarde.
	 */
	public function __get( $attribuut ) {
		switch ( $attribuut ) {
			default:
				return $this->_data[ $attribuut ];
		}
	}

	/**
	 * Setter, maakt gebruik van de PHP magic function.
	 *
	 * Wijzig het attribuut van het object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( $attribuut, $waarde ) {
		switch ( $attribuut ) {
			default:
				$this->_data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Sla de data op.
	 *
	 * @since 4.0.87
	 */
	abstract public function save();

	/**
	 * Load de data
	 *
	 * Laad de data van de database.
	 *
	 * @since 4.0.87
	 *
	 * @param array $data attribute waarden.
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
