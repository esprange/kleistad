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

/**]
 * Kleistad Abonnee class.
 *
 * @since 6.11.0
 */
class Abonnee extends Gebruiker {

	/**
	 * Het abonnement
	 *
	 * @var Abonnement $abonnement Het abonnement.
	 */
	public Abonnement $abonnement;

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
		$this->abonnement = new Abonnement( $this->ID );
	}

	/**
	 * Bepaal of de abonnee nu actief is.
	 *
	 * @return bool True als actief.
	 */
	public function is_actief() : bool {
		return user_can( $this->ID, LID );
	}
}
