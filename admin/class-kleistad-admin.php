<?php
/**
 * De admin functies van de kleistad plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

/**
 * De admin-specifieke functies van de plugin.
 */
class Kleistad_Admin {

	/**
	 * Plugin-database-versie
	 */
	const DBVERSIE = 17;

	/**
	 * De versie van de plugin.
	 *
	 * @since    4.0.87
	 * @access   private
	 * @var      string    $version    De huidige versie.
	 */
	private $version;

	/**
	 *  De plugin opties
	 *
	 * @since     4.0.87
	 * @access    private
	 * @var       array     $options  De plugin options
	 */
	private $options;

	/**
	 *  Oven beheer
	 *
	 * @since     5.0.2
	 * @access    private
	 * @var       object    $ovens_handler  De handler voor ovens beheer
	 */
	private $ovens_handler;

	/**
	 *  Cursisten beheer
	 *
	 * @since     5.0.2
	 * @access    private
	 * @var       object    $cursisten_handler  De handler voor cursisten beheer
	 */
	private $cursisten_handler;

	/**
	 *  Abonnees beheer
	 *
	 * @since     5.0.2
	 * @access    private
	 * @var       object    $abonnees_handler  De handler voor abonnees beheer
	 */
	private $abonnees_handler;

	/**
	 *  Stooksaldo beheer
	 *
	 * @since     5.0.2
	 * @access    private
	 * @var       object    $stooksaldo_handler  De handler voor stooksaldo beheer
	 */
	private $stooksaldo_handler;

	/**
	 *  Regeling stookkosten beheer
	 *
	 * @since     5.0.2
	 * @access    private
	 * @var       object    $regelingen_handler  De handler voor regeling stookkosten beheer
	 */
	private $regelingen_handler;

	/**
	 * Initializeer het object.
	 *
	 * @since    4.0.87
	 * @param      string $version     De versie van de plugin.
	 * @param      array  $options     De plugin options.
	 */
	public function __construct( $version, $options ) {
		$this->version            = $version;
		$this->options            = $options;
		$this->ovens_handler      = new Kleistad_Admin_Ovens_Handler();
		$this->cursisten_handler  = new Kleistad_Admin_Cursisten_Handler();
		$this->abonnees_handler   = new Kleistad_Admin_Abonnees_Handler();
		$this->stooksaldo_handler = new Kleistad_Admin_Stooksaldo_Handler();
		$this->regelingen_handler = new Kleistad_Admin_Regelingen_Handler();
	}

	/**
	 * Registreer de stylesheets van de admin functies.
	 *
	 * @since    4.0.87
	 */
	public function enqueue_scripts_and_styles() {
		wp_enqueue_style( 'kleistad_admin', plugin_dir_url( __FILE__ ) . 'css/kleistad-admin.css', [], $this->version, 'all' );
		wp_enqueue_style( 'jqueryui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', [], '1.12.1' );
		wp_enqueue_script( 'kleistad_admin', plugin_dir_url( __FILE__ ) . 'js/kleistad-admin.js', [ 'jquery', 'jquery-ui-datepicker' ], $this->version, false );
	}

	/**
	 * Definieer de admin panels
	 *
	 * @since    4.0.87
	 */
	public function add_plugin_admin_menu() {
		add_menu_page( 'Instellingen', 'Kleistad', 'manage_options', 'kleistad', [ $this, 'display_settings_page' ], plugins_url( '/images/kleistad_icon.png', __FILE__ ), ++$GLOBALS['_wp_last_object_menu'] );
		$this->ovens_handler->add_pages();
		$this->abonnees_handler->add_pages();
		$this->cursisten_handler->add_pages();
		$this->stooksaldo_handler->add_pages();
		$this->regelingen_handler->add_pages();
	}

