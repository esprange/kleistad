<?php
/**
 * De admin functies van de kleistad plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * Eventuele upgrades van data en databse bij nieuwe versies van de plugin.
 */
class Admin_Upgrade {

	/**
	 * Plugin-database-versie
	 */
	const DBVERSIE = 43;

	/**
	 * Voer de upgrade acties uit indien nodig.
	 *
	 * @since 6.1.0
	 */
	public function run() {
		$database_version = intval( get_option( 'kleistad-database-versie', 0 ) );
		if ( $database_version < self::DBVERSIE ) {
			$this->convert_opties();
			$this->convert_database();
			$this->convert_data();
			$this->convert_roles();
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
			'sleutel'              => '',
			'sleutel_test'         => '',
			'google_kalender_id'   => '',
			'google_sleutel'       => '',
			'google_client_id'     => '',
			'imap_server'          => '',
			'imap_pwd'             => '',
			'betalen'              => 0,
			'factureren'           => '',
			'extra'                => [],
		];
		$current_options = \Kleistad\Kleistad::get_options();
		$options         = wp_parse_args( empty( $current_options ) ? [] : $current_options, $default_options );
		update_option( 'kleistad-opties', $options );
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
			kosten numeric(10,2),
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
			organisatie tinytext,
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
			referentie varchar(20) NOT NULL,
			regels varchar(2000),
			opmerking varchar(200),
			PRIMARY KEY  (id)
			) $charset_collate;"
		);

		if ( ! $wpdb->get_var( "SHOW INDEX FROM {$wpdb->prefix}kleistad_orders WHERE Key_name = 'referenties' " ) ) {
			$wpdb->query( "CREATE INDEX referenties ON {$wpdb->prefix}kleistad_orders (referentie)" );
		}
	}

	// phpcs:disable

	/**
	 * Omdat het onderstaand voornamelijk uitgecommentarieerde code bevat, de code style check uitgezet.
	 * De code is mogelijk in de toekomst nog in aangepaste vorm nodig.
	 */

	/**
	 * Convert saldo, omdat de key wijzigt zal dit maar één keer uitgevoerd worden.
	 */
	private function convert_saldo() {
		/*
		$saldo_users = get_users( [ 'meta_key' => 'stooksaldo' ] );
		foreach ( $saldo_users as $saldo_user ) {
			$huidig_saldo  = get_user_meta( $saldo_user->ID, 'stooksaldo', true );
			$saldo         = new \Kleistad\Saldo( $saldo_user->ID );
			$saldo->bedrag = (float) $huidig_saldo;
			$saldo->save();
			delete_user_meta( $saldo_user->ID, 'stooksaldo' );
		}
		*/
	}

	/**
	 * Convert dagdelenkaart, er wordt gecontroleerd of er een enkel record bestaat.
	 */
	private function convert_dagdelenkaart() {
		/*
		$dagdelenkaart_users = get_users( [ 'meta_key' => \Kleistad\Dagdelenkaart::META_KEY ] );
		foreach ( $dagdelenkaart_users as $dagdelenkaart_user ) {
			$huidig_dagdelenkaart = get_user_meta( $dagdelenkaart_user->ID, \Kleistad\Dagdelenkaart::META_KEY, true );
			if ( isset( $huidig_dagdelenkaart['code'] ) ) {
				$dagdelenkaart[1] = $huidig_dagdelenkaart;
				update_user_meta( $dagdelenkaart_user->ID, \Kleistad\Dagdelenkaart::META_KEY, $dagdelenkaart );
			}
		}
		*/
	}

	/**
	 * Convert abonnement, geef aan dat er geen overbrugging email meer voor oude abo's hoeft te worden gestuurd.
	 */
	private function convert_abonnement() {
		/*
		$betalen          = new \Kleistad\Betalen();
		$vandaag          = strtotime( 'today' );
		$abonnement_users = get_users( [ 'meta_key' => \Kleistad\Abonnement::META_KEY ] );
		foreach ( $abonnement_users as $abonnement_user ) {
			$abonnement                     = new \Kleistad\Abonnement( $abonnement_user->ID );
			$abonnement->overbrugging_email = $vandaag >= strtotime( '-7 days', $abonnement->driemaand_datum );
			if ( $betalen->heeft_mandaat( $abonnement_user->ID ) ) {
				$betalen->annuleer( $abonnement_user->ID );
			}
			$abonnement->save();
		}
		*/
	}

	/**
	 * Converteer inschrijving, maak de orders aan.
	 */
	private function convert_inschrijving() {
		/*
		$inschrijvingen = \Kleistad\Inschrijving::all();
		$cursussen      = \Kleistad\Cursus::all();
		$vandaag        = strtotime( 'today' );
		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			foreach ( $cursist_inschrijvingen as $cursus_id => $inschrijving ) {
				if ( $inschrijving->geannuleerd || $cursussen[ $cursus_id ]->vervallen || $vandaag > $cursussen[ $cursus_id ]->eind_datum ) {
					continue;
				}
				if ( $vandaag >= $cursussen[ $cursus_id ]->start_datum && ! $inschrijving->restant_email ) {
					$inschrijving->restant_email = true;
					$inschrijving->save();
				}
				if ( \Kleistad\Order::zoek_order( $inschrijving->referentie() ) ) {
					continue;
				}
				$betaald = $inschrijving->aantal *
					( intval( $inschrijving->i_betaald ) * (float) $cursussen[ $cursus_id ]->inschrijfkosten + intval( $inschrijving->c_betaald ) * (float) $cursussen[ $cursus_id ]->cursuskosten );
				$inschrijving->bestel_order( $betaald, 'cursus' );
			}
		}
		*/
	}

	/**
	 * Converteer emails
	 */
	private function convert_email() {
		/*
		global $wpdb;
		$wpdb->query( "UPDATE {$wpdb->prefix}posts SET post_type='kleistad_email', post_title=SUBSTRING( post_title, 16 ) WHERE post_title LIKE 'kleistad_email_%'" );
		*/
	}

	/**
	 * Converteer cursussen
	 */
	private function convert_cursus() {
		/*
		global $wpdb;
		$wpdb->query( "UPDATE {$wpdb->prefix}kleistad_cursussen SET inschrijfslug=SUBSTRING( inschrijfslug, 16 ) WHERE inschrijfslug LIKE 'kleistad_email_%'" );
		$wpdb->query( "UPDATE {$wpdb->prefix}kleistad_cursussen SET indelingslug=SUBSTRING( indelingslug, 16 ) WHERE indelingslug LIKE 'kleistad_email_%'" );
		*/
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
	}
}
