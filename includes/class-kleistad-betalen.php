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

	const MOLLIE_ID   = 'mollie_customer_id';
	const QUERY_PARAM = 'betaling';

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
		if ( defined( 'KLEISTAD_MOLLIE_SIM' ) ) {
			$this->mollie = new Kleistad_MollieSimulatie();
			return;
		}
		$options      = Kleistad::get_options();
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
			Kleistad_Public::api(),
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
			Kleistad_Public::api(),
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
		$bank = filter_input( INPUT_POST, 'bank', FILTER_SANITIZE_STRING, [ 'options' => [ 'default' => null ] ] );
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
		} else {
			$mollie_gebruiker = $this->mollie->customers->create(
				[
					'name'  => $referentie['naam'],
					'email' => $referentie['email'],
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
				'redirectUrl'  => add_query_arg( self::QUERY_PARAM, $uniqid, Kleistad_ShortcodeForm::get_url() ),
				'webhookUrl'   => Kleistad_Public::base_url() . '/betaling/',
			]
		);
		set_transient( $uniqid, $betaling->id );
		Kleistad_ShortcodeForm::set_redirect( $betaling->getCheckOutUrl() ); // Dit is alleen de registratie van de redirect, niet de werkelijke uitvoering.
	}

	/**
	 * Controleer of de order gelukt is.
	 *
	 * @since      4.2.0
	 *
	 * @return WP_ERROR | string de status van de betaling als tekst of leeg als er geen betaling is.
	 */
	public static function controleer() {
		$error              = new WP_Error();
		$mollie_betaling_id = false;
		$uniqid             = filter_input( INPUT_GET, self::QUERY_PARAM );
		if ( ! is_null( $uniqid ) ) {
			$mollie_betaling_id = get_transient( $uniqid );
			delete_transient( $uniqid );
		}
		if ( false === $mollie_betaling_id ) {
			return '';
		}
		$object = new static();
		try {
			$betaling = $object->mollie->payments->get( $mollie_betaling_id );
			if ( $betaling->isPaid() ) {
				return $betaling->metadata->bericht;
			} elseif ( $betaling->isFailed() ) {
				$error->add( 'betalen', 'De betaling heeft niet kunnen plaatsvinden. Probeer het opnieuw.' );
			} elseif ( $betaling->isExpired() ) {
				$error->add( 'betalen', 'De betaling is verlopen. Probeer het opnieuw.' );
			} elseif ( $betaling->isCanceled() ) {
				$error->add( 'betalen', 'De betaling is geannuleerd. Probeer het opnieuw.' );
			} else {
				$error->add( 'betalen', 'De betaling is waarschijnlijk mislukt. Controleer s.v.p. de status van de bankrekening en neem eventueel contact op met Kleistad.' );
			}
			return $error;
		} catch ( Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore
			return '<div class="kleistad_fout"><p>Interne fout: ' . $e->getMessage() . '</p></div>';
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
	public static function actief( $gebruiker_id, $subscriptie_id ) {
		$object              = new static();
		$mollie_gebruiker_id = get_user_meta( $gebruiker_id, self::MOLLIE_ID, true );

		try {
			if ( '' !== $mollie_gebruiker_id && '' !== $subscriptie_id ) {
				$mollie_gebruiker = $object->mollie->customers->get( $mollie_gebruiker_id );
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
		return '';
	}

	/**
	 * Webhook functie om herhaalbetaling status te verwerken. Wordt aangeroepen door Mollie.
	 *
	 * @since      4.2.0
	 *
	 * @param WP_REST_Request $request het request.
	 * @return \WP_REST_response de response.
	 */
	public static function callback_herhaalbetaling_verwerkt( WP_REST_Request $request ) {
		$mollie_betaling_id = $request->get_param( 'id' );

		$object   = new static();
		$emailer  = new Kleistad_Email();
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
			$emailer->send(
				[
					'to'         => "$gebruiker->display_name <$gebruiker->user_email>",
					'subject'    => 'Kleistad incasso mislukt',
					'slug'       => 'kleistad_email_incasso_mislukt',
					'parameters' =>
					[
						'voornaam'   => $gebruiker->first_name,
						'achternaam' => $gebruiker->last_name,
						'bedrag'     => $betaling->amount->value,
						'reden'      => $betaling->details->bankReason,
					],
				]
			);
		}

		return new WP_REST_response(); // Geeft default http status 200 terug.
	}

	/**
	 * Webhook functie om betaling status te verwerken. Wordt aangeroepen door Mollie.
	 *
	 * @since      4.2.0
	 *
	 * @param WP_REST_Request $request het request.
	 * @return \WP_REST_response de response.
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
