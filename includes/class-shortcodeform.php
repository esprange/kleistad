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

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use Exception;

/**
 * De abstract class voor shortcodes
 */
abstract class ShortcodeForm extends Shortcode {

	/**
	 * Validatie functie, wordt voor form validatie gebruikt
	 *
	 * @since   4.0.87
	 * @param array $data de gevalideerde data.
	 * @return WP_ERROR|bool
	 */
	abstract protected function validate( array &$data );

	/**
	 * Save functie, wordt gebruikt bij formulieren.
	 *
	 * @since   4.0.87
	 * @param array $data de gevalideerde data die kan worden opgeslagen.
	 * @return array
	 */
	abstract protected function save( array $data ) : array;

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
	 * @param array $input de ingevoerde data.
	 * @return bool|WP_Error
	 */
	protected function validate_gebruiker( array &$input ) {
		$error = new WP_Error();
		if ( ! $this->validate_email( $input['user_email'] ) ) {
			$error->add( 'verplicht', "De invoer {$input['user_email']} is geen geldig E-mail adres." );
			$input['user_email']     = '';
			$input['email_controle'] = '';
		}
		if ( 0 !== strcasecmp( $input['email_controle'], $input['user_email'] ) ) {
			$error->add( 'verplicht', "De ingevoerde e-mail adressen {$input['user_email']} en {$input['email_controle']} zijn niet identiek" );
			$input['email_controle'] = '';
		}
		if ( ! $this->validate_telnr( $input['telnr'] ) ) {
			$error->add( 'onjuist', "Het ingevoerde telefoonnummer {$input['telnr']} lijkt niet correct. Alleen Nederlandse telefoonnummers kunnen worden doorgegeven" );
			$input['telnr'] = '';
		}
		if ( ! $this->validate_pcode( $input['pcode'] ) ) {
			$error->add( 'onjuist', "De ingevoerde postcode {$input['pcode']} lijkt niet correct. Alleen Nederlandse postcodes kunnen worden doorgegeven" );
			$input['pcode'] = '';
		}
		if ( ! $this->validate_naam( $input['first_name'] ) ) {
			$error->add( 'verplicht', 'Een voornaam (een of meer alfabetische karakters) is verplicht' );
			$input['first_name'] = '';
		}
		if ( ! $this->validate_naam( $input['last_name'] ) ) {
			$error->add( 'verplicht', 'Een achternaam (een of meer alfabetische karakters) is verplicht' );
			$input['last_name'] = '';
		}
		return empty( $error->get_error_codes() ) ?: $error;
	}

	/**
	 * Hulp functie, om een telefoonnr te valideren
	 *
	 * @since 5.2.0
	 * @param string $telnr het telefoonnummer, inclusief spaties, streepjes etc.
	 * @return bool if false, dan niet gevalideerd.
	 */
	protected function validate_telnr( string &$telnr ) : bool {
		if ( empty( $telnr ) ) {
			return true;
		}
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
	protected function validate_pcode( string &$pcode ) : bool {
		if ( empty( $pcode ) ) {
			return true;
		}
		$pcode = strtoupper( str_replace( ' ', '', $pcode ) );
		return 1 === preg_match( '/^[1-9][0-9]{3} ?[a-zA-Z]{2}$/', $pcode );
	}

	/**
	 * Hulp functie, om een naam te valideren
	 *
	 * @since 5.2.0
	 * @param string $naam de naam.
	 * @return bool if false, dan niet gevalideerd.
	 */
	protected function validate_naam( string $naam ) : bool {
		return 1 === preg_match( "/^(['a-zA-Z])(([',. -][a-zA-Z ])?[a-zA-Z]*)*$/", htmlspecialchars_decode( remove_accents( $naam ), ENT_QUOTES ) );
	}

	/**
	 * Hulp functie, om een email
	 *
	 * @since 5.2.0
	 * @param string $email het email adres.
	 * @return bool if false, dan niet gevalideerd.
	 */
	protected function validate_email( string &$email ) : bool {
		$email = strtolower( $email );
		return filter_var( $email, FILTER_VALIDATE_EMAIL );
	}

	/**
	 * Helper functie voor een formulier
	 *
	 * @since 5.7.0
	 */
	protected function form() {
		?>
		<form action="#" autocomplete="off" enctype="multipart/form-data" >
		<?php
	}

	/**
	 * Register rest URI's.
	 *
	 * @since 5.7.0
	 */
	public static function register_rest_routes() {
		register_rest_route(
			KLEISTAD_API,
			'/formsubmit',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'callback_formsubmit' ],
				'permission_callback' => function( WP_REST_Request $request ) {
					$shortcode_tag = $request->get_param( 'tag' ) ?: '';
					$shortcodes    = new ShortCodes();
					return $shortcodes->check_access( $shortcode_tag );
				},
			]
		);
	}

	/**
	 * Verwerk een form submit via ajax call
	 *
	 * @since 5.7.0
	 * @param  WP_REST_Request $request De callback parameters.
	 * @return WP_REST_Response De response.
	 * @throws Exception Onbekend object.
	 */
	public static function callback_formsubmit( WP_REST_Request $request ) : WP_REST_Response {
		try {
			$shortcode = self::get_shortcode( $request );
			if ( ! is_a( $shortcode, __CLASS__ ) ) {
				throw new Exception( 'callback_formsubmit voor onbekend object' );
			}
			$data   = [ 'form_actie' => $request->get_param( 'form_actie' ) ];
			$result = $shortcode->validate( $data );
			if ( ! is_wp_error( $result ) ) {
				return new WP_REST_Response( $shortcode->save( $data ) );
			}
			return new WP_REST_Response( [ 'status' => $shortcode->status( $result ) ] );
		} catch ( Kleistad_Exception $exceptie ) {
			return new WP_REST_Response( [ 'status' => $shortcode->status( new WP_Error( $exceptie->getMessage() ) ) ] );
		} catch ( Exception $exceptie ) {
			error_log( $exceptie->getMessage() ); // phpcs:ignore
			return new WP_REST_Response( [ 'status' => $shortcode->status( new WP_Error( 'Er is een onbekende fout opgetreden' ) ) ] );
		}
	}

}
