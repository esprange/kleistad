<?php
/**
 * Interface class naar Mollie ontvangen.
 *
 * @link       https://www.kleistad.nl
 * @since      4.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Mollie\Api\Exceptions\ApiException;
use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use Exception;

/**
 * Definitie van de betalen class.
 */
class Ontvangen {

	const CHARGEBACKS = '_chargebacks';
	const REFUNDS     = '_refunds';

	/**
	 * Controleer of de order gelukt is.
	 *
	 * @return WP_Error | string | bool De status van de betaling als tekst, WP_error of mislukts of false als er geen betaling is.
	 */
	public function controleer(): WP_Error|bool|string {
		$mollie_betaling_id = false;
		$uniqid             = filter_input( INPUT_GET, Betalen::QUERY_PARAM );
		if ( ! is_null( $uniqid ) ) {
			$mollie_betaling_id = get_transient( $uniqid );
			delete_transient( $uniqid );
		}
		if ( false === $mollie_betaling_id ) {
			return false;
		}
		try {
			$service  = new MollieClient();
			$betaling = $service->get_payment( $mollie_betaling_id );
			if ( $betaling->isPaid() ) {
				return $betaling->metadata->bericht;
			} elseif ( $betaling->isFailed() ) {
				return new WP_Error( 'betalen', 'De betaling heeft niet kunnen plaatsvinden. Probeer het opnieuw.' );
			} elseif ( $betaling->isExpired() ) {
				return new WP_Error( 'betalen', 'De betaling is verlopen. Probeer het opnieuw.' );
			} elseif ( $betaling->isCanceled() ) {
				return new WP_Error( 'betalen', 'De betaling is geannuleerd. Probeer het opnieuw.' );
			}
			return new WP_Error( 'betalen', 'De betaling is waarschijnlijk mislukt. Controleer s.v.p. de status van de bankrekening en neem eventueel contact op met Kleistad.' );
		} catch ( Exception $e ) {
			fout( __CLASS__, $e->getMessage() );
			return false;
		}
	}

	/**
	 * Webhook functie om betaling status te verwerken. Wordt aangeroepen door Mollie.
	 *
	 * @param WP_REST_Request $request het request.
	 *
	 * @return WP_REST_Response|WP_Error de response.
	 * @throws ApiException Moet op hoger nivo afgehandeld worden.
	 */
	public function callback_betaling_verwerkt( WP_REST_Request $request ): WP_Error|WP_REST_Response {
		// phpcs:disable WordPress.NamingConventions
		$service         = new MollieClient();
		$betaling        = $service->get_payment( (string) $request->get_param( 'id' ) );
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->get_object( $betaling->metadata->order_id );
		if ( is_null( $artikel ) ) {
			fout( __CLASS__, 'onbekende betaling ' . $betaling->metadata->order_id );
			return new WP_Error( 'onbekend', 'betaling niet herkend' );
		}
		if ( ! $betaling->hasRefunds() && ! $betaling->hasChargebacks() ) {
			$order = new Order( $betaling->metadata->order_id );
			$this->payment( $betaling, $artikel, $order );
		}
		if ( $betaling->hasRefunds() ) {
			$order = new Order( $betaling->id );
			$this->refunds( $betaling, $artikel, $order );
		} elseif ( $betaling->hasChargebacks() ) {
			$order = new Order( $betaling->id );
			$this->chargebacks( $betaling, $artikel, $order );
		}
		return new WP_REST_Response(); // Geeft default http status 200 terug.
	}

	/**
	 * Verwerk de betaling
	 *
	 * @param object  $betaling Het mollie betaal object.
	 * @param Artikel $artikel  Het artikel waarop de betaling betrekking heeft.
	 * @param Order   $order    De order.
	 * @return void
	 */
	private function payment( object $betaling, Artikel $artikel, Order $order ) : void {
		$artikel->betaling->verwerk(
			$order,
			$betaling->amount->value,
			$betaling->isPaid(),
			$betaling->method,
			$betaling->id
		);
	}

	/**
	 * Verwerk refunds
	 *
	 * @param object  $betaling Het mollie betaal object.
	 * @param Artikel $artikel  Het artikel waarop de refund betrekking heeft.
	 * @param Order   $order    De order.
	 * @return void
	 */
	private function refunds( object $betaling, Artikel $artikel, Order $order ) : void {
		$transient  = $betaling->id . self::REFUNDS;
		$refund_ids = get_transient( $transient ) ?: [];
		foreach ( $betaling->refunds() as $refund ) {
			if ( in_array( $refund->id, $refund_ids, true ) ) {
				$artikel->betaling->verwerk(
					$order,
					- $refund->amount->value,
					'failed' !== $refund->status,
					$betaling->method,
					$betaling->id
				);
				unset( $refund_ids[ $refund->id ] );
			}
		}
		if ( count( $refund_ids ) ) {
			set_transient( $transient, $refund_ids, $this->expiratie( $betaling->createdAt ) );
			return;
		}
		delete_transient( $transient );
	}

	/**
	 * Verwerk chargebacks
	 *
	 * @param object  $betaling Het mollie betaal object.
	 * @param Artikel $artikel  Het artikel waarop de chargeback betrekking heeft.
	 * @param Order   $order    De order.
	 * @return void
]	 */
	private function chargebacks( object $betaling, Artikel $artikel, Order $order ) : void {
		$transient      = $betaling->id . self::CHARGEBACKS;
		$chargeback_ids = get_transient( $transient ) ?: [];
		foreach ( $betaling->chargebacks() as $chargeback ) {
			if ( ! in_array( $chargeback->id, $chargeback_ids, true ) ) {
				$artikel->betaling->verwerk(
					$order,
					- $chargeback->amount->value,
					$betaling->isPaid(),
					$betaling->method,
					$betaling->id
				);
				$chargeback_ids[] = $chargeback->id;
			}
		}
		set_transient( $transient, $chargeback_ids, $this->expiratie( $betaling->createdAt ) );
	}

	/**
	 * Na 13 maanden expiratie transient.
	 *
	 * @param string $timestamp De starttijd.
	 * @return integer
	 */
	private function expiratie( string $timestamp ) : int {
		return 13 * MONTH_IN_SECONDS - ( time() - strtotime( $timestamp ) );
	}
}