	/**
	 * Registreer de exporter van privacy gevoelige data.
	 *
	 * @since 4.3.0
	 *
	 * @param array $exporters De exporters die WP aanroept bij het genereren van de zip file.
	 */
	public function register_exporter( $exporters ) {
		$exporters['kleistad'] = [
			'exporter_friendly_name' => 'plugin folder Kleistad',
			'callback'               => [ 'Kleistad_Admin_GDPR', 'exporter' ],
		];
		return $exporters;
	}

	/**
	 * Registreer de eraser van privacy gevoelige data.
	 *
	 * @since 4.3.0
	 *
	 * @param array $erasers De erasers die WP aanroept bij het verwijderen persoonlijke data.
	 */
	public function register_eraser( $erasers ) {
		$erasers['kleistad'] = [
			'eraser_friendly_name' => 'plugin folder Kleistad',
			'callback'             => [ 'Kleistad_Admin_GDPR', 'eraser' ],
		];
		return $erasers;
	}

	/**
	 * Auto update van de plugin via het administrator board.
	 *
	 * @since 4.3.8
	 *
	 * @param  object $transient Het object waarin WP de updates deelt.
	 * @return object De transient.
	 */
	public function check_update( $transient ) {
		if ( ! empty( $transient->checked ) ) {
			$obj = $this->get_remote( 'version' );
			if ( false !== $obj ) {
				if ( version_compare( $this->version, $obj->new_version, '<' ) ) {
					$transient->response[ $obj->plugin ] = $obj;
				} else {
					$transient->no_update[ $obj->plugin ] = $obj;
				}
			}
		}
		return $transient;
	}

	/**
	 * Haal informatie op, aangeroepen vanuit API plugin hook.
	 *
	 * @since 4.3.8
	 *
	 * @param  object $obj Wordt niet gebruikt.
	 * @param  string $action De gevraagde actie.
	 * @param  object $arg Argument door WP ingevuld.
	 * @return bool|object
	 */
	public function check_info( $obj, $action = '', $arg = null ) {
		if ( ( 'query_plugins' === $action || 'plugin_information' === $action ) && isset( $arg->slug ) && 'kleistad' === $arg->slug ) {
			$plugin_info  = get_site_transient( 'update_plugins' );
			$arg->version = $plugin_info->checked['kleistad/kleistad.php'];
			$info         = $this->get_remote( 'info' );
			if ( false !== $info ) {
				return $info;
			}
		}
		return $obj;
	}

	/**
	 * Haal de info bij de update server op.
	 *
	 * @since 4.3.8
	 *
	 * @param  string $action De gevraagde actie.
	 * @return bool|object remote info.
	 */
	public function get_remote( $action = '' ) {
		$params  = [
			'body' => [
				'action' => $action,
			],
		];
		$request = wp_remote_post( 'http://sprako.xs4all.nl/kleistad_plugin/update.php', $params );
		if ( ! is_wp_error( $request ) || ( is_array( $request ) && wp_remote_retrieve_response_code( $request ) === 200 ) ) {
			// phpcs:ignore
			return @unserialize( $request['body'] );
		}
		return false;
	}

	/**
	 * Aangeroepen na update van de kleistad opties.
	 *
	 * @param array $oud Oude waarde.
	 * @param array $nieuw Nieuwe waarde.
	 * @since 5.0.0
	 */
	public function opties_gewijzigd( $oud, $nieuw ) {
		if ( $oud['google_sleutel'] !== $nieuw['google_sleutel'] ||
			$oud['google_client_id'] !== $nieuw['google_client_id'] ) {
			delete_option( Kleistad_Google::ACCESS_TOKEN );
		}
	}

	/**
	 * Maak de body op.
	 *
	 * @param array $arr Key/value parameters.
	 */
	private function maak_body( $arr ) {
		$text = '';
		foreach ( $arr as $key => $value ) {
			$length = strlen( $value );
			$text  .= "--#$#\r\nContent-Disposition: form-data; name=\"$key\"\r\nContent-Length: $length\r\n\r\n$value\r\n";
		}
		$text .= '--#$#--';
		return $text;
	}

