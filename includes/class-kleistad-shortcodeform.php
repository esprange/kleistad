<?php
/**
 * De  abstracte class voor shortcodes.
 *
 * @link       https://www.kleistad.nl
 * @since      4.3.11
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * De abstract class voor shortcodes
 */
abstract class Kleistad_ShortcodeForm extends Kleistad_ShortCode {

	/**
	 * File handle voor download bestanden
	 *
	 * @var resource de file pointer
	 */
	public $file_handle;

	/**
	 * Validatie functie, wordt voor form validatie gebruikt
	 *
	 * @since   4.0.87
	 * @param array $data de gevalideerde data.
	 * @return \WP_ERROR|bool
	 */
	abstract protected function validate( &$data );

	/**
	 * Save functie, wordt gebruikt bij formulieren
	 *
	 * @since   4.0.87
	 * @param array $data de gevalideerde data die kan worden opgeslagen.
	 * @return \WP_ERROR|string
	 */
	abstract protected function save( $data );

	/**
	 * Valideer opvoeren nieuwe gebruiker
	 *
	 * @since 5.2.1
	 * @param \WP_ERROR $error bestaand wp error object waar nieuwe fouten aan toegevoegd kunnen worden.
	 * @param array     $input de ingevoerde data.
	 */
	public function validate_gebruiker( &$error, $input ) {
		if ( ! $this->validate_email( $input['EMAIL'] ) ) {
			$error->add( 'verplicht', 'De invoer ' . $input['EMAIL'] . ' is geen geldig E-mail adres.' );
			$input['EMAIL']          = '';
			$input['email_controle'] = '';
		} else {
			$this->validate_email( $input['email_controle'] );
			if ( $input['email_controle'] !== $input['EMAIL'] ) {
				$error->add( 'verplicht', 'De ingevoerde e-mail adressen ' . $input['EMAIL'] . ' en ' . $input['email_controle'] . ' zijn niet identiek' );
				$input['email_controle'] = '';
			}
		}
		if ( ! empty( $input['telnr'] ) && ! $this->validate_telnr( $input['telnr'] ) ) {
			$error->add( 'onjuist', 'Het ingevoerde telefoonnummer lijkt niet correct. Alleen Nederlandse telefoonnummers kunnen worden doorgegeven' );
		}
		if ( ! empty( $input['pcode'] ) && ! $this->validate_pcode( $input['pcode'] ) ) {
			$error->add( 'onjuist', 'De ingevoerde postcode lijkt niet correct. Alleen Nederlandse postcodes kunnen worden doorgegeven' );
		}
		if ( ! $this->validate_naam( $input['FNAME'] ) ) {
			$error->add( 'verplicht', 'Een voornaam (een of meer alfabetische karakters) is verplicht' );
			$input['FNAME'] = '';
		}
		if ( ! $this->validate_naam( $input['LNAME'] ) ) {
			$error->add( 'verplicht', 'Een achternaam (een of meer alfabetische karakters) is verplicht' );
			$input['LNAME'] = '';
		}

		return $error;
	}

	/**
	 * Hulp functie, om een telefoonnr te valideren
	 *
	 * @since 5.2.0
	 * @param string $telnr het telefoonnummer, inclusief spaties, streepjes etc.
	 * @return bool if false, dan niet gevalideerd.
	 */
	public function validate_telnr( &$telnr ) {
		$telnr = str_replace( [ ' ', '-' ], [ '', '' ], $telnr );
		return 1 === preg_match( '/^(((0)[1-9]{2}[0-9][-]?[1-9][0-9]{5})|((\\+31|0|0031)[1-9][0-9][-]?[1-9][0-9]{6}))$/', $telnr ) ||
				1 === preg_match( '/^(((\\+31|0|0031)6){1}[1-9]{1}[0-9]{7})$/i', $telnr );
	}

	/**
	 * Hulp functie, om een postcode te valideren
	 *
	 * @since 5.2.0
	 * @param string $pcode de postcode, inclusief spaties, streepjes etc.
	 * @return bool if false, dan niet gevalideerd.
	 */
	public function validate_pcode( &$pcode ) {
		$pcode = strtoupper( str_replace( ' ', '', $pcode ) );
		return 1 === preg_match( '/^[1-9][0-9]{3} ?[a-zA-Z]{2}$/', $pcode );
	}

	/**
	 * Hulp functie, om een adres te valideren
	 *
	 * @since 5.2.0
	 * @param string $adres het adres.
	 * @return bool if false, dan niet gevalideerd.
	 */
	public function validate_adres( $adres ) {
		return 1 === preg_match( '/^([1-9][e][\s])*([a-zA-Z]+(([\.][\s])|([\s]))?)+[1-9][0-9]*(([-][1-9][0-9]*)|([\s]?[a-zA-Z]+))?$/i', $adres );
	}

