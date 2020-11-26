<?php
/**
 * Definitie van de publieke class van de shortcode handler.
 *
 * @link       https://www.kleistad.nl
 * @since      6.4.2
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad class voor de publieke pagina's.
 */
class Public_Shortcode_Handler {

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
			'access' => [ Roles::LID ],
		],
		'abonnement_overzicht'  => [
			'script' => false,
			'js'     => [ 'jquery', 'datatables' ],
			'css'    => [ 'datatables' ],
			'access' => [ Roles::BESTUUR ],
		],
		'betaling'              => [
			'script' => true,
			'js'     => [ 'jquery', 'datatables' ],
			'css'    => [ 'datatables' ],
			'access' => [],
		],
		'contact'               => [
			'script' => false,
			'js'     => [],
			'css'    => [],
			'access' => [],
		],
		'corona'                => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-button', 'jquery-ui-dialog', 'jquery-ui-datepicker', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
			'access' => [ Roles::DOCENT, Roles::LID, Roles::BESTUUR ],
		],
		'cursus_beheer'         => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-spinner', 'jquery-ui-datepicker', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
			'access' => [ Roles::BESTUUR ],
		],
		'cursus_extra'          => [
			'script' => true,
			'js'     => [ 'jquery' ],
			'css'    => [],
			'access' => [],
		],
		'cursus_inschrijving'   => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-selectmenu', 'jquery-ui-spinner', 'jquery-ui-tooltip' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [],
		],
		'cursus_overzicht'      => [
			'script' => false,
			'js'     => [ 'jquery', 'jquery-ui-dialog', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
			'access' => [ Roles::DOCENT, Roles::BESTUUR ],
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
			'access' => [ Roles::BOEKHOUD ],
		],
		'email'                 => [
			'script' => true,
			'js'     => [ 'jquery', 'jstree' ],
			'css'    => [ 'jquery-ui', 'jstree' ],
			'access' => [ Roles::DOCENT, Roles::BESTUUR ],
		],
		'kalender'              => [
			'script' => true,
			'js'     => [ 'jquery', 'fullcalendar' ],
			'css'    => [ 'fullcalendar' ],
			'access' => [ Roles::DOCENT, Roles::LID, Roles::BESTUUR ],
		],
		'omzet_rapportage'      => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-datepicker', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables' ],
			'access' => [ Roles::BESTUUR ],
		],
		'rapport'               => [
			'script' => false,
			'js'     => [ 'jquery', 'datatables' ],
			'css'    => [ 'datatables' ],
			'access' => [ Roles::DOCENT, Roles::LID, Roles::BESTUUR ],
		],
		'recept_beheer'         => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-autocomplete', 'datatables' ],
			'css'    => [ 'jquery-ui', 'datatables', 'dashicons' ],
			'access' => [ Roles::DOCENT, Roles::LID, Roles::BESTUUR ],
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
			'access' => [ Roles::BESTUUR ],
		],
		'registratie'           => [
			'script' => true,
			'js'     => [ 'jquery', 'password-strength-meter' ],
			'css'    => [],
			'access' => [ Roles::DOCENT, Roles::LID, Roles::BESTUUR ],
		],
		'reservering'           => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-dialog' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [ Roles::DOCENT, Roles::LID, Roles::BESTUUR ],
		],
		'saldo_overzicht'       => [
			'script' => false,
			'js'     => [ 'jquery', 'datatables' ],
			'css'    => [ 'datatables' ],
			'access' => [ Roles::BESTUUR ],
		],
		'saldo'                 => [
			'script' => true,
			'js'     => [ 'jquery' ],
			'css'    => [],
			'access' => [ Roles::DOCENT, Roles::LID, Roles::BESTUUR ],
		],
		'stookbestand'          => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-datepicker' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [ Roles::BESTUUR ],
		],
		'verkoop'               => [
			'script' => true,
			'js'     => [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-tabs' ],
			'css'    => [ 'jquery-ui' ],
			'access' => [ Roles::BESTUUR ],
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
			'access' => [ Roles::BESTUUR ],
		],
	];

	/**
	 * De kleistad plugin opties.
	 *
	 * @var array kleistad plugin settings
	 */
	private $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    6.4.2
	 *
	 * @param array $options       De plugin options.
	 */
	public function __construct( $options ) {
		$this->options = $options;
	}

	/**
	 * Voeg de shortcodes toe.
	 */
	public function register() {
		foreach ( array_keys( self::SHORTCODES ) as $shortcode ) {
			add_shortcode( "kleistad_$shortcode", [ $this, 'handler' ] );
		}
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
	public function handler( $atts, $content, $tag ) {
		$shortcode        = substr( $tag, strlen( 'kleistad-' ) );
		$shortcode_object = Shortcode::get_instance( $shortcode, $atts, $this->options );
		if ( is_null( $shortcode_object ) ) {
			return '';
		}
		if ( ! self::check_access( $shortcode ) ) {
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
	 * Controleer toegang tot deze shortcode.
	 *
	 * @since 5.7.2
	 *
	 * @param string $shortcode De shortcode.
	 * @return bool Of er toegang is.
	 */
	public static function check_access( $shortcode ) {
		$access = self::SHORTCODES[ $shortcode ]['access'];
		if ( ! empty( $access ) ) {
			$gebruiker = wp_get_current_user();
			return $gebruiker->ID && 0 !== count( array_intersect( $access, (array) $gebruiker->roles ) );
		}
		return true;
	}

}
