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
 * De kleistad class voor de publieke pagina's.
 */
class Public_Main {

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
		$this->shortcode_handler = new \Kleistad\Public_Shortcode_Handler( $options );
	}

	/**
	 * Geeft de basis url terug voor de endpoints.
	 *
	 * @return string url voor endpoints
	 */
	public static function api() {
		return 'kleistad_api';
	}

	/**
	 * Geeft de basis url terug voor de endpoints.
	 *
	 * @return string url voor endpoints
	 */
	public static function base_url() {
		return rest_url( self::api() );
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
		$dev        = defined( 'KLEISTAD_DEV' ) ? '' : '.min';
		$wp_scripts = wp_scripts();
		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
		// Volgens stricte wp rules zou de versie toegevoegd moeten worden als parameter.
		wp_register_style( 'jquery-ui', sprintf( '//code.jquery.com/ui/%s/themes/smoothness/jquery-ui.css', $wp_scripts->registered['jquery-ui-core']->ver ), [], null );
		wp_register_style( 'datatables', '//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css', [], null );
		wp_register_style( 'fullcalendar', '//cdn.jsdelivr.net/npm/fullcalendar@5.3.2/main.min.css', [], null );
		wp_register_style( 'jstree', '//cdn.jsdelivr.net/npm/jstree@3.3.9/dist/themes/default/style.min.css', [], null );
		wp_register_style( 'kleistad', plugin_dir_url( __FILE__ ) . "css/public$dev.css", [], $this->version );

		wp_register_script( 'datatables', '//cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js', [ 'jquery' ], null, false );
		wp_register_script( 'fullcalendar-core', '//cdn.jsdelivr.net/npm/fullcalendar@5.3.2/main.min.js', [], null, false );
		wp_register_script( 'fullcalendar', '//cdn.jsdelivr.net/npm/fullcalendar@5.3.2/locales-all.min.js', [ 'fullcalendar-core' ], null, false );
		wp_register_script( 'jstree', '//cdn.jsdelivr.net/npm/jstree@3.3.9/dist/jstree.min.js', [ 'jquery' ], null, false );
		wp_register_script( 'kleistad', plugin_dir_url( __FILE__ ) . "js/public$dev.js", [ 'jquery', 'jquery-ui-dialog' ], $this->version, true );
		wp_register_script( 'kleistad-form', plugin_dir_url( __FILE__ ) . "js/public-form$dev.js", [ 'kleistad' ], $this->version, true );

		foreach ( \Kleistad\Public_Shortcode_Handler::SHORTCODES as $shortcode => $dependencies ) {
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
	 */
	public function register_endpoints() {
		\Kleistad\Adres::register_rest_routes(); // Postcode.
		\Kleistad\Betalen::register_rest_routes(); // Mollie.
		\Kleistad\Public_Kalender::register_rest_routes(); // Google API.
		\Kleistad\Public_Recept::register_rest_routes(); // Recept zoeker.
		\Kleistad\Public_Reservering::register_rest_routes(); // Oven reserveringen.
		\Kleistad\Shortcode::register_rest_routes(); // Shortcode opvragen.
		\Kleistad\ShortcodeForm::register_rest_routes(); // Shortcode formulieren.
	}

	/**
	 * Maak de custom post types en taxonomy
	 *
	 * @since 4.1.0
	 *
	 * @internal Action for init.
	 */
	public static function register_post_types() {
		global $wp_post_types;
		\Kleistad\Recept::create_type();
		\Kleistad\WorkshopAanvraag::create_type();
		\Kleistad\Email::create_type();
		$wp_post_types[ \Kleistad\WorkshopAanvraag::POST_TYPE ]->exclude_from_search = true;
		$wp_post_types[ \Kleistad\Email::POST_TYPE ]->exclude_from_search            = true;
	}

	/**
	 * Wordt aangeroepen door filter single_template, zorgt dat WP de juiste template file toont.
	 *
	 * @since 4.1.0
	 *
	 * @param string $single_template het template path.
	 * @return string
	 *
	 * @internal Filter for single_template.
	 */
	public function single_template( $single_template ) {
		global $post;

		if ( false !== strpos( $post->post_type, 'kleistad_' ) ) {
			$object          = substr( $post->post_type, strlen( 'kleistad_' ) );
			$single_template = dirname( __FILE__ ) . "/partials/public-single-$object.php";
		}
		return $single_template;
	}

	/**
	 * Wordt aangeroepen door filter comments_template, zorgt dat WP de juiste template file toont.
	 *
	 * @since 4.1.0
	 *
	 * @param string $comments_template het template path.
	 * @return string
	 *
	 * @internal Filter for comments_template.
	 */
	public function comments_template( $comments_template ) {
		global $post;

		if ( false !== strpos( $post->post_type, 'kleistad_' ) ) {
			$object            = substr( $post->post_type, strlen( 'kleistad_' ) );
			$comments_template = dirname( __FILE__ ) . "/partials/public-comments-$object.php";
		}
		return $comments_template;
	}

	/**
	 * Wordt aangeroepen door filter comment form default fields, om niet te vragen naar een website url.
	 *
	 * @since 4.1.0
	 *
	 * @param array $fields De commentaar velden.
	 * @return array
	 *
	 * @internal Filter for comment_form_default_fields.
	 */
	public function comment_fields( $fields ) {
		if ( isset( $fields['url'] ) ) {
			unset( $fields['url'] );
		}
		return $fields;
	}

	/**
	 * Wordt aangeroepen door filter email_change_email, als er een email adres gewijzigd wordt.
	 *
	 * @param array $email_change_email Basis voor WP_mail.
	 * @param array $user               De bestaande user info.
	 * @param array $userdata           De gewijzigd user info.
	 *
	 * @internal Filter for email_change_email.
	 * phpcs:disable
	 */
	public function email_change_email( /** @scrutinizer ignore-unused */ $email_change_email, $user, $userdata ) {
		// phpcs:enable
		$emailer = new \Kleistad\Email();
		return $emailer->notify(
			[
				'slug'       => 'email_wijziging',
				'to'         => $userdata['user_email'],
				'cc'         => [ $user['user_email'] ],
				'subject'    => 'Wijziging email adres',
				'parameters' => [
					'voornaam'   => $userdata['first_name'],
					'achternaam' => $userdata['last_name'],
					'email'      => $userdata['user_email'],
				],
			]
		);
	}

	/**
	 * Wordt aangeroepen door filter password_change_email, als het wachtwoord gewijzigd wordt.
	 *
	 * @param array $email_change_email Basis voor WP_mail.
	 * @param array $user               De bestaande user info.
	 * @param array $userdata           De gewijzigd user info.
	 *
	 * @internal Filter for password_change_email.
	 * phpcs:disable
	 */
	public function password_change_email( /** @scrutinizer ignore-unused */ $email_change_email, /** @scrutinizer ignore-unused */ $user, $userdata ) {
		// phpcs:enable
		$emailer = new \Kleistad\Email();
		return $emailer->notify(
			[
				'to'         => $userdata['user_email'],
				'subject'    => 'Wachtwoord gewijzigd',
				'slug'       => 'wachtwoord_wijziging',
				'parameters' => [
					'voornaam'   => $userdata['first_name'],
					'achternaam' => $userdata['last_name'],
				],
			]
		);
	}

	/**
	 * Wordt aangeroepen door filter retrieve_password_message, als er een wachtwoord reset gevraagd wordt.
	 *
	 * @param string   $message    Het bericht.
	 * @param string   $key        De reset sleutel.
	 * @param string   $user_login De gebruiker login naam.
	 * @param \WP_User $user_data  Het user record van de gebruiker.
	 *
	 * @internal Filter for retrieve_password_message.
	 * phpcs:disable
	 */
	public function retrieve_password_message( /** @scrutinizer ignore-unused */ $message, $key, $user_login = '', $user_data = '' ) {
		// phpcs:enable
		$emailer = new \Kleistad\Email();
		$result  = $emailer->notify(
			[
				'slug'       => 'wachtwoord_reset',
				'to'         => $user_data->user_email,
				'subject'    => 'Wachtwoord reset',
				'parameters' => [
					'voornaam'   => $user_data->first_name,
					'achternaam' => $user_data->last_name,
					'reset_link' => '<a href="' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . '" >wachtwoord reset pagina</a>',
				],
			]
		);
		return $result['message'];
	}

	/**
	 * Password hint
	 *
	 * @return string
	 *
	 * @internal Filter for password_hint.
	 */
	public function password_hint() {
		return "Hint: het wachtwoord moet minimaal 9 tekens lang zijn. Bij de invoer wordt gecontroleerd op te gemakkelijk te bedenken wachtwoorden (als 1234...).\nGebruik hoofd- en kleine letters, nummers en tekens zoals ! \" ? $ % ^ & ) om het wachtwoord sterker te maken.";
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
	 * Uitbreiding \WP_User object met adres gegevens
	 *
	 * @since 4.5.1
	 *
	 * @param array $user_contact_method De extra velden met adresgegevens.
	 * @return array de extra velden.
	 *
	 * @internal Filter for user_contactmethods.
	 */
	public function user_contact_methods( $user_contact_method ) {

		$user_contact_method['telnr']  = 'Telefoon nummer';
		$user_contact_method['straat'] = 'Straat';
		$user_contact_method['huisnr'] = 'Nummer';
		$user_contact_method['pcode']  = 'Postcode';
		$user_contact_method['plaats'] = 'Plaats';

		return $user_contact_method;
	}

	/**
	 * Pas de template aan ingeval van de pagina voor de ideal betaal link.
	 *
	 * @param string $template De locatie van de template file.
	 *
	 * @internal Filter for template_include.
	 */
	public function template_include( $template ) {
		if ( is_page( 'kleistad-betaling' ) ) {
			return dirname( __FILE__ ) . '/partials/public-betaling-page.php';
		}
		return $template;
	}

	/**
	 * Ontvang en verwerk email
	 *
	 * @internal Action for rcv_email.
	 */
	public function rcv_email() {
		\Kleistad\WorkshopAanvraag::ontvang_en_verwerk();
	}

	/**
	 * Update het wachtwoord (aangeroepen via admin_ajax).
	 *
	 * @internal Action for wp_ajax_kleistad_wachtwoord, wp_ajax_nopriv_kleistad_wachtwoord.
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
	 * @param int $id Het gebruiker id.
	 *
	 * @internal Action for user_register.
	 */
	public function user_register( $id ) {
		$userdata = get_userdata( $id );
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
	 * @param int $id Het gebruiker id.
	 *
	 * @internal Action for profile_update.
	 */
	public function profile_update( $id ) {
		$userdata = get_userdata( $id );
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
