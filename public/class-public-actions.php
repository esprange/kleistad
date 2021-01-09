<?php
/**
 * Definitie van de publieke class van de plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_User;

/**
 * De kleistad class voor de publieke actions.
 */
class Public_Actions {

	/**
	 * De versie van de plugin.
	 *
	 * @since    4.0.87
	 *
	 * @access   private
	 * @var      string    $version    De huidige versie van deze plugin.
	 */
	private $version;

	/**
	 * De handler voor de shortcodes.
	 *
	 * @since 6.4.2
	 * @access private
	 * @var object $shortcode_handler De handler voor de shortcodes.
	 */
	private $shortcode_handler;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.87
	 *
	 * @param string $version       The version of this plugin.
	 * @param array  $options       De plugin options.
	 */
	public function __construct( $version, $options ) {
		$this->version           = $version;
		$this->shortcode_handler = new Public_Shortcode_Handler( $options );
	}

	/**
	 * Voeg de shortcodes toe.
	 *
	 * @internal Action for init.
	 */
	public function register_shortcodes() {
		$this->shortcode_handler->register();
	}

	/**
	 * Registreer de scripts en stylesheets voor de publieke functies van de plugin.
	 *
	 * @since    4.0.87
	 *
	 * @internal Action for wp_enqueue_scripts.
	 */
	public function register_styles_and_scripts() {
		$dev            = 'development' === wp_get_environment_type() ? '' : '.min';
		$jquery_version = wp_scripts()->registered['jquery-ui-core']->ver;
		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
		// Volgens stricte wp rules zou de versie toegevoegd moeten worden als parameter.
		wp_register_style( 'jquery-ui', sprintf( '//code.jquery.com/ui/%s/themes/smoothness/jquery-ui.css', $jquery_version ), [], $jquery_version );
		wp_register_style( 'datatables', '//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css', [], '1.10.21' );
		wp_register_style( 'fullcalendar', '//cdn.jsdelivr.net/npm/fullcalendar@5.3.2/main.min.css', [], '5.3.2' );
		wp_register_style( 'jstree', '//cdn.jsdelivr.net/npm/jstree@3.3.9/dist/themes/default/style.min.css', [], '3.3.9' );
		wp_register_style( 'kleistad', plugin_dir_url( __FILE__ ) . "css/public$dev.css", [], $this->version );

		wp_register_script( 'datatables', '//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js', [ 'jquery' ], '1.10.21', false );
		wp_register_script( 'fullcalendar-core', '//cdn.jsdelivr.net/npm/fullcalendar@5.3.2/main.min.js', [], '5.3.2', false );
		wp_register_script( 'fullcalendar', '//cdn.jsdelivr.net/npm/fullcalendar@5.3.2/locales-all.min.js', [ 'fullcalendar-core' ], '5.3.2', false );
		wp_register_script( 'jstree', '//cdn.jsdelivr.net/npm/jstree@3.3.9/dist/jstree.min.js', [ 'jquery' ], '3.3.9', false );
		wp_register_script( 'kleistad', plugin_dir_url( __FILE__ ) . "js/public$dev.js", [ 'jquery', 'jquery-ui-dialog' ], $this->version, true );
		wp_register_script( 'kleistad-form', plugin_dir_url( __FILE__ ) . "js/public-form$dev.js", [ 'kleistad' ], $this->version, true );

		foreach ( Public_Shortcode_Handler::SHORTCODES as $shortcode => $dependencies ) {
			if ( $dependencies['script'] ) {
				$file = str_replace( '_', '-', $shortcode );
				wp_register_script( "kleistad$shortcode", plugin_dir_url( __FILE__ ) . "js/public-$file$dev.js", $dependencies['js'], $this->version, false );
			}
		}
		// phpcs:enable
	}

	/**
	 * Registreer de AJAX endpoints
	 *
	 * @since   4.0.87
	 *
	 * @internal Action for rest_api_init.
	 * @suppressWarnings(PHPMD.StaticAccess)
	 */
	public function register_endpoints() {
		Adres::register_rest_routes(); // Postcode.
		Betalen::register_rest_routes(); // Mollie.
		Public_Kalender::register_rest_routes(); // Google API.
		Public_Recept::register_rest_routes(); // Recept zoeker.
		Public_Reservering::register_rest_routes(); // Oven reserveringen.
		Public_Werkplek::register_rest_routes(); // Werkplek reserveringen.
		Shortcode::register_rest_routes(); // Shortcode opvragen.
		ShortcodeForm::register_rest_routes(); // Shortcode formulieren.
	}

