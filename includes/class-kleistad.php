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

use WP_Error;

/**
 * Custom capabilities van kleistad gebruikers.
 */
const OVERRIDE  = 'kleistad_reserveer_voor_ander';
const RESERVEER = 'kleistad_reservering_aanmaken';
const BESTUUR   = 'bestuur';
const DOCENT    = 'docenten';
const LID       = 'leden';
const CURSIST   = 'cursisten';
const BOEKHOUD  = 'boekhouding';
const VERKOOP   = 'verkoper';
const INTERN    = 'intern';
const OCHTEND   = 'Ochtend';
const MIDDAG    = 'Middag';
const NAMIDDAG  = 'Namiddag';
const AVOND     = 'Avond';
const BTW       = 21;

/**
 * Hulpfunctie, bepaald in welk dagdeel een activiteit valt.
 *
 * @param int $start_tijd De start tijd van de activiteit.
 * @param int $eind_tijd  De eind tijd van de activiteit.
 *
 * @return array De dagdelen
 */
function bepaal_dagdelen( int $start_tijd, int $eind_tijd ) : array {
	$dagdelen      = [];
	$grenzen       = [
		OCHTEND  => 12 * 60 + 30, // 12:30.
		MIDDAG   => 16 * 60,      // 16:00.
		NAMIDDAG => 20 * 60,      // 20:00.
	];
	$start_minuten = ( $start_tijd % DAY_IN_SECONDS ) / 60;
	$eind_minuten  = ( $eind_tijd % DAY_IN_SECONDS ) / 60;

	foreach ( $grenzen as $dagdeel => $grens ) {
		if ( $start_minuten <= $grens ) {
			$dagdelen[] = $dagdeel;
			if ( $eind_minuten <= $grens ) {
				return $dagdelen; // Start en eind binnen hetzelfde dagdeel.
			}
			continue; // Eind is in een later dagdeel.
		}
		if ( count( $dagdelen ) ) { // Start is al vastgelegd.
			$dagdelen[] = $dagdeel;
			if ( $eind_minuten <= $grens ) {
				return $dagdelen;
			}
		}
	}
	return [ AVOND ];
}

/**
 * De opties van de plugin.
 *
 * @since     4.4.0
 * @return    array    De opties.
 */
function opties() : array {
	static $opties = [];
	if ( empty( $opties ) || defined( 'KLEISTAD_TEST' ) ) {
		$opties = get_option( 'kleistad-opties', [] );
	}
	return $opties;
}

/**
 * Maak een melding tekst aan.
 *
 * @param int    $status  1 succes, 0 fout, -1 notificatie.
 * @param string $bericht Het bericht.
 * @return string De opgemaakte tekst.
 * @noinspection PhpUnnecessaryCurlyVarSyntaxInspection
 */
function melding( int $status, string $bericht ) : string {
	$levels = [
		-1 => 'kleistad-inform',
		0  => 'kleistad-fout',
		1  => 'kleistad-succes',
	];
	return "<div class=\"{$levels[$status]}\"><p>$bericht</p></div>";
}

/**
 * Generieke call voor error reporting naar de log.
 *
 * @param string $object  Object waar de fout geconstateerd wordt.
 * @param string $bericht Het fout bericht.
 */
function fout( string $object, string $bericht ) : void {
	error_log( "kleistad $object: $bericht" ); // phpcs:ignore
}

/**
 * De technische setup van de plugin.
 *
 * @since     6.2.1
 * @return    array    De setup opties.
 */
function setup() : array {
	static $setup = [];
	if ( empty( $setup ) ) {
		$setup = get_option( 'kleistad-setup', [] );
	}
	return $setup;
}

/**
 * De versie van de plugin
 *
 * @return string De versie.
 */
function versie() : string {
	static $versie = '';
	if ( empty( $versie ) ) {
		$versie = get_option( 'kleistad-plugin-versie', '6.2.0' );
	}
	return $versie;
}

/**
 * Registreer de gebruiker op basis van input
 *
 * @param array $data De input.
 * @return int|WP_Error
 */
