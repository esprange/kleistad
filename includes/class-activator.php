<?php
/**
 * Activering van de plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * De activator class
 */
class Activator {

	/**
	 * Activeer de plugin.
	 */
	public static function activate() {
		/*
		* n.b. in principe heeft de (toekomstige) rol bestuur de override capability en de (toekomstige) rol lid de reserve capability
		* zolang die rollen nog niet gedefinieerd zijn hanteren we de onderstaande toekenning
		*/
		$bestuur = get_role( 'bestuur' );
		if ( ! is_null( $bestuur ) ) {
			$bestuur->add_cap( \Kleistad\Roles::OVERRIDE, true );
			$bestuur->add_cap( \Kleistad\Roles::RESERVEER, true );
		}
	}
}
