<?php
/**
 * Interface class naar Mollie betalen.
 *
 * @link       https://www.kleistad.nl
 * @since      4.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Mollie;
use Exception;

/**
 * Definitie van de betalen class.
 */
class Betalen {

	const QUERY_PARAM = 'betaling';

	/**
	 * Bereid de order informatie voor.
	 *
	 * @param int|array $klant        klant waarvoor de betaling wordt uitgevoerd (WordPress id of array order/naam/email).
	 * @param string    $referentie   de externe order referentie, maximaal 35 karakters.
	 * @param float     $bedrag       het bedrag.
	 * @param string    $beschrijving de externe order beschrijving, maximaal 35 karakters.
	 * @param string    $bericht      het bericht bij succesvolle betaling.
	 * @param bool      $mandateren   er wordt een herhaalde betaling voorbereid.
	 * @return bool|string De redirect bestemming of false.
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	public function order( $klant, string $referentie, float $bedrag, string $beschrijving, string $bericht, bool $mandateren ) {
		$bank    = filter_input( INPUT_POST, 'bank', FILTER_SANITIZE_STRING, [ 'options' => [ 'default' => null ] ] );
		$service = new MollieClient();
		// Registreer de gebruiker in Mollie en het id in WordPress als er een mandaat nodig is.
		try {
			$mollie_gebruiker = $service->get_client( $klant );
			$uniqid           = 'kleistad_' . bin2hex( random_bytes( 6 ) );
			$betaling         = $mollie_gebruiker->createPayment(
				[
					'amount'       => [
						'currency' => 'EUR',
						'value'    => number_format( $bedrag, 2, '.', '' ),
					],
					'description'  => $beschrijving,
					'issuer'       => $bank,
					'metadata'     => [
						'order_id' => $referentie,
						'bericht'  => $bericht,
					],
					'method'       => Mollie\Api\Types\PaymentMethod::IDEAL,
					'sequenceType' => $mandateren ? Mollie\Api\Types\SequenceType::SEQUENCETYPE_FIRST : Mollie\Api\Types\SequenceType::SEQUENCETYPE_ONEOFF,
					'redirectUrl'  => add_query_arg( self::QUERY_PARAM, $uniqid, wp_get_referer() ),
					'webhookUrl'   => base_url() . '/betaling/',
				]
			);
			set_transient( $uniqid, $betaling->id, 20 * MINUTE_IN_SECONDS ); // 20 minuten expiry (iDeal heeft in Mollie een expiratie van 15 minuten).
			return $betaling->getCheckOutUrl();
		} catch ( Exception $e ) {
			error_log( 'Controleer betaling fout: ' . $e->getMessage() ); // phpcs:ignore
			return false;
		}
	}

	/**
	 * Doe een eenmalige order bij een gebruiker waarvan al een mandaat bestaat.
	 *
	 * @param int    $gebruiker_id Het wp gebruiker_id.
	 * @param string $referentie   De externe order referentie, maximaal 35 karakters.
	 * @param float  $bedrag       Het te betalen bedrag.
	 * @param string $beschrijving De beschrijving bij de betaling.
	 * @return string De transactie_id.
	 */
	public function eenmalig( int $gebruiker_id, string $referentie, float $bedrag, string $beschrijving ) : string {
		$service          = new MollieClient();
		$mollie_gebruiker = $service->get_client( $gebruiker_id );
		try {
			$betaling = $mollie_gebruiker->createPayment(
				[
					'amount'       => [
						'currency' => 'EUR',
						'value'    => number_format( $bedrag, 2, '.', '' ),
					],
					'metadata'     => [
						'order_id' => $referentie,
					],
					'description'  => $beschrijving,
					'sequenceType' => Mollie\Api\Types\SequenceType::SEQUENCETYPE_RECURRING,
					'webhookUrl'   => base_url() . '/betaling/',
				]
			);
			return $betaling->id;
		} catch ( Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore
		}
		return '';
	}

