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
	 * De versie van de plugin.
	 *
	 * @since    4.0.87
	 * @access   private
	 * @var      string    $version    De huidige versie.
	 */
	private $version;

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
	 *  Recept termen beheer
	 *
	 * @since     6.3.6
	 * @access    private
	 * @var       object    $recepttermen_handler  De handler voor beheer van de recept termen.
	 */
	private $recepttermen_handler;

	/**
	 *  Werkplekken beheer
	 *
	 * @since     6.11.0
	 * @access    private
	 * @var       object    $werkplekken_handler  De handler voor beheer van de werkplekken.
	 */
	private $werkplekken_handler;

	/**
	 *  Instellingen beheer
	 *
	 * @since     6.4.2
	 * @access    private
	 * @var       object    $instellingen_handler  De handler voor beheer van de instellingen.
	 */
	private $instellingen_handler;

	/**
	 * Background object
	 *
	 * @since   6.1.0
	 * @access  private
	 * @var     object $background Het background object.
	 */
	private $background;

	/**
	 * Initializeer het object.
	 *
	 * @since    4.0.87
	 * @param string $version De versie van de plugin.
	 * @param array  $options De plugin options.
	 * @param array  $setup   De plugin setup.
	 */
	public function __construct( $version, $options, $setup ) {
		$this->version              = $version;
		$this->ovens_handler        = new Admin_Ovens_Handler();
		$this->cursisten_handler    = new Admin_Cursisten_Handler();
		$this->abonnees_handler     = new Admin_Abonnees_Handler();
		$this->stooksaldo_handler   = new Admin_Stooksaldo_Handler();
		$this->regelingen_handler   = new Admin_Regelingen_Handler();
		$this->recepttermen_handler = new Admin_Recepttermen_Handler();
		$this->werkplekken_handler  = new Admin_Werkplekken_Handler();
		$this->instellingen_handler = new Admin_Instellingen_Handler( $options, $setup );
	}

	/**
	 * Registreer de stylesheets van de admin functies.
	 *
	 * @since    4.0.87
	 *
	 * @internal Action for admin_enqueue_scripts.
	 */
	public function enqueue_scripts_and_styles() {
		wp_enqueue_style( 'jqueryui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', [], '1.12.1' );
		wp_enqueue_script( 'kleistad_admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', [ 'jquery', 'jquery-ui-datepicker' ], $this->version, false );
	}

	/**
	 * Filter de acties voor een email post.
	 *
	 * @param array    $acties De acties.
	 * @param \WP_Post $post De post.
	 *
	 * @internal Filter for post_row_actions.
	 */
	public function post_row_actions( $acties, $post ) {
		if ( Email::POST_TYPE === $post->post_type ) {
			unset( $acties['view'] );
			unset( $acties['inline hide-if-no-js'] );
		}
		return $acties;
	}

	/**
	 * Voeg een header label toe voor de email templates.
	 *
	 * @param array $columns De bestaande labels.
	 * @return array
	 *
	 * @internal Filter for manage_kleistad_email_posts_columns.
	 */
	public function email_posts_columns( $columns ) {
		unset( $columns['date'] );
		return array_merge( $columns, [ 'wijziging' => 'Datum' ] );
	}

	/**
	 * Geef aan dat de wijziging column ook sorteerbaar is.
	 *
	 * @param array $columns De labels.
	 * @return array
	 *
	 * @internal Filter for manage_edit-kleistad_email_sortable_columns.
	 */
	public function email_sortable_columns( $columns ) {
		return array_merge( $columns, [ 'wijziging' => 'wijziging' ] );
	}

	/**
	 * Zorg dat er gesorteerd wordt op wijzig datum.
	 *
	 * @param \WP_Query $wp_query De query.
	 *
	 * @internal Filter for pre_get_posts.
	 */
	public function email_get_posts_order( $wp_query ) {
		if ( is_admin() ) {
			if ( isset( $wp_query->query['post_type'] ) && Email::POST_TYPE === $wp_query->query['post_type'] ) {
				$wp_query->set( 'orderby', 'modified' );
			}
		}
	}

	/**
	 * Toon extra columns in het email template overzicht.
	 *
	 * @param string $column De kolom.
	 * @param int    $post_id De post id.
	 *
	 * @internal Action for manage_kleistad_email_posts_custom_column.
	 */
	public function email_posts_custom_column( $column, $post_id ) {
		if ( 'wijziging' === $column ) {
			$date = get_the_modified_date( '', $post_id ) ?: '';
			$time = get_the_modified_time( '', $post_id ) ?: '';
			echo "Gewijzigd<br><span title=\"$date $time\">$date $time</span>"; // phpcs:ignore
		}
	}

	/**
	 * Definieer de admin panels
	 *
	 * @since    4.0.87
	 *
	 * @internal Action for admin_menu.
	 */
	public function add_plugin_admin_menu() {
		add_menu_page( 'Instellingen', 'Kleistad', 'manage_options', 'kleistad', [ $this->instellingen_handler, 'display_settings_page' ], plugins_url( '/images/kleistad_icon.png', __FILE__ ), 30 );
		add_submenu_page( 'kleistad', 'Instellingen', 'Instellingen', 'manage_options', 'kleistad', null );
		$this->ovens_handler->add_pages();
		$this->abonnees_handler->add_pages();
		$this->cursisten_handler->add_pages();
		$this->stooksaldo_handler->add_pages();
		$this->regelingen_handler->add_pages();
		$this->recepttermen_handler->add_pages();
		$this->werkplekken_handler->add_pages();
	}

	/**
	 * Registreer de exporter van privacy gevoelige data.
	 *
	 * @since 4.3.0
	 *
	 * @param array $exporters De exporters die WP aanroept bij het genereren van de zip file.
	 *
	 * @internal Filter for wp_privacy_personal_data_exporters.
	 */
	public function register_exporter( $exporters ) {
		$gdpr                  = new Admin_GDPR();
		$exporters['kleistad'] = [
			'exporter_friendly_name' => 'plugin folder Kleistad',
			'callback'               => [ $gdpr, 'exporter' ],
		];
		return $exporters;
	}

	/**
	 * Registreer de eraser van privacy gevoelige data.
	 *
	 * @since 4.3.0
	 *
	 * @param array $erasers De erasers die WP aanroept bij het verwijderen persoonlijke data.
	 *
	 * @internal Filter for wp_privacy_personal_data_erasers.
	 */
	public function register_eraser( $erasers ) {
		$gdpr                = new Admin_GDPR();
		$erasers['kleistad'] = [
			'eraser_friendly_name' => 'Kleistad',
			'callback'             => [ $gdpr, 'eraser' ],
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
	 *
	 * @internal Filter for pre_set_site_transient_update_plugins.
	 */
	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		$obj = $this->get_remote( 'version' );
		if ( false === $obj ) {
			return $transient;
		}
		if ( version_compare( $this->version, $obj->new_version, '<' ) ) {
			$transient->response[ $obj->plugin ] = $obj;
			return $transient;
		}
		$transient->no_update[ $obj->plugin ] = $obj;
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
	 *
	 * @internal Filter for plugins_api.
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
	private function get_remote( $action = '' ) {
		$params  = [
			'timeout' => 10,
			'body'    => [
				'action' => $action,
			],
		];
		$request = wp_remote_get( 'http://plugin.kleistad.nl/update.php', $params );
		if ( ! is_wp_error( $request ) || ( is_array( $request ) && wp_remote_retrieve_response_code( $request ) === 200 ) ) {
			// phpcs:ignore
			return unserialize( $request['body'] );
		}
		return false;
	}

	/**
	 * Aangeroepen na update van de kleistad opties.
	 *
	 * @param array $oud Oude waarde.
	 * @param array $nieuw Nieuwe waarde.
	 * @since 5.0.0
	 *
	 * @internal Action for update_option_kleistad-setup.
	 */
	public function setup_gewijzigd( $oud, $nieuw ) {
		if ( $oud['google_sleutel'] !== $nieuw['google_sleutel'] ||
			$oud['google_client_id'] !== $nieuw['google_client_id'] ) {
			delete_option( Googleconnect::ACCESS_TOKEN );
		}
	}

	/**
	 * Bereid het background proces voor.
	 *
	 * @internal Action for plugins_loaded.
	 */
	public function instantiate_background() {
		if ( is_null( $this->background ) ) {
			$this->background = new Background();
		}
	}

	/**
	 * Doe de dagelijkse jobs
	 *
	 * @internal Action for Kleistad_daily_jobs.
	 */
	public function daily_jobs() {
		$this->background->push_to_queue( 'Shortcode::cleanup_downloads' );
		$this->background->push_to_queue( 'Workshops::doe_dagelijks' );
		$this->background->push_to_queue( 'Abonnementen::doe_dagelijks' );
		$this->background->push_to_queue( 'Stoken::doe_dagelijks' );
		$this->background->push_to_queue( 'Cursussen::doe_dagelijks' );
		$this->background->push_to_queue( 'Inschrijvingen::doe_dagelijks' );
		$this->background->push_to_queue( 'Dagdelenkaarten::doe_dagelijks' );
		// phpcs:ignore $this->background->push_to_queue( 'Gebruiker::doe_dagelijks' );
		$this->background->save()->dispatch();
	}

	/**
	 * Doe de gdpr cleaning, vooralsnog alleen op de laatste dag van de maand.
	 *
	 * @internal Action for Kleistad_daily_gdpr.
	 */
	public function daily_gdpr() {
		if ( intval( date( 'd' ) ) === intval( date( 't' ) ) ) {
			$gdpr = new Admin_GDPR();
			$gdpr->erase_old_privacy_data();
		}
	}

	/**
	 * Registreer de kleistad settings, uitgevoerd tijdens admin init.
	 *
	 * @since   4.0.87
	 *
	 * @internal Action for admin_init.
	 */
	public function initialize() {
		$upgrade = new Admin_Upgrade();
		$upgrade->run();

		ob_start();
		if ( ! wp_next_scheduled( 'kleistad_rcv_email' ) ) {
			$time = time();
			wp_schedule_event( $time + ( 900 - ( $time % 900 ) ), '15_mins', 'kleistad_rcv_email' );
		}
		if ( ! wp_next_scheduled( 'kleistad_daily_jobs' ) ) {
			wp_schedule_event( strtotime( '08:00' ), 'daily', 'kleistad_daily_jobs' );
		}
		if ( ! wp_next_scheduled( 'kleistad_daily_gdpr' ) ) {
			wp_schedule_event( strtotime( '01:00' ), 'daily', 'kleistad_daily_gdpr' );
		}
		register_setting( 'kleistad-opties', 'kleistad-opties', [ 'sanitize_callback' => [ $this->instellingen_handler, 'validate_settings' ] ] );
		register_setting( 'kleistad-setup', 'kleistad-setup', [ 'sanitize_callback' => [ $this->instellingen_handler, 'validate_settings' ] ] );
	}

}
