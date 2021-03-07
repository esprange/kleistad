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

namespace Kleistad;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Exception;

/**
 * De abstract class voor shortcodes
 */
abstract class Shortcode {

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
	 * File handle voor download bestanden
	 *
	 * @var resource de file pointer.
	 */
	protected $file_handle;

	/**
	 * Lijst van actieve shortcodes.
	 *
	 * @var array de lijst.
	 */
	private static $shortcode_lijst = [];

	/**
	 * Abstract definitie van de prepare functie
	 *
	 * @since   4.0.87
	 *
	 * @param array $data de data die voorbereid moet worden voor display.
	 * @return WP_Error|bool
	 */
	abstract protected function prepare( &$data);

	/**
	 * Enqueue the scripts and styles for the shortcode.
	 */
	protected function enqueue() {
		$shortcodes = new Shortcodes();
		foreach ( $shortcodes->definities[ $this->shortcode ]->css as $style ) {
			wp_enqueue_style( $style );
		}
		if ( ! wp_style_is( 'kleistad' ) ) {
			wp_enqueue_style( 'kleistad' );
		}

		if ( ! wp_script_is( 'kleistad' ) ) {
			wp_enqueue_script( 'kleistad' );
			wp_localize_script(
				'kleistad',
				'kleistadData',
				[
					'nonce'           => wp_create_nonce( 'wp_rest' ),
					'success_message' => 'de bewerking is geslaagd!',
					'error_message'   => 'het was niet mogelijk om de bewerking uit te voeren',
					'base_url'        => base_url(),
					'admin_url'       => admin_url( 'admin-ajax.php' ),
				]
			);
		}
		if ( wp_script_is( "kleistad{$this->shortcode}", 'registered' ) ) {
			wp_enqueue_script( "kleistad{$this->shortcode}" );
			return;
		}
		foreach ( $shortcodes->definities[ $this->shortcode ]->js as $script ) {
			wp_enqueue_script( $script );
		}
	}

	/**
	 * Maak de uit te voeren html aan
	 *
	 * @since 4.5.1
	 *
	 * @param  array $data de uit te wisselen data.
	 * @return string html tekst.
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function display( &$data = [ 'actie' => '-' ] ) {
		$this->enqueue();
		$html = apply_filters( 'kleistad_melding', '' );
		try {
			$betalen       = new Betalen();
			$betaal_result = $betalen->controleer();
			if ( is_string( $betaal_result ) ) { // Er is een succesvolle betaling, toon het bericht.
				return $this->status( $betaal_result ) . $this->goto_home();
			}
			$result = $this->prepare( $data );
			if ( is_wp_error( $result ) ) {
				$html = $this->status( $result );
			} else {
				ob_start();
				require plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/public-' . str_replace( '_', '-', $this->shortcode ) . '.php';
				$html = ob_get_clean();
			}
			if ( is_wp_error( $betaal_result ) ) { // Er is een betaling maar niet succesvol.
				return $this->status( $betaal_result ) . $html;
			}
		} catch ( Kleistad_Exception $exceptie ) {
			$html = $this->status( new WP_Error( 'exceptie', $exceptie->getMessage() ) );
		} catch ( Exception $exceptie ) {
			error_log( $exceptie->getMessage() ); //phpcs:ignore
			$html = $this->status( new WP_Error( 'exceptie', 'Er is een onbekende fout opgetreden' ) );
		}
		$betalen       = new Betalen();
		$betaal_result = $betalen->controleer();
		if ( is_string( $betaal_result ) ) { // Er is een succesvolle betaling, toon het bericht.
			return $this->status( $betaal_result ) . $this->goto_home();
		} elseif ( is_wp_error( $betaal_result ) ) { // Er is een betaling maar niet succesvol.
			return $this->status( $betaal_result ) . $html;
		}
		return $html; // Er is geen betaling, toon de reguliere inhoud van de shortcode.
	}

	/**
	 * Toon de status van de het resultaat
	 *
	 * @since 5.7.0
	 *
	 * @param string | array | WP_Error $result Het resultaat dat getoond moet worden.
	 */
	public function status( $result ) {
		$html = '';
		if ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $error ) {
				$html .= melding( 0, $error );
			}
			return $html;
		}
		$succes = $result['status'] ?? ( is_string( $result ) ? $result : '' );
		if ( ! empty( $succes ) ) {
			$html = melding( 1, $succes );
		}
		return $html;
	}

	/**
	 * Toon een OK button in het midden van het scherm
	 *
	 * @since 5.7.0
	 * @return string
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	public function goto_home() {
		if ( ! is_user_logged_in() ) {
			$url = home_url();
		} elseif ( current_user_can( BESTUUR ) ) {
			$url = home_url( '/bestuur/' );
		} else {
			$url = home_url( '/leden/' );
		}
		ob_start();
		?>
		<br/><br/>
		<div style="text-align:center;" >
			<button type="button" onclick="location.href='<?php echo esc_url( $url ); ?>';" >
				&nbsp;OK&nbsp;
			</button>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Singleton handler
	 *
	 * @param string       $shortcode_tag Shortcode (zonder kleistad- ).
	 * @param array|string $atts          Shortcode parameters.
	 * @param array        $options       Plugin opties.
	 * @return Shortcode | null
	 */
	public static function get_instance( string $shortcode_tag, $atts, array $options ) : ?Shortcode {
		if ( in_array( $shortcode_tag, self::$shortcode_lijst, true ) ) {
			return null;
		}
		self::$shortcode_lijst[] = $shortcode_tag;
		$shortcode_class         = '\\' . __NAMESPACE__ . '\\Public_' . ucwords( $shortcode_tag, '_' );
		return new $shortcode_class( $shortcode_tag, $atts, $options );
	}

	/**
	 * De constructor
	 *
	 * @since   4.0.87
	 *
	 * @param string $shortcode   Shortcode (zonder kleistad- ).
	 * @param array  $atts        Shortcode parameters.
	 * @param array  $options     Plugin opties.
	 */
	private function __construct( $shortcode, $atts, $options ) {
		$this->atts      = $atts;
		$this->options   = $options;
		$this->shortcode = $shortcode;
	}

	/**
	 * Register rest URI's.
	 *
	 * @since 5.7.0
	 */
	public static function register_rest_routes() {
		register_rest_route(
			KLEISTAD_API,
			'/getitem', // /(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_getitem' ],
				'permission_callback' => function( WP_REST_Request $request ) {
					$shortcode  = $request->get_param( 'tag' );
					$shortcodes = new ShortCodes();
					return $shortcodes->check_access( $shortcode );
				},
			]
		);
		register_rest_route(
			KLEISTAD_API,
			'/getitems',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_getitem' ],
				'permission_callback' => function( WP_REST_Request $request ) {
					$shortcode  = $request->get_param( 'tag' );
					$shortcodes = new ShortCodes();
					return $shortcodes->check_access( $shortcode );
				},
			]
		);
		register_rest_route(
			KLEISTAD_API,
			'/download',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_download' ],
				'permission_callback' => function( WP_REST_Request $request ) {
					$shortcode  = $request->get_param( 'tag' );
					$shortcodes = new ShortCodes();
					return $shortcodes->check_access( $shortcode );
				},
			]
		);
	}

	/**
	 * Helper functie, geef het object terug of een foutboodschap.
	 *
	 * @param WP_REST_Request $request De informatie vanuit de client of het weer te geven item.
	 * @return Shortcode | null  De response of false.
	 */
	protected static function get_shortcode( WP_REST_Request $request ) : ?Shortcode {
		$tag   = $request->get_param( 'tag' ) ?? '';
		$class = '\\' . __NAMESPACE__ . '\\Public_' . ucwords( $tag, '_' );
		if ( class_exists( $class ) ) {
			$atts = json_decode( $request->get_param( 'atts' ) ?? '', true );
			return new $class( $tag, $atts, opties() );
		}
		return null;
	}

	/**
	 * Get an item and display it.
	 *
	 * @param WP_REST_Request $request De informatie vanuit de client of het weer te geven item.
	 * @return WP_REST_Response De response.
	 * @throws Exception Onbekend object.
	 */
	public static function callback_getitem( WP_REST_Request $request ) : WP_REST_Response {
		try {
			$shortcode = self::get_shortcode( $request );
			if ( ! is_a( $shortcode, __CLASS__ ) ) {
				throw new Exception( 'callback_formsubmit voor onbekend object' );
			}
			$data = [
				'actie' => sanitize_text_field( $request->get_param( 'actie' ) ),
				'id'    => is_numeric( $request->get_param( 'id' ) ) ? absint( $request->get_param( 'id' ) ) : sanitize_text_field( $request->get_param( 'id' ) ),
			];
			return new WP_REST_Response( [ 'content' => $shortcode->display( $data ) ] );
		} catch ( Kleistad_Exception $exceptie ) {
			return new WP_REST_Response( [ 'status'->$shortcode->status( new WP_Error( $exceptie->getMessage() ) ) ] );
		} catch ( Exception $exceptie ) {
			error_log( $exceptie->GetMessage() ); // phpcs:ignore
			return new WP_REST_Response( [ 'status'->$shortcode->status( new WP_Error( 'Er is een onbekende fout opgetreden' ) ) ] );
		}
	}

	/**
	 * Maak een tijdelijk bestand aan voor download.
	 *
	 * @param Shortcode $shortcode De shortcode waarvoor de download plaatsvindt.
	 * @param string    $functie   De shortcode functie die aangeroepen moet worden.
	 * @return array
	 */
	protected static function download( Shortcode $shortcode, $functie ) {
		$upload_dir = wp_upload_dir();
		$file       = '/kleistad_tmp_' . uniqid() . '.csv';
		$result     = fopen( $upload_dir['basedir'] . $file, 'w' );
		if ( false !== $result ) {
			$shortcode->file_handle = $result;
			fwrite( $shortcode->file_handle, "\xEF\xBB\xBF" );
			$result = call_user_func( [ $shortcode, $functie ] );
			fclose( $shortcode->file_handle );
			if ( empty( $result ) ) {
				return [ 'file_uri' => $upload_dir['baseurl'] . $file ];
			}
			unlink( $upload_dir['basedir'] . $file );
			return [ 'file_uri' => $result ];
		}
		return [
			'status'  => $shortcode->status( new WP_Error( 'intern', 'bestand kon niet aangemaakt worden' ) ),
			'content' => $shortcode->goto_home(),
		];
	}

	/**
	 * Ruim eventuele download files op.
	 */
	public static function cleanup_downloads() {
		$upload_dir = wp_upload_dir();
		$files      = glob( $upload_dir['basedir'] . '/kleistad_tmp_*.csv' );
		$now        = time();

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				if ( $now - filemtime( $file ) >= DAY_IN_SECONDS ) {
					unlink( $file );
				}
			}
		}
	}

	/**
	 * Get an item and display it.
	 *
	 * @param WP_REST_Request $request De informatie vanuit de client of het weer te geven item.
	 * @return WP_REST_Response de response.
	 * @throws Exception Onbekend object.
	 */
	public static function callback_download( WP_REST_Request $request ) : WP_REST_Response {
		try {
			$shortcode = self::get_shortcode( $request );
			if ( ! is_a( $shortcode, __CLASS__ ) ) {
				throw new Exception( 'callback_formsubmit voor onbekend object' );
			}
			return new WP_REST_Response( self::download( $shortcode, $request->get_param( 'actie' ) ) );
		} catch ( Kleistad_Exception $exceptie ) {
			return new WP_REST_Response( [ 'status'->$shortcode->status( new WP_Error( $exceptie->getMessage() ) ) ] );
		} catch ( Exception $exceptie ) {
			error_log( $exceptie->GetMessage() ); // phpcs:ignore
			return new WP_REST_Response( [ 'status'->$shortcode->status( new WP_Error( 'Er is een onbekende fout opgetreden' ) ) ] );
		}
	}

	/**
	 * Toon de uitvoer van de shortcode, eventueel voorafgegaan door een melding van een betaal status.
	 *
	 * @since 4.5.1
	 */
	public function run() {
		return $this->display();
	}

}
