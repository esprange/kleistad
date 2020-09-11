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
		// phpcs:disable
		// het onderstaande uitgecommentarieerd want er is geen reden meer om dit steeds opnieuw te doen.
		// $roles = wp_roles();

		// $roles->add_cap( 'administrator', \Kleistad\Roles::OVERRIDE );
		// $roles->add_cap( 'editor', \Kleistad\Roles::OVERRIDE );
		// $roles->add_cap( 'author', \Kleistad\Roles::OVERRIDE );

		// $roles->add_cap( 'administrator', \Kleistad\Roles::RESERVEER );
		// $roles->add_cap( 'editor', \Kleistad\Roles::RESERVEER );
		// $roles->add_cap( 'author', \Kleistad\Roles::RESERVEER );
		// $roles->add_cap( 'contributor', \Kleistad\Roles::RESERVEER );
		// $roles->add_cap( 'subscriber', \Kleistad\Roles::RESERVEER );
		// phpcs:enable

		Public_Main::register_post_types();
		flush_rewrite_rules();
	}
}
