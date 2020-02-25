<?php
/**
 * Definitie van de core Kleistad plugin class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Include deze admin functie om de plugin versie te achterhalen uit de header van het hoofdplugin script.
 */
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * De Kleistad plugin class.
 *
 * @since      4.0.87
 */
class Kleistad {

	/**
	 * De loader waarmee alle hooks geregistreerd worden.
	 *
	 * @since    4.0.87
	 *
	 * @access   protected
	 * @var      \Kleistad\Loader    $loader    Beheert en registreert alle hooks van de plugin.
	 */
	protected $loader;

	/**
	 * De huidige versie van de plugin.
	 *
	 * @since    4.0.87
	 * @access   protected
	 * @var      string    $version    De versie.
	 */
	protected $version;

	/**
	 * Constructor
	 *
	 * @since    4.0.87
	 */
	public function __construct() {
		$version = get_option( 'kleistad-plugin-versie', '6.2.0' );
		if ( $version ) {
			$this->version = $version;
		}
		$this->load_dependencies();
		setlocale( LC_TIME, 'NLD_nld', 'nl_NL', 'nld_nld', 'Dutch', 'nl_NL.utf8' );
		$this->define_admin_hooks();
		$this->define_common_hooks();
		$this->define_public_hooks();
		$this->register_artikelen();
	}

	/**
	 * Laad de afhankelijkheden van de plugin.
	 *
	 * @since    4.0.87
	 * @access   private
	 */
	private function load_dependencies() {
		$this->loader = new \Kleistad\Loader();
	}

