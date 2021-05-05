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

use WP_REST_Response;
use WP_REST_Request;
use WP_ERROR;
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
	 * @return WP_ERROR | string | bool De status van de betaling als tekst, WP_error of mislukts of false als er geen betaling is.
	 */
	public function controleer() {
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
			error_log( 'Controleer betaling fout: ' . $e->getMessage() ); // phpcs:ignore
			return false;
		}
	}

	/**
	 * Webhook functie om betaling status te verwerken. Wordt aangeroepen door Mollie.
	 *
	 * @param WP_REST_Request $request het request.
	 * @return WP_REST_Response|WP_Error de response.
	 */
	public function callback_betaling_verwerkt( WP_REST_Request $request ) {
		// phpcs:disable WordPress.NamingConventions
		$service         = new MollieClient();
		$betaling        = $service->get_payment( (string) $request->get_param( 'id' ) );
		$order           = new Order( $betaling->metadata->order_id );
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->geef_object( $betaling->metadata->order_id );
		if ( is_null( $artikel ) ) {
			error_log( 'onbekende betaling ' . $betaling->metadata->order_id ); // phpcs:ignore
			return new WP_Error( 'onbekend', 'betaling niet herkend' );
		}
		if ( ! $betaling->hasRefunds() && ! $betaling->hasChargebacks() ) {
			$this->payment( $betaling, $artikel, $order->id );
		}
		if ( $betaling->hasRefunds() ) {
			$this->refunds( $betaling, $artikel, $order->id );
		} elseif ( $betaling->hasChargebacks() ) {
			$this->chargebacks( $betaling, $artikel, $order->id );
		}
		return new WP_REST_Response(); // Geeft default http status 200 terug.
	}

	/**
	 * Verwerk de betaling
	 *
	 * @param object  $betaling Het mollie betaal object.
	 * @param Artikel $artikel  Het artikel waarop de betaling betrekking heeft.
	 * @param integer $order_id Het order id.
	 * @return void
	 */
	private function payment( object $betaling, Artikel $artikel, int $order_id ) {
		$artikel->betaling->verwerk(
			$order_id,
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
	 * @param integer $order_id Het order id.
	 * @return void
	 */
	private function refunds( object $betaling, Artikel $artikel, int $order_id ) {
		$transient  = $betaling->id . self::REFUNDS;
		$refund_ids = get_transient( $transient ) ?: [];
		foreach ( $betaling->refunds() as $refund ) {
			if ( in_array( $refund->id, $refund_ids, true ) ) {
				$artikel->betaling->verwerk(
					$order_id,
					- $refund->amount->value,
					'failed' !== $refund->status,
					$betaling->method,
					$betaling->id
				);
				unset( $refund_ids[ $refund->id ] );
			}
		}
		set_transient( $transient, $refund_ids, $this->expiratie( $betaling->createdAt ) );
	}

	/**
	 * Verwerk chargebacks
	 *
	 * @param object  $betaling Het mollie betaal object.
	 * @param Artikel $artikel  Het artikel waarop de chargeback betrekking heeft.
	 * @param integer $order_id Het order id.
	 * @return void
	 */
	private function chargebacks( object $betaling, Artikel $artikel, int $order_id ) {
		$transient      = $betaling->id . self::CHARGEBACKS;
		$chargeback_ids = get_transient( $transient ) ?: [];
		foreach ( $betaling->chargebacks() as $chargeback ) {
			if ( ! in_array( $chargeback->id, $chargeback_ids, true ) ) {
				$artikel->betaling->verwerk(
					$order_id,
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
