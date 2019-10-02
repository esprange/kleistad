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
			'js'     => [ 'jquery', 'jquery-ui-selectmenu', 'jquery-ui-datepicker' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [ 'administrator', '#' ],
		],
		'abonnee_wijziging'     => [
			'js'     => [ 'jquery', 'jquery-ui-dialog' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [ 'leden' ],
		],
		'abonnement_overzicht'  => [
			'js'     => [ 'jquery', 'datatables' ],
			'css'    => [ 'datatables' ],
			'access' => [ 'bestuur' ],
		],
		'betaling'              => [
			'js'     => [ 'jquery' ],
			'css'    => [],
			'access' => [],
		],
		'betalingen'            => [
			'js'     => [ 'jquery', 'datatables' ],
			'css'    => [ 'datatables' ],
			'access' => [ 'bestuur' ],
		],
		'cursus_beheer'         => [
			'js'     => [ 'jquery', 'jquery-ui-spinner', 'jquery-ui-datepicker', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
			'access' => [ 'bestuur' ],
		],
		'cursus_inschrijving'   => [
			'js'     => [ 'jquery', 'jquery-ui-selectmenu', 'jquery-ui-spinner' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [],
		],
		'cursus_overzicht'      => [
			'js'     => [ 'jquery', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
			'access' => [ 'leden', 'bestuur' ],
		],
		'dagdelenkaart'         => [
			'js'     => [ 'jquery', 'jquery-ui-datepicker' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [],
		],
		'email'                 => [
			'js'     => [ 'jquery', 'jstree' ],
			'css'    => [ 'jquery-ui', 'jstree' ],
			'access' => [ 'bestuur' ],
		],
		'kalender'              => [
			'js'     => [ 'jquery', 'fullcalendar-core', 'fullcalendar-nl', 'fullcalendar-day', 'fullcalendar-week' ],
			'css'    => [ 'fullcalendar-core', 'fullcalendar-day', 'fullcalendar-week' ],
			'access' => [ 'leden', 'bestuur' ],
		],
		'rapport'               => [
			'js'     => [ 'jquery', 'datatables' ],
			'css'    => [ 'datatables' ],
			'access' => [ 'leden', 'bestuur' ],
		],
		'recept_beheer'         => [
			'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-autocomplete', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
			'access' => [ 'leden', 'bestuur' ],
		],
		'recept'                => [
			'js'     => [ 'jquery' ],
			'css'    => [ 'dashicons' ],
			'access' => [],
		],
		'registratie_overzicht' => [
			'js'     => [ 'jquery', 'jquery-ui-dialog', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
			'access' => [ 'bestuur' ],
		],
		'registratie'           => [
			'js'     => [ 'jquery' ],
			'css'    => [],
			'access' => [ 'leden', 'bestuur' ],
		],
		'reservering'           => [
			'js'     => [ 'jquery', 'jquery-ui-dialog' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [ 'leden', 'bestuur' ],
		],
		'saldo_overzicht'       => [
			'js'     => [ 'jquery', 'datatables' ],
			'css'    => [ 'datatables' ],
			'access' => [ 'bestuur' ],
		],
		'saldo'                 => [
			'js'     => [ 'jquery' ],
			'css'    => [],
			'access' => [ 'leden', 'bestuur' ],
		],
		'stookbestand'          => [
			'js'     => [ 'jquery', 'jquery-ui-datepicker' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [ 'bestuur' ],
		],
		'workshop_aanvraag'     => [
			'js'     => [ 'jquery' ],
			'css'    => [],
			'access' => [],
		],
		'workshop_beheer'       => [
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
	public function styles_and_scripts() {
		$dev        = defined( 'KLEISTAD_DEV' ) ? '' : '.min';
		$wp_scripts = wp_scripts();
		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
		// Volgens stricte wp rules zou de versie toegevoegd moeten worden als parameter.
		wp_register_style( 'jquery-ui', sprintf( '//code.jquery.com/ui/%s/themes/smoothness/jquery-ui.css', $wp_scripts->registered['jquery-ui-core']->ver ), [], null );
		wp_register_style( 'datatables', '//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css', [], null );
		wp_register_style( 'fullcalendar-core', '//cdn.jsdelivr.net/npm/@fullcalendar/core@4.3.1/main.min.css', [], null );
		wp_register_style( 'fullcalendar-day', '//cdn.jsdelivr.net/npm/@fullcalendar/daygrid@4.3.0/main.min.css', [ 'fullcalendar-core' ], null );
		wp_register_style( 'fullcalendar-week', '//cdn.jsdelivr.net/npm/@fullcalendar/timegrid@4.3.0/main.min.css', [ 'fullcalendar-core' ], null );
		wp_register_style( 'jstree', '//cdnjs.cloudflare.com/ajax/libs/jstree/3.3.8/themes/default/style.min.css', [], null );
		wp_register_style( 'kleistad', plugin_dir_url( __FILE__ ) . "css/public$dev.css", [], $this->version );

		wp_register_script( 'datatables', '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js', [ 'jquery' ], null, false );
		wp_register_script( 'fullcalendar-core', '//cdn.jsdelivr.net/npm/@fullcalendar/core@4.3.1/main.min.js', [], null, false );
		wp_register_script( 'fullcalendar-nl', '//cdn.jsdelivr.net/npm/@fullcalendar/core@4.3.0/locales/nl.min.js', [ 'fullcalendar-core' ], null, false );
		wp_register_script( 'fullcalendar-day', '//cdn.jsdelivr.net/npm/@fullcalendar/daygrid@4.3.0/main.min.js', [ 'fullcalendar-core' ], null, false );
		wp_register_script( 'fullcalendar-week', '//cdn.jsdelivr.net/npm/@fullcalendar/timegrid@4.3.0/main.min.js', [ 'fullcalendar-core' ], null, false );
		wp_register_script( 'jstree', '//cdnjs.cloudflare.com/ajax/libs/jstree/3.3.8/jstree.min.js', [ 'jquery' ], null, false );
		wp_register_script( 'kleistad', plugin_dir_url( __FILE__ ) . "js/public$dev.js", [ 'jquery', 'jquery-ui-dialog' ], $this->version, true );

		foreach ( self::SHORTCODES as $shortcode => $dependencies ) {
			$file = str_replace( '_', '-', $shortcode );
			wp_register_script( "kleistad$shortcode", plugin_dir_url( __FILE__ ) . "js/public-$file$dev.js", $dependencies['js'], $this->version, false );
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
		\Kleistad\Recept::create_type();
		\Kleistad\WorkshopAanvraag::create_type();
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
		static $divs      = false; // De ondersteunende divs zijn maar eenmalig nodig.
		$shortcode        = substr( $tag, strlen( 'kleistad-' ) );
		$shortcode_class  = '\Kleistad\Public_' . ucwords( $shortcode, '_' );
		$shortcode_object = new $shortcode_class( $shortcode, $atts, $this->options, self::SHORTCODES[ $shortcode ]['access'] );
		$html             = '';
		if ( ! $divs ) {
			$html .= '<div id="kleistad_berichten" ></div><div id="kleistad_bevestigen" ></div><div id="kleistad_wachten" ></div>';
			$divs  = true;
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
	 * Update abonnement batch job.
	 *
	 * @param int    $id    De id van de abonnee.
	 * @param string $actie De uit te voeren actie.
	 * @param int    $datum Datum waarop het moet worden uitgevoerd.
	 */
	public function update_abonnement( $id, $actie, $datum ) {
		$abonnement = new \Kleistad\Abonnement( $id );
		$abonnement->event( $actie, $datum );
	}

	/**
	 * Update workshop batch job.
	 *
	 * @param int    $id    De id van de workshop.
	 * @param string $actie De uit te voeren actie.
	 */
	public function update_workshop( $id, $actie ) {
		$workshop = new \Kleistad\Workshop( $id );
		$workshop->event( $actie );
	}

	/**
	 * Update ovenkosten batch job
	 */
	public function update_ovenkosten() {
		set_time_limit( 300 ); // Voorkom dat deze job er door een execution time out crasht, dus 300 sec = 5 minuten.
		\Kleistad\Saldo::meld_en_verwerk();
	}

	/**
	 * Ontvang en verwerk email
	 */
	public function rcv_email() {
		\Kleistad\WorkshopAanvraag::ontvang_en_verwerk();
	}

	/**
	 * Doe de dagelijkse cleanup
	 */
	public function daily_cleanup() {
		\Kleistad\Shortcode::cleanup_downloads();
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
