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

/**
 * De activator class
 */
class Kleistad_Activator {

	/**
	 * Activeer de plugin.
	 *
	 * @since    4.0.87
	 */
	public static function activate() {
		if ( ! wp_next_scheduled( 'kleistad_kosten' ) ) {
			wp_schedule_event( strtotime( 'midnight' ), 'daily', 'kleistad_kosten' );
		}
		if ( ! wp_next_scheduled( 'kleistad_rcv_email' ) ) {
			wp_schedule_event( time(), '15_mins', 'kleistad_rcv_email' );
		}

		/*
		* n.b. in principe heeft de (toekomstige) rol bestuur de override capability en de (toekomstige) rol lid de reserve capability
		* zolang die rollen nog niet gedefinieerd zijn hanteren we de onderstaande toekenning
		*/
		$roles = wp_roles();

		$roles->add_cap( 'administrator', Kleistad_Roles::OVERRIDE );
		$roles->add_cap( 'editor', Kleistad_Roles::OVERRIDE );
		$roles->add_cap( 'author', Kleistad_Roles::OVERRIDE );

		$roles->add_cap( 'administrator', Kleistad_Roles::RESERVEER );
		$roles->add_cap( 'editor', Kleistad_Roles::RESERVEER );
		$roles->add_cap( 'author', Kleistad_Roles::RESERVEER );
		$roles->add_cap( 'contributor', Kleistad_Roles::RESERVEER );
		$roles->add_cap( 'subscriber', Kleistad_Roles::RESERVEER );

		flush_rewrite_rules();
	}
}
