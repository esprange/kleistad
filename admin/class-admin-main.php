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
	 *  De plugin setup
	 *
	 * @since     6.2.1
	 * @access    private
	 * @var       array     $setup  De plugin technische setup.
	 */
	private $setup;

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
		$this->options              = $options;
		$this->setup                = $setup;
		$this->ovens_handler        = new \Kleistad\Admin_Ovens_Handler();
		$this->cursisten_handler    = new \Kleistad\Admin_Cursisten_Handler();
		$this->abonnees_handler     = new \Kleistad\Admin_Abonnees_Handler();
		$this->stooksaldo_handler   = new \Kleistad\Admin_Stooksaldo_Handler();
		$this->regelingen_handler   = new \Kleistad\Admin_Regelingen_Handler();
		$this->recepttermen_handler = new \Kleistad\Admin_Recepttermen_Handler();
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
	 * Filter de acties voor een email post.
	 *
	 * @param array    $acties De acties.
	 * @param \WP_Post $post De post.
	 */
	public function post_row_actions( $acties, $post ) {
		if ( \Kleistad\Email::POST_TYPE === $post->post_type ) {
			unset( $acties['view'] );
			unset( $acties['inline hide-if-no-js'] );
		}
		return $acties;
	}

	/**
	 * Definieer de admin panels
	 *
	 * @since    4.0.87
	 */
	public function add_plugin_admin_menu() {
		global $submenu;
		add_menu_page( 'Instellingen', 'Kleistad', 'manage_options', 'kleistad', [ $this, 'display_settings_page' ], plugins_url( '/images/kleistad_icon.png', __FILE__ ), ++$GLOBALS['_wp_last_object_menu'] );
		add_submenu_page( 'kleistad', 'Instellingen', 'Instellingen', 'manage_options', 'kleistad', null );
		$this->ovens_handler->add_pages();
		$this->abonnees_handler->add_pages();
		$this->cursisten_handler->add_pages();
		$this->stooksaldo_handler->add_pages();
		$this->regelingen_handler->add_pages();
		$this->recepttermen_handler->add_pages();
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
			'timeout' => 10,
			'body'    => [
				'action' => $action,
			],
		];
		$request = wp_remote_get( 'http://plugin.kleistad.nl/update.php', $params );
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
	public function setup_gewijzigd( $oud, $nieuw ) {
		if ( $oud['google_sleutel'] !== $nieuw['google_sleutel'] ||
			$oud['google_client_id'] !== $nieuw['google_client_id'] ) {
			delete_option( \Kleistad\Google::ACCESS_TOKEN );
		}
	}

	/**
	 * Bereid het background proces voor.
	 */
	public function instantiate_background() {
		if ( is_null( $this->background ) ) {
			$this->background = new \Kleistad\Background();
		}
	}

	/**
	 * Doe de dagelijkse jobs
	 */
	public function daily_jobs() {
		$this->background->push_to_queue( '\Kleistad\Shortcode::cleanup_downloads' );
		$this->background->push_to_queue( '\Kleistad\Workshop::dagelijks' );
		$this->background->push_to_queue( '\Kleistad\Abonnement::dagelijks' );
		$this->background->push_to_queue( '\Kleistad\Saldo::dagelijks' );
		$this->background->push_to_queue( '\Kleistad\Inschrijving::dagelijks' );
		$this->background->push_to_queue( '\Kleistad\Dagdelenkaart::dagelijks' );
		$this->background->save()->dispatch();
	}

	/**
	 * Doe de gdpr cleaning, vooralsnog alleen op de laatste dag van de maand.
	 */
	public function daily_gdpr() {
		if ( intval( date( 'd' ) ) === intval( date( 't' ) ) ) {
			\Kleistad\Admin_GDPR::erase_old_privacy_data();
		}
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
		if ( ! wp_next_scheduled( 'kleistad_daily_gdpr' ) ) {
			wp_schedule_event( strtotime( '01:00' ), 'daily', 'kleistad_daily_gdpr' );
		}

		register_setting( 'kleistad-opties', 'kleistad-opties', [ 'sanitize_callback' => [ $this, 'validate_settings' ] ] );
		register_setting( 'kleistad-setup', 'kleistad-setup', [ 'sanitize_callback' => [ $this, 'validate_settings' ] ] );
	}

	/**
	 * Toon de instellingen page van de plugin.
	 *
	 * @since    4.0.87
	 */
	public function display_settings_page() {
		$result = true;
		if ( ! is_null( filter_input( INPUT_POST, 'connect' ) ) ) {
			\Kleistad\Google::vraag_service_aan( admin_url( 'admin.php?page=kleistad&tab=setup' ) );
		} elseif ( ! is_null( filter_input( INPUT_GET, 'code' ) ) ) {
			$result = \Kleistad\Google::koppel_service();
		} elseif ( ! is_null( filter_input( INPUT_POST, 'dagelijks' ) ) ) {
			$this->daily_jobs();
		} elseif ( ! is_null( filter_input( INPUT_POST, 'corona' ) ) ) {
			$this->corona();
		}
		$active_tab = filter_input( INPUT_GET, 'tab' ) ?: 'instellingen';
		?>
		<div class="wrap">
			<?php if ( is_wp_error( $result ) ) : ?>
			<div class="error">
				<p><?php echo esc_html( $result->get_error_message() ); ?></p>
			</div>
			<?php endif ?>
			<h2 class="nav-tab-wrapper">
			    <a href="?page=kleistad&tab=instellingen" class="nav-tab <?php echo 'instellingen' === $active_tab ? 'nav-tab-active' : ''; ?>">Functionele instellingen</a>
			    <a href="?page=kleistad&tab=setup" class="nav-tab <?php echo 'setup' === $active_tab ? 'nav-tab-active' : ''; ?>">Technische instellingen</a>
			    <a href="?page=kleistad&tab=shortcodes" class="nav-tab <?php echo 'shortcodes' === $active_tab ? 'nav-tab-active' : ''; ?>">Shortcodes</a>
			    <a href="?page=kleistad&tab=email-parameters" class="nav-tab <?php echo 'email-parameters' === $active_tab ? 'nav-tab-active' : ''; ?>">Email parameters</a>
			</h2>
			<?php require "partials/admin-$active_tab.php"; ?>
		</div>
		<?php
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

	/**
	 * Lees het corona beschikbaarheid bestand en sla dit op.
	 *
	 * @return void
	 */
	private function corona() {
		if ( isset( $_FILES['corona_file'] ) ) {
			$vandaag         = strtotime( 'today' );
			$beschikbaarheid = get_option( 'kleistad_corona_beschikbaarheid', [] );
			$csv             = array_map( 'str_getcsv', file( $_FILES['corona_file']['tmp_name'] ) ?: [] );
			foreach ( $beschikbaarheid as $datum => $tijden ) {
				if ( $datum >= $vandaag ) {
					unset( $beschikbaarheid[ $datum ] );
				}
			}
			foreach ( $csv as $line ) {
				list( $s_datum, $start, $eind, $limiet_draaien, $limiet_handvormen, $limiet_boven ) = explode( ';', $line[0] );
				$datum = strtotime( $s_datum );
				$tijd  = "$start - $eind";
				if ( false === $datum || $datum < $vandaag ) {
					continue;
				}
				$beschikbaarheid[ $datum ][] =
					[
						'T' => $tijd,
						'D' => $limiet_draaien,
						'H' => $limiet_handvormen,
						'B' => $limiet_boven,
					];
			}
			update_option( 'kleistad_corona_beschikbaarheid', $beschikbaarheid );
		}
	}
}
