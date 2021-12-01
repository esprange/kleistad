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
use ReflectionClass;

/**
 * De abstract class voor shortcodes
 */
abstract class Shortcode {

	const STANDAARD_ACTIE = 'overzicht';

	/**
	 * De shortcode.
	 *
	 * @var string shortcode (zonder kleistad-)
	 */
	protected string $shortcode;

	/**
	 * De parameters welke gebruikt worden in de aanroep van de shortcode.
	 *
	 * @var array shortcode parameters
	 */
	private array $atts = [];

	/**
	 * De data die gebruikt wordt voor display.
	 *
	 * @var array shortcode data
	 */
	protected array $data = [];

	/**
	 * Controle array om te voorkomen dat shortcodes meerdere keren voorkomen.
	 *
	 * @var array shortcode tags
	 */
	protected static $tags = [];

	/**
	 * Actie welke bepaald welke informatie getoond moet worden.
	 *
	 * @var string $display_actie De uit te voeren actie.
	 */
	protected string $display_actie = '';

	/**
	 * File download resource.
	 *
	 * @var resource $filehandle File handle voor output.
	 */
	protected $filehandle;

	/**
	 * Enqueue the scripts and styles for the shortcode.
	 */
	protected function enqueue() {
		$reflect = new ReflectionClass( $this );
		$script  = 'kleistad-' . substr( strtolower( $reflect->getShortName() ), strlen( 'public-' ) );
		if ( wp_script_is( $script, 'registered' ) ) {
			wp_enqueue_script( $script );
		}
		if ( ! wp_script_is( 'kleistad' ) ) {
			wp_enqueue_script( 'kleistad' );
		}
	}

	/**
	 * Basis prepare functie om te bepalen wat er getoond moet worden.
	 * Als er geen acties zijn, dan kan de prepare functie overschreven worden.
	 *
	 * @return string
	 */
	protected function prepare() : string {
		$method = 'prepare_' . $this->display_actie;
		if ( method_exists( $this, $method ) ) {
			return $this->$method();
		}
		fout( __CLASS__, "method $method ontbreekt" );
		return $this->status( new WP_Error( 'intern', 'Er is een onbekende fout opgetreden' ) );
	}

	/**
	 * Maak de uit te voeren html aan
	 *
	 * @since 4.5.1
	 *
	 * @return string html tekst.
	 */
	protected function display() : string {
		$this->enqueue();
		$this->bepaal_actie();
		try {
			$ontvangen     = new Ontvangen();
			$betaal_result = $ontvangen->controleer();
			if ( is_string( $betaal_result ) ) { // Er is een succesvolle betaling, toon het bericht.
				return $this->status( $betaal_result ) . $this->goto_home();
			}
			if ( is_wp_error( $betaal_result ) ) { // Er is een betaling maar niet succesvol.
				return $this->status( $betaal_result ) . $this->prepare();
			}
			return $this->prepare(); // Er is geen betaling, toon de reguliere inhoud van de shortcode.
		} catch ( Exception $exceptie ) {
			fout( __CLASS__, $exceptie->getMessage() );
			return $this->status( new WP_Error( 'exceptie', 'Er is een onbekende fout opgetreden' ) );
		}
	}

