<?php
/**
 * De admin functies van de kleistad plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 *
 * @suppressWarnings(PHPMD)
 */

namespace Kleistad;

/**
 * Eventuele upgrades van data en databse bij nieuwe versies van de plugin.
 */
class Admin_Upgrade {

	/**
	 * Plugin-database-versie
	 */
	const DBVERSIE = 171;

	/**
	 * Voer de upgrade acties uit indien nodig.
	 *
	 * @since 6.1.0
	 */
	public function run() : void {
		$data = get_plugin_data( plugin_dir_path( dirname( __FILE__ ) ) . 'kleistad.php', false, false );
		update_option( 'kleistad-plugin-versie', $data['Version'] );
		$database_version = intval( get_option( 'kleistad-database-versie', 0 ) );
		if ( $database_version < self::DBVERSIE ) {
			$this->convert_opties();
			$this->convert_database();
			$this->convert_data();
			update_option( 'kleistad-database-versie', self::DBVERSIE );
		}
	}

	/**
	 * Converteer opties.
	 */
	private function convert_opties() : void {
		$default_options = [
			'onbeperkt_abonnement' => 50,
			'beperkt_abonnement'   => 30,
			'dagdelenkaart'        => 60,
			'cursusprijs'          => 130,
			'cursusinschrijfprijs' => 25,
			'cursusmaximum'        => 12,
			'workshopprijs'        => 120,
			'materiaalprijs'       => 2.50,
			'administratiekosten'  => 1.00,
			'termijn'              => 4,
			'start_maanden'        => 3,
			'min_pauze_weken'      => 2,
			'max_pauze_weken'      => 9,
			'extra'                => [],
			'oven_midden'          => 1100,
			'oven_hoog'            => 1200,
			'stook_max'            => 2,
			'weken_werkplek'       => 5,
			'weken_workshop'       => 12,
			'workshop_wijzigbaar'  => 0,
			'max_activiteit'       => 1,
			'verloopaanvraag'      => 1,
			'werkruimte'           => [
				[
					'naam'  => 'Handvormen',
					'kleur' => '#E1E599',
				],
				[
					'naam'  => 'Draaien',
					'kleur' => '#F7CAAC',
				],
				[
					'naam'  => 'Bovenverdieping',
					'kleur' => '#D9D9D9',
				],
			],
			'activiteit'           => [
				[ 'naam' => 'Workshop' ],
				[ 'naam' => 'Kinderfeest' ],
			],
			'actpauze'             => [],
		];
		$default_setup   = [
			'sleutel'            => '',
			'sleutel_test'       => '',
			'google_kalender_id' => '',
			'google_sleutel'     => '',
			'google_client_id'   => '',
			'imap_server'        => '',
			'imap_adres'         => '',
			'imap_pwd'           => '',
			'betalen'            => 0,
			'stort'              => 1,
			'profiel'            => 0,
		];
		$current_options = get_option( 'kleistad-opties', [] );
		$current_setup   = get_option( 'kleistad-setup', [] );
		$options         = [];
		$setup           = [];
		foreach ( array_keys( $default_options ) as $key ) {
			if ( isset( $current_options[ $key ] ) ) {
				$options[ $key ] = $current_options[ $key ];
			}
		}
		foreach ( array_keys( $default_setup ) as $key ) {
			if ( isset( $current_setup[ $key ] ) ) {
				$setup[ $key ] = $current_setup[ $key ];
			} elseif ( isset( $current_options[ $key ] ) ) {
				$setup[ $key ] = $current_options[ $key ];
			}
		}
		update_option( 'kleistad-opties', wp_parse_args( $options, $default_options ) );
		update_option( 'kleistad-setup', wp_parse_args( $setup, $default_setup ) );
	}

