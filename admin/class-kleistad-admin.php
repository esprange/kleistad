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
	 * Het ID van de plugin.
	 *
	 * @since    4.0.87
	 * @access   private
	 * @var      string    $plugin_name    Het ID van de plugin.
	 */
	private $plugin_name;

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
	 * @param      string $plugin_name De naam van de plugin.
	 * @param      string $version     De versie van de plugin.
	 * @param      array  $options     De plugin options.
	 */
	public function __construct( $plugin_name, $version, $options ) {
		$this->plugin_name        = $plugin_name;
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
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/kleistad-admin.css', [], $this->version, 'all' );
		wp_enqueue_style( 'jqueryui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', [], '1.12.1' );
	}

	/**
	 * Registreer de JavaScript voor de admin functies.
	 *
	 * @since    4.0.87
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/kleistad-admin.js', [ 'jquery', 'jquery-ui-datepicker' ], $this->version, false );
		wp_localize_jquery_ui_datepicker();
	}

	/**
	 * Definieer de admin panels
	 *
	 * @since    4.0.87
	 */
	public function add_plugin_admin_menu() {
		add_menu_page( 'Instellingen', 'Kleistad', 'manage_options', $this->plugin_name, [ $this, 'display_settings_page' ], plugins_url( '/images/kleistad_icon.png', __FILE__ ), ++$GLOBALS['_wp_last_object_menu'] );
		$this->ovens_handler->add_pages( $this->plugin_name );
		$this->abonnees_handler->add_pages( $this->plugin_name );
		$this->cursisten_handler->add_pages( $this->plugin_name );
		$this->stooksaldo_handler->add_pages( $this->plugin_name );
		$this->regelingen_handler->add_pages( $this->plugin_name );
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
			'callback'               => [ get_class(), 'exporter' ],
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
			'callback'             => [ get_class(), 'eraser' ],
		];
		return $erasers;
	}

	/**
	 * Exporteer persoonlijke data.
	 *
	 * @since 4.3.0
	 *
	 * @param string $email Het email adres van de te exporteren persoonlijke data.
	 * @param int    $page  De pagina die opgevraagd wordt.
	 */
	public static function exporter( $email, $page = 1 ) {
		$export_items = [];
		$gebruiker_id = email_exists( $email );
		if ( false !== $gebruiker_id ) {
			$gebruiker    = get_userdata( $gebruiker_id );
			$export_items = array_merge(
				[
					[
						'group_id'    => 'contactinfo',
						'group_label' => 'Contact informatie',
						'item_id'     => 'contactinfo',
						'data'        => [
							[
								'name'  => 'Telefoonnummer',
								'value' => $gebruiker->telnr,
							],
							[
								'name'  => 'Straat',
								'value' => $gebruiker->straat,
							],
							[
								'name'  => 'Nummer',
								'value' => $gebruiker->huisnr,
							],
							[
								'name'  => 'Postcode',
								'value' => $gebruiker->pcode,
							],
							[
								'name'  => 'Plaats',
								'value' => $gebruiker->plaats,
							],
						],
					],
				],
				Kleistad_Inschrijving::export( $gebruiker_id ),
				Kleistad_Abonnement::export( $gebruiker_id ),
				Kleistad_Saldo::export( $gebruiker_id ),
				Kleistad_Reservering::export( $gebruiker_id )
			);
		}
		// Geef aan of er nog meer te exporteren valt, de controle op page nummer is een dummy.
		$done = ( 1 === $page ); // Dummy actie.
		return [
			'data' => $export_items,
			'done' => $done,
		];
	}

	/**
	 * Erase / verwijder persoonlijke data.
	 *
	 * @since 4.3.0
	 *
	 * @param string $email Het email adres van de te verwijderen persoonlijke data.
	 * @param int    $page  De pagina die opgevraagd wordt.
	 */
	public static function eraser( $email, $page = 1 ) {
		$count        = 0;
		$gebruiker_id = email_exists( $email );
		if ( false !== $gebruiker_id ) {
			update_user_meta( $gebruiker_id, 'telnr', '******' );
			update_user_meta( $gebruiker_id, 'straat', '******' );
			update_user_meta( $gebruiker_id, 'huisnr', '******' );
			update_user_meta( $gebruiker_id, 'pcode', '******' );
			update_user_meta( $gebruiker_id, 'plaats', '******' );
			$count = 5 + Kleistad_Abonnement::erase( $gebruiker_id ) + Kleistad_Saldo::erase( $gebruiker_id );
		}
		return [
			'items_removed'  => $count,
			'items_retained' => false,
			'messages'       => [],
			'done'           => ( 0 < $count && 1 === $page ), // Controle op page is een dummy.
		];
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
		if ( ( 'query_plugins' === $action || 'plugin_information' === $action ) &&
			isset( $arg->slug ) && $arg->slug === $this->plugin_name ) {
			$plugin_info  = get_site_transient( 'update_plugins' );
			$arg->version = $plugin_info->checked[ $this->plugin_name . '/' . $this->plugin_name . '.php' ];
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
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function opties_gewijzigd( $oud, $nieuw ) {
		if ( $oud['google_sleutel'] !== $nieuw['google_sleutel'] ||
			$oud['google_client_id'] !== $nieuw['google_client_id'] ) {
			delete_option( Kleistad_Event::ACCESS_TOKEN );
		}
	}

	/**
	 * Registreer de kleistad settings, uitgevoerd tijdens admin init.
	 *
	 * @since   4.0.87
	 */
	public function register_settings() {
		register_setting( 'kleistad-opties', 'kleistad-opties', [ $this, 'validate_settings' ] );
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
	 * @suppress PhanUnusedVariable
	 */
	public function google_connect_meta_box_handler() {
		$result = true;
		if ( ! is_null( filter_input( INPUT_POST, 'connect' ) ) ) {
			Kleistad_Event::vraag_google_service_aan( admin_url( 'admin.php?page=kleistad&tab=google_connect' ) );
		}
		if ( ! is_null( filter_input( INPUT_GET, 'code' ) ) ) {
			$result = Kleistad_Event::koppel_google_service();
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
