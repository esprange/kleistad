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

/**
 * Definitie van de betalen class.
 */
class Betalen {

	const MOLLIE_ID   = 'mollie_customer_id';
	const QUERY_PARAM = 'betaling';

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
			$this->mollie = new \Kleistad\MollieSimulatie();
			return;
		}
		$setup        = \Kleistad\Kleistad::get_setup();
		$this->mollie = new \Mollie\Api\MollieApiClient();

		if ( '1' === $setup['betalen'] ) {
			if ( '' !== $setup['sleutel'] ) {
				$this->mollie->setApiKey( $setup['sleutel'] );
			}
		} else {
			if ( '' !== $setup['sleutel_test'] ) {
				$this->mollie->setApiKey( $setup['sleutel_test'] );
			}
		}
	}

	/**
	 * Register rest URI's.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			Public_Main::api(),
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
	 * @param string    $order_id     de externe order referentie, maximaal 35 karakters.
	 * @param float     $bedrag       het bedrag.
	 * @param string    $beschrijving de externe order beschrijving, maximaal 35 karakters.
	 * @param string    $bericht      het bericht bij succesvolle betaling.
	 * @param bool      $mandateren   er wordt een herhaalde betaling voorbereid.
	 * @return bool|string De redirect bestemming of false.
	 */
	public function order( $klant, $order_id, $bedrag, $beschrijving, $bericht, $mandateren = false ) {
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
			$uniqid   = uniqid();
			$betaling = $mollie_gebruiker->createPayment(
				[
					'amount'       => [
						'currency' => 'EUR',
						'value'    => number_format( $bedrag, 2, '.', '' ),
					],
					'description'  => $beschrijving,
					'issuer'       => $bank,
					'metadata'     => [
						'order_id' => $order_id,
						'bericht'  => $bericht,
					],
					'method'       => \Mollie\Api\Types\PaymentMethod::IDEAL,
					'sequenceType' => $mandateren ? \Mollie\Api\Types\SequenceType::SEQUENCETYPE_FIRST : \Mollie\Api\Types\SequenceType::SEQUENCETYPE_ONEOFF,
					'redirectUrl'  => add_query_arg( self::QUERY_PARAM, $uniqid, \Kleistad\ShortcodeForm::get_url() ),
					'webhookUrl'   => \Kleistad\Public_Main::base_url() . '/betaling/',
				]
			);
			set_transient( $uniqid, $betaling->id );
			return $betaling->getCheckOutUrl();
		} catch ( \Exception $e ) {
			error_log( 'Controleer betaling fout: ' . $e->getMessage() ); // phpcs:ignore
			return false;
		}
	}

	/**
	 * Controleer of de order gelukt is.
	 *
	 * @return \WP_ERROR | string | bool De status van de betaling als tekst, WP_error of mislukts of false als er geen betaling is.
	 */
	public static function controleer() {
		$mollie_betaling_id = false;
		$uniqid             = filter_input( INPUT_GET, self::QUERY_PARAM );
		if ( ! is_null( $uniqid ) ) {
			$mollie_betaling_id = get_transient( $uniqid );
			delete_transient( $uniqid );
		}
		if ( false === $mollie_betaling_id ) {
			return false;
		}
		$object = new static();
		try {
			$betaling = $object->mollie->payments->get( $mollie_betaling_id );
			if ( $betaling->isPaid() ) {
				return $betaling->metadata->bericht;
			} elseif ( $betaling->isFailed() ) {
				return new \WP_Error( 'betalen', 'De betaling heeft niet kunnen plaatsvinden. Probeer het opnieuw.' );
			} elseif ( $betaling->isExpired() ) {
				return new \WP_Error( 'betalen', 'De betaling is verlopen. Probeer het opnieuw.' );
			} elseif ( $betaling->isCanceled() ) {
				return new \WP_Error( 'betalen', 'De betaling is geannuleerd. Probeer het opnieuw.' );
			} else {
				return new \WP_Error( 'betalen', 'De betaling is waarschijnlijk mislukt. Controleer s.v.p. de status van de bankrekening en neem eventueel contact op met Kleistad.' );
			}
		} catch ( \Exception $e ) {
			error_log( 'Controleer betaling fout: ' . $e->getMessage() ); // phpcs:ignore
			return false;
		}
	}

	/**
	 * Doe een eenmalige order bij een gebruiker waarvan al een mandaat bestaat.
	 *
	 * @param int    $gebruiker_id Het wp gebruiker_id.
	 * @param string $order_id     de externe order referentie, maximaal 35 karakters.
	 * @param float  $bedrag       Het te betalen bedrag.
	 * @param string $beschrijving De beschrijving bij de betaling.
	 */
	public function eenmalig( $gebruiker_id, $order_id, $bedrag, $beschrijving ) {
		$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );
		if ( '' !== $mollie_gebruiker_id ) {
			try {
				$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
				$mollie_gebruiker->createPayment(
					[
						'amount'       => [
							'currency' => 'EUR',
							'value'    => number_format( $bedrag, 2, '.', '' ),
						],
						'metadata'     => [
							'order_id' => $order_id,
						],
						'description'  => $beschrijving,
						'sequenceType' => \Mollie\Api\Types\SequenceType::SEQUENCETYPE_RECURRING,
						'webhookUrl'   => \Kleistad\Public_Main::base_url() . '/betaling/',
					]
				);
			} catch ( \Exception $e ) {
				error_log( $e->getMessage() ); // phpcs:ignore
			}
		}
	}

	/**
	 * Stort een eerder bedrag (deels) terug.
	 *
	 * @param string $mollie_betaling_id Het id van de oorspronkelijke betaling.
	 * @param string $order_id           De externe referentie.
	 * @param float  $bedrag             Het terug te storten bedrag.
	 * @param string $beschrijving       De externe beschrijving van de opdracht.
	 */
	public function terugstorting( $mollie_betaling_id, $order_id, $bedrag, $beschrijving ) {
		$betaling = $this->mollie->payments->get( $mollie_betaling_id );
		$value    = number_format( $bedrag, 2, '.', '' );
		if ( $betaling->canBeRefunded() && 'EUR' === $betaling->amountRemaining->currency && $betaling->amountRemaining->value >= $value ) { //phpcs:ignore WordPress.NamingConventions
			$betaling->refund(
				[
					'amount'      => [
						'currency' => 'EUR',
						'value'    => $value,
					],
					'metadata'    => [
						'order_id' => $order_id,
					],
					'description' => $beschrijving,
				]
			);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Test of er een refund actief is.
	 *
	 * @param string $mollie_betaling_id De transactie id.
	 */
	public function terugstorting_actief( $mollie_betaling_id ) {
		$betaling = $this->mollie->payments->get( $mollie_betaling_id );
		foreach ( $betaling->refunds() as $refund ) {
			if ( false !== strpos( 'queued pending processing', $refund->status ) ) {
				return true;
			}
		}
		return false; // Status is failed of refunded.
	}

	/**
	 * Test of de gebruiker een mandaat heeft afgegeven.
	 *
	 * @param int $gebruiker_id De gebruiker waarvoor getest wordt of deze mandaat heeft.
	 */
	public function heeft_mandaat( $gebruiker_id ) {
		$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );

		try {
			if ( '' !== $mollie_gebruiker_id ) {
				$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
				return $mollie_gebruiker->hasValidMandate();
			}
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore
		}
		return false;
	}

	/**
	 * Verwijder mandaten.
	 *
	 * @param int $gebruiker_id De gebruiker waarvoor mandaten verwijderd moeten worden.
	 * @return boolean
	 */
	public function verwijder_mandaat( $gebruiker_id ) {
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
		} catch ( \Exception $e ) {
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
		$method = $object->mollie->methods->get( \Mollie\Api\Types\PaymentMethod::IDEAL, [ 'include' => 'issuers' ] );
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
	public static function info( $gebruiker_id ) {
		$object              = new static();
		$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );
		if ( '' !== $mollie_gebruiker_id ) {
			try {
				$html             = 'Mollie info: ';
				$mollie_gebruiker = $object->mollie->customers->get( $mollie_gebruiker_id );
				$mandaten         = $mollie_gebruiker->mandates();
				foreach ( $mandaten as $mandaat ) {
					if ( $mandaat->isValid() ) {
						$html .= "Er is op {$mandaat->signatureDate} een geldig mandaat afgegeven om incasso te doen vanaf bankrekening {$mandaat->details->consumerAccount} op naam van {$mandaat->details->consumerName}. ";
					}
				}
				return $html;
			} catch ( \Exception $e ) {
				error_log( $e->getMessage() ); // phpcs:ignore
				return '';
			}
		}
		return '';
	}

	/**
	 * Webhook functie om betaling status te verwerken. Wordt aangeroepen door Mollie.
	 *
	 * @param \WP_REST_Request $request het request.
	 * @return \WP_REST_Response de response.
	 */
	public static function callback_betaling_verwerkt( \WP_REST_Request $request ) {
		$mollie_betaling_id = $request->get_param( 'id' );
		$object             = new static();
		$betaling           = $object->mollie->payments->get( $mollie_betaling_id );
		$artikel            = \Kleistad\Artikel::get_artikel( $betaling->metadata->order_id );
		$order_id           = \Kleistad\Order::zoek_order( $betaling->metadata->order_id );
		if ( $betaling->hasRefunds() ) {
			foreach ( $betaling->refunds() as $refund ) {
				if ( 'refunded' === $refund->status ) {
					$artikel->verwerk_betaling(
						$order_id,
						- $refund->amount->value,
						true,
						$betaling->method,
						$mollie_betaling_id
					);
				} elseif ( 'failed' === $refund->status ) {
					$emailer = new \Kleistad\Email();
					$emailer->send(
						[
							'to'      => 'Boekhouding <info@' . \Kleistad\Email::domein() . '>',
							'subject' => 'terugstorting niet mogelijk',
							'content' => "<p>Mollie heeft aangegeven dat voor {$betaling->metadata->order_id} de terugstorting van {$refund->amount->value} niet mogelijk was</p>",
							'sign'    => 'Mollie',
						]
					);
				}
			}
		} else {
			$artikel->verwerk_betaling(
				$order_id,
				$betaling->amount->value,
				$betaling->isPaid(),
				$betaling->method,
				$mollie_betaling_id
			);
		}
		return new \WP_REST_Response(); // Geeft default http status 200 terug.
	}
}
