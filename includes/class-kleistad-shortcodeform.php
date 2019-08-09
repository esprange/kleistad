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
	 * @var resource de file pointer.
	 */
	public $file_handle;

	/**
	 * Redirect voor o.a. ideal betalingen
	 *
	 * @var string de url voor een redirect terug naar de site.
	 */
	private static $form_url = null;

	/**
	 * Redirect naar Mollie voor ideal betaling
	 *
	 * @var string de url voor een redirect naar Mollie.
	 */
	private static $redirect_url = null;

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
	protected function validate_gebruiker( &$error, $input ) {
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
	protected function validate_telnr( &$telnr ) {
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
	protected function validate_pcode( &$pcode ) {
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
	protected function validate_adres( $adres ) {
		return 1 === preg_match( '/^([1-9][e][\s])*([a-zA-Z]+(([\.][\s])|([\s]))?)+[1-9][0-9]*(([-][1-9][0-9]*)|([\s]?[a-zA-Z]+))?$/i', $adres );
	}

	/**
	 * Hulp functie, om een naam te valideren
	 *
	 * @since 5.2.0
	 * @param string $naam de naam.
	 * @return bool if false, dan niet gevalideerd.
	 */
	protected function validate_naam( $naam ) {
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
	protected function validate_email( &$email ) {
		$email = strtolower( $email );
		return filter_var( $email, FILTER_VALIDATE_EMAIL );
	}

	/**
	 * Helper functie voor een formulier
	 *
	 * @since 5.7.0
	 *
	 * @param string $extra De eventuele extra toe te voegen attributen.
	 */
	protected function form( $extra = null ) {
		$info = [
			'shortcode' => $this->shortcode,
			'atts'      => $this->atts,
			'class'     => get_class( $this ),
		];
		?>
		<form class="kleistad_shortcodeform" action="#" method="POST" <?php echo esc_attr( $extra ?: '' ); ?> >
		<input type="hidden" name="shortcodeform_info" value="<?php echo esc_attr( maybe_serialize( $info ) ); ?>" />
		<?php
	}

	/**
	 * Register rest URI's.
	 *
	 * @since 5.7.0
	 */
	public static function register_rest_routes() {
		register_rest_route(
			Kleistad_Public::url(),
			'/formsubmit',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'callback_formsubmit' ],
				'permission_callback' => function() {
					return true;
				},
			]
		);
	}

	/**
	 * Geef de url terug, zoals eerder vanuit de client doorgegeven. Dit omdat permalink niet werkt in een Ajax call.
	 *
	 * @since 5.7.0
	 */
	public static function url() {
		return self::$form_url;
	}

	/**
	 * Registreer de url waar naar toe de redirect moet plaatsvinden.
	 *
	 * @since 5.7.0
	 *
	 * @param string $url Het url adres.
	 */
	public static function redirect( $url ) {
		self::$redirect_url = $url;
	}

	/**
	 * Toon de status van de het resultaat
	 *
	 * @since 5.7.0
	 *
	 * @param string | WP_Error $result Het resultaat dat getoond moet worden.
	 */
	private static function status( $result ) {
		$html = '';
		if ( is_string( $result ) ) {
			$html .= '<div class="kleistad_succes"><p>' . $result . '</p></div>';
		} elseif ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $error ) {
				$html .= '<div class="kleistad_fout"><p>' . $error . '</p></div>';
			}
		}
		return $html;
	}

	/**
	 * Verwerk een form submit via ajax call
	 *
	 * @since 5.7.0
	 * @return WP_REST_response de response.
	 */
	public static function callback_formsubmit() {
		$data = [];
		$info  = unserialize( filter_input( INPUT_POST, 'shortcodeform_info' ) ); //phpcs:ignore
		if ( class_exists( $info['class'] ) ) {
			$shortcode          = new $info['class']( $info['shortcode'], $info['atts'], Kleistad::get_options() );
			$data['form_actie'] = filter_input( INPUT_POST, 'form_actie' );
			self::$form_url     = filter_input( INPUT_POST, 'form_url' );
			$result             = $shortcode->validate( $data );
			if ( ! is_wp_error( $result ) ) {
				if ( 0 === strpos( $data['form_actie'], 'test_' ) ) {
					return new WP_REST_Response( [ 'html' => self::status( $shortcode->test( $data ) ) . $shortcode->display( $data ) ] );
				} elseif ( 0 === strpos( $data['form_actie'], 'download_' ) ) {
					$upload_dir             = wp_upload_dir();
					$filename               = 'kleistad_tmp_' . uniqid() . '.csv';
					$functie                = str_replace( 'download_', '', $data['form_actie'] );
					$shortcode->file_handle = fopen( $upload_dir['basedir'] . "/$filename", 'w' );
					if ( false !== $shortcode->file_handle ) {
						fwrite( $shortcode->file_handle, "\xEF\xBB\xBF" );
						call_user_func( [ $shortcode, $functie ] );
						fclose( $shortcode->file_handle );
						return new WP_REST_response( [ 'file_uri' => $upload_dir['baseurl'] . "/$filename" ] );
					} else {
						return new WP_REST_Response( [ 'html' => '<div class="kleistad_fout"><p>bestand kon niet aangemaakt worden</p></div>' ] );
					}
				} else {
					$result = $shortcode->save( $data );
					if ( ! is_wp_error( $result ) && ! empty( self::$redirect_url ) ) {
						return new WP_REST_response( [ 'redirect_uri' => self::$redirect_url ] );
					} else {
						return new WP_REST_Response( [ 'html' => self::status( $result ) . $shortcode->display() ] );
					}
				}
			} else {
				return new WP_REST_Response( [ 'html' => self::status( $result ) . $shortcode->display( $data ) ] );
			}
		} else {
			return new WP_REST_Response( [ 'html' => '<div class="kleistad_fout"><p>interne fout</p></div>' ] );
		}
	}

	/**
	 * Ruim eventuele download files op.
	 *
	 * @since 5.7.0
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
}
