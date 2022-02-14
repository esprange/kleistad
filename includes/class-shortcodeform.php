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
	 * Validator
	 *
	 * @var Validator $validator Subclass met validatie functies.
	 */
	public Validator $validator;

	/**
	 * Formulier actie
	 *
	 * @var string $form_actie De door de gebruiker gekozen formulier actie.
	 */
	public string $form_actie = '';

	/**
	 * Validatie functie, wordt voor form validatie gebruikt
	 *
	 * @since   4.0.87
	 *
	 * @return array
	 */
	abstract public function process() : array;

	/**
	 * Enqueue the scripts and styles for the shortcode.
	 */
	protected function enqueue() {
		parent::enqueue();
		if ( ! wp_script_is( 'kleistad-form' ) ) {
			wp_enqueue_script( 'kleistad-form' );
		}
	}

	/**
	 * Save functie, wordt gebruikt bij formulieren. Kan overschreven worden door een meer specifieke functie.
	 *
	 * @since   4.0.87
	 * @return array
	 */
	protected function save() : array {
		if ( method_exists( $this, $this->form_actie ) ) {
			return $this->{$this->form_actie}();
		}
		return [ 'status' => $this->status( new WP_Error( 'intern', 'interne fout, probeer het eventueel opnieuw' ) ) ];
	}

	/**
	 * De constructor
	 *
	 * @since   6.15.6
	 *
	 * @param string $shortcode  Shortcode (zonder kleistad- ).
	 * @param array  $attributes Shortcode parameters.
	 */
	protected function __construct( string $shortcode, array $attributes ) {
		parent::__construct( $shortcode, $attributes );
		$this->validator = new Validator();
	}

	/**
	 * Geef een foutmelding terug
	 *
	 * @param WP_Error $error De fout.
	 *
	 * @return array
	 */
	protected function melding( WP_Error $error ) : array {
		return [ 'status' => $this->status( $error ) ];
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
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public static function callback_formsubmit( WP_REST_Request $request ) : WP_REST_Response {
		$shortcode = self::get_shortcode( $request );
		try {
			if ( ! is_a( $shortcode, __CLASS__ ) ) {
				throw new Exception( 'callback_formsubmit voor onbekend object' );
			}
			$shortcode->form_actie = $request->get_param( 'form_actie' );
			return new WP_REST_Response( $shortcode->process() );
		} catch ( Kleistad_Exception $exceptie ) {
			return new WP_REST_Response( [ 'status' => $shortcode->status( new WP_Error( $exceptie->getMessage() ) ) ] );
		} catch ( Exception $exceptie ) {
			fout( __CLASS__, $exceptie->getMessage() );
			return new WP_REST_Response( [ 'status' => $shortcode->status( new WP_Error( 'Er is een onbekende fout opgetreden' ) ) ] );
		}
	}

}
