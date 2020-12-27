<?php
/**
 * De definitie van de abonnee class.
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
 * Kleistad Dagdelenkaart gebruiker class.
 *
 * @since 6.11.0
 */
class Dagdelengebruiker extends Gebruiker {

	/**
	 * De dagdelenkaart
	 *
	 * @var Dagdelenkaart $dagdelenkaart De kaart.
	 */
	public Dagdelenkaart $dagdelenkaart;

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
		$this->dagdelenkaart = new Dagdelenkaart( $this->ID );
	}

	/**
	 * Bepaal of de dagdelenkaart nog actief is.
	 *
	 * @return bool True als actief.
	 */
	public function is_actief() {
		return $this->dagdelenkaart->eind_datum > strtotime( 'today' );
	}
}