	/**
	 * Maak de custom post types en taxonomy
	 *
	 * @since 4.1.0
	 *
	 * @internal Action for init.
	 * @suppressWarnings(PHPMD.StaticAccess)
	 */
	public static function register_post_types() {
		global $wp_post_types;
		Recept::create_type();
		WorkshopAanvraag::create_type();
		Email::create_type();
		$wp_post_types[ WorkshopAanvraag::POST_TYPE ]->exclude_from_search = true;
		$wp_post_types[ Email::POST_TYPE ]->exclude_from_search            = true;
	}

	/**
	 * Voegt inline style in, zoals om te voorkomen dat er zwakke wachtwoorden mogelijk zijn.
	 *
	 * @internal Action for init.
	 */
	public function inline_style() {
		wp_add_inline_style( 'login', '.pw-weak {display:none !important;}' );
	}

	/**
	 * Ontvang en verwerk email
	 *
	 * @internal Action for rcv_email.
	 */
	public function rcv_email() {
		WorkshopAanvraag::ontvang_en_verwerk();
	}

	/**
	 * Update het wachtwoord (aangeroepen via admin_ajax).
	 *
	 * @internal Action for wp_ajax_kleistad_wachtwoord, wp_ajax_nopriv_kleistad_wachtwoord.
	 * @suppressWarnings(PHPMD.ExitExpression)
	 */
	public function wachtwoord() {
		check_ajax_referer( 'wp_rest', 'security' );
		global $current_user;
		$actie = filter_input( INPUT_POST, 'actie', FILTER_SANITIZE_STRING );
		if ( 'wijzig_wachtwoord' === $actie ) {
			$wachtwoord = filter_input( INPUT_POST, 'wachtwoord', FILTER_SANITIZE_STRING );
			$userdata   = [
				'ID'        => $current_user->ID,
				'user_pass' => $wachtwoord,
			];
			$user_id    = wp_update_user( $userdata );
			echo ( $user_id === $current_user->ID ) ? 'success' : 'error';
		}
		exit();
	}

	/**
	 * Format the fields after insert of user
	 *
	 * @param int $gebruiker_id Het gebruiker id.
	 *
	 * @internal Action for user_register.
	 */
	public function user_register( $gebruiker_id ) {
		$userdata = get_userdata( $gebruiker_id );
		if ( false !== $userdata ) {
			$user_login = sanitize_user( strtolower( preg_replace( '/\s+/', '', $userdata->first_name . $userdata->last_name ) ), true );
			while ( 8 > mb_strlen( $user_login ) || username_exists( $user_login ) ) {
				$user_login .= chr( wp_rand( ord( '0' ), ord( '9' ) ) ); // Aanvullen met een cijfer tot minimaal 8 karakters en uniek.
			}
			$userdata->user_login = $user_login;
			$userdata->role       = '';
			wp_update_user( $userdata );
		}
	}

	/**
	 * Update the fields after update of user
	 *
	 * @param int $gebruiker_id Het gebruiker id.
	 *
	 * @internal Action for profile_update.
	 */
	public function profile_update( $gebruiker_id ) {
		$userdata = get_userdata( $gebruiker_id );
		if ( false !== $userdata ) {
			remove_action( 'profile_update', [ $this, __FUNCTION__ ] ); // Voorkom dat na de update deze actie opnieuw aangeroepen wordt.
			$nice_voornaam           = strtolower( preg_replace( '/[^a-zA-Z\s]/', '', remove_accents( $userdata->first_name ) ) );
			$nice_achternaam         = strtolower( preg_replace( '/[^a-zA-Z\s]/', '', remove_accents( $userdata->last_name ) ) );
			$userdata->user_nicename = "$nice_voornaam-$nice_achternaam";
			$userdata->display_name  = "{$userdata->first_name} {$userdata->last_name}";
			wp_update_user( $userdata );
		}
	}

}
