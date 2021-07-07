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
