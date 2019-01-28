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

/**
 * Definitie van de betalen class.
 */
class Kleistad_Betalen {

	const MOLLIE_ID       = 'mollie_customer_id';
	const MOLLIE_BETALING = 'mollie_betaling';
	const MOLLIE_AD_HOC   = 'mollie_ad_hoc';

	/**
	 * Het mollie object.
	 *
	 * @since      4.2.0
	 *
	 * @var object het mollie service object.
	 */
	private $mollie;

	/**
	 * De constructor
	 *
	 * @since      4.2.0
	 */
	public function __construct() {
		$options = Kleistad::get_options();

		$this->mollie = new \Mollie\Api\MollieApiClient();

		if ( '1' === $options['betalen'] ) {
			if ( '' !== $options['sleutel'] ) {
				$this->mollie->setApiKey( $options['sleutel'] );
			}
		} else {
			if ( '' !== $options['sleutel_test'] ) {
				$this->mollie->setApiKey( $options['sleutel_test'] );
			}
		}
	}

	/**
	 * Register rest URI's.
	 *
	 * @since 4.5.3
	 */
	public static function register_rest_routes() {
		register_rest_route(
			Kleistad_Public::url(),
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
		register_rest_route(
			Kleistad_Public::url(),
			'/betaling/herhaal',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'callback_herhaalbetaling_verwerkt' ],
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

		register_rest_route(
			Kleistad_Public::url(),
			'/betaling/ondemand',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'callback_ondemandbetaling_verwerkt' ],
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
	 * @since      4.2.0
	 *
	 * @param int|array $referentie   referentie waarvoor de betaling wordt uitgevoerd (WordPress id of array order/naam/email).
	 * @param string    $order_id     de externe order referentie, maximaal 35 karakters.
	 * @param float     $bedrag       het bedrag.
	 * @param string    $beschrijving de externe order referentie, maximaal 35 karakters.
	 * @param string    $bericht      het bericht bij succesvolle betaling.
	 * @param bool      $mandateren   er wordt een herhaalde betaling voorbereid.
	 */
	public function order( $referentie, $order_id, $bedrag, $beschrijving, $bericht, $mandateren = false ) {
		$bank = filter_input( INPUT_POST, 'bank', FILTER_SANITIZE_STRING );

		// Registreer de gebruiker in Mollie en het id in WordPress als er een mandaat nodig is.
		if ( ! is_array( $referentie ) ) {
			$gebruiker_id        = $referentie;
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
			if ( $mandateren ) {
				$betaling = $mollie_gebruiker->createPayment(
					[
						'amount'       => [
							'currency' => 'EUR',
							'value'    => number_format( $bedrag, 2, '.', '' ),
						],
						'description'  => $beschrijving,
						'issuer'       => ! empty( $bank ) ? $bank : null,
						'metadata'     => [
							'order_id' => $order_id,
							'bericht'  => $bericht,
						],
						'method'       => \Mollie\Api\Types\PaymentMethod::IDEAL,
						'sequenceType' => \Mollie\Api\Types\SequenceType::SEQUENCETYPE_FIRST,
						'redirectUrl'  => add_query_arg( 'gebruiker_id', $gebruiker_id, get_permalink() ),
						'webhookUrl'   => Kleistad_Public::base_url() . '/betaling/',
					]
				);
			} else {
				$betaling = $mollie_gebruiker->createPayment(
					[
						'amount'       => [
							'currency' => 'EUR',
							'value'    => number_format( $bedrag, 2, '.', '' ),
						],
						'description'  => $beschrijving,
						'issuer'       => ! empty( $bank ) ? $bank : null,
						'metadata'     => [
							'order_id' => $order_id,
							'bericht'  => $bericht,
						],
						'method'       => \Mollie\Api\Types\PaymentMethod::IDEAL,
						'sequenceType' => \Mollie\Api\Types\SequenceType::SEQUENCETYPE_ONEOFF,
						'redirectUrl'  => add_query_arg( 'gebruiker_id', $gebruiker_id, get_permalink() ),
						'webhookUrl'   => Kleistad_Public::base_url() . '/betaling/',
					]
				);
			}
			update_user_meta( $gebruiker_id, self::MOLLIE_BETALING, $betaling->id );
		} else {
			$mollie_gebruiker = $this->mollie->customers->create(
				[
					'name'  => $referentie['naam'],
					'email' => $referentie['email'],
				]
			);
			$betaling         = $mollie_gebruiker->createPayment(
				[
					'amount'       => [
						'currency' => 'EUR',
						'value'    => number_format( $bedrag, 2, '.', '' ),
					],
					'description'  => $beschrijving,
					'issuer'       => ! empty( $bank ) ? $bank : null,
					'metadata'     => [
						'order_id' => $order_id,
						'bericht'  => $bericht,
					],
					'method'       => \Mollie\Api\Types\PaymentMethod::IDEAL,
					'sequenceType' => \Mollie\Api\Types\SequenceType::SEQUENCETYPE_ONEOFF,
					'redirectUrl'  => add_query_arg( 'order_id', $referentie['order_id'], get_permalink() ),
					'webhookUrl'   => Kleistad_Public::base_url() . '/betaling/',
				]
			);
			$order_betalingen = get_option( self::MOLLIE_AD_HOC );
			if ( false === $order_betalingen ) {
				$order_betalingen = [];
			}
			$order_betalingen[ $referentie['order_id'] ] = $betaling->id;
			update_option( self::MOLLIE_AD_HOC, $order_betalingen );
		}
		wp_redirect( $betaling->getCheckOutUrl(), 303 );
		exit;
	}

	/**
	 * Eenmalige betaling, op basis van eerder verkregen mandaat.
	 *
	 * @since      4.2.0
	 *
	 * @param int    $gebruiker_id Het wp gebruiker_id.
	 * @param float  $bedrag       Het te betalen bedrag.
	 * @param string $beschrijving De beschrijving bij de betaling.
	 */
	public function on_demand_order( $gebruiker_id, $bedrag, $beschrijving ) {
		$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );
		if ( '' !== $mollie_gebruiker_id ) {
			$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
			$mollie_gebruiker->createPayment(
				[
					'amount'       => [
						'currency' => 'EUR',
						'value'    => number_format( $bedrag, 2, '.', '' ),
					],
					'description'  => $beschrijving,
					'sequenceType' => \Mollie\Api\Types\SequenceType::SEQUENCETYPE_RECURRING,
					'webhookUrl'   => Kleistad_Public::base_url() . '/betaling/ondemand/',
				]
			);
		}
	}

	/**
	 * Herhaal een order op basis van een mandaat, en herhaal deze maandelijks.
	 *
	 * @since      4.2.0
	 *
	 * @param int    $gebruiker_id de gebruiker die de betaling uitvoert.
	 * @param float  $bedrag       het bedrag.
	 * @param string $beschrijving de externe order referentie, maximaal 35 karakters.
	 * @param int    $start        de startdatum voor de periodieke afgeschrijving.
	 */
	public function herhaalorder( $gebruiker_id, $bedrag, $beschrijving, $start ) {
		$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );

		if ( '' !== $mollie_gebruiker_id ) {
			try {
				$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
				$subscripties     = $mollie_gebruiker->subscriptions();
				foreach ( $subscripties as $subscriptie ) {
					if ( $subscriptie->isActive() && $beschrijving === $subscriptie->description ) {
						$mollie_gebruiker->cancelSubscription( $subscriptie->id );
					}
				}
				$subscriptie = $mollie_gebruiker->createSubscription(
					[
						'amount'      => [
							'currency' => 'EUR',
							'value'    => number_format( $bedrag, 2, '.', '' ),
						],
						'description' => $beschrijving,
						'interval'    => '1 month',
						'startDate'   => strftime( '%Y-%m-%d', $start ),
						'webhookUrl'  => Kleistad_Public::base_url() . '/betaling/herhaal/',
					]
				);
				return $subscriptie->id;
			} catch ( Exception $e ) {
				error_log( $e->getMessage() ); // phpcs:ignore
			}
		}
		return '';
	}

