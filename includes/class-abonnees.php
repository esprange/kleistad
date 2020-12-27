<?php
/**
 * De definitie van de abonnees class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Abonnees class.
 *
 * @since 6.11.0
 */
class Abonnees extends Gebruikers {

	/**
	 * De constructor
	 */
	public function __construct() {
		$abonnees = get_users(
			[
				'fields'       => [ 'ID' ],
				'meta_key'     => Abonnement::META_KEY,
				'meta_compare' => '!==',
				'meta_value'   => '',
				'orderby'      => 'display_name',
			]
		);
		foreach ( $abonnees as $abonnee ) {
			$this->gebruikers[] = new Abonnee( $abonnee->ID );
		}
	}
}
