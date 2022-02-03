<?php
/**
 * Interface class naar adres/postcode opzoek service.
 *
 * @link       https://www.kleistad.nl
 * @since      5.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Definitie van de adres class.
 */
class Adres {

	/**
	 * Register rest URI's.
	 *
	 * @since 4.5.3
	 */
	public static function register_rest_routes() {
		register_rest_route(
			KLEISTAD_API,
			'/adres',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_adres_zoeken' ],
				'args'                => [
					'postcode' => [
						'required' => true,
					],
					'huisnr'   => [
						'required' => true,
					],
				],
				'permission_callback' => function() {
						return true;
				},
			]
		);
	}

	/**
	 * Webhook functie om adres te zoeken. Wordt aangeroepen vanuit client.
	 *
	 * @since      5.2.0
	 *
	 * @param WP_REST_Request $request het request.
	 * @return WP_Error|WP_REST_Response de response of de error.
	 */
	public static function callback_adres_zoeken( WP_REST_Request $request ): WP_Error|WP_REST_Response {
		$postcode = $request->get_param( 'postcode' );
		$huisnr   = $request->get_param( 'huisnr' );
		$url      = 'https://geodata.nationaalgeoregister.nl/locatieserver/free?fq=' .
			rawurlencode( 'postcode:' . $postcode ) . '&fq=' . rawurlencode( 'huisnummer~' . $huisnr . '*' );
		$response = wp_remote_get( $url );
		if ( ! is_array( $response ) ) {
			return new WP_Error(
				'rest_custom_error',
				'Geen geldig antwoord van geodata service.',
				[ 'status' => 503 ]
			);
		}
		$api_response = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( false === $api_response || 0 === count( $api_response['response']['docs'] ) ) {
			return new WP_Error(
				'rest_custom_error',
				'Niet gevonden.',
				[ 'status' => 204 ]
			); // 204 is niet gevonden, antwoord leeg.
		}
		return new WP_REST_Response(
			[
				'straat' => $api_response['response']['docs'][0]['straatnaam'] ?? '',
				'plaats' => $api_response['response']['docs'][0]['woonplaatsnaam'] ?? '',
			]
		); // Geeft default http status 200 terug.
	}

}
