<?php
/**
 * Definieer de Kleistad roles class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * De class roles
 */
class Roles {
	/**
	 * Custom capabilities van kleistad gebruikers.
	 */
	const OVERRIDE  = 'kleistad_reserveer_voor_ander';
	const RESERVEER = 'kleistad_reservering_aanmaken';

	/**
	 * Bepaal of de gebruiker eer reservering voor een ander mag maken of aanpassen
	 *
	 * @param int $id het wp user id, indien niet ingevuld, de huidige gebruiker.
	 * @return bool
	 */
	public static function override( $id = 0 ) {
		return ( $id ? user_can( $id, self::OVERRIDE ) : current_user_can( self::OVERRIDE ) );
	}

	/**
	 * Bepaal of de gebruiker een reservering mag maken of aanpassen
	 *
	 * @param int $id het wp user id, indiden niet ingevuld, de huidige gebruiker.
	 * @return bool
	 */
	public static function reserveer( $id = 0 ) {
		return ( $id ? user_can( $id, self::RESERVEER ) : current_user_can( self::RESERVEER ) );
	}

}
