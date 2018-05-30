<?php
/**
 * The file that defines the x plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Description of class-kleistad-roles
 *
 * @author espra
 */
class Kleistad_Roles {
	/**
	 * Custom capabilities
	 */
	const OVERRIDE  = 'kleistad_reserveer_voor_ander';
	const RESERVEER = 'kleistad_reservering_aanmaken';

	/**
	 * Help functie, bestuursleden kunnen publiceren en mogen daarom aanpassen
	 *
	 * @param int $id the user id.
	 * @return bool
	 */
	public static function override( $id = 0 ) {
		return ( $id ? user_can( $id, self::OVERRIDE ) : current_user_can( self::OVERRIDE ) );
	}

	/**
	 * Help functie, leden moeten kunnen reserveren en stooksaldo aanpassingen doen
	 *
	 * @param int $id the user id.
	 * @return bool
	 */
	public static function reserveer( $id = 0 ) {
		return ( $id ? user_can( $id, self::RESERVEER ) : current_user_can( self::RESERVEER ) );
	}

}
