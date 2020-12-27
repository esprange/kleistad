<?php
/**
 * De definitie van de cursisten class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Cursisten class.
 *
 * @since 6.11.0
 */
class Cursisten extends Gebruikers {

	/**
	 * De constructor
	 */
	public function __construct() {
		$cursisten = get_users(
			[
				'fields'       => [ 'ID' ],
				'meta_key'     => Inschrijving::META_KEY,
				'meta_compare' => '!==',
				'meta_value'   => '',
				'orderby'      => 'display_name',
			]
		);
		foreach ( $cursisten as $cursist ) {
			$this->gebruikers[] = new Cursist( $cursist->ID );
		}
	}

	/**
	 * Geef de huidige gebruiker terug.
	 *
	 * @return Cursist De gebruiker.
	 */
	public function current(): Cursist {
		return $this->gebruikers[ $this->current_index ];
	}



}
