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

namespace Kleistad;

/**
 * De abstract class voor shortcodes
 */
abstract class ShortcodeForm extends Shortcode {

	/**
	 * Redirect voor o.a. ideal betalingen
	 *
	 * @var string de url voor een redirect terug naar de site.
	 */
	private static $form_url = null;

	/**
	 * Validatie functie, wordt voor form validatie gebruikt
	 *
	 * @since   4.0.87
	 * @param array $data de gevalideerde data.
	 * @return \WP_ERROR|bool
	 */
	abstract protected function validate( &$data );

	/**
	 * Save functie, wordt gebruikt bij formulieren.
	 *
	 * @since   4.0.87
	 * @param array $data de gevalideerde data die kan worden opgeslagen.
	 * @return array
	 */
	abstract protected function save( $data );

	/**
	 * Test functie, eventueel te overschrijven in child class.
	 *
	 * @param array $data de te gebruiken testdata.
	 * @return array
	 */
	protected function test( $data ) {
		if ( isset( $data ) ) {
			return [];
		};
	}

	/**
	 * Enqueue nu ook de form specieke javascript.
	 */
	protected function enqueue() {
		parent::enqueue();
		if ( ! wp_script_is( 'kleistad-form' ) ) {
			wp_enqueue_script( 'kleistad-form' );
		}
	}

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
	 * @throws \Exception    Als de json encode faalt.
	 */
	protected function form( $extra = null ) {
		?>
		<form class="ShortcodeForm" action="#" autocomplete="off" enctype="multipart/form-data" <?php echo ( $extra ?: '' ); // phpcs:ignore ?> >
		<?php
	}

	/**
	 * Register rest URI's.
	 *
	 * @since 5.7.0
	 */
	public static function register_rest_routes() {
		register_rest_route(
			Public_Main::api(),
			'/formsubmit',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'callback_formsubmit' ],
				'permission_callback' => function( \WP_REST_Request $request ) {
					$shortcode = $request->get_param( 'tag' );
					return \Kleistad\Public_Shortcode_Handler::check_access( $shortcode );
				},
			]
		);
	}

	/**
	 * Geef de url terug, zoals eerder vanuit de client doorgegeven. Dit omdat permalink niet werkt in een Ajax call.
	 *
	 * @since 5.7.0
	 */
	public static function get_url() {
		return self::$form_url;
	}

	/**
	 * Verwerk een form submit via ajax call
	 *
	 * @since 5.7.0
	 * @param  \WP_REST_Request $request De callback parameters.
	 * @return \WP_REST_Response|\WP_Error de response.
	 */
	public static function callback_formsubmit( \WP_REST_Request $request ) {
		$shortcode_object = self::get_shortcode_object( $request );
		if ( ! is_a( $shortcode_object, __CLASS__ ) ) {
			return new \WP_Error( 'intern', 'interne fout' );
		}
		$data           = [ 'form_actie' => $request->get_param( 'form_actie' ) ];
		self::$form_url = $request->get_header( 'referer' );
		$result         = $shortcode_object->validate( $data );
		if ( ! is_wp_error( $result ) ) {
			if ( 'test' === strtok( $data['form_actie'], '_' ) ) {
				$result = $shortcode_object->test( $data );
			} else {
				$result = $shortcode_object->save( $data );
			}
		} else {
			return new \WP_REST_Response( [ 'status' => $shortcode_object->status( $result ) ] );
		}
		return new \WP_REST_Response( $result );
	}

}
