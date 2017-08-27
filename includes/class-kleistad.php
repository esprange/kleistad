<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      4.0.0
 * @package    Kleistad
 * @subpackage Kleistad/includes
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    4.0.0
	 * @access   protected
	 * @var      Kleistad_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    4.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    4.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    4.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'kleistad';
		$this->version = '4.0.0';

		$this->load_dependencies();
		setlocale( LC_TIME, 'NLD_nld', 'nl_NL', 'nld_nld', 'Dutch', 'nl_NL.utf8' );
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Kleistad_Loader. Orchestrates the hooks of the plugin.
	 * - Kleistad_i18n. Defines internationalization functionality.
	 * - Kleistad_Admin. Defines all hooks for the admin area.
	 * - Kleistad_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
	 * The class responsible for orchestrating the actions and filters of the
	 * core plugin.
	 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-loader.php';

		/**
	 * The class responsible for defining all actions that occur in the admin area.
	 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-kleistad-admin.php';

		/**
	 * The class responsible for defining all actions that occur in the public-facing
	 * side of the site.
	 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-kleistad-public.php';

		$this->loader = new Kleistad_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Kleistad_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		$this->loader->add_action( 'show_user_profile', $plugin_admin, 'use_profile_field' );
		$this->loader->add_action( 'edit_user_profile', $plugin_admin, 'use_profile_field' );
		$this->loader->add_action( 'personal_options_update', $plugin_admin, 'user_profile_field_save' );
		$this->loader->add_action( 'edit_user_profile_update', $plugin_admin, 'user_profile_field_save' );
		$this->loader->add_action( 'manage_users_custom_column', $plugin_admin, 'manage_users_column_content', 10, 3 );
		$this->loader->add_action( 'admin_footer-users.php', $plugin_admin, 'manage_users_css' );

		  $this->loader->add_filter( 'manage_users_columns', $plugin_admin, 'manage_users_columns' );
		$this->loader->add_filter( 'user_profile_update_errors', $plugin_admin, 'check_role', 10, 3 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Kleistad_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_login', $plugin_public, 'user_login', 10, 2 );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'register_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'register_scripts' );
		$this->loader->add_action( 'delete_user', $plugin_public, 'verwijder_gebruiker' );
		$this->loader->add_action( 'rest_api_init', $plugin_public, 'register_endpoints' );
		$this->loader->add_action( 'kleistad_kosten', $plugin_public, 'update_ovenkosten' );

		$this->loader->add_filter( 'login_message', $plugin_public, 'user_login_message' );

		$this->loader->add_shortcode( 'kleistad_reservering', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_stookbestand', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_saldo_overzicht', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_rapport', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_saldo', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_cursus_inschrijving', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_registratie', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_abonnee_inschrijving', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_registratie_overzicht', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_betalingen', $plugin_public, 'shortcode_handler' );
		$this->loader->add_shortcode( 'kleistad_cursus_beheer', $plugin_public, 'shortcode_handler' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    4.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     4.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     4.0.0
	 * @return    Kleistad_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     4.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
