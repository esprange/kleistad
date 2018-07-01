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
	 * De constructor
	 *
	 * @since   4.0.87
	 * @param string $plugin_name plugin naam.
	 * @param array  $atts        shortcode parameters.
	 * @param array  $options     plugin opties.
	 */
	public function __construct( $plugin_name, $atts, $options ) {
		$this->plugin_name = $plugin_name;
		$this->atts        = $atts;
		$this->options     = $options;
		wp_localize_jquery_ui_datepicker();
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
