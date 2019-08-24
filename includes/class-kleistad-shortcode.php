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
	 * De access level.
	 *
	 * @var array nivo van toegang
	 */
	protected $access;

	/**
	 * Controleer toegang tot deze shortcode.
	 *
	 * @since 5.7.2
	 *
	 * @param array $access Array met Kleistad rollen die toegang hebben.
	 * @return bool Of er toegang is.
	 */
	protected static function check_access( $access ) {
		if ( ! empty( $access ) ) {
			$gebruiker = wp_get_current_user();
			$match     = array_intersect( $access, (array) $gebruiker->roles );
			return 0 !== count( $match );
		}
		return true;
	}

	/**
	 * Maak de uit te voeren html aan
	 *
	 * @since 4.5.1
	 *
	 * @param  array $data de uit te wisselen data.
	 * @return string html tekst.
	 */
	protected function display( &$data = null ) {
		if ( ! self::check_access( $this->access ) ) {
			$error = new WP_Error();
			$error->add( 'toegang', 'Je hebt geen toegang tot deze functie' );
			return ( self::status( $error ) );
		}
		$result = $this->prepare( $data );
		if ( is_wp_error( $result ) ) {
			return self::status( $result );
		}
		ob_start();
		require plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/kleistad-public-' . str_replace( '_', '-', $this->shortcode ) . '.php';
		return ob_get_clean();
	}

	/**
	 * Toon de status van de het resultaat
	 *
	 * @since 5.7.0
	 *
	 * @param string | array | WP_Error $result Het resultaat dat getoond moet worden.
	 */
	public static function status( $result ) {
		$html = '';
		if ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $error ) {
				$html .= '<div class="kleistad_fout"><p>' . $error . '</p></div>';
			}
		} else {
			$succes = $result['status'] ?? ( is_string( $result ) ? $result : '' );
			if ( ! empty( $succes ) ) {
				$html = '<div class="kleistad_succes"><p>' . $succes . '</p></div>';
			}
		}
		return $html;
	}

	/**
	 * Toon een OK button in het midden van het scherm
	 *
	 * @since 5.7.0
	 * @return string
	 */
	public static function goto_home() {
		ob_start();
		?>
		</br></br>
		<div style="text-align:center;" >
			<button onclick="location.href='<?php echo esc_url( home_url() ); ?>';" >
				&nbsp;OK&nbsp;
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * De constructor
	 *
	 * @since   4.0.87
	 *
	 * @param string $shortcode   Shortcode (zonder kleistad- ).
	 * @param array  $atts        Shortcode parameters.
	 * @param array  $options     Plugin opties.
	 * @param array  $access      Roles voor shortcode toegang.
	 *
	 * @throws Exception          Foutmelding ingeval de shortcode meerdere keren op de pagina voorkomt.
	 */
	public function __construct( $shortcode, $atts, $options, $access = [] ) {
		static $active_shortcodeforms = [];
		try {
			if ( in_array( $shortcode, $active_shortcodeforms, true ) ) {
				throw new Exception( "Pagina bevat meer dan een identieke $shortcode aanroep" );
			} else {
				$active_shortcodeforms[] = $shortcode;
				$this->atts              = $atts;
				$this->options           = $options;
				$this->shortcode         = $shortcode;
				$this->access            = $access;
			}
		} catch ( Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore
		}
	}

	/**
	 * Toon de uitvoer van de shortcode, eventueel voorafgegaan door een melding van een betaalactie.
	 *
	 * @since 4.5.1
	 */
	public function run() {
		return apply_filters( 'kleistad_display', $this->display() );
	}

	/**
	 * Abstract definitie van de prepare functie
	 *
	 * @since   4.0.87
	 *
	 * @param array $data de data die voorbereid moet worden voor display.
	 * @return \WP_ERROR|bool
	 */
	abstract protected function prepare( &$data);
}