	/**
	 * Stort een eerder bedrag (deels) terug.
	 *
	 * @param string $mollie_betaling_id Het id van de oorspronkelijke betaling.
	 * @param string $referentie         De externe referentie.
	 * @param float  $bedrag             Het terug te storten bedrag.
	 * @param string $beschrijving       De externe beschrijving van de opdracht.
	 * @return bool
	 */
	public function terugstorting( string $mollie_betaling_id, string $referentie, float $bedrag, string $beschrijving ) : bool {
		$service  = new MollieClient();
		$betaling = $service->get_payment( $mollie_betaling_id );
		$value    = number_format( $bedrag, 2, '.', '' );
		if ( $betaling->canBeRefunded() && 'EUR' === $betaling->amountRemaining->currency && $betaling->amountRemaining->value >= $value ) { //phpcs:ignore WordPress.NamingConventions
			$refund       = $betaling->refund(
				[
					'amount'      => [
						'currency' => 'EUR',
						'value'    => $value,
					],
					'metadata'    => [
						'order_id' => $referentie,
					],
					'description' => $beschrijving,
				]
			);
			$transient    = $betaling->id . Ontvangen::REFUNDS;
			$refund_ids   = get_transient( $transient ) ?: [];
			$refund_ids[] = $refund->id;
			set_transient( $transient, $refund_ids );
			return true;
		}
		return false;
	}

	/**
	 * Test of er een refund actief is.
	 *
	 * @param string $mollie_betaling_id De transactie id.
	 * @return bool
	 */
	public function terugstorting_actief( string $mollie_betaling_id ) : bool {
		return ! empty( get_transient( $mollie_betaling_id . Ontvangen::REFUNDS ) );
	}

	/**
	 * Test of de gebruiker een mandaat heeft afgegeven.
	 *
	 * @param int $gebruiker_id De gebruiker waarvoor getest wordt of deze mandaat heeft.
	 * @return bool
	 */
	public function heeft_mandaat( int $gebruiker_id ) : bool {
		$service          = new MollieClient();
		$mollie_gebruiker = $service->get_client( $gebruiker_id );
		try {
			return $mollie_gebruiker->hasValidMandate();
		} catch ( Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore
		}
		return false;
	}

	/**
	 * Verwijder mandaten.
	 *
	 * @param int $gebruiker_id De gebruiker waarvoor mandaten verwijderd moeten worden.
	 * @return bool
	 */
	public function verwijder_mandaat( int $gebruiker_id ) : bool {
		$service          = new MollieClient();
		$mollie_gebruiker = $service->get_client( $gebruiker_id );
		try {
			$mandaten = $mollie_gebruiker->mandates();
			foreach ( $mandaten as $mandaat ) {
				if ( $mandaat->isValid() ) {
					$mollie_gebruiker->revokeMandate( $mandaat->id );
				}
			}
			return true;
		} catch ( Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore
		}
		return false;
	}

	/**
	 * Geef informatie terug van mollie over de klant
	 *
	 * @param int $gebruiker_id De gebruiker waarvan de informatie wordt opgevraagd.
	 * @return string leeg als de gebruiker onbekend is of string met opgemaakte HTML text.
	 */
	public function info( int $gebruiker_id ) : string {
		$service          = new MollieClient();
		$mollie_gebruiker = $service->get_client( $gebruiker_id );
		try {
			$html = 'Mollie info: ';
			foreach ( $mollie_gebruiker->mandates() as $mandaat ) {
				if ( $mandaat->isValid() ) {
					$html .= "Er is op $mandaat->signatureDate een geldig mandaat afgegeven om incasso te doen vanaf bankrekening {$mandaat->details->consumerAccount} op naam van {$mandaat->details->consumerName}. ";
				}
			}
			return $html;
		} catch ( Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore
		}
		return '';
	}

}
