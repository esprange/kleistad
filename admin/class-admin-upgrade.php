<?php
/**
 * De admin functies van de kleistad plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
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
	const DBVERSIE = 96;

	/**
	 * Voer de upgrade acties uit indien nodig.
	 *
	 * @since 6.1.0
	 */
	public function run() {
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
	private function convert_opties() {
		$default_options = [
			'onbeperkt_abonnement' => 50,
			'beperkt_abonnement'   => 30,
			'dagdelenkaart'        => 60,
			'cursusprijs'          => 130,
			'cursusinschrijfprijs' => 25,
			'cursusmaximum'        => 12,
			'workshopprijs'        => 120,
			'termijn'              => 4,
			'extra'                => [],
			'oven_midden'          => 1100,
			'oven_hoog'            => 1200,
		];
		$default_setup   = [
			'sleutel'            => '',
			'sleutel_test'       => '',
			'google_kalender_id' => '',
			'google_sleutel'     => '',
			'google_client_id'   => '',
			'imap_server'        => '',
			'imap_pwd'           => '',
			'betalen'            => 0,
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
	 */
	public function convert_database() {
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
			opmerking tinytext,
			PRIMARY KEY  (id)
			) $charset_collate;"
		);

		dbDelta(
			"CREATE TABLE {$wpdb->prefix}kleistad_ovens (
			id int(10) NOT NULL AUTO_INCREMENT,
			naam tinytext,
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
			start_tijd time,
			eind_tijd time,
			docent tinytext,
			technieken tinytext,
			organisatie tinytext DEFAULT '',
			organisatie_adres tinytext DEFAULT '',
			organisatie_email tinytext DEFAULT '',
			contact tinytext,
			email tinytext,
			telefoon tinytext,
			programma text,
			vervallen tinyint(1) DEFAULT 0,
			kosten numeric(10,2),
			aantal tinyint(2) DEFAULT 99,
			betaald tinyint(1) DEFAULT 0,
			definitief tinyint(1) DEFAULT 0,
			betaling_email tinyint(1) DEFAULT 0,
			aanvraag_id int(10) DEFAULT 0,
			PRIMARY KEY  (id)
			) $charset_collate;"
		);

		dbDelta(
			"CREATE TABLE {$wpdb->prefix}kleistad_orders (
			id int(10) NOT NULL AUTO_INCREMENT,
			betaald numeric(10,2) DEFAULT 0,
			datum datetime,
			credit_id int(10) DEFAULT 0,
			origineel_id int(10) DEFAULT 0,
			gesloten tinyint(1) DEFAULT 0,
			historie varchar(2000),
			klant tinytext,
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
	}

	// phpcs:disable

	/**
	 * Convert saldo, omdat de key wijzigt zal dit maar één keer uitgevoerd worden.
	 */
	private function convert_saldo() {
	}

	/**
	 * Convert dagdelenkaart, er wordt gecontroleerd of er een enkel record bestaat.
	 */
	private function convert_dagdelenkaart() {
		$dagdelengebruikers = get_users(
			[
				'meta_key' => 'kleistad_dagdelenkaart',
				'fields'   => [ 'ID' ],
			]
		);
		foreach( $dagdelengebruikers as $dagdelengebruiker ) {
			if ( get_user_meta( $dagdelengebruiker->ID, 'kleistad_dagdelenkaart_v2' ) ) {
				continue;
			}
			$dagdelenkaarten = get_user_meta( $dagdelengebruiker->ID, 'kleistad_dagdelenkaart', true );
			$nieuw = [];
			foreach( $dagdelenkaarten as $dagdelenkaart ) {
				$nieuw[] = [
					'code'        => $dagdelenkaart['code'],
					'datum'       => strtotime( $dagdelenkaart['datum'] ),
					'start_datum' => strtotime( $dagdelenkaart['start_datum'] ),
					'eind_datum'  => strtotime( '+3 month ' . $dagdelenkaart['start_datum'] ),
					'geannuleerd' => $dagdelenkaart['geannuleerd'],
					'opmerking'   => $dagdelenkaart['opmerking'],
				];
			}
			add_user_meta( $dagdelengebruiker->ID, 'kleistad_dagdelenkaart_v2', $nieuw, true ); 
		}
	}

	/**
	 * Convert abonnement, geef aan dat er geen overbrugging email meer voor oude abo's hoeft te worden gestuurd.
	 */
	private function convert_abonnement() {
		$abonnees = get_users(
			[
				'meta_key' => 'kleistad_abonnement',
				'fields'   => [ 'ID' ],
			]
		);
		foreach ( $abonnees as $abonnee ) {
			if ( get_user_meta( $abonnee->ID, 'kleistad_abonnement_v2' ) ) {
				continue;
			}
			$abonnement = get_user_meta( $abonnee->ID, 'kleistad_abonnement', true );
			$nieuw      = [
				'code'               => $abonnement['code'],
				'datum'              => strtotime( $abonnement['datum'] ),
				'start_datum'        => strtotime( $abonnement['start_datum'] ),
				'start_eind_datum'   => strtotime( $abonnement['start_eind_datum'] ),
				'dag'                => $abonnement['dag'],
				'opmerking'          => $abonnement['opmerking'],
				'soort'              => $abonnement['soort'],
				'pauze_datum'        => empty( $abonnement['pauze_datum'] ) ? 0 : strtotime( $abonnement['pauze_datum'] ),
				'eind_datum'         => empty( $abonnement['eind_datum'] ) ? 0 : strtotime( $abonnement['eind_datum'] ),
				'herstart_datum'     => empty( $abonnement['herstart_datum'] ) ? 0 : strtotime( $abonnement['herstart_datum'] ),
				'reguliere_datum'    => empty( $abonnement['reguliere_datum'] ) ? 0 : strtotime( $abonnement['reguliere_datum'] ),
				'overbrugging_email' => boolval( $abonnement['overbrugging_email'] ),
				'extras'             => isset( $abonnement['extras'] ) ? $abonnement['extras'] : [],
				'factuur_maand'      => isset( $abonnement['factuur_maand'] ) ? $abonnement['factuur_maand'] : 0,
				'historie'           => json_decode( isset( $abonnement[ 'historie' ] ) ? $abonnement[ 'historie' ] : '', true ) ?: [],
			];
			add_user_meta( $abonnee->ID, 'kleistad_abonnement_v2', $nieuw, true ); 
		}
	}

	/**
	 * Converteer inschrijving, maak de orders aan.
	 */
	private function convert_inschrijving() {
		$cursisten = get_users(
			[
				'meta_key' => 'kleistad_cursus',
				'fields'   => [ 'ID' ],
			]
		);
		foreach ( $cursisten as $cursist ) {
			if ( get_user_meta( $cursist->ID, 'kleistad_inschrijving' ) ) {
				continue;
			}
			$inschrijvingen = get_user_meta( $cursist->ID, 'kleistad_cursus', true );
			$nieuw = [];
			foreach ( $inschrijvingen as $cursus_id => $inschrijving ) {
				$nieuw[$cursus_id] = [
					'code'             => $inschrijving['code'],
					'datum'            => strtotime( $inschrijving['datum'] ),
					'technieken'       => isset( $inschrijving['technieken'] ) ? $inschrijving['technieken'] : [],
					'ingedeeld'        => boolval( $inschrijving['ingedeeld'] ),
					'geannuleerd'      => boolval( $inschrijving['geannuleerd'] ),
					'opmerking'        => $inschrijving['opmerking'],
					'aantal'           => $inschrijving['aantal'],
					'restant_email'    => boolval( $inschrijving['restant_email'] ),
					'herinner_email'   => boolval( $inschrijving['herinner_email'] ),
					'wacht_datum'      => empty( $inschrijving['wacht_datum'] ) ? 0 : strtotime( $inschrijving['wacht_datum'] ),
					'extra_cursisten'  => isset( $inschrijving['extra_cursisten'] ) ? $inschrijving['extra_cursisten'] : [],
					'hoofd_cursist_id' => isset( $inschrijving['hoofd_cursist_id'] ) ? $inschrijving['hoofd_cursist_id'] : 0,
				];
			}
			add_user_meta( $cursist->ID, 'kleistad_inschrijving', $nieuw, true ); 
		}
	}

	/**
	 * Converteer emails
	 */
	private function convert_email() {
	}

	/**
	 * Converteer cursussen
	 */
	private function convert_cursus() {
	}

	/**
	 * Converteer de orders.
	 */
	private function convert_order() {
	}

	/**
	 * Converteer recepten en gerelateerde elementen.
	 */
	private function convert_recept() {
	}

	/**
	 * Converteer gebruikers.
	 */
	private function convert_users() {
	}

	/**
	 * Converteer de ovens.
	 */
	private function convert_ovens() {
	}

	/**
	 * Converteer stookreserveringen.
	 */
	private function convert_reserveringen() {
		global $wpdb;
		$wpdb->query( "UPDATE {$wpdb->prefix}kleistad_reserveringen SET datum = concat( jaar, '-', maand, '-', dag ) WHERE datum is NULL" );
	}

	/**
	 * Converteer de corona reserveringen naar werkplek gebruik
	 */
	private function convert_werkplekgebruik() {
		global $wpdb;
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}options WHERE option_name like 'kleistad_corona_%'" );
		if ( is_array( $results ) ) {
			foreach( $results as $corona_regel ) {
				$datum = strtotime( str_replace( '-', '/', strtok( $corona_regel->option_name, 'kleistad_corona_' ) ) );
				if ( $datum && false === get_option( 'kleistad_werkplek_' . date( 'Ymd', $datum ) ) ) {
					$werkplekgebruik = new WerkplekGebruik( $datum );
					$reserveringen   = get_option( $corona_regel->option_name );
					$index = 0;
					$oude_labels = [ 'H', 'D', 'B' ];
					foreach ( WerkplekConfig::DAGDEEL as $dagdeel ) {
						foreach ( WerkplekConfig::ACTIVITEIT as $activiteit ) {
							$werkplekgebruik->wijzig( $dagdeel, $activiteit, $reserveringen[ $index ][ $oude_labels[ $index ] ] ?? [] );
						};
						$index++;
					}
				}
			}
		}
	}


	// phpcs:enable

	/**
	 * Converteer data
	 */
	private function convert_data() {
		/**
		 * Conversie naar ...
		 */
		$this->convert_saldo();
		$this->convert_dagdelenkaart();
		$this->convert_abonnement();
		$this->convert_inschrijving();
		$this->convert_email();
		$this->convert_cursus();
		$this->convert_order();
		$this->convert_opties();
		$this->convert_recept();
		$this->convert_users();
		$this->convert_ovens();
		$this->convert_reserveringen();
		$this->convert_werkplekgebruik();
	}
}