	/**
	 * Converteer de database.
	 *
	 * @suppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function convert_database() : void {
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
			datum date,
			gebruiker_id int(10) NOT NULL,
			temperatuur int(10),
			soortstook tinytext,
			programma smallint(4),
			gemeld tinyint(1) DEFAULT 0,
			verwerkt tinyint(1) DEFAULT 0,
			verdeling text,
			opmerking tinytext DEFAULT '',
			PRIMARY KEY  (id)
			) $charset_collate;"
		);

		dbDelta(
			"CREATE TABLE {$wpdb->prefix}kleistad_ovens (
			id int(10) NOT NULL AUTO_INCREMENT,
			naam tinytext DEFAULT '',
			kosten_laag numeric(10,2),
			kosten_midden numeric(10,2),
			kosten_hoog numeric(10,2),
			beschikbaarheid tinytext,
			PRIMARY KEY  (id)
			) $charset_collate;"
		);

		dbDelta(
			"CREATE TABLE {$wpdb->prefix}kleistad_cursussen (
			id int(10) NOT NULL AUTO_INCREMENT,
			naam tinytext,
			start_datum date,
			eind_datum date,
			ruimte_datum datetime,
			lesdatums varchar(2000),
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
			PRIMARY KEY  (id)
			) $charset_collate;"
		);

		dbDelta(
			"CREATE TABLE {$wpdb->prefix}kleistad_workshops (
			id int(10) NOT NULL AUTO_INCREMENT,
			naam tinytext,
			datum date,
			aanvraagdatum date,
			start_tijd time,
			eind_tijd time,
			docent tinytext,
			technieken tinytext,
			organisatie tinytext DEFAULT '',
			organisatie_adres tinytext DEFAULT '',
			organisatie_email tinytext DEFAULT '',
			contact tinytext,
			email tinytext,
			telnr tinytext,
			programma text,
			vervallen tinyint(1) DEFAULT 0,
			kosten numeric(10,2),
			aantal tinyint(2) DEFAULT 99,
			betaald tinyint(1) DEFAULT 0,
			definitief tinyint(1) DEFAULT 0,
			betaling_email tinyint(1) DEFAULT 0,
			aanvraag_id int(10) DEFAULT 0,
			communicatie longblob DEFAULT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;"
		);

		dbDelta(
			"CREATE TABLE {$wpdb->prefix}kleistad_inschrijvingen (
			cursist_id int(10) NOT NULL,
			cursus_id int(10) NOT NULL,
			datum datetime,
			technieken tinytext,
			extra_cursisten tinytext,
			hoofd_cursist_id int(10),
			ingedeeld tinyint(1) DEFAULT 0,
			geannuleerd tinyint(1) DEFAULT 0,
			opmerking tinytext,
			aantal tinyint(2) DEFAULT 0,
			wacht_datum datetime,
			restant_email tinyint(1) DEFAULT 0,
			herinner_email tinyint(1) DEFAULT 0,
			maatwerkkosten numeric(10,2) DEFAULT 0,
			PRIMARY KEY  (cursist_id, cursus_id)
			) $charset_collate;"
		);
		dbDelta(
			"CREATE TABLE {$wpdb->prefix}kleistad_orders (
			id int(10) NOT NULL AUTO_INCREMENT,
			betaald numeric(10,2) DEFAULT 0,
			datum datetime,
			credit_id int(10) DEFAULT 0,
			origineel_id int(10) DEFAULT 0,
			credit tinyint(0) DEFAULT 0,
			gesloten tinyint(1) DEFAULT 0,
			historie varchar(2000),
			klant tinytext,
			klant_id int(10) DEFAULT 0,
			mutatie_datum datetime,
			verval_datum datetime,
			referentie varchar(30) NOT NULL,
			transactie_id varchar(20) NOT NULL DEFAULT '',
			regels varchar(2000),
			opmerking varchar(500),
			factuurnr int(10) DEFAULT 0,
			PRIMARY KEY  (id)
			) $charset_collate;"
		);

		if ( ! $wpdb->get_var( "SHOW INDEX FROM {$wpdb->prefix}kleistad_orders WHERE Key_name = 'referenties' " ) ) {
			$wpdb->query( "CREATE INDEX referenties ON {$wpdb->prefix}kleistad_orders (referentie)" );
		}
		if ( ! $wpdb->get_var( "SHOW INDEX FROM {$wpdb->prefix}kleistad_orders WHERE Key_name = 'transactie_ids' " ) ) {
			$wpdb->query( "CREATE INDEX transactie_ids ON {$wpdb->prefix}kleistad_orders (transactie_id)" );
		}
	}

	// phpcs:disable

	/**
	 * Converteer data
	 */
	private function convert_data() : void {
	}

}
