<?php
/**
 * Interface class naar Mollie.
 *
 * @link       https://www.kleistad.nl
 * @since      4.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Mollie;
use MollieSimulatie;

/**
 * Definitie van de betalen class.
 */
class MollieClient {

	const MOLLIE_ID = 'mollie_customer_id';

	/**
	 * Het mollie object.
	 *
	 * @var object het mollie service object.
	 */
	private object $mollie_service;

	/**
	 * De constructor
	 *
	 * @throws Mollie\Api\Exceptions\ApiException Moet op hoger nivo afgevangen worden.
	 */
	public function __construct() {
		if ( defined( 'KLEISTAD_MOLLIE_SIM' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'tests/class-molliesimulatie.php';
			$this->mollie_service = new MollieSimulatie();
			return;
		}
		$setup                = setup();
		$this->mollie_service = new Mollie\Api\MollieApiClient();

		if ( '1' === $setup['betalen'] ) {
			if ( '' !== $setup['sleutel'] ) {
				$this->mollie_service->setApiKey( $setup['sleutel'] );
			}
			return;
		}
		if ( '' !== $setup['sleutel_test'] ) {
			$this->mollie_service->setApiKey( $setup['sleutel_test'] );
		}
	}

	/**
	 * Geef het mollie klant object
	 *
	 * @param int|array $klant WP user id of naam/email combi.
	 *
	 * @return object Het object.
	 * @throws Mollie\Api\Exceptions\ApiException Moet op hoger nivo afgehandeld worden.
	 */
	public function get_client( int|array $klant ) : object {
		if ( ! is_array( $klant ) ) {
			$gebruiker_id        = $klant;
			$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );
			if ( '' === $mollie_gebruiker_id || is_null( $mollie_gebruiker_id ) ) {
				$gebruiker        = get_userdata( $gebruiker_id );
				$mollie_gebruiker = $this->mollie_service->customers->create(
					[
						'name'  => $gebruiker->display_name,
						'email' => $gebruiker->user_email,
					]
				);
						update_user_meta( $gebruiker_id, self::MOLLIE_ID, $mollie_gebruiker->id );
				return $mollie_gebruiker;
			}
			return $this->mollie_service->customers->get( $mollie_gebruiker_id );
		}
		return $this->mollie_service->customers->create(
			[
				'name'  => $klant['naam'],
				'email' => $klant['email'],
			]
		);
	}

	/**
	 * Geef het mollie payment object
	 *
	 * @param string $betaling_id Het Mollie betaal id.
	 *
	 * @return object Het object.
	 * @throws Mollie\Api\Exceptions\ApiException Moet op hoger nivo afgehandeld worden.
	 */
	public function get_payment( string $betaling_id ) : object {
		return $this->mollie_service->payments->get( $betaling_id );
	}

	/**
	 * Geef de deelnemende banken
	 *
	 * Het object bevat
	 *    id,    bijv 'ideal_RABONL2U'
	 *    name,  bijv 'Rabobank'
	 *    image, met als properties 'size1x', 'size2x' en 'svg' welke elk een url bevatten naar resp. png's en een svg image
	 * Vanuit performance overwegingen vragen we dit maar eens per week op vanuit Mollie.
	 *
	 * @return object
	 * @throws Mollie\Api\Exceptions\ApiException Moet op hoger nivo afgehandeld worden.
	 */
	public function get_banks() : object {
		$issuers = get_transient( 'mollie_banken' );
		if ( $issuers ) {
			return $issuers;
		}
		$method  = $this->mollie_service->methods->get( Mollie\Api\Types\PaymentMethod::IDEAL, [ 'include' => 'issuers' ] );
		$issuers = $method->issuers();
		set_transient( 'mollie_banken', $issuers, WEEK_IN_SECONDS );
		return $issuers;
	}

	/**
	 * Register rest URI's.
	 */
	public static function register_rest_routes() {
		$ontvangen = new Ontvangen();
		register_rest_route(
			KLEISTAD_API,
			'/betaling',
			[
				'methods'             => 'POST',
				'callback'            => [ $ontvangen, 'callback_betaling_verwerkt' ],
				'args'                => [
					'id' => [
						'required' => true,
					],
				],
				'permission_callback' => function() {
						return true;
				},
			]
		);
	}

}
