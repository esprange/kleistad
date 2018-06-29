<?php
/**
 * Registreer alle actions en filters van de plugin
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Houdt een lijst bij van alle hooks binnen de plugin en registreer deze via de WordPress API.
 * Roep de run function aan om de lijst van actions en filters uit te voeren.
 */
class Kleistad_Loader {

	/**
	 * Het array van plugin actions.
	 *
	 * @since    4.0.87
	 * @access   protected
	 * @var      array    $actions    De geregistreerde acties.
	 */
	protected $actions;

	/**
	 * Het array van plugin filters.
	 *
	 * @since    4.0.87
	 * @access   protected
	 * @var      array    $filters    De geregistreerde filters.
	 */
	protected $filters;

	/**
	 * Het array van plugin shortcodes.
	 *
	 * @since    4.0.87
	 * @access   protected
	 * @var      array    $shortcodes  De geregistreerde shortcodes.
	 */
	protected $shortcodes;

	/**
	 * De constructor, initializeer de collecties.
	 *
	 * @since    4.0.87
	 */
	public function __construct() {

		$this->actions    = [];
		$this->filters    = [];
		$this->shortcodes = [];
	}

	/**
	 * Voeg een actie toe aan de collectie.
	 *
	 * @since    4.0.87
	 * @param    string $hook             De actie naam.
	 * @param    object $component        De class naam waar de actie gedefineerd is.
	 * @param    string $callback         De naam van de functie.
	 * @param    int    $priority         Optioneel. De prioriteit. Default is 10.
	 * @param    int    $accepted_args    Optioneel. Het aantal argumenten dat door wordt gegeven aan de callback. Default is 1.
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Voeg een filter toe aan de collectie.
	 *
	 * @since    4.0.87
	 * @param    string $hook             De filter naam.
	 * @param    object $component        De class naam waar het filter gedefinieerd is.
	 * @param    string $callback         De naam van de functie.
	 * @param    int    $priority         Optioneel. De prioriteit. Default is 10.
	 * @param    int    $accepted_args    Optioneel. Het aantal argumenten dat door wordt gegeven aan de callback. Default is 1.
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Voeg een shortcode toe aan de collectie.
	 *
	 * @since     4.0.87
	 * @param     string $tag            De naam van de shortcode.
	 * @param     object $component      De class naam waar de shortcode gedefinieerd is.
	 * @param     string $callback       De naam van de functie.
	 */
	public function add_shortcode( $tag, $component, $callback ) {
		$this->shortcodes = $this->add( $this->shortcodes, $tag, $component, $callback, 0, 0 );
	}
	/**
	 * Een hulp functie voor de registratie van acties, filters en shortcodes.
	 *
	 * @since    4.0.87
	 * @access   private
	 * @param    array  $hooks            De collectie hooks die geregistreerd moet worden.
	 * @param    string $hook             De naam van de te registeren hook.
	 * @param    object $component        De class naam waar de hook geregistreerd wordt.
	 * @param    string $callback         De functie.
	 * @param    int    $priority         De prioriteit.
	 * @param    int    $accepted_args    Het aantal argumenten.
	 * @return   array                    De collectie.
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {

		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}
	/**
	 * Registreer de filters, actions en shortcodes in WordPress.
	 *
	 * @since    4.0.87
	 */
	public function run() {

		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->shortcodes as $hook ) {
			add_shortcode( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
		}
	}

}
