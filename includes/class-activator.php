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
		$upgrade = new Admin_Upgrade();
		$upgrade->run();

		/*
		* n.b. in principe heeft de (toekomstige) rol bestuur de override capability en de (toekomstige) rol lid de reserve capability
		* zolang die rollen nog niet gedefinieerd zijn hanteren we de onderstaande toekenning
		*/

		flush_rewrite_rules();
	}
}