	/**
	 * Registreer de kleistad settings, uitgevoerd tijdens admin init.
	 *
	 * @since   4.0.87
	 */
	public function initialize() {
		$this->database_version();
		register_setting( 'kleistad-opties', 'kleistad-opties', [ $this, 'validate_settings' ] );
	}

	/**
	 * Controleer of de database voldoet aan de juiste versie.
	 *
	 * @since 5.4.1
	 */
	private function database_version() {
		global $wpdb;
		$database_version = intval( get_option( 'kleistad-database-versie', 0 ) );
		if ( $database_version < self::DBVERSIE ) {
			$charset_collate = $wpdb->get_charset_collate();

			$default_options = [
				'onbeperkt_abonnement' => 50,
				'beperkt_abonnement'   => 30,
				'borg_kast'            => 5,
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
				'extra'                => [],
			];
			$current_options = Kleistad::get_options();
			$options         = wp_parse_args( empty( $current_options ) ? '' : $current_options, $default_options );
			update_option( 'kleistad-opties', $options );

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
                PRIMARY KEY  (id)
              ) $charset_collate;"
			);
			update_option( 'kleistad-database-versie', self::DBVERSIE );
		}
	}

	/**
	 * Toon de instellingen page van de plugin.
	 *
	 * @since    4.0.87
	 */
	public function display_settings_page() {
		add_meta_box( 'kleistad_instellingen_form_meta_box', 'Instellingen', [ $this, 'instellingen_form_meta_box_handler' ], 'instellingen', 'normal', 'default' );
		add_meta_box( 'kleistad_google_connect_meta_box', 'Connect Google Kalender', [ $this, 'google_connect_meta_box_handler' ], 'google_connect', 'normal', 'default' );
		add_meta_box( 'kleistad_shortcodes_meta_box', 'Gebruik van de plugin', [ $this, 'shortcodes_meta_box_handler' ], 'shortcodes', 'normal', 'default' );
		add_meta_box( 'kleistad_email_parameters_meta_box', 'E-Mail Parameters', [ $this, 'email_parameters_meta_box_handler' ], 'email_parameters', 'normal', 'default' );

		require 'partials/kleistad-admin-display-settings.php';
	}

	/**
	 * Toon de custom meta box met de instellingen
	 *
	 * @since    4.0.87
	 */
	public function instellingen_form_meta_box_handler() {
		require 'partials/kleistad-admin-instellingen-form-meta-box.php';
	}

	/**
	 * Toon het overzicht van de shortcodes in een meta box
	 *
	 * @since    4.0.87
	 */
	public function shortcodes_meta_box_handler() {
		require 'partials/kleistad-admin-shortcodes-meta-box.php';
	}

	/**
	 * Toon de emails en hun parameters in een meta box
	 *
	 * @since    4.0.87
	 */
	public function email_parameters_meta_box_handler() {
		require 'partials/kleistad-admin-email-parameters-meta-box.php';
	}

	/**
	 * Toon de emails en hun parameters in een meta box
	 *
	 * @since    5.0.0
	 */
	public function google_connect_meta_box_handler() {
		$result = true;
		if ( ! is_null( filter_input( INPUT_POST, 'connect' ) ) ) {
			Kleistad_Google::vraag_service_aan( admin_url( 'admin.php?page=kleistad&tab=google_connect' ) );
		}
		if ( ! is_null( filter_input( INPUT_GET, 'code' ) ) ) {
			$result = Kleistad_Google::koppel_service();
		}
		require 'partials/kleistad-admin-google-connect-meta-box.php';
	}

	/**
	 * Valideer de ingevoerde instellingen
	 *
	 * @since    4.0.87
	 *
	 * @param array $input de ingevoerde instellingen.
	 * @return array  $input
	 */
	public function validate_settings( $input ) {
		foreach ( $input as &$element ) {
			if ( is_string( $element ) ) {
				$element = sanitize_text_field( $element );
			} else {
				if ( is_array( $element ) ) {
					$element = $this->validate_settings( $element );
				}
			}
		}
		return $input;
	}
}