	/**
	 * Registreer alle admin hooks.
	 *
	 * @since    4.0.87
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new \Kleistad\Admin_Main( $this->get_version(), self::get_options(), self::get_setup() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts_and_styles' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'initialize' );
		$this->loader->add_action( 'kleistad_daily_jobs', $plugin_admin, 'daily_jobs' );
		$this->loader->add_action( 'kleistad_daily_gdpr', $plugin_admin, 'daily_gdpr' );
		$this->loader->add_action( 'plugins_loaded', $plugin_admin, 'instantiate_background' );
		$this->loader->add_action( 'update_option_kleistad-setup', $plugin_admin, 'setup_gewijzigd', 10, 2 );
		$this->loader->add_filter( 'wp_privacy_personal_data_exporters', $plugin_admin, 'register_exporter', 10 );
		$this->loader->add_filter( 'wp_privacy_personal_data_erasers', $plugin_admin, 'register_eraser', 10 );
		$this->loader->add_filter( 'pre_set_site_transient_update_plugins', $plugin_admin, 'check_update' );
		$this->loader->add_filter( 'plugins_api', $plugin_admin, 'check_info', 10, 3 );
		$this->loader->add_filter( 'post_row_actions', $plugin_admin, 'post_row_actions', 10, 2 );
	}

	/**
	 * Registreer all common hooks.
	 *
	 * @since   5.5.1
	 * @access  private
	 */
	private function define_common_hooks() {
		$plugin_common = new \Kleistad\Common();

		$this->loader->add_action( 'wp_login', $plugin_common, 'user_login', 10, 2 );
		$this->loader->add_action( 'login_enqueue_scripts', $plugin_common, 'login_enqueue_scripts' );
		$this->loader->add_action( 'login_headerurl', $plugin_common, 'login_headerurl' );
		$this->loader->add_action( 'login_headertext', $plugin_common, 'login_headertext' );
		$this->loader->add_action( 'after_setup_theme', $plugin_common, 'verberg_toolbar' );

		$this->loader->add_filter( 'login_message', $plugin_common, 'user_login_message' );
		$this->loader->add_filter( 'login_redirect', $plugin_common, 'login_redirect', 10, 3 );
		$this->loader->add_filter( 'wp_nav_menu_items', $plugin_common, 'loginuit_menu', 10, 2 );
		$this->loader->add_filter( 'cron_schedules', $plugin_common, 'cron_schedules' ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
	}

	/**
	 * Registreer alle public hooks.
	 *
	 * @since    4.0.87
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new \Kleistad\Public_Main( $this->get_version(), self::get_options() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'register_styles_and_scripts' );
		$this->loader->add_action( 'rest_api_init', $plugin_public, 'register_endpoints' );
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );
		$this->loader->add_action( 'init', $plugin_public, 'register_post_types' );
		$this->loader->add_action( 'kleistad_rcv_email', $plugin_public, 'rcv_email' );
		$this->loader->add_action( 'init', $plugin_public, 'inline_style', 100 );
		$this->loader->add_action( 'wp_ajax_kleistad_wachtwoord', $plugin_public, 'wachtwoord', 100 );
		$this->loader->add_action( 'wp_ajax_nopriv_kleistad_wachtwoord', $plugin_public, 'wachtwoord', 100 );

		$this->loader->add_filter( 'single_template', $plugin_public, 'single_template' );
		$this->loader->add_filter( 'comments_template', $plugin_public, 'comments_template' );
		$this->loader->add_filter( 'comment_form_default_fields', $plugin_public, 'comment_fields' );
		$this->loader->add_filter( 'user_contactmethods', $plugin_public, 'user_contact_methods', 10, 2 );
		$this->loader->add_filter( 'template_include', $plugin_public, 'template_include' );
		$this->loader->add_filter( 'email_change_email', $plugin_public, 'email_change_email', 10, 3 );
		$this->loader->add_filter( 'password_change_email', $plugin_public, 'password_change_email', 10, 3 );
		$this->loader->add_filter( 'retrieve_password_message', $plugin_public, 'retrieve_password_message', 10, 4 );
		$this->loader->add_filter( 'password_hint', $plugin_public, 'password_hint' );
	}

	/**
	 * Registreer de artikelen voor later gebruik.
	 */
	private function register_artikelen() {
		\Kleistad\Artikel::register(
			'A',
			[
				'naam'   => 'abonnement',
				'class'  => '\Kleistad\Abonnement',
				'pcount' => 1,
			]
		);
		\Kleistad\Artikel::register(
			'C',
			[
				'naam'   => 'cursus',
				'class'  => '\Kleistad\Inschrijving',
				'pcount' => 2,
			]
		);
		\Kleistad\Artikel::register(
			'K',
			[
				'naam'   => 'dagdelenkaart',
				'class'  => '\Kleistad\Dagdelenkaart',
				'pcount' => 1,
			]
		);
		\Kleistad\Artikel::register(
			'S',
			[
				'naam'   => 'stooksaldo',
				'class'  => '\Kleistad\Saldo',
				'pcount' => 1,
			]
		);
		\Kleistad\Artikel::register(
			'W',
			[
				'naam'   => 'workshop',
				'class'  => '\Kleistad\Workshop',
				'pcount' => 1,
			]
		);
		\Kleistad\Artikel::register(
			'X',
			[
				'naam'   => 'overige verkoop',
				'class'  => '\Kleistad\LosArtikel',
				'pcount' => 1,
			]
		);
		\Kleistad\Artikel::register(
			'@',
			[
				'naam'   => 'dubieuze debiteuren',
				'class'  => '',
				'pcount' => 1,
			]
		);
	}

	/**
	 * Run de loader zodat alle hooks worden uitgevoerd.
	 *
	 * @since    4.0.87
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Referentie naar de class die de hooks laadt.
	 *
	 * @since     4.0.87
	 * @return    \Kleistad\Loader    de loader.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * De versie van de plugin.
	 *
	 * @since     4.0.87
	 * @return    string    De versie.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * De opties van de plugin.
	 *
	 * @since     4.4.0
	 * @return    array    De opties.
	 */
	public static function get_options() {
		static $options = [];
		if ( empty( $options ) ) {
			$options = get_option( 'kleistad-opties', [] );
		}
		return $options;
	}

	/**
	 * De technische setup van de plugin.
	 *
	 * @since     6.2.1
	 * @return    array    De setup opties.
	 */
	public static function get_setup() {
		static $setup = [];
		if ( empty( $setup ) ) {
			$setup = get_option( 'kleistad-setup', [] );
		}
		return $setup;
	}

}