	/**
	 * Annuleer de subscriptie.
	 *
	 * @since      4.2.0
	 *
	 * @param int    $gebruiker_id   De gebruiker waarvoor een subscription loopt.
	 * @param string $subscriptie_id De subscriptie die geannuleerd moet worden.
	 */
	public function annuleer( $gebruiker_id, $subscriptie_id ) {
		$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );

		try {
			if ( '' !== $mollie_gebruiker_id && '' !== $subscriptie_id ) {
				$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
				$subscriptie      = $mollie_gebruiker->getSubscription( $subscriptie_id );
				if ( $subscriptie->isActive() ) {
					$mollie_gebruiker->cancelSubscription( $subscriptie_id );
				}
			}
		} catch ( Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore
		}
		return '';
	}

	/**
	 * Controleer of actieve subscriptie bestaat.
	 *
	 * @since      4.2.0
	 *
	 * @param int    $gebruiker_id   De gebruiker waarvoor een subscription loopt.
	 * @param string $subscriptie_id De subscriptie die gecheckt moet worden.
	 */
	public function actief( $gebruiker_id, $subscriptie_id ) {
		$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );

		try {
			if ( '' !== $mollie_gebruiker_id && '' !== $subscriptie_id ) {
				$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
				$subscription     = $mollie_gebruiker->getSubscription( $subscriptie_id );
				return $subscription->isActive();
			}
		} catch ( Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore
		}
		return false;
	}

	/**
	 * Test of de gebruiker een mandaat heeft afgegeven.
	 *
	 * @since      4.2.0
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
		} catch ( Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore
		}
		return false;
	}

	/**
	 * Verwijder mandaten.
	 *
	 * @since      4.2.0
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
		} catch ( Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore
		}
		return false;
	}

	/**
	 * Controleer of de betaling gelukt is.
	 *
	 * @since      4.2.0
	 *
	 * @param  array $referentie de referentie wie of waarvoor er zojuist betaald heeft.
	 * @return mixed de status van de betaling als tekst of een error object.
	 */
	public function controleer( $referentie ) {
		$mollie_betaling_id = '';
		$error              = new WP_Error();
		if ( ! is_null( $referentie['gebruiker_id'] ) ) {
			$mollie_betaling_id = get_user_meta( $referentie['gebruiker_id'], self::MOLLIE_BETALING, true );
		}
		if ( ! is_null( $referentie['order_id'] ) ) {
			$order_betalingen = get_option( self::MOLLIE_AD_HOC );
			if ( isset( $order_betalingen[ $referentie['order_id'] ] ) ) {
				$mollie_betaling_id = $order_betalingen[ $referentie['order_id'] ];
			}
			unset( $order_betalingen[ $referentie['order_id'] ] );
			update_option( self::MOLLIE_AD_HOC, $order_betalingen );
		}
		if ( '' !== $mollie_betaling_id ) {

			try {
				$betaling = $this->mollie->payments->get( $mollie_betaling_id );
				if ( $betaling->isPaid() ) {
					return $betaling->metadata->bericht;
				}
			} catch ( Exception $e ) {
				error_log( $e->getMessage() ); // phpcs:ignore
			}
		}
		$error->add( 'betaling', 'De betaling via iDeal heeft niet plaatsgevonden. Probeer het opnieuw.' );
		return $error;
	}

	/**
	 * Toon deelnemende banken.
	 *
	 * @since      4.2.0
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
	 * @since      4.4.0
	 *
	 * @param int $gebruiker_id De gebruiker waarvan de informatie wordt opgevraagd.
	 * @return bool|string false als de gebruiker onbekend is of string met opgemaakte HTML text.
	 */
	public function info( $gebruiker_id ) {
		$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );
		if ( '' !== $mollie_gebruiker_id ) {
			try {
				$html             = 'Mollie info: ';
				$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
				$mandaten         = $mollie_gebruiker->mandates();
				$subscripties     = $mollie_gebruiker->subscriptions();
				foreach ( $mandaten as $mandaat ) {
					if ( $mandaat->isValid() ) {
						$html .= "Er is op {$mandaat->signatureDate} een geldig mandaat afgegeven om incasso te doen vanaf bankrekening {$mandaat->details->consumerAccount} op naam van {$mandaat->details->consumerName}. ";
					}
				}
				foreach ( $subscripties as $subscriptie ) {
					if ( $subscriptie->isActive() ) {
						$html .= "Er is een actieve subscriptie om {$subscriptie->amount->currency} {$subscriptie->amount->value} met een interval van {$subscriptie->interval} af te schrijven startend vanaf {$subscriptie->startDate}. ";
					}
				}
				return $html;
			} catch ( Exception $e ) {
				error_log( $e->getMessage() ); // phpcs:ignore
				return '';
			}
		}
		return false;
	}

	/**
	 * Webhook functie om herhaalbetaling status te verwerken. Wordt aangeroepen door Mollie.
	 *
	 * @since      4.2.0
	 *
	 * @param WP_REST_Request $request het request.
	 * @return \WP_REST_response de response.
	 * @suppress PhanUnusedPublicMethodParameter, PhanUndeclaredProperty
	 */
	public static function callback_herhaalbetaling_verwerkt( WP_REST_Request $request ) {
		$mollie_betaling_id = $request->get_param( 'id' );

		$object   = new static();
		$betaling = $object->mollie->payments->get( $mollie_betaling_id );
		if ( $betaling->hasChargeBacks() ) {
			$gebruiker_ids = get_users(
				[
					'meta_key'   => self::MOLLIE_ID,
					'meta_value' => $betaling->customerId, //phpcs:ignore
					'fields'     => 'ids',
					'number'     => 1,
				]
			);
			$gebruiker     = get_userdata( reset( $gebruiker_ids ) );
			$to            = "$gebruiker->display_name <$gebruiker->user_email>";
			return Kleistad_public::compose_email(
				$to,
				'Kleistad incasso mislukt',
				'kleistad_email_incasso_mislukt',
				[
					'voornaam'   => $gebruiker->first_name,
					'achternaam' => $gebruiker->last_name,
					'bedrag'     => $betaling->amount->value,
					'reden'      => $betaling->details->bankReason,
				]
			);
		}

		return new WP_REST_response(); // Geeft default http status 200 terug.
	}

	/**
	 * Webhook functie om ondemandbetaling status te verwerken. Wordt aangeroepen door Mollie.
	 *
	 * @param WP_REST_Request $request het request.
	 * @return \WP_REST_response de response.
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public static function callback_ondemandbetaling_verwerkt( WP_REST_Request $request ) {
		// Voorlopig geen acties gedefinieerd.
		return new WP_REST_response(); // Geeft default http status 200 terug.
	}

	/**
	 * Webhook functie om betaling status te verwerken. Wordt aangeroepen door Mollie.
	 *
	 * @since      4.2.0
	 *
	 * @param WP_REST_Request $request het request.
	 * @return \WP_REST_response de response.
	 * @suppress PhanUnusedVariable
	 */
	public static function callback_betaling_verwerkt( WP_REST_Request $request ) {
		$mollie_betaling_id = $request->get_param( 'id' );

		$object   = new static();
		$betaling = $object->mollie->payments->get( $mollie_betaling_id );
		$status   = $betaling->isPaid() && ! $betaling->hasRefunds() && ! $betaling->hasChargeBacks();
		$class    = strtok( $betaling->metadata->order_id, '-' );
		if ( class_exists( $class ) ) {
			$parameters = explode( '-', substr( $betaling->metadata->order_id, strlen( $class ) + 2 ) );
			$class::callback( $parameters, $betaling->amount->value, $status );
		}
		// Andere status mogelijk a.g.v. isExpired, isCanceled of isFailed.
		return new WP_REST_response(); // Geeft default http status 200 terug.
	}
}
