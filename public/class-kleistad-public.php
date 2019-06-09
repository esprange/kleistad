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

/**
 * De kleistad class voor de publieke pagina's.
 */
class Kleistad_Public {

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
	 * De kleistad plugin opties.
	 *
	 * @var array kleistad plugin settings
	 */
	private $options;

	/**
	 * Geeft de basis url terug voor de endpoints.
	 *
	 * @return string url voor endpoints
	 */
	public static function url() {
		return 'kleistad_api';
	}

	/**
	 * Geeft de basis url terug voor de endpoints.
	 *
	 * @return string url voor endpoints
	 */
	public static function base_url() {
		return rest_url( self::url() );
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.87
	 *
	 * @param      string $version       The version of this plugin.
	 * @param      array  $options       De plugin options.
	 */
	public function __construct( $version, $options ) {
		$this->version = $version;
		$this->options = $options;
	}

	/**
	 * Voeg de shortcodes toe en enqueue scripts en style sheets waar nodig.
	 *
	 * @param array  $shortcodes Array met informatie of de shortcodes.
	 * @param string $dev        Eventuele prefix voor de bestanden.
	 */
	private function register_shortcodes( $shortcodes, $dev ) {
		global $post;

		foreach ( $shortcodes as $shortcode => $dependencies ) {
			add_shortcode( "kleistad_$shortcode", [ $this, 'shortcode_handler' ] );
			if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, "kleistad_$shortcode" ) ) {
				$file = str_replace( '_', '-', $shortcode );
				wp_enqueue_style(
					"kleistad$shortcode",
					plugin_dir_url( __FILE__ ) . "css/kleistad-public$dev.css",
					$dependencies['css'],
					$this->version
				);
				wp_enqueue_script(
					"kleistad$shortcode",
					plugin_dir_url( __FILE__ ) . "js/kleistad-public-$file$dev.js",
					$dependencies['js'],
					$this->version,
					false
				);
			}
		}
	}

	/**
	 * Registreer de scripts en stylesheets voor de publieke functies van de plugin.
	 *
	 * @since    4.0.87
	 */
	public function styles_and_scripts() {
		if ( defined( 'KLEISTAD_DEV' ) ) {
			$dev = KLEISTAD_DEV ? '' : '.min';
		} else {
			$dev = '.min';
		}
		$wp_scripts = wp_scripts();
		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
		// Volgens stricte wp rules zou de versie toegevoegd moeten worden als parameter.
		wp_register_style( 'jquery-ui', sprintf( '//code.jquery.com/ui/%s/themes/smoothness/jquery-ui.css', $wp_scripts->registered['jquery-ui-core']->ver ), [], null );
		wp_register_style( 'datatables', '//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css', [], null );
		wp_register_style( 'fullcalendar-core', '//cdn.jsdelivr.net/npm/@fullcalendar/core@4.1.0/main.min.css', [], null );
		wp_register_style( 'fullcalendar-day', '//cdn.jsdelivr.net/npm/@fullcalendar/daygrid@4.1.0/main.min.css', [ 'fullcalendar-core' ], null );
		wp_register_style( 'fullcalendar-week', '//cdn.jsdelivr.net/npm/@fullcalendar/timegrid@4.1.0/main.min.css', [ 'fullcalendar-core' ], null );

		wp_register_script( 'datatables', '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js', [ 'jquery' ], null, false );
		wp_register_script( 'fullcalendar-core', '//cdn.jsdelivr.net/npm/@fullcalendar/core@4.1.0/main.min.js', [], null, false );
		wp_register_script( 'fullcalendar-nl', '//cdn.jsdelivr.net/npm/@fullcalendar/core@4.1.0/locales/nl.min.js', [ 'fullcalendar-core' ], null, false );
		wp_register_script( 'fullcalendar-day', '//cdn.jsdelivr.net/npm/@fullcalendar/daygrid@4.1.0/main.min.js', [ 'fullcalendar-core' ], null, false );
		wp_register_script( 'fullcalendar-week', '//cdn.jsdelivr.net/npm/@fullcalendar/timegrid@4.1.0/main.min.js', [ 'fullcalendar-core' ], null, false );
		// phpcs:enable

		wp_enqueue_script( 'kleistad', plugin_dir_url( __FILE__ ) . "js/kleistad-public$dev.js", [ 'jquery', 'jquery-ui-dialog' ], $this->version, true );
		wp_localize_script(
			'kleistad',
			'kleistadData',
			[
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'success_message' => 'de bewerking is geslaagd!',
				'error_message'   => 'het was niet mogelijk om de bewerking uit te voeren',
				'base_url'        => self::base_url(),
			]
		);

		$this->register_shortcodes(
			[
				'abonnee_inschrijving'  => [
					'js'  => [ 'jquery', 'jquery-ui-selectmenu', 'jquery-ui-datepicker' ],
					'css' => [ 'jquery-ui' ],
				],
				'abonnee_wijziging'     => [
					'js'  => [ 'jquery', 'jquery-ui-dialog' ],
					'css' => [ 'jquery-ui' ],
				],
				'abonnement_overzicht'  => [
					'js'  => [ 'jquery', 'datatables' ],
					'css' => [ 'datatables' ],
				],
				'betaling'              => [
					'js'  => [ 'jquery' ],
					'css' => [],
				],
				'betalingen'            => [
					'js'  => [ 'jquery', 'datatables' ],
					'css' => [ 'datatables' ],
				],
				'cursus_beheer'         => [
					'js'  => [ 'jquery', 'jquery-ui-spinner', 'jquery-ui-datepicker', 'datatables' ],
					'css' => [ 'jquery-ui', 'datatables', 'dashicons' ],
				],
				'cursus_inschrijving'   => [
					'js'  => [ 'jquery', 'jquery-ui-selectmenu', 'jquery-ui-spinner' ],
					'css' => [ 'jquery-ui' ],
				],
				'cursus_overzicht'      => [
					'js'  => [ 'jquery', 'jquery-ui-dialog', 'datatables' ],
					'css' => [ 'jquery-ui', 'datatables' ],
				],
				'dagdelenkaart'         => [
					'js'  => [ 'jquery', 'jquery-ui-datepicker' ],
					'css' => [ 'jquery-ui' ],
				],
				'email'                 => [
					'js'  => [ 'jquery', 'tiny_mce' ],
					'css' => [ 'jquery-ui' ],
				],
				'kalender'              => [
					'js'  => [ 'jquery', 'fullcalendar-core', 'fullcalendar-nl', 'fullcalendar-day', 'fullcalendar-week' ],
					'css' => [ 'fullcalendar-core', 'fullcalendar-day', 'fullcalendar-week' ],
				],
				'rapport'               => [
					'js'  => [ 'jquery', 'datatables' ],
					'css' => [ 'datatables' ],
				],
				'recept_beheer'         => [
					'js'  => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-autocomplete', 'datatables' ],
					'css' => [ 'jquery-ui', 'datatables', 'dashicons' ],
				],
				'recept'                => [
					'js'  => [ 'jquery' ],
					'css' => [ 'dashicons' ],
				],
				'registratie_overzicht' => [
					'js'  => [ 'jquery', 'jquery-ui-dialog', 'datatables' ],
					'css' => [ 'jquery-ui', 'datatables', 'dashicons' ],
				],
				'registratie'           => [
					'js'  => [ 'jquery' ],
					'css' => [],
				],
				'reservering'           => [
					'js'  => [ 'jquery', 'jquery-ui-dialog' ],
					'css' => [ 'jquery-ui' ],
				],
				'saldo_overzicht'       => [
					'js'  => [ 'jquery', 'datatables' ],
					'css' => [ 'datatables' ],
				],
				'saldo'                 => [
					'js'  => [ 'jquery' ],
					'css' => [],
				],
				'stookbestand'          => [
					'js'  => [ 'jquery', 'jquery-ui-datepicker' ],
					'css' => [ 'jquery-ui' ],
				],
				'workshop_beheer'       => [
					'js'  => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-spinner', 'jquery-ui-datepicker', 'datatables' ],
					'css' => [ 'jquery-ui', 'datatables' ],
				],
			],
			$dev
		);
	}

	/**
	 * Registreer de AJAX endpoints
	 *
	 * @since   4.0.87
	 */
	public function register_endpoints() {
		Kleistad_Public_Reservering::register_rest_routes();
		Kleistad_Public_Recept::register_rest_routes();
		Kleistad_Public_Kalender::register_rest_routes();
		Kleistad_Betalen::register_rest_routes();
		Kleistad_Adres::register_rest_routes();
	}

	/**
	 * Maak de custom post types en taxonomy
	 *
	 * @since 4.1.0
	 */
	public function register_post_types() {
		Kleistad_Recept::create_type();
		ob_start(); // Dit heeft niets van doen met registreren post types maar omdat dit vanuit de init wordt aangeroepen lost dit een headers problem op.
	}

	/**
	 * Wordt aangeroepen door filter single_template, zorgt dat WP de juiste template file toont.
	 *
	 * @since 4.1.0
	 *
	 * @param string $single_template het template path.
	 * @return string
	 */
	public function single_template( $single_template ) {
		global $post;

		if ( false !== strpos( $post->post_type, 'kleistad_' ) ) {
			$object          = substr( $post->post_type, strlen( 'kleistad_' ) );
			$single_template = dirname( __FILE__ ) . "/partials/kleistad-public-single-$object.php";
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
	 */
	public function comments_template( $comments_template ) {
		global $post;

		if ( false !== strpos( $post->post_type, 'kleistad_' ) ) {
			$object            = substr( $post->post_type, strlen( 'kleistad_' ) );
			$comments_template = dirname( __FILE__ ) . "/partials/kleistad-public-comments-$object.php";
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
	 */
	public function comment_fields( $fields ) {
		if ( isset( $fields['url'] ) ) {
			unset( $fields['url'] );
		}
		return $fields;
	}

	/**
	 * Kijk bij de login of een account geblokkeerd is
	 *
	 * @since 4.0.87
	 * @param string $user_login niet gebruikte parameter.
	 * @param object $user wp user object.
	 */
	public function user_login( $user_login, $user = null ) {

		if ( ! $user ) {
			$user = get_user_by( 'login', $user_login );
		}
		if ( ! $user ) {
			return;
		}
		$disabled = get_user_meta( $user->ID, 'kleistad_disable_user', true );

		if ( '1' === $disabled ) {
			wp_clear_auth_cookie();

			$login_url = add_query_arg( 'disabled', '1', site_url( 'wp-login.php', 'login' ) );
			wp_safe_redirect( $login_url );
			die();
		}
	}

	/**
	 * Toon een melding aan geblokkeerde gebruikers bij het inloggen.
	 *
	 * @since 4.0.87
	 *
	 * @param string $message the message shown to the user.
	 * @return string
	 */
	public function user_login_message( $message ) {

		$disabled = filter_input( INPUT_GET, 'disabled' );
		if ( ! is_null( $disabled ) && 1 === $disabled ) {
			$message = '<div id="login_error">' . apply_filters( 'kleistad_disable_users_notice', 'Inloggen op dit account niet toegestaan' ) . '</div>';
		}
		return $message;
	}

	/**
	 * Redirect gebruikers naar de leden pagina.
	 *
	 * @since 4.5.2
	 *
	 * @param string  $url De bestaande url als er niets gewijzigd wordt.
	 * @param object  $request Wordt niet gebruikt.
	 * @param WP_User $user Het WordPress user object.
	 * @return string De Url.
	 */
	public function login_redirect( $url, $request, $user ) {
		if ( isset( $request ) && $user && is_object( $user ) && is_a( $user, 'WP_User' ) ) { // De test van request is dummy statement, altijd true.
			$url = ( $user->has_cap( 'bestuur' ) ) ? home_url( '/bestuur/' ) : home_url( '/leden/' );
		}
		return $url;
	}

	/**
	 * Verberg de toolbar voor iedereen die geen edit toegang op pagina's heeft.
	 *
	 * @since 4.0.87
	 */
	public function verberg_toolbar() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			if ( is_admin_bar_showing() ) {
				show_admin_bar( false );
			}
		}
	}

	/**
	 * Toont login en loguit menu opties.
	 *
	 * @since 4.0.87
	 *
	 * @staticvar bool $is_active Bewaart de activeringsstatus, als true dan niets doen.
	 * @param string   $items     De menu opties.
	 * @param stdClass $args      De argumenten van het filter.
	 * @return string
	 */
	public function loginuit_menu( $items, $args ) {
		static $is_active = false;

		if ( is_admin() || 'primary' !== $args->theme_location || $is_active ) {
			return $items;
		}

		$redirect = get_permalink();
		if ( false === $redirect || is_home() ) {
			$redirect = home_url();
		}
		if ( is_user_logged_in() ) {
			$link = '<a href="' . wp_logout_url( home_url() ) . '" title="Uitloggen">Uitloggen</a>';
		} else {
			$link = '<a href="' . wp_login_url( $redirect ) . '" title="Inloggen">Inloggen</a>';
		}
		$is_active = true;
		$items    .= '<li id="log-in-out-link" class="menu-item menu-type-link">' . $link . '</li>';
		return $items;
	}

	/**
	 * Uitbreiding WP_User object met adres gegevens
	 *
	 * @since 4.5.1
	 *
	 * @param array $user_contact_method De extra velden met adresgegevens.
	 * @return array de extra velden.
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
	 * Shortcode form handler functie, toont formulier, valideert input, bewaart gegevens en toont resultaat
	 *
	 * @since 4.0.87
	 *
	 * @param array  $atts      de meegegeven params van de shortcode.
	 * @param string $content   wordt niet gebruikt.
	 * @param string $tag       wordt gebruikt als selector voor de diverse functie aanroepen.
	 * @return string           html resultaat.
	 */
	public function shortcode_handler( $atts, $content, $tag ) {
		$form        = substr( $tag, strlen( 'kleistad-' ) );
		$form_class  = 'Kleistad_Public_' . str_replace( ' ', '_', ucwords( str_replace( '_', ' ', $form ) ) );
		$form_object = new $form_class( $form, $atts, $this->options );
		if ( ! empty( $atts ) ) {
			$atts = wp_json_encode( $atts );
		}
		$html  = '<div id="kleistad_shortcode" data-atts=' . "'$atts' >" . $form_object->run() . '</div>';
		$html .= '<div id="kleistad_bevestigen" data-ok="" ></div>';
		$html .= '<div id="kleistad_wachten" ></div>';
		return $html;
	}

	/**
	 * Update abonnement batch job.
	 *
	 * @since 4.0.87
	 *
	 * @param int    $id    De id van de abonnee.
	 * @param string $actie De uit te voeren actie.
	 * @param int    $datum Datum waarop het moet worden uitgevoerd.
	 */
	public function update_abonnement( $id, $actie, $datum ) {
		$abonnement = new Kleistad_Abonnement( $id );
		$abonnement->event( $actie, $datum );
	}

	/**
	 * Update workshop batch job.
	 *
	 * @since 5.0.0
	 *
	 * @param int    $id    De id van de workshop.
	 * @param string $actie De uit te voeren actie.
	 */
	public function update_workshop( $id, $actie ) {
		$workshop = new Kleistad_Workshop( $id );
		$workshop->event( $actie );
	}

	/**
	 *
	 * Update ovenkosten batch job
	 *
	 * @since 4.0.87
	 */
	public function update_ovenkosten() {
		set_time_limit( 300 ); // Voorkom dat deze job er door een execution time out crasht, dus 300 sec = 5 minuten.
		Kleistad_Saldo::meld_en_verwerk();
	}

	/**
	 * Insert of update de gebruiker.
	 *
	 * @since 4.5.1
	 *
	 * @param array $userdata De gebruiker gegevens, inclusief contact informatie.
	 * @return int|WP_Error  De user_id of een error object.
	 */
	public static function upsert_user( $userdata ) {
		$nice_voornaam   = strtolower( preg_replace( '/[^a-zA-Z\s]/', '', remove_accents( $userdata['first_name'] ) ) );
		$nice_achternaam = strtolower( preg_replace( '/[^a-zA-Z\s]/', '', remove_accents( $userdata['last_name'] ) ) );

		if ( is_null( $userdata['ID'] ) ) {
			$uniek     = '';
			$startnaam = $nice_voornaam;
			if ( 8 > mb_strlen( $startnaam ) ) { // Gebruikersnaam moet minimaal 8 karakters hebben.
				$startnaam = substr( $startnaam . $nice_achternaam, 0, 8 );
				while ( 8 > mb_strlen( $startnaam ) ) {
					$startnaam .= chr( wp_rand( ord( '0' ), ord( '9' ) ) ); // Aanvullen met een cijfer.
				}
			}
			while ( username_exists( $startnaam . $uniek ) ) {
				$uniek = intval( $uniek ) + 1;
			}

			$userdata['user_login']      = $startnaam . $uniek;
			$userdata['user_pass']       = wp_generate_password( 12, true );
			$userdata['user_registered'] = date( 'Y-m-d H:i:s' );
			$userdata['user_nicename']   = $nice_voornaam . '-' . $nice_achternaam;
			$userdata['display_name']    = $userdata['first_name'] . ' ' . $userdata['last_name'];
			$userdata['role']            = '';
			$result                      = wp_insert_user( (object) $userdata );
		} else {
			$userdata['user_nicename'] = $nice_voornaam . '-' . $nice_achternaam;
			$userdata['display_name']  = $userdata['first_name'] . ' ' . $userdata['last_name'];
			$result                    = wp_update_user( (object) $userdata );
		}

		return $result;
	}

}
