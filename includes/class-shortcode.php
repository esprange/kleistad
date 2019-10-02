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
	 * Controleer toegang tot deze shortcode.
	 *
	 * @since 5.7.2
	 *
	 * @param string $shortcode De shortcode.
	 * @return bool Of er toegang is.
	 */
	protected static function check_access( $shortcode ) {
		$access = Public_Main::SHORTCODES[ $shortcode ]['access'];
		if ( ! empty( $access ) ) {
			$gebruiker = wp_get_current_user();
			if ( $gebruiker->ID ) {
				return 0 !== count( array_intersect( $access, (array) $gebruiker->roles ) );
			} else {
				return in_array( '#', $access, true );
			}
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
		foreach ( \Kleistad\Public_Main::SHORTCODES[ $this->shortcode ]['css'] as $dependency ) {
			wp_enqueue_style( $dependency );
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
					'base_url'        => \Kleistad\Public_Main::base_url(),
				]
			);
		}
		wp_enqueue_script( "kleistad{$this->shortcode}" );

		if ( ! self::check_access( $this->shortcode ) ) {
			$result = $this->status( new \WP_Error( 'toegang', 'Je hebt geen toegang tot deze functie' ) );
		} else {
			$result = $this->prepare( $data );
			if ( is_wp_error( $result ) ) {
				$result = $this->status( $result );
			} else {
				ob_start();
				require plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/public-' . str_replace( '_', '-', $this->shortcode ) . '.php';
				$result = ob_get_clean();
			}
		}

		$betaal_result = \Kleistad\Betalen::controleer();
		if ( is_string( $betaal_result ) ) {
			if ( ! empty( $betaal_result ) ) { // Er is een succesvolle betaling.
				return $this->status( $betaal_result ) . $this->goto_home();
			}
		} else { // Er is een betaling maar niet succesvol.
			return $this->status( $betaal_result ) . $result;
		}
		return $result;
	}

	/**
	 * Toon de status van de het resultaat
	 *
	 * @since 5.7.0
	 *
	 * @param string | array | \WP_Error $result Het resultaat dat getoond moet worden.
	 */
	public function status( $result ) {
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
	public function goto_home() {
		$user = wp_get_current_user();
		if ( 0 === $user->ID ) {
			$url = home_url();
		} elseif ( $user->has_cap( 'bestuur' ) ) {
			$url = home_url( '/bestuur/' );
		} else {
			$url = home_url( '/leden/' );
		}
		ob_start();
		?>
		<br/><br/>
		<div style="text-align:center;" >
			<button onclick="location.href='<?php echo esc_url( $url ); ?>';" >
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
	 * @throws \Exception          Foutmelding ingeval de shortcode meerdere keren op de pagina voorkomt.
	 */
	public function __construct( $shortcode, $atts, $options, $access = [] ) {
		static $active_shortcodeforms = [];
		try {
			if ( in_array( $shortcode, $active_shortcodeforms, true ) ) {
				throw new \Exception( "Pagina bevat meer dan een identieke $shortcode aanroep" );
			} else {
				$active_shortcodeforms[] = $shortcode;
				$this->atts              = $atts;
				$this->options           = $options;
				$this->shortcode         = $shortcode;
			}
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore
		}
	}

	/**
	 * Register rest URI's.
	 *
	 * @since 5.7.0
	 */
	public static function register_rest_routes() {
		register_rest_route(
			Public_Main::api(),
			'/getitem', // /(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_getitem' ],
				'permission_callback' => function( \WP_REST_Request $request ) {
					$shortcode = $request->get_param( 'tag' );
					return self::check_access( $shortcode );
				},
			]
		);
		register_rest_route(
			Public_Main::api(),
			'/getitems',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_getitem' ],
				'permission_callback' => function( \WP_REST_Request $request ) {
					$shortcode = $request->get_param( 'tag' );
					return self::check_access( $shortcode );
				},
			]
		);
		register_rest_route(
			Public_Main::api(),
			'/download',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_download' ],
				'permission_callback' => function( \WP_REST_Request $request ) {
					$shortcode = $request->get_param( 'tag' );
					return self::check_access( $shortcode );
				},
			]
		);
	}

	/**
	 * Helper functie, geef het object terug of een foutboodschap.
	 *
	 * @param \WP_REST_Request $request De informatie vanuit de client of het weer te geven item.
	 * @return \WP_REST_Response| bool  De response of false.
	 */
	protected static function get_shortcode_object( \WP_REST_Request $request ) {
		$tag   = $request->get_param( 'tag' );
		$class = '\Kleistad\Public_' . ucwords( $tag, '_' );
		if ( class_exists( $class ) ) {
			$atts = json_decode( $request->get_param( 'atts' ), true );
			return new $class( $tag, $atts, \Kleistad\Kleistad::get_options() );
		}
		return false;
	}

	/**
	 * Get an item and display it.
	 *
	 * @param \WP_REST_Request $request De informatie vanuit de client of het weer te geven item.
	 * @return \WP_REST_Response|\WP_Error de response.
	 */
	public static function callback_getitem( \WP_REST_Request $request ) {
		$shortcode_object = self::get_shortcode_object( $request );
		if ( ! is_a( $shortcode_object, __CLASS__ ) ) {
			return new \WP_Error( 'intern', 'interne fout' );
		}
		$data = [
			'actie' => sanitize_text_field( $request->get_param( 'actie' ) ),
			'id'    => absint( $request->get_param( 'id' ) ),
		];
		return new \WP_REST_Response(
			[
				'content' => $shortcode_object->display( $data ),
			]
		);
	}

	/**
	 * Maak een tijdelijk bestand aan voor download.
	 *
	 * @param \Kleistad\Shortcode $shortcode_object De shortcode waarvoor de download plaatsvindt.
	 * @param string              $functie          De shortcode functie die aangeroepen moet worden.
	 * @return \WP_REST_Response
	 */
	protected static function download( \Kleistad\Shortcode $shortcode_object, $functie ) {
		$upload_dir = wp_upload_dir();
		$file       = '/kleistad_tmp_' . uniqid() . '.csv';
		$result     = fopen( $upload_dir['basedir'] . $file, 'w' );
		if ( false !== $result ) {
			$shortcode_object->file_handle = $result;
			fwrite( $shortcode_object->file_handle, "\xEF\xBB\xBF" );
			call_user_func( [ $shortcode_object, $functie ] );
			fclose( $shortcode_object->file_handle );
			return new \WP_REST_Response(
				[
					'file_uri' => $upload_dir['baseurl'] . $file,
				]
			);
		} else {
			return new \WP_REST_Response(
				[
					'status'  => $shortcode_object->status( new \WP_Error( 'intern', 'bestand kon niet aangemaakt worden' ) ),
					'content' => $shortcode_object->goto_home(),
				]
			);
		}
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
				if ( $now - filemtime( $file ) >= 60 * 60 * 24 ) {
					unlink( $file );
				}
			}
		}
	}

	/**
	 * Get an item and display it.
	 *
	 * @param \WP_REST_Request $request De informatie vanuit de client of het weer te geven item.
	 * @return \WP_REST_Response|\WP_Error de response.
	 */
	public static function callback_download( \WP_REST_Request $request ) {
		$shortcode_object = self::get_shortcode_object( $request );
		if ( ! is_a( $shortcode_object, __CLASS__ ) ) {
			return new \WP_Error( 'intern', 'interne fout' );
		}
		return self::download( $shortcode_object, $request->get_param( 'actie' ) );
	}

	/**
	 * Toon de uitvoer van de shortcode, eventueel voorafgegaan door een melding van een betaalactie.
	 *
	 * @since 4.5.1
	 */
	public function run() {
		$data = [
			'actie' => '-',
		];
		return $this->display( $data );
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