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

/**
 * De kleistad class voor de publieke actions.
 */
class Public_Actions {

	/**
	 * De handler voor de shortcodes.
	 *
	 * @since 6.4.2
	 * @access private
	 * @var Public_Shortcode_Handler $shortcode_handler De handler voor de shortcodes.
	 */
	private Public_Shortcode_Handler $shortcode_handler;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.87
	 */
	public function __construct() {
		$this->shortcode_handler = new Public_Shortcode_Handler();
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
		$dev                  = 'development' === wp_get_environment_type() ? '' : '.min';
		$jquery_ui_version    = wp_scripts()->registered['jquery-ui-core']->ver;
		$fullcalendar_version = '5.6.0';
		$datatables_version   = '1.10.24';
		$jstree_version       = '3.3.11';
		wp_register_style( 'jquery-ui', sprintf( '//code.jquery.com/ui/%s/themes/smoothness/jquery-ui.css', $jquery_ui_version ), [], $jquery_ui_version );
		wp_register_style( 'datatables', sprintf( '//cdn.datatables.net/%s/css/jquery.dataTables.min.css', $datatables_version ), [], $datatables_version );
		wp_register_style( 'fullcalendar', sprintf( '//cdn.jsdelivr.net/npm/fullcalendar@%s/main.min.css', $fullcalendar_version ), [], $fullcalendar_version );
		wp_register_style( 'jstree', sprintf( '//cdn.jsdelivr.net/npm/jstree@%s/dist/themes/default/style.min.css', $jstree_version ), [], $jstree_version );

		wp_register_script( 'datatables', sprintf( '//cdn.datatables.net/%s/js/jquery.dataTables.min.js', $datatables_version ), [ 'jquery' ], $datatables_version, true );
		wp_register_script( 'fullcalendar-core', sprintf( '//cdn.jsdelivr.net/npm/fullcalendar@%s/main.min.js', $fullcalendar_version ), [], $fullcalendar_version, true );
		wp_register_script( 'fullcalendar', sprintf( '//cdn.jsdelivr.net/npm/fullcalendar@%s/locales-all.min.js', $fullcalendar_version ), [ 'fullcalendar-core' ], $fullcalendar_version, true );
		wp_register_script( 'jstree', sprintf( '//cdn.jsdelivr.net/npm/jstree@%s/dist/jstree.min.js', $jstree_version ), [ 'jquery' ], $jstree_version, true );

		wp_register_script( 'kleistad', plugin_dir_url( __FILE__ ) . "js/public$dev.js", [ 'jquery' ], versie(), true );
		wp_register_script( 'kleistad-form', plugin_dir_url( __FILE__ ) . "js/public-form$dev.js", [ 'kleistad', 'jquery-ui-dialog' ], versie(), true );

		$shortcodes = new Shortcodes();
		$styles     = [];
		foreach ( $shortcodes->heeft_shortcode() as $tag ) {
			$styles  = array_merge( $styles, $shortcodes->definities[ $tag ]->css );
			$script  = "kleistad-$tag";
			$scripts = array_merge(
				$shortcodes->definities[ $tag ]->js,
				[ ( get_parent_class( Shortcode::get_class_name( $tag ) ) === Shortcode::class ) ? 'kleistad' : 'kleistad-form' ]
			);
			$file    = ( $shortcodes->definities[ $tag ]->script ) ?
				plugin_dir_url( __FILE__ ) . 'js/public-' . str_replace( '_', '-', $tag ) . "$dev.js" : false;
			wp_register_script( $script, $file, $scripts, versie(), true );
		}
		wp_enqueue_style( 'kleistad', plugin_dir_url( __FILE__ ) . "css/public$dev.css", array_unique( $styles ), versie() );
		wp_localize_script(
			'kleistad',
			'kleistadData',
			[
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'error_message' => 'het was niet mogelijk om de bewerking uit te voeren',
				'base_url'      => base_url(),
				'admin_url'     => admin_url( 'admin-ajax.php' ),
			]
		);

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
		foreach (
			[
				'Adres',
				'MollieClient',
				'Public_Kalender',
				'Public_Recept',
				'Public_Reservering',
				'Public_Werkplek',
				'Shortcode',
				'ShortcodeForm',
			]  as $object ) {
				call_user_func( [ '\\' . __NAMESPACE__ . '\\' . $object, 'register_rest_routes' ] ); // Postcode.
		}
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
		Recept::create_type();
		WorkshopAanvraag::create_type();
		Email::create_type();
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
		$receiver = new EmailReceiver();
		$receiver->ontvang( [ '\\' . __NAMESPACE__ . '\\WorkshopAanvraag', 'verwerk' ] );
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
	public function user_register( int $gebruiker_id ) {
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
	public function profile_update( int $gebruiker_id ) {
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
