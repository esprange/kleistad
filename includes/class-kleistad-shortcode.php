<?php
/**
 * De  abstracte class voor shortcodes.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * De abstract class voor shortcodes
 */
abstract class Kleistad_Shortcode {
	/**
	 * De plugin naam.
	 *
	 * @var string  plugin_naam
	 */
	protected $plugin_name;

	/**
	 * De shortcode.
	 *
	 * @var string shortcode (zonder kleistad-)
	 */
	protected $shortcode;

	/**
	 * De parameters welke gebruikt worden in de aanroep van de shortcode.
	 *
	 * @var array shortcode parameters
	 */
	protected $atts;

	/**
	 * De plugin options.
	 *
	 * @var array plugin options
	 */
	protected $options;

	/**
	 * Maak de uit te voeren html aan
	 *
	 * @since 4.5.1
	 *
	 * @param  array $data de uit te wisselen data.
	 * @return string html tekst.
	 */
	protected function display( &$data = null ) {
		$result = $this->prepare( $data );
		if ( is_wp_error( $result ) ) {
			return '<div class="kleistad_fout"><p>' . $result->get_error_message() . '</p></div>';
		}
		ob_start();
		require plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/kleistad-public-' . str_replace( '_', '-', $this->shortcode ) . '.php';
		$html = ob_get_contents();
		ob_clean();
		return $html;
	}

	/**
	 * De constructor
	 *
	 * @since   4.0.87
	 * @param string $plugin_name plugin naam.
	 * @param string $shortcode   shortcode (zonder kleistad- ).
	 * @param array  $atts        shortcode parameters.
	 * @param array  $options     plugin opties.
	 */
	public function __construct( $plugin_name, $shortcode, $atts, $options ) {
		$this->plugin_name = $plugin_name;
		$this->atts        = $atts;
		$this->options     = $options;
		$this->shortcode   = $shortcode;
		wp_localize_jquery_ui_datepicker();
	}

	/**
	 * Voer het rapport van de shortcode uit.
	 *
	 * @since 4.5.1
	 */
	public function run() {
		return $this->display();
	}

	/**
	 * Abstract definitie van de prepare functie
	 *
	 * @since   4.0.87
	 *
	 * @param array $data de data die voorbereid moet worden voor display.
	 * @return \WP_ERROR|bool
	 */
	abstract public function prepare( &$data);
}
