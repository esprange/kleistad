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

use WP_REST_Response;
use WP_REST_Request;
use WP_ERROR;
use Mollie;
use Exception;

/**
 * Definitie van de betalen class.
 */
class Betalen {

	const MOLLIE_ID   = 'mollie_customer_id';
	const QUERY_PARAM = 'betaling';
	const REFUNDS     = '_refunds';
	const CHARGEBACKS = '_chargebacks';

	/**
	 * Het mollie object.
	 *
	 * @var object het mollie service object.
	 */
	private $mollie;

	/**
	 * De constructor
	 */
	public function __construct() {
		if ( defined( 'KLEISTAD_MOLLIE_SIM' ) ) {
			$this->mollie = new MollieSimulatie();
			return;
		}
		$setup        = setup();
		$this->mollie = new Mollie\Api\MollieApiClient();

		if ( '1' === $setup['betalen'] ) {
			if ( '' !== $setup['sleutel'] ) {
				$this->mollie->setApiKey( $setup['sleutel'] );
			}
			return;
		}
		if ( '' !== $setup['sleutel_test'] ) {
			$this->mollie->setApiKey( $setup['sleutel_test'] );
		}
	}

	/**
	 * Register rest URI's.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			KLEISTAD_API,
			'/betaling',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'callback_betaling_verwerkt' ],
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
		$bank = filter_input( INPUT_POST, 'bank', FILTER_SANITIZE_STRING, [ 'options' => [ 'default' => null ] ] );
		// Registreer de gebruiker in Mollie en het id in WordPress als er een mandaat nodig is.
		try {
			if ( ! is_array( $klant ) ) {
				$gebruiker_id        = $klant;
				$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );
				if ( '' === $mollie_gebruiker_id || is_null( $mollie_gebruiker_id ) ) {
					$gebruiker           = get_userdata( $gebruiker_id );
					$mollie_gebruiker    = $this->mollie->customers->create(
						[
							'name'  => $gebruiker->display_name,
							'email' => $gebruiker->user_email,
						]
					);
					$mollie_gebruiker_id = $mollie_gebruiker->id;
					update_user_meta( $gebruiker_id, self::MOLLIE_ID, $mollie_gebruiker_id );
				}
				$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
			} else {
				$mollie_gebruiker = $this->mollie->customers->create(
					[
						'name'  => $klant['naam'],
						'email' => $klant['email'],
					]
				);
			}
			$uniqid   = 'kleistad_' . bin2hex( random_bytes( 6 ) );
			$betaling = $mollie_gebruiker->createPayment(
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
	 * Controleer of de order gelukt is.
	 *
	 * @return WP_ERROR | string | bool De status van de betaling als tekst, WP_error of mislukts of false als er geen betaling is.
	 */
	public function controleer() {
		$mollie_betaling_id = false;
		$uniqid             = filter_input( INPUT_GET, self::QUERY_PARAM );
		if ( ! is_null( $uniqid ) ) {
			$mollie_betaling_id = get_transient( $uniqid );
			delete_transient( $uniqid );
		}
		if ( false === $mollie_betaling_id ) {
			return false;
		}
		try {
			$betaling = $this->mollie->payments->get( $mollie_betaling_id );
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
	 * Doe een eenmalige order bij een gebruiker waarvan al een mandaat bestaat.
	 *
	 * @param int    $gebruiker_id Het wp gebruiker_id.
	 * @param string $referentie   De externe order referentie, maximaal 35 karakters.
	 * @param float  $bedrag       Het te betalen bedrag.
	 * @param string $beschrijving De beschrijving bij de betaling.
	 * @return string De transactie_id.
	 */
	public function eenmalig( int $gebruiker_id, string $referentie, float $bedrag, string $beschrijving ) : string {
		$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );
		if ( '' !== $mollie_gebruiker_id ) {
			try {
				$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
				$betaling         = $mollie_gebruiker->createPayment(
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
		$betaling = $this->mollie->payments->get( $mollie_betaling_id );
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
			$transient    = $mollie_betaling_id . self::REFUNDS;
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
		return ! empty( get_transient( $mollie_betaling_id . self::REFUNDS ) );
	}

	/**
	 * Test of de gebruiker een mandaat heeft afgegeven.
	 *
	 * @param int $gebruiker_id De gebruiker waarvoor getest wordt of deze mandaat heeft.
	 * @return bool
	 */
	public function heeft_mandaat( int $gebruiker_id ) : bool {
		$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );

		try {
			if ( '' !== $mollie_gebruiker_id ) {
				$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
				return $mollie_gebruiker->hasValidMandate();
			}
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
		$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );

		try {
			if ( '' !== $mollie_gebruiker_id ) {
				$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
				$mandaten         = $mollie_gebruiker->mandates();
				foreach ( $mandaten as $mandaat ) {
					if ( $mandaat->isValid() ) {
						$mollie_gebruiker->revokeMandate( $mandaat->id );
					}
				}
				return true;
			}
		} catch ( Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore
		}
		return false;
	}

	/**
	 * Toon deelnemende banken.
	 */
	public static function issuers() {
		$object = new static();
		?>
	<img src="<?php echo esc_url( plugins_url( '../public/images/iDEAL_48x48.png', __FILE__ ) ); ?>" alt="iDEAL" style="padding-left:40px"/>
	<strong>Mijn bank:&nbsp;</strong>
	<select name="bank" id="kleistad_bank" style="padding-left:15px;width: 200px;font-weight:normal">
		<option value="" >&nbsp;</option>
		<?php
		$method = $object->mollie->methods->get( Mollie\Api\Types\PaymentMethod::IDEAL, [ 'include' => 'issuers' ] );
		foreach ( $method->issuers() as $issuer ) :
			?>
			<option value="<?php echo esc_attr( $issuer->id ); ?>"><?php echo esc_html( $issuer->name ); ?></option>
			<?php
		endforeach
		?>
	</select>
		<?php
	}

	/**
	 * Geef informatie terug van mollie over de klant
	 *
	 * @param int $gebruiker_id De gebruiker waarvan de informatie wordt opgevraagd.
	 * @return string leeg als de gebruiker onbekend is of string met opgemaakte HTML text.
	 */
	public function info( int $gebruiker_id ) : string {
		$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );
		if ( '' !== $mollie_gebruiker_id ) {
			try {
				$html             = 'Mollie info: ';
				$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
				$mandaten         = $mollie_gebruiker->mandates();
				foreach ( $mandaten as $mandaat ) {
					if ( $mandaat->isValid() ) {
						$html .= "Er is op {$mandaat->signatureDate} een geldig mandaat afgegeven om incasso te doen vanaf bankrekening {$mandaat->details->consumerAccount} op naam van {$mandaat->details->consumerName}. ";
					}
				}
				return $html;
			} catch ( Exception $e ) {
				error_log( $e->getMessage() ); // phpcs:ignore
			}
		}
		return '';
	}

	/**
	 * Webhook functie om betaling status te verwerken. Wordt aangeroepen door Mollie.
	 *
	 * @param WP_REST_Request $request het request.
	 * @return WP_REST_Response|WP_Error de response.
	 */
	public static function callback_betaling_verwerkt( WP_REST_Request $request ) {
		// phpcs:disable WordPress.NamingConventions
		$mollie_betaling_id = (string) $request->get_param( 'id' );
		$object             = new static();
		$betaling           = $object->mollie->payments->get( $mollie_betaling_id );
		$expiratie          = 13 * MONTH_IN_SECONDS - ( time() - strtotime( $betaling->createdAt ) );  // Na 13 maanden expiratie transient.
		$order              = new Order( $betaling->metadata->order_id );
		$artikelregister    = new Artikelregister();
		$artikel            = $artikelregister->geef_object( $betaling->metadata->order_id );
		if ( is_null( $artikel ) ) {
			error_log( 'onbekende betaling ' . $betaling->metadata->order_id ); // phpcs:ignore
			return new WP_Error( 'onbekend', 'betaling niet herkend' );
		}
		if ( ! $betaling->hasRefunds() && ! $betaling->hasChargebacks() ) {
			$artikel->betaling->verwerk(
				$order->id,
				$betaling->amount->value,
				$betaling->isPaid(),
				$betaling->method,
				$mollie_betaling_id
			);
		}
		if ( $betaling->hasRefunds() ) {
			$transient  = $mollie_betaling_id . self::REFUNDS;
			$refund_ids = get_transient( $transient ) ?: [];
			foreach ( $betaling->refunds() as $refund ) {
				if ( in_array( $refund->id, $refund_ids, true ) ) {
					$artikel->betaling->verwerk(
						$order->id,
						- $refund->amount->value,
						'failed' !== $refund->status,
						$betaling->method,
						$mollie_betaling_id
					);
					unset( $refund_ids[ $refund->id ] );
				}
			}
			set_transient( $transient, $refund_ids, $expiratie );
		} elseif ( $betaling->hasChargebacks() ) {
			$transient      = $mollie_betaling_id . self::CHARGEBACKS;
			$chargeback_ids = get_transient( $transient ) ?: [];
			foreach ( $betaling->chargebacks() as $chargeback ) {
				if ( ! in_array( $chargeback->id, $chargeback_ids, true ) ) {
					$artikel->betaling->verwerk(
						$order->id,
						- $chargeback->amount->value,
						$betaling->isPaid(),
						$betaling->method,
						$mollie_betaling_id
					);
					$chargeback_ids[] = $chargeback->id;
				}
			}
			set_transient( $transient, $chargeback_ids, $expiratie );
		}
		return new WP_REST_Response(); // Geeft default http status 200 terug.
	}
}
