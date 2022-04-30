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
	 * @param int|string|stdClass|WP_User $gebruiker_id      User's ID, a WP_User object, or a user object from the DB.
	 * @param string                      $name    Optional. User's username.
	 * @param int                         $site_id Optional Site ID, defaults to current site.
	 */
	public function __construct( $gebruiker_id = 0, $name = '', $site_id = null ) {
		parent::__construct( $gebruiker_id, $name, $site_id );
		$this->saldo = new Saldo( $this->ID );
	}

	/**
	 * Verwijder de stook van de gebruiker i.v.m. pauze of einde abonnement
	 *
	 * @param int $vanaf_datum Datum, minimaal vandaag.
	 * @param int $tot_datum Default 31-12-9999.
	 *
	 * @return void
	 */
	public function annuleer_stook( int $vanaf_datum, int $tot_datum = 253402210800 ) {
		$vanaf_datum = max( $vanaf_datum, strtotime( 'today' ) );
		foreach ( new Ovens() as $oven ) {
			foreach ( new Stoken( $oven, $vanaf_datum, $tot_datum ) as $stook ) {
				if ( $this->ID === $stook->hoofdstoker_id ) {
					if ( ! $stook->verwijder() ) {
						fout( __CLASS__, 'reservering kon niet verwijderd worden' );
					}
				}
			}
		}
	}
}
