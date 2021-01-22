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

use WP_Error;

/**
 * De kleistad class voor de publieke pagina's.
 */
class Public_Shortcode_Handler {

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
		$shortcodes = new Shortcodes();
		foreach ( array_keys( $shortcodes->definities ) as $tag ) {
			add_shortcode( "kleistad_$tag", [ $this, 'handler' ] );
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
	 * @suppressWarnings(PHPMD.UnusedFormalParameter)
	 * @suppressWarnings(PHPMD.StaticAccess)
	 */
	public function handler( $atts, $content, $tag ) {
		$shortcode        = substr( $tag, strlen( 'kleistad-' ) );
		$shortcode_object = Shortcode::get_instance( $shortcode, $atts, $this->options );
		if ( is_null( $shortcode_object ) ) {
			return '';
		}
		$shortcodes = new ShortCodes();
		if ( ! $shortcodes->check_access( $shortcode ) ) {
			return $shortcode_object->status( new WP_Error( 'toegang', 'Je hebt geen toegang tot deze functie' ) );
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
		$shortcodes = new Shortcodes();
		$access     = $shortcodes->definities[ $shortcode ]->access;
		if ( ! empty( $access ) ) {
			$gebruiker = wp_get_current_user();
			return $gebruiker->ID && 0 !== count( array_intersect( $access, (array) $gebruiker->roles ) );
		}
		return true;
	}

}
