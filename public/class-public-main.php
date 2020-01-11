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
	 * De kleistad plugin opties.
	 *
	 * @var array kleistad plugin settings
	 */
	private $options;

	/**
	 * De shortcodes van kleistad
	 *
	 * @var array shortcodes met hun style en jscript afhankelijkheden.
	 */
	const SHORTCODES = [
		'abonnee_inschrijving'  => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-selectmenu', 'jquery-ui-datepicker' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [],
		],
		'abonnee_wijziging'     => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-datepicker' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [ 'leden' ],
		],
		'abonnement_overzicht'  => [
			'script' => false,
			'js'     => [ 'jquery', 'datatables' ],
			'css'    => [ 'datatables' ],
			'access' => [ 'bestuur' ],
		],
		'betaling'              => [
			'script' => true,
			'js'     => [ 'jquery', 'datatables' ],
			'css'    => [ 'datatables' ],
			'access' => [],
		],
		'cursus_beheer'         => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-spinner', 'jquery-ui-datepicker', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
			'access' => [ 'bestuur' ],
		],
		'cursus_inschrijving'   => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-selectmenu', 'jquery-ui-spinner' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [],
		],
		'cursus_overzicht'      => [
			'script' => false,
			'js'     => [ 'jquery', 'jquery-ui-dialog', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
			'access' => [ 'docenten', 'bestuur' ],
		],
		'dagdelenkaart'         => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-datepicker' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [],
		],
		'debiteuren'            => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-dialog', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables' ],
			'access' => [ 'boekhouding' ],
		],
		'email'                 => [
			'script' => true,
			'js'     => [ 'jquery', 'jstree' ],
			'css'    => [ 'jquery-ui', 'jstree' ],
			'access' => [ 'docenten', 'bestuur' ],
		],
		'kalender'              => [
			'script' => true,
			'js'     => [ 'jquery', 'fullcalendar-core', 'fullcalendar-nl', 'fullcalendar-day', 'fullcalendar-week' ],
			'css'    => [ 'fullcalendar-core', 'fullcalendar-day', 'fullcalendar-week' ],
			'access' => [ 'docenten', 'leden', 'bestuur' ],
		],
		'omzet_rapportage'      => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-datepicker', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables' ],
			'access' => [ 'bestuur' ],
		],
		'rapport'               => [
			'script' => false,
			'js'     => [ 'jquery', 'datatables' ],
			'css'    => [ 'datatables' ],
			'access' => [ 'docenten', 'leden', 'bestuur' ],
		],
		'recept_beheer'         => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-autocomplete', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
			'access' => [ 'docenten', 'leden', 'bestuur' ],
		],
		'recept'                => [
			'script' => true,
			'js'     => [ 'jquery' ],
			'css'    => [ 'dashicons' ],
			'access' => [],
		],
		'registratie_overzicht' => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-dialog', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
			'access' => [ 'bestuur' ],
		],
		'registratie'           => [
			'script' => false,
			'js'     => [ 'jquery' ],
			'css'    => [],
			'access' => [ 'docenten', 'leden', 'bestuur' ],
		],
		'reservering'           => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-dialog' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [ 'docenten', 'leden', 'bestuur' ],
		],
		'saldo_overzicht'       => [
			'script' => false,
			'js'     => [ 'jquery', 'datatables' ],
			'css'    => [ 'datatables' ],
			'access' => [ 'bestuur' ],
		],
		'saldo'                 => [
			'script' => true,
			'js'     => [ 'jquery' ],
			'css'    => [],
			'access' => [ 'docenten', 'leden', 'bestuur' ],
		],
		'stookbestand'          => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-datepicker' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [ 'bestuur' ],
		],
		'workshop_aanvraag'     => [
			'script' => false,
			'js'     => [ 'jquery' ],
			'css'    => [],
			'access' => [],
		],
		'workshop_beheer'       => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-spinner', 'jquery-ui-datepicker', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables' ],
			'access' => [ 'bestuur' ],
		],
	];

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
	 * Voeg de shortcodes toe.
	 */
	public function register_shortcodes() {
		foreach ( array_keys( self::SHORTCODES ) as $shortcode ) {
			add_shortcode( "kleistad_$shortcode", [ $this, 'shortcode_handler' ] );
		}
	}

	/**
	 * Registreer de scripts en stylesheets voor de publieke functies van de plugin.
	 *
	 * @since    4.0.87
	 */
	public function register_styles_and_scripts() {
		$dev        = defined( 'KLEISTAD_DEV' ) ? '' : '.min';
		$wp_scripts = wp_scripts();
		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
		// Volgens stricte wp rules zou de versie toegevoegd moeten worden als parameter.
		wp_register_style( 'jquery-ui', sprintf( '//code.jquery.com/ui/%s/themes/smoothness/jquery-ui.css', $wp_scripts->registered['jquery-ui-core']->ver ), [], null );
		wp_register_style( 'datatables', '//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css', [], null );
		wp_register_style( 'fullcalendar-core', '//cdn.jsdelivr.net/npm/@fullcalendar/core@4.3.1/main.min.css', [], null );
		wp_register_style( 'fullcalendar-day', '//cdn.jsdelivr.net/npm/@fullcalendar/daygrid@4.3.0/main.min.css', [ 'fullcalendar-core' ], null );
		wp_register_style( 'fullcalendar-week', '//cdn.jsdelivr.net/npm/@fullcalendar/timegrid@4.3.0/main.min.css', [ 'fullcalendar-core' ], null );
		wp_register_style( 'jstree', '//cdn.jsdelivr.net/npm/jstree@3.3.8/dist/themes/default/style.min.css', [], null );
		wp_register_style( 'kleistad', plugin_dir_url( __FILE__ ) . "css/public$dev.css", [], $this->version );

		wp_register_script( 'datatables', '//cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js', [ 'jquery' ], null, false );
		wp_register_script( 'fullcalendar-core', '//cdn.jsdelivr.net/npm/@fullcalendar/core@4.3.1/main.min.js', [], null, false );
		wp_register_script( 'fullcalendar-nl', '//cdn.jsdelivr.net/npm/@fullcalendar/core@4.3.0/locales/nl.min.js', [ 'fullcalendar-core' ], null, false );
		wp_register_script( 'fullcalendar-day', '//cdn.jsdelivr.net/npm/@fullcalendar/daygrid@4.3.0/main.min.js', [ 'fullcalendar-core' ], null, false );
		wp_register_script( 'fullcalendar-week', '//cdn.jsdelivr.net/npm/@fullcalendar/timegrid@4.3.0/main.min.js', [ 'fullcalendar-core' ], null, false );
		wp_register_script( 'jstree', '//cdn.jsdelivr.net/npm/jstree@3.3.8/dist/jstree.min.js', [ 'jquery' ], null, false );
		wp_register_script( 'kleistad', plugin_dir_url( __FILE__ ) . "js/public$dev.js", [ 'jquery', 'jquery-ui-dialog' ], $this->version, true );
		wp_register_script( 'kleistad-form', plugin_dir_url( __FILE__ ) . "js/public-form$dev.js", [ 'kleistad' ], $this->version, true );

		foreach ( self::SHORTCODES as $shortcode => $dependencies ) {
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
	 * phpcs:disable
	 */
	public function email_change_email( /** @scrutinizer ignore-unused */ $email_change_email, /** @scrutinizer ignore-unused */ $user, $userdata ) {
		$emailer = new \Kleistad\Email();
		return $emailer->notify( 'email_wijziging', $userdata );
	} // phpcs:enable

	/**
	 * Wordt aangeroepen door filter password_change_email, als het wachtwoord gewijzigd wordt.
	 *
	 * @param array $email_change_email Basis voor WP_mail.
	 * @param array $user               De bestaande user info.
	 * @param array $userdata           De gewijzigd user info.
	 * phpcs:disable
	 */
	public function password_change_email( /** @scrutinizer ignore-unused */ $email_change_email, /** @scrutinizer ignore-unused */ $user, $userdata ) {
		$emailer = new \Kleistad\Email();
		return $emailer->notify( 'paswoord_wijziging', $userdata );
	} // phpcs:enable

	/**
	 * Uitbreiding \WP_User object met adres gegevens
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
	 * Pas de template aan ingeval van de pagina voor de ideal betaal link.
	 *
	 * @param string $template De locatie van de template file.
	 */
	public function template_include( $template ) {
		if ( is_page( 'kleistad-betaling' ) ) {
			return dirname( __FILE__ ) . '/partials/public-betaling-page.php';
		}
		return $template;
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
		$shortcode = substr( $tag, strlen( 'kleistad-' ) );

		$shortcode_class  = '\Kleistad\Public_' . ucwords( $shortcode, '_' );
		$shortcode_object = new $shortcode_class( $shortcode, $atts, $this->options );
		if ( ! \Kleistad\Shortcode::check_access( $shortcode ) ) {
			return $shortcode_object->status( new \WP_Error( 'toegang', 'Je hebt geen toegang tot deze functie' ) );
		}
		$html        = '';
		static $divs = false; // De ondersteunende divs zijn maar eenmalig nodig.
		if ( ! $divs ) {
			$divs  = true;
			$html .= '<div id="kleistad_berichten" ></div><div id="kleistad_bevestigen" ></div><div id="kleistad_wachten" ></div>';
		}
		$html .= '<div class="kleistad_shortcode" data-tag="' . $shortcode . '" ';
		if ( ! empty( $atts ) ) {
			$json_atts = wp_json_encode( $atts, JSON_HEX_QUOT | JSON_HEX_TAG );
			$html     .= ' data-atts=' . "'$json_atts'";
		}
		$html .= ' >' . $shortcode_object->run() . '</div>';
		return $html;
	}

	/**
	 * Ontvang en verwerk email
	 */
	public function rcv_email() {
		\Kleistad\WorkshopAanvraag::ontvang_en_verwerk();
	}

	/**
	 * Insert of update de gebruiker.
	 *
	 * @param array $userdata De gebruiker gegevens, inclusief contact informatie.
	 * @return int|\WP_Error  De user_id of een error object.
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
