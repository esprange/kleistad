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
	 * Plugin-database-versie
	 */
	const DBVERSIE = 11;

	/**
	 * Activeer de plugin.
	 * Zorg dat alle opties van een initiële waarde voorzien zijn, maak de database tabellen aan.
	 * Voeg de capaciteiten toe aan de rollen.
	 *
	 * @since    4.0.87
	 */
	public static function activate() {
		$default_options = [
			'onbeperkt_abonnement' => 50,
			'beperkt_abonnement'   => 30,
			'borg_kast'            => 5,
			'dagdelenkaart'        => 60,
			'cursusprijs'          => 130,
			'cursusinschrijfprijs' => 25,
			'cursusmaximum'        => 12,
			'workshopprijs'        => 110,
			'kinderworkshopprijs'  => 110,
			'termijn'              => 4,
			'sleutel'              => '',
			'sleutel_test'         => '',
			'betalen'              => 0,
		];
		$options         = wp_parse_args( get_option( 'kleistad-opties' ), $default_options );
		update_option( 'kleistad-opties', $options );

		$database_version = intval( get_option( 'kleistad-database-versie', 0 ) );
		if ( $database_version < self::DBVERSIE ) {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta(
				"CREATE TABLE {$wpdb->prefix}kleistad_reserveringen (
                id int(10) NOT NULL AUTO_INCREMENT,
                oven_id smallint(4) NOT NULL,
                jaar smallint(4) NOT NULL,
                maand tinyint(2) NOT NULL,
                dag tinyint(1) NOT NULL,
                gebruiker_id int(10) NOT NULL,
                temperatuur int(10),
                soortstook tinytext,
                programma smallint(4),
                gemeld tinyint(1) DEFAULT 0,
                verwerkt tinyint(1) DEFAULT 0,
                verdeling text,
                opmerking tinytext,
                PRIMARY KEY (id)
                ) $charset_collate;"
			);

			dbDelta(
				"CREATE TABLE {$wpdb->prefix}kleistad_ovens (
                id int(10) NOT NULL AUTO_INCREMENT,
                naam tinytext,
                kosten numeric(10,2),
                beschikbaarheid tinytext,
                PRIMARY KEY (id)
                ) $charset_collate;"
			);

			dbDelta(
				"CREATE TABLE {$wpdb->prefix}kleistad_cursussen (
                id int(10) NOT NULL AUTO_INCREMENT,
                naam tinytext,
                start_datum date,
                eind_datum date,
                start_tijd time,
                eind_tijd time,
                docent tinytext,
                technieken tinytext,
                vervallen tinyint(1) DEFAULT 0,
                vol tinyint(1) DEFAULT 0,
                techniekkeuze tinyint(1) DEFAULT 0,
                inschrijfkosten numeric(10,2),
                cursuskosten numeric(10,2),
                inschrijfslug tinytext,
                indelingslug tinytext,
				maximum tinyint(2) DEFAULT 99,
				meer tinyint(1) DEFAULT 0,
				tonen tinyint(1) DEFAULT 0,
                PRIMARY KEY (id)
              ) $charset_collate;"
			);
			update_option( 'kleistad-database-versie', self::DBVERSIE );

			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}kleistad_cursussen SET tonen = %d", 1 ) );
		}

		if ( ! wp_next_scheduled( 'kleistad_kosten' ) ) {
			wp_schedule_event( strtotime( 'midnight' ), 'daily', 'kleistad_kosten' );
		}

		/*
		* n.b. in principe heeft de (toekomstige) rol bestuurde de override capability en de (toekomstige) rol lid de reserve capability
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
