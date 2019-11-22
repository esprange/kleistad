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

namespace Kleistad;

/**
 * De admin-specifieke functies van de plugin.
 */
class Admin_Main {

	/**
	 * Plugin-database-versie
	 */
	const DBVERSIE = 27;

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
	 * @var       array     $options  De plugin options.
	 */
	private $options;

	/**
	 *  Oven beheer
	 *
	 * @since     5.0.2
	 * @access    private
	 * @var       object    $ovens_handler  De handler voor ovens beheer.
	 */
	private $ovens_handler;

	/**
	 *  Cursisten beheer
	 *
	 * @since     5.0.2
	 * @access    private
	 * @var       object    $cursisten_handler  De handler voor cursisten beheer.
	 */
	private $cursisten_handler;

	/**
	 *  Abonnees beheer
	 *
	 * @since     5.0.2
	 * @access    private
	 * @var       object    $abonnees_handler  De handler voor abonnees beheer.
	 */
	private $abonnees_handler;

	/**
	 *  Stooksaldo beheer
	 *
	 * @since     5.0.2
	 * @access    private
	 * @var       object    $stooksaldo_handler  De handler voor stooksaldo beheer.
	 */
	private $stooksaldo_handler;

	/**
	 *  Regeling stookkosten beheer
	 *
	 * @since     5.0.2
	 * @access    private
	 * @var       object    $regelingen_handler  De handler voor regeling stookkosten beheer.
	 */
	private $regelingen_handler;

	/**
	 * Background object
	 *
	 * @since   6.1.0
	 * @access  private
	 * @var     object   $background Het background object.
	 */
	private static $background;

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
		$this->ovens_handler      = new \Kleistad\Admin_Ovens_Handler();
		$this->cursisten_handler  = new \Kleistad\Admin_Cursisten_Handler();
		$this->abonnees_handler   = new \Kleistad\Admin_Abonnees_Handler();
		$this->stooksaldo_handler = new \Kleistad\Admin_Stooksaldo_Handler();
		$this->regelingen_handler = new \Kleistad\Admin_Regelingen_Handler();
	}

	/**
	 * Registreer de stylesheets van de admin functies.
	 *
	 * @since    4.0.87
	 */
	public function enqueue_scripts_and_styles() {
		wp_enqueue_style( 'kleistad_admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', [], $this->version, 'all' );
		wp_enqueue_style( 'jqueryui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', [], '1.12.1' );
		wp_enqueue_script( 'kleistad_admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', [ 'jquery', 'jquery-ui-datepicker' ], $this->version, false );
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
			'callback'               => [ 'Admin_Main_GDPR', 'exporter' ],
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
			'callback'             => [ 'Admin_Main_GDPR', 'eraser' ],
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
			delete_option( \Kleistad\Google::ACCESS_TOKEN );
		}
	}

	/**
	 * Bereid het background proces voor.
	 */
	public function instantiate_background() {
		if ( is_null( self::$background ) ) {
			self::$background = new \Kleistad\Background();
		}
	}

	/**
	 * Doe de dagelijkse jobs
	 */
	public function daily_jobs() {
		self::$background->push_to_queue( '\Kleistad\Shortcode::cleanup_downloads' );
		self::$background->push_to_queue( '\Kleistad\Workshop::dagelijks' );
		self::$background->push_to_queue( '\Kleistad\Abonnement::dagelijks' );
		self::$background->push_to_queue( '\Kleistad\Saldo::dagelijks' );
		self::$background->save()->dispatch();
	}

	/**
	 * Registreer de kleistad settings, uitgevoerd tijdens admin init.
	 *
	 * @since   4.0.87
	 */
	public function initialize() {
		$upgrade = new \Kleistad\Admin_Upgrade();
		$upgrade->run();

		ob_start();
		if ( ! wp_next_scheduled( 'kleistad_rcv_email' ) ) {
			$time = time();
			wp_schedule_event( $time + ( 900 - ( $time % 900 ) ), '15_mins', 'kleistad_rcv_email' );
		}
		if ( ! wp_next_scheduled( 'kleistad_daily_jobs' ) ) {
			wp_schedule_event( strtotime( '08:00' ), 'daily', 'kleistad_daily_jobs' );
		}

		register_setting( 'kleistad-opties', 'kleistad-opties', [ 'sanitize_callback' => [ $this, 'validate_settings' ] ] );
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

		require 'partials/admin-display-settings.php';
	}

	/**
	 * Toon de custom meta box met de instellingen
	 *
	 * @since    4.0.87
	 */
	public function instellingen_form_meta_box_handler() {
		require 'partials/admin-instellingen-form-meta-box.php';
	}

	/**
	 * Toon het overzicht van de shortcodes in een meta box
	 *
	 * @since    4.0.87
	 */
	public function shortcodes_meta_box_handler() {
		require 'partials/admin-shortcodes-meta-box.php';
	}

	/**
	 * Toon de emails en hun parameters in een meta box
	 *
	 * @since    4.0.87
	 */
	public function email_parameters_meta_box_handler() {
		require 'partials/admin-email-parameters-meta-box.php';
	}

	/**
	 * Toon de emails en hun parameters in een meta box
	 *
	 * @since    5.0.0
	 */
	public function google_connect_meta_box_handler() {
		$result = true;
		if ( ! is_null( filter_input( INPUT_POST, 'connect' ) ) ) {
			\Kleistad\Google::vraag_service_aan( admin_url( 'admin.php?page=kleistad&tab=google_connect' ) );
		}
		if ( ! is_null( filter_input( INPUT_GET, 'code' ) ) ) {
			$result = \Kleistad\Google::koppel_service();
		}
		require 'partials/admin-google-connect-meta-box.php';
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
