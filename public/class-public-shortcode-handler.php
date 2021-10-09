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
	 * @param array|string $atts meegegeven params van de shortcode of een lege string.
	 * @param string       $content    wordt niet gebruikt.
	 * @param string       $tag        wordt gebruikt als selector voor de diverse functie aanroepen.
	 *
	 * @return string html resultaat.
	 * @suppressWarnings(PHPMD.UnusedFormalParameter)
	 * @suppressWarnings(PHPMD.StaticAccess)
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function handler( $atts, string $content, string $tag ) : string {
		$shortcode_tag = substr( $tag, strlen( 'kleistad-' ) );
		$attributes    = is_string( $atts ) ? [ $atts ] : $atts;
		try {
			$shortcode = Shortcode::get_instance( $shortcode_tag, $attributes );
		} catch ( Kleistad_Exception $exceptie ) {
			fout( __CLASS__, $exceptie->getMessage() );
			return '';
		}
		$shortcodes = new ShortCodes();
		if ( ! $shortcodes->check_access( $shortcode_tag ) ) {
			return $shortcode->status( new WP_Error( 'toegang', 'Je hebt geen toegang tot deze functie' ) );
		}
		$html        = '';
		static $divs = false; // De ondersteunende divs zijn maar eenmalig nodig.
		if ( ! $divs ) {
			$divs  = true;
			$html .= '<div class="kleistad" id="kleistad_berichten" ></div><div class="kleistad" id="kleistad_bevestigen" ></div><div id="kleistad_wachten" ></div>';
		}
		$html .= '<div class="kleistad kleistad-shortcode" data-tag="' . $shortcode_tag . '" ';
		if ( ! empty( $atts ) ) {
			$json_atts = wp_json_encode( $atts, JSON_HEX_QUOT | JSON_HEX_TAG );
			$html     .= ' data-atts=' . "'$json_atts'";
		}
		$html .= ' >' . $shortcode->run() . '</div>';
		return $html;
	}

}