function registreren( array $data ): WP_Error|int {
	$gebruiker_id = intval( $data['gebruiker_id'] ?? 0 );
	if ( ! $gebruiker_id ) {
		$userdata = [
			'ID'         => email_exists( $data['user_email'] ) ?: null,
			'first_name' => $data['first_name'],
			'last_name'  => $data['last_name'],
			'telnr'      => $data['telnr'] ?? '',
			'user_email' => $data['user_email'],
			'straat'     => $data['straat'] ?? '',
			'huisnr'     => $data['huisnr'] ?? '',
			'pcode'      => $data['pcode'] ?? '',
			'plaats'     => $data['plaats'] ?? '',
		];
		if ( is_null( $userdata['ID'] ) ) {
			$userdata['role']          = '';
			$userdata['user_login']    = $userdata['user_email'];
			$userdata['user_pass']     = wp_generate_password( 12, true );
			$userdata['user_nicename'] = strtolower( $userdata['first_name'] . '-' . $userdata['last_name'] );
			return wp_insert_user( (object) $userdata );
		}
		return wp_update_user( (object) $userdata );
	}
	return $gebruiker_id;
}

/**
 * Geeft de basis url terug voor de endpoints.
 *
 * @return string url voor endpoints
 */
function base_url() : string {
	return rest_url( KLEISTAD_API );
}

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
	 * @var      Loader    $loader    Beheert en registreert alle hooks van de plugin.
	 */
	protected Loader $loader;

	/**
	 * Constructor
	 *
	 * @since    4.0.87
	 */
	public function __construct() {
		$this->load_dependencies();
		setlocale( LC_TIME, 'NLD_nld', 'nl_NL', 'nld_nld', 'Dutch', 'nl_NL.utf8' );
		$this->define_admin_hooks();
		$this->define_common_hooks();
		$this->define_shop_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Laad de afhankelijkheden van de plugin.
	 *
	 * @since    4.0.87
	 * @access   private
	 */
	private function load_dependencies() : void {
		$this->loader = new Loader();
	}

	/**
	 * Registreer alle admin hooks.
	 *
	 * @since    4.0.87
	 * @access   private
	 */
	private function define_admin_hooks() : void {
		$plugin_filters = new Admin_Filters();
		$plugin_actions = new Admin_Actions();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_actions, 'enqueue_scripts_and_styles' );
		$this->loader->add_action( 'admin_menu', $plugin_actions, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_actions, 'initialize' );
		$this->loader->add_action( 'kleistad_daily_jobs', $plugin_actions, 'daily_jobs' );
		$this->loader->add_action( 'kleistad_daily_gdpr', $plugin_actions, 'daily_gdpr' );
		$this->loader->add_action( 'plugins_loaded', $plugin_actions, 'instantiate_background' );
		$this->loader->add_action( 'update_option_kleistad-setup', $plugin_actions, 'setup_gewijzigd', 10, 2 );
		$this->loader->add_action( 'manage_kleistad_email_posts_custom_column', $plugin_actions, 'email_posts_custom_column', 10, 2 );
		$this->loader->add_filter( 'wp_privacy_personal_data_exporters', $plugin_filters, 'register_exporter' );
		$this->loader->add_filter( 'wp_privacy_personal_data_erasers', $plugin_filters, 'register_eraser' );
		$this->loader->add_filter( 'pre_set_site_transient_update_plugins', $plugin_filters, 'check_update' );
		$this->loader->add_filter( 'plugins_api', $plugin_filters, 'check_info', 20, 3 );
		$this->loader->add_filter( 'post_row_actions', $plugin_filters, 'post_row_actions', 10, 2 );
		$this->loader->add_filter( 'manage_kleistad_email_posts_columns', $plugin_filters, 'email_posts_columns' );
		$this->loader->add_filter( 'manage_edit-kleistad_email_sortable_columns', $plugin_filters, 'email_sortable_columns' );
		$this->loader->add_filter( 'pre_get_posts', $plugin_filters, 'email_get_posts_order' );
	}

	/**
	 * Registreer all common hooks.
	 *
	 * @since   5.5.1
	 * @access  private
	 */
	private function define_common_hooks() : void {
		$plugin_common = new Common();

		$this->loader->add_action( 'wp_login', $plugin_common, 'user_login', 10, 2 );
		$this->loader->add_action( 'login_enqueue_scripts', $plugin_common, 'login_enqueue_scripts' );
		$this->loader->add_action( 'login_headerurl', $plugin_common, 'login_headerurl' );
		$this->loader->add_action( 'login_headertext', $plugin_common, 'login_headertext' );
		$this->loader->add_action( 'after_setup_theme', $plugin_common, 'verberg_toolbar' );
		$this->loader->add_action( 'plugins_loaded', $plugin_common, 'disable_emoji' );

		$this->loader->add_filter( 'login_message', $plugin_common, 'user_login_message' );
		$this->loader->add_filter( 'login_redirect', $plugin_common, 'login_redirect', 10, 3 );
		$this->loader->add_filter( 'wp_nav_menu_items', $plugin_common, 'loginuit_menu', 10, 2 );
		$this->loader->add_filter( 'cron_schedules', $plugin_common, 'cron_schedules' ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
	}

	/**
	 * Registreer shop specifieke hooks
	 *
	 * @since 7.3.0
	 * @return void
	 */
	private function define_shop_hooks() : void {
		$plugin_actions = new Shop();

		$this->loader->add_action( 'init', $plugin_actions, 'shop_init' );
		$this->loader->add_action( 'kleistad_order_annulering', $plugin_actions, 'order_annulering' );
		$this->loader->add_action( 'kleistad_order_stornering', $plugin_actions, 'order_stornering' );
		$this->loader->add_action( 'kleistad_betaalinfo_update', $plugin_actions, 'betaalinfo_update' );
	}

	/**
	 * Registreer alle public hooks.
	 *
	 * @since    4.0.87
	 * @access   private
	 */
	private function define_public_hooks() : void {
		$plugin_filters = new Public_Filters();
		$plugin_actions = new Public_Actions();

		$this->loader->add_action( 'rest_api_init', $plugin_actions, 'register_endpoints' );
		$this->loader->add_action( 'init', $plugin_actions, 'register_post_types' );
		$this->loader->add_action( 'init', $plugin_actions, 'register_shortcodes' );
		$this->loader->add_action( 'init', $plugin_actions, 'register_styles_and_scripts' );
		$this->loader->add_action( 'init', $plugin_actions, 'inline_style', 100 );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_actions, 'enqueue_styles_and_scripts', 99 );
		$this->loader->add_action( 'kleistad_rcv_email', $plugin_actions, 'rcv_email' );
		$this->loader->add_action( 'wp_ajax_kleistad_wachtwoord', $plugin_actions, 'wachtwoord', 100 );
		$this->loader->add_action( 'wp_ajax_nopriv_kleistad_wachtwoord', $plugin_actions, 'wachtwoord', 100 );
		$this->loader->add_action( 'profile_update', $plugin_actions, 'profile_update' );

		$this->loader->add_filter( 'single_template', $plugin_filters, 'single_template', 99 );
		$this->loader->add_filter( 'comments_template', $plugin_filters, 'comments_template' );
		$this->loader->add_filter( 'comment_form_default_fields', $plugin_filters, 'comment_fields' );
		$this->loader->add_filter( 'user_contactmethods', $plugin_filters, 'user_contact_methods', 10, 2 );
		$this->loader->add_filter( 'template_include', $plugin_filters, 'template_include' );
		$this->loader->add_filter( 'email_change_email', $plugin_filters, 'email_change_email', 10, 3 );
		$this->loader->add_filter( 'password_change_email', $plugin_filters, 'password_change_email', 10, 3 );
		$this->loader->add_filter( 'retrieve_password_message', $plugin_filters, 'retrieve_password_message', 10, 4 );
		$this->loader->add_filter( 'password_hint', $plugin_filters, 'password_hint' );
		$this->loader->add_filter( 'mce_buttons', $plugin_filters, 'mce_buttons' );
	}

	/**
	 * Run de loader zodat alle hooks worden uitgevoerd.
	 *
	 * @since    4.0.87
	 */
	public function run() : void {
		$this->loader->run();
	}

}
