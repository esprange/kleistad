<?php
/**
 * De definitie van de cursist class.
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
 * Kleistad Cursist class.
 *
 * @since 6.11.0
 */
class Cursist extends Gebruiker {

	/**
	 * De cursist inschrijvingen
	 *
	 * @var array $inschrijvingen De inschrijvingen.
	 */
	public array $inschrijvingen = [];

	/**
	 * Constructor
	 *
	 * @param int|string|stdClass|WP_User $id      User's ID, a WP_User object, or a user object from the DB.
	 * @param string                      $name    Optional. User's username.
	 * @param int                         $site_id Optional Site ID, defaults to current site.
	 * @suppressWarnings(PHPMD.ShortVariable)
	 */
	public function __construct( $id = 0, $name = '', $site_id = null ) {
		global $wpdb;
		parent::__construct( $id, $name, $site_id );
		$data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_inschrijvingen WHERE cursist_id = %d", $this->ID ), ARRAY_A );
		foreach ( $data as $row ) {
			$this->inschrijvingen[] = new Inschrijving( $row['cursus_id'], $this->ID, $row );
		}
	}

	/**
	 * Geef de inschrijving terug
	 *
	 * @param int $cursus_id Het cursus nummer waarop ingeschreven is.
	 * @return Inschrijving|bool De inschrijving of false als er niet op de cursus ingeschreven is.
	 */
	public function geef_inschrijving( int $cursus_id ) {
		foreach ( $this->inschrijvingen as $inschrijving ) {
			if ( $cursus_id === $inschrijving->cursus->id ) {
				return $inschrijving;
			}
		}
		return false;
	}

	/**
	 * Bepaal of de cursist nu actief is.
	 *
	 * @return bool True als actief.
	 */
	public function is_actief() : bool {
		$vandaag = strtotime( 'today' );
		foreach ( $this->inschrijvingen as $inschrijving ) {
			if ( $vandaag <= $inschrijving->cursus->eind_datum ) {
				return true;
			}
		}
		return false;
	}
}
