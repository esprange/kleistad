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
	const BESTUUR   = 'bestuur';
	const DOCENT    = 'docenten';
	const LID       = 'leden';
	const BOEKHOUD  = 'boekhouding';

	/**
	 * Bepaal of de gebruiker eer reservering voor een ander mag maken of aanpassen
	 *
	 * @param int $id het wp user id, indien niet ingevuld, de huidige gebruiker.
	 * @return bool
	 */
	public static function override( $id = null ) {
		return is_null( $id ) ? current_user_can( self::OVERRIDE ) : user_can( $id, self::OVERRIDE );
	}

	/**
	 * Bepaal of de gebruiker een reservering mag maken of aanpassen
	 *
	 * @param int $id het wp user id, indiden niet ingevuld, de huidige gebruiker.
	 * @return bool
	 */
	public static function reserveer( $id = null ) {
		return is_null( $id ) ? current_user_can( self::RESERVEER ) : user_can( $id, self::RESERVEER );
	}

	/**
	 * Bepaal of de gebruiker een bestuurslid is.
	 *
	 * @param int $id Het wp user id.
	 */
	public static function is_bestuur( $id = null ) {
		return is_null( $id ) ? current_user_can( self::BESTUUR ) : user_can( $id, self::BESTUUR );
	}

	/**
	 * Bepaal of de gebruiker een docent is.
	 *
	 * @param int $id Het wp user id.
	 */
	public static function is_docent( $id = null ) {
		return is_null( $id ) ? current_user_can( self::DOCENT ) : user_can( $id, self::DOCENT );
	}

	/**
	 * Bepaal of de gebruiker toegang tot de boekhouding heeft.
	 *
	 * @param int $id Het wp user id.
	 */
	public static function is_boekhoud( $id = null ) {
		return is_null( $id ) ? current_user_can( self::BOEKHOUD ) : user_can( $id, self::BOEKHOUD );
	}

	/**
	 * Bepaal of de gebruiker toegang tot leden heeft.
	 *
	 * @param int $id Het wp user id.
	 */
	public static function is_lid( $id = null ) {
		return is_null( $id ) ? current_user_can( self::LID ) : user_can( $id, self::LID );
	}
}
