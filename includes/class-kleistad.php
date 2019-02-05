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

/**
 * Include deze admin functie om de plugin versie te achterhalen uit de header van het hoofdplugin script.
 */
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * De Kleistad plugin class.
 *
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */
class Kleistad {

	/**
	 * De loader waarmee alle hooks geregistreerd worden.
	 *
	 * @since    4.0.87
	 *
	 * @access   protected
	 * @var      Kleistad_Loader    $loader    Beheert en registreert alle hooks van de plugin.
	 */
	protected $loader;

	/**
	 * De unieke id van de plugin.
	 *
	 * @since    4.0.87
	 * @access   protected
	 * @var      string    $plugin_name    De naam waarmee de plugin uniek geïdentificeerd wordt.
	 */
	protected $plugin_name;

	/**
	 * De huidige versie van de plugin.
	 *
	 * @since    4.0.87
	 * @access   protected
	 * @var      string    $version    De versie.
	 */
	protected $version;

	/**
	 * De opties van de plugin.
	 *
	 * @since    4.4.0
	 * @access   protected
	 * @var      array     $options De opties.
	 */
	protected static $options = [];

	/**
	 * Constructor
	 *
	 * @since    4.0.87
	 */
	public function __construct() {

		$this->plugin_name = 'kleistad';
		$data              = get_plugin_data( plugin_dir_path( dirname( __FILE__ ) ) . $this->plugin_name . '.php', false, false );
		$this->version     = $data['Version'];
		$options           = get_option( 'kleistad-opties' );
		if ( is_array( $options ) ) {
			self::$options = $options; // zou altijd zo moeten zijn.
		}
		self::register_autoloader();
		$this->load_dependencies();
		setlocale( LC_TIME, 'NLD_nld', 'nl_NL', 'nld_nld', 'Dutch', 'nl_NL.utf8' );
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Autoloader, laadt alle classes.
	 */
	public static function register_autoloader() {
		spl_autoload_register(
			function ( $class ) {
				$file     = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
				$path     = plugin_dir_path( dirname( __FILE__ ) );
				$dir      = ( false === strpos( $file, 'kleistad-public' ) ) ? ( ( false === strpos( $file, 'kleistad-admin' ) ) ? 'includes/' : 'admin/' ) : 'public/';
				$filepath = $path . $dir . $file;
				if ( file_exists( $filepath ) ) {
					require $filepath;
					return true;
				}
				return false;
			},
			true, // Throw error if registration fails.
			false // Prepend this loader.
		);
	}

	/**
	 * Laad de afhankelijkheden van de plugin.
	 *
	 * @since    4.0.87
	 * @access   private
	 */
	private function load_dependencies() {
		$this->loader = new Kleistad_Loader();
	}

	/**
	 * Registreer alle admin hooks.
	 *
	 * @since    4.0.87
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Kleistad_Admin( $this->get_plugin_name(), $this->get_version(), self::get_options() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		$this->loader->add_action( 'update_option_kleistad-opties', $plugin_admin, 'opties_gewijzigd', 10, 2 );
		$this->loader->add_filter( 'wp_privacy_personal_data_exporters', $plugin_admin, 'register_exporter', 10 );
		$this->loader->add_filter( 'wp_privacy_personal_data_erasers', $plugin_admin, 'register_eraser', 10 );
		$this->loader->add_filter( 'pre_set_site_transient_update_plugins', $plugin_admin, 'check_update' );
		$this->loader->add_filter( 'plugins_api', $plugin_admin, 'check_info', 10, 3 );
	}

	/**
	 * Registreer alle public hooks.
	 *
	 * @since    4.0.87
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Kleistad_Public( $this->get_plugin_name(), $this->get_version(), self::get_options() );

		$this->loader->add_action( 'wp_login', $plugin_public, 'user_login', 10, 2 );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'register_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'register_scripts' );
		$this->loader->add_action( 'delete_user', $plugin_public, 'verwijder_gebruiker' );
		$this->loader->add_action( 'rest_api_init', $plugin_public, 'register_endpoints' );
		$this->loader->add_action( 'init', $plugin_public, 'create_recept_type' );
		$this->loader->add_action( 'kleistad_kosten', $plugin_public, 'update_ovenkosten' );
		$this->loader->add_action( 'kleistad_abonnement', $plugin_public, 'update_abonnement', 10, 3 );
		$this->loader->add_action( 'kleistad_workshop', $plugin_public, 'update_workshop', 10, 3 );
		$this->loader->add_action( 'after_setup_theme', $plugin_public, 'verberg_toolbar' );

		$this->loader->add_filter( 'login_message', $plugin_public, 'user_login_message' );
		$this->loader->add_filter( 'login_redirect', $plugin_public, 'login_redirect', 10, 3 );
		$this->loader->add_filter( 'single_template', $plugin_public, 'recept_template' );
		$this->loader->add_filter( 'comments_template', $plugin_public, 'comments_template' );
		$this->loader->add_filter( 'comment_form_default_fields', $plugin_public, 'comment_fields' );
		$this->loader->add_filter( 'wp_mail_from', $plugin_public, 'mail_from' );
		$this->loader->add_filter( 'wp_mail_from_name', $plugin_public, 'mail_from_name' );
		$this->loader->add_filter( 'wp_nav_menu_items', $plugin_public, 'loginuit_menu', 10, 2 );
		$this->loader->add_filter( 'user_contactmethods', $plugin_public, 'user_contact_methods', 10, 2 );

		$this->loader->add_shortcode( 'kleistad_reservering', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_stookbestand', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_saldo_overzicht', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_rapport', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_saldo', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_cursus_inschrijving', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_registratie', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_abonnee_inschrijving', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_abonnee_wijziging', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_registratie_overzicht', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_cursus_overzicht', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_abonnement_overzicht', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_betalingen', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_cursus_beheer', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_recept_beheer', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_recept', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_dagdelenkaart', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_betaling', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_workshop_beheer', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_kalender', $plugin_public, 'shortcode_handler' );
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
	 * Geef de naam van de plugin.
	 *
	 * @since     4.0.87
	 * @return    string    De naam van de plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Referentie naar de class die de hooks laadt.
	 *
	 * @since     4.0.87
	 * @return    Kleistad_Loader    de loader.
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
		return self::$options;
	}

}
