<?php
/**
 * De definitie van de stoker class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use WP_User;
use stdClass;

/**
 * Kleistad Stoker class.
 *
 * @since 6.11.0
 */
class Stoker extends Gebruiker {

	/**
	 * Het stoker saldo
	 *
	 * @var Saldo $saldo Het saldo object.
	 */
	public Saldo $saldo;

	/**
	 * Constructor
	 *
	 * @param int|string|stdClass|WP_User $id      User's ID, a WP_User object, or a user object from the DB.
	 * @param string                      $name    Optional. User's username.
	 * @param int                         $site_id Optional Site ID, defaults to current site.
	 * @suppressWarnings(PHPMD.ShortVariable)
	 */
	public function __construct( $id = 0, $name = '', $site_id = null ) {
		parent::__construct( $id, $name, $site_id );
		$this->saldo = new Saldo( $this->ID );
	}

}