	/**
	 * Hulp functie, om een naam te valideren
	 *
	 * @since 5.2.0
	 * @param string $naam de naam.
	 * @return bool if false, dan niet gevalideerd.
	 */
	public function validate_naam( $naam ) {
		$naam = preg_replace( '/[^a-zA-Z\s]/', '', $naam );
		return ! empty( $naam );
	}

	/**
	 * Hulp functie, om een email
	 *
	 * @since 5.2.0
	 * @param string $email het email adres.
	 * @return bool if false, dan niet gevalideerd.
	 */
	public function validate_email( &$email ) {
		$email = strtolower( $email );
		return filter_var( $email, FILTER_VALIDATE_EMAIL );
	}

	/**
	 * Verwerk de formulier invoer
	 *
	 * @since 4.5.1
	 *
	 * @param  array $data de uit te wisselen data.
	 * @return string html tekst.
	 */
	private function process( &$data ) {
		$html               = '';
		$data['form_actie'] = filter_input( INPUT_POST, 'kleistad_submit_' . $this->shortcode );
		if ( ! is_null( $data['form_actie'] ) ) {
			if ( wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'kleistad_' . $this->shortcode ) ) {
				$result = $this->validate( $data );
				if ( ! is_wp_error( $result ) ) {
					if ( 0 === strpos( $data['form_actie'], 'test_' ) ) {
						$result = $this->test( substr( $data['form_actie'], strlen( 'test_' ) ), $data );
						$html  .= '<div class="kleistad_succes"><p>' . $result . '</p></div>';
					} else {
						$result = $this->save( $data );
						if ( is_string( $result ) ) {
							$url = add_query_arg( 'kleistad_succes', rawurlencode( $result ), get_permalink() );
							wp_safe_redirect( $url, 303 );
							die();
						}
					}
				}
				if ( is_wp_error( $result ) ) {
					foreach ( $result->get_error_messages() as $error ) {
						$html .= '<div class="kleistad_fout"><p>' . $error . '</p></div>';
					}
				}
			} else {
				$html .= '<div class="kleistad_fout"><p>security fout</p></div>';
			}
		}
		return $html;
	}

	/**
	 * Voer het rapport van de shortcode uit.
	 *
	 * @since 4.5.1
	 */
	public function run() {
		$succes = filter_input( INPUT_GET, 'kleistad_succes' );
		$html   = ! empty( $succes ) ? '<div class="kleistad_succes"><p>' . $succes . '</p></div>' : '';
		$html  .= Kleistad_Betalen::controleer();
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$data  = [];
			$html .= $this->process( $data );
			$html .= $this->display( $data );
		} else {
			$html .= $this->display();
		}
		return $html;
	}

	/**
	 * Register rest URI's.
	 *
	 * @since 6.0.0
	 */
	public static function register_rest_routes() {
		register_rest_route(
			Kleistad_Public::url(),
			'/download',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'callback_download' ],
				'args'                => [
					'inputs' => [
						'required' => true,
					],
					'naam'   => [
						'required' => true,
					],
					'waarde' => [
						'required' => true,
					],
				],
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			]
		);
	}

	/**
	 * Download callback
	 *
	 * @since 6.0.0
	 *
	 * @param WP_REST_Request $request het request.
	 * @return \WP_REST_response de response.
	 */
	public static function callback_download( WP_REST_Request $request ) {
		parse_str( $request->get_param( 'inputs' ), $inputs );
		$class                  = 'Kleistad_Public_' . ucfirst( str_replace( 'kleistad_submit_', '', $request->get_param( 'naam' ) ) );
		$functie                = str_replace( 'download_', '', $request->get_param( 'waarde' ) );
		$upload_dir             = wp_upload_dir();
		$filename               = 'kleistad_' . uniqid() . '.csv';
		$shortcode              = new $class( null, null, Kleistad::get_options() );
		$shortcode->file_handle = fopen( $upload_dir['basedir'] . "/$filename", 'w' );
		fwrite( $shortcode->file_handle, "\xEF\xBB\xBF" );
		call_user_func( [ $shortcode, $functie ], $inputs );
		fclose( $shortcode->file_handle );
		return new WP_REST_response( [ 'file_uri' => $upload_dir['baseurl'] . "/$filename" ] );
	}

	/**
	 * Voer een testrun uit
	 *
	 * @param string $test Naam van de functie voor het uitvoeren van de test.
	 * @param array  $data De uit te wisselen data.
	 *
	 * @since 5.5.1
	 */
	private function test( $test, $data ) {
		return call_user_func( [ $this, $test ], $data );
	}

	/**
	 * Ruim eventuele download files op.
	 */
	public static function cleanup_downloads() {
		$upload_dir = wp_upload_dir();
		$files      = glob( $upload_dir['basedir'] . 'kleistad_*.csv' );
		$now        = time();

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				if ( $now - filemtime( $file ) >= 60 * 60 * 24 ) {
					unlink( $file );
				}
			}
		}
	}
}