	/**
	 * Toon de status van de het resultaat
	 *
	 * @since 5.7.0
	 *
	 * @param string | array | WP_Error $result Het resultaat dat getoond moet worden.
	 * @return string Html tekst.
	 */
	public function status( $result ) : string {
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
	 */
	public function goto_home() : string {
		$html_objectclass = get_class( $this ) . '_Display';
		if ( ! class_exists( $html_objectclass ) ) {
			fout( __CLASS__, "Display object $html_objectclass niet aanwezig" );
			return '';
		}
		$dummy   = [];
		$display = new $html_objectclass( $dummy, '' );
		ob_start();
		$display->home();
		return ob_get_clean();
	}

	/**
	 * Singleton handler
	 *
	 * @param string $shortcode_tag Shortcode (zonder kleistad- ).
	 * @param array  $attributes    Shortcode parameters.
	 * @return Shortcode | null
	 * @throws Kleistad_Exception Als er de shortcode meer dat eens op de pagina voorkomt.
	 */
	public static function get_instance( string $shortcode_tag, array $attributes ) : ?Shortcode {
		if ( in_array( $shortcode_tag, self::$tags, true ) && ! is_admin() ) {
			throw new Kleistad_Exception( "De shortcode kleistad_$shortcode_tag mag maar éénmaal per pagina gebruikt worden" );
		}
		self::$tags[]    = $shortcode_tag;
		$shortcode_class = self::get_class_name( $shortcode_tag );
		return new $shortcode_class( $shortcode_tag, $attributes );
	}

	/**
	 * Geef de class naam behorende bij de shortcode
	 *
	 * @param string $shortcode_tag Shortcode (zonder kleistad- ).
	 *
	 * @return string
	 */
	public static function get_class_name( string $shortcode_tag ) : string {
		return '\\' . __NAMESPACE__ . '\\Public_' . ucwords( $shortcode_tag, '_' );
	}

	/**
	 * De constructor
	 *
	 * @since   4.0.87
	 *
	 * @param string $shortcode  Shortcode (zonder kleistad- ).
	 * @param array  $attributes Shortcode parameters.
	 */
	protected function __construct( string $shortcode, array $attributes ) {
		foreach ( $attributes as $att_key => $attribute ) {
			$this->atts[ $att_key ] = htmlspecialchars_decode( $attribute );
		}
		$this->data      = array_merge( $this->data, $this->atts );
		$this->shortcode = $shortcode;
	}

	/**
	 * Haal de tekst van de shortcode op.
	 *
	 * @return string
	 */
	protected function content() : string {
		$display_class = get_class( $this ) . '_Display';
		$display       = new $display_class( $this->data, $this->display_actie );
		return $display->render();
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
					$shortcode  = $request->get_param( 'tag' ) ?: '';
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
					$shortcode  = $request->get_param( 'tag' ) ?: '';
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
					$shortcode  = $request->get_param( 'tag' ) ?: '';
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
			$atts       = json_decode( $request->get_param( 'atts' ) ?? '', true );
			$attributes = is_array( $atts ) ? $atts : [ $atts ];
			return new $class( $tag, $attributes, opties() );
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
		$shortcode = self::get_shortcode( $request );
		try {
			if ( ! is_a( $shortcode, __CLASS__ ) ) {
				throw new Exception( 'callback_formsubmit voor onbekend object' );
			}
			$shortcode->atts = json_decode( $request->get_param( 'atts' ) ?? '', true );
			$shortcode->data = [
				'actie' => sanitize_text_field( $request->get_param( 'actie' ) ),
				'id'    => is_numeric( $request->get_param( 'id' ) ) ? absint( $request->get_param( 'id' ) ) : sanitize_text_field( $request->get_param( 'id' ) ),
			];
			return new WP_REST_Response( [ 'content' => $shortcode->display() ] );
		} catch ( Kleistad_Exception $exceptie ) {
			return new WP_REST_Response( [ 'status' => $shortcode->status( new WP_Error( $exceptie->getMessage() ) ) ] );
		} catch ( Exception $exceptie ) {
			fout( __CLASS__, $exceptie->GetMessage() );
			return new WP_REST_Response( [ 'status' => $shortcode->status( new WP_Error( 'Er is een onbekende fout opgetreden' ) ) ] );
		}
	}

	/**
	 * Maak een tijdelijk bestand aan voor download.
	 *
	 * @param Shortcode $shortcode De shortcode waarvoor de download plaatsvindt.
	 * @param string    $functie   De shortcode functie die aangeroepen moet worden.
	 *
	 * @return array
	 */
	protected static function download( Shortcode $shortcode, string $functie ) : array {
		if ( 0 === strpos( $functie, 'url_' ) ) {
			return [ 'file_uri' => $shortcode->$functie() ];
		}
		$upload_dir            = wp_upload_dir();
		$file                  = '/kleistad_tmp_' . uniqid() . '.csv';
		$shortcode->filehandle = fopen( $upload_dir['basedir'] . $file, 'w' );
		if ( false !== $shortcode->filehandle ) {
			fwrite( $shortcode->filehandle, "\xEF\xBB\xBF" );
			$result = $shortcode->$functie();
			fclose( $shortcode->filehandle );
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
		$shortcode = self::get_shortcode( $request );
		try {
			if ( ! is_a( $shortcode, __CLASS__ ) ) {
				throw new Exception( 'callback_download voor onbekend object' );
			}
			$functie = $request->get_param( 'actie' ) ?? '';
			if ( method_exists( $shortcode, $functie ) ) {
				return new WP_REST_Response( self::download( $shortcode, $functie ) );
			}
			throw new Exception( 'callback_download voor onbekende method' );
		} catch ( Kleistad_Exception $exceptie ) {
			return new WP_REST_Response( [ 'status' => $shortcode->status( new WP_Error( $exceptie->getMessage() ) ) ] );
		} catch ( Exception $exceptie ) {
			fout( __CLASS__, $exceptie->GetMessage() );
			return new WP_REST_Response( [ 'status' => $shortcode->status( new WP_Error( 'Er is een onbekende fout opgetreden' ) ) ] );
		}
	}

	/**
	 * Toon de uitvoer van de shortcode, eventueel voorafgegaan door een melding van een betaal status.
	 *
	 * @since 4.5.1
	 *
	 * @return string De uitvoer.
	 */
	public function run() : string {
		return $this->display();
	}

	/**
	 * Bepaal de display actie.
	 * prio 1: actie via de url
	 * prio 2: actie al aanwezig in de data (afkomstig callback functie)
	 * prio 3: actie vanuit de actie parameter in de tag
	 * prio 4: de default actie
	 */
	private function bepaal_actie() {
		foreach ( [ filter_input( INPUT_GET, 'actie', FILTER_SANITIZE_STRING ), $this->data['actie'] ?? null, $this->atts['actie'] ?? null, self::STANDAARD_ACTIE ] as $actie ) {
			if ( ! empty( $actie ) ) {
				$this->display_actie = $actie;
				return;
			}
		}
	}
}
