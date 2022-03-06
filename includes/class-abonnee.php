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
	 * Bepaal aantal actieve reservering dat de gebruiker open heeft staan.
	 *
	 * @return int
	 */
	public function aantal_actieve_stook() : int {
		global $wpdb;
		$vandaag = date( 'Y-m-d' );
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}kleistad_reserveringen WHERE gebruiker_id = %d AND datum >= %s",
				$this->ID,
				$vandaag
			)
		);
	}

}
