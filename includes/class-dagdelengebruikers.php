<?php
/**
 * De definitie van de dagdelengebruikers class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Dagdelengebruikers class.
 *
 * @since 6.11.0
 */
class Dagdelengebruikers extends Gebruikers {

	/**
	 * De constructor
	 */
	public function __construct() {
		$dagdelengebruikers = get_users(
			[
				'fields'       => [ 'ID' ],
				'meta_key'     => Dagdelenkaart::META_KEY,
				'meta_compare' => '!==',
				'meta_value'   => '',
				'orderby'      => 'display_name',
			]
		);
		foreach ( $dagdelengebruikers as $dagdelengebruiker ) {
			$this->gebruikers[] = new Dagdelengebruiker( $dagdelengebruiker->ID );
		}
	}

	/**
	 * Geef de huidige gebruiker terug.
	 *
	 * @return Dagdelengebruiker De gebruiker.
	 */
	public function current(): Dagdelengebruiker {
		return $this->gebruikers[ $this->current_index ];
	}


}
