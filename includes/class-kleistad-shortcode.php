<?php
/**
 * The abstract class for shortcodes.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The abstract class for shortcodes
 */
abstract class Kleistad_Shortcode {
	/**
	 * The plugin name.
	 *
	 * @var string  plugin_name
	 */
	protected $plugin_name;

	/**
	 * The parameters used to call the shortcode.
	 *
	 * @var array shortcode parameters
	 */
	protected $atts;

	/**
	 * The plugin options.
	 *
	 * @var array plugin options
	 */
	protected $options;

	/**
	 * The constructor
	 *
	 * @since   4.0.87
	 * @param string $plugin_name plugin naam.
	 * @param array  $atts shortcode parameters.
	 */
	public function __construct( $plugin_name, $atts ) {
		$this->plugin_name = $plugin_name;
		$this->atts        = $atts;
		$this->options     = get_option( 'kleistad-opties' );
		wp_localize_jquery_ui_datepicker();
	}

	/**
	 * Abstract definition of prepare function
	 *
	 * @since   4.0.87
	 *
	 * @param array $data the data to prepare.
	 */
	abstract public function prepare( &$data);

	/**
	 * Validate function, only used in case of forms
	 *
	 * @since   4.0.87
	 * @param array $data the data validated.
	 */
	public function validate( &$data ) {
		return true;
	}

	/**
	 * Save function, only used in case of forms
	 *
	 * @since   4.0.87
	 * @param array $data the data to store.
	 */
	public function save( $data ) {
		return true;
	}
}
