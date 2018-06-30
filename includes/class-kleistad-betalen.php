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

	/**
	 * Het mollie object.
	 *
	 * @since      4.2.0
	 *
	 * @var object het mollie service object.
	 */
	public $mollie;

	/**
	 * De constructor
	 *
	 * @since      4.2.0
	 */
	public function __construct() {
		$options = get_option( 'kleistad-opties' );

		$this->mollie = new \Mollie\Api\MollieApiClient();

		if ( '1' === $options['betalen'] ) {
			$this->mollie->setApiKey( $options['sleutel'] );
		} else {
			$this->mollie->setApiKey( $options['sleutel_test'] );
		}
	}

	/**
	 * Bereid de order informatie voor.
	 *
	 * @since      4.2.0
	 *
	 * @param int    $gebruiker_id de gebruiker die de betaling uitvoert.
	 * @param string $order_id     de externe order referentie, maximaal 35 karakters.
	 * @param float  $bedrag       het bedrag.
	 * @param string $beschrijving de externe order referentie, maximaal 35 karakters.
	 * @param string $bericht      het bericht bij succesvolle betaling.
	 * @param bool   $mandateren   er wordt een herhaalde betaling voorbereid.
	 */
	public function order( $gebruiker_id, $order_id, $bedrag, $beschrijving, $bericht, $mandateren = false ) {
		$bank = filter_input( INPUT_POST, 'bank', FILTER_SANITIZE_STRING );

		// Registreer de gebruiker in Mollie en het id in WordPress als er een mandaat nodig is.
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
					'redirectUrl'  => add_query_arg( 'betaald', $gebruiker_id, get_permalink() ),
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
					'redirectUrl'  => add_query_arg( 'betaald', $gebruiker_id, get_permalink() ),
					'webhookUrl'   => Kleistad_Public::base_url() . '/betaling/',
				]
			);
		}
		update_user_meta( $gebruiker_id, self::MOLLIE_BETALING, $betaling->id );
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
					'webhookUrl'   => Kleistad_Public::base_url() . '/ondemandbetaling/',
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
			$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
			$subscriptie      = $mollie_gebruiker->createSubscription(
				[
					'amount'      => [
						'currency' => 'EUR',
						'value'    => number_format( $bedrag, 2, '.', '' ),
					],
					'description' => $beschrijving,
					'interval'    => '1 month',
					'startDate'   => strftime( '%Y-%m-%d', $start ),
					'webhookUrl'  => Kleistad_Public::base_url() . '/herhaalbetaling/',
				]
			);
			return $subscriptie->id;
		} else {
			return '';
		}
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

		if ( '' !== $mollie_gebruiker_id && '' !== $subscriptie_id ) {
			$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
			$subscription     = $mollie_gebruiker->getSubscription( $subscriptie_id );
			if ( $subscription->isActive() ) {
				$mollie_gebruiker->cancelSubscription( $subscriptie_id );
			}
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

		if ( '' !== $mollie_gebruiker_id && '' !== $subscriptie_id ) {
			$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
			$subscription     = $mollie_gebruiker->getSubscription( $subscriptie_id );
			return $subscription->isActive();
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
		if ( '' !== $mollie_gebruiker_id ) {
			$mollie_gebruiker = $this->mollie->customers->get( $mollie_gebruiker_id );
			$mandaten         = $mollie_gebruiker->mandates();
			foreach ( $mandaten as $mandaat ) {
				if ( $mandaat->isValid() ) {
					return true;
				}
			}
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
		return false;
	}

	/**
	 * Controleer of de betaling gelukt is.
	 *
	 * @since      4.2.0
	 *
	 * @param  int $gebruiker_id de gebruiker die zojuist betaald heeft.
	 * @return mixed de status van de betaling als tekst of een error object.
	 */
	public function controleer( $gebruiker_id ) {
		$error = new WP_Error();
		$error->add( 'betaling', 'De betaling via iDeal heeft niet plaatsgevonden. Probeer het opnieuw.' );

		$mollie_betaling_id = get_user_meta( $gebruiker_id, self::MOLLIE_BETALING, true );
		if ( '' !== $mollie_betaling_id ) {
			$betaling = $this->mollie->payments->get( $mollie_betaling_id );
			if ( $betaling->isPaid() ) {
				return $betaling->metadata->bericht;
			}
		}
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
	 */
	public static function callback_herhaalbetaling_verwerkt( WP_REST_Request $request ) {
		// Voorlopig geen acties gedefinieerd.
		return new WP_REST_response(); // Geeft default http status 200 terug.
	}

	/**
	 * Webhook functie om ondemandbetaling status te verwerken. Wordt aangeroepen door Mollie.
	 *
	 * @param WP_REST_Request $request het request.
	 * @return \WP_REST_response de response.
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
	 */
	public static function callback_betaling_verwerkt( WP_REST_Request $request ) {
		$mollie_betaling_id = $request->get_param( 'id' );

		$object   = new static();
		$betaling = $object->mollie->payments->get( $mollie_betaling_id );
		if ( $betaling->isPaid() && ! $betaling->hasRefunds() && ! $betaling->hasChargeBacks() ) {
			/**
			 * Alleen actie als er daadwerkelijk betaald is, niet als er terugbetaald is o.i.d.
			 *
			 * Cursus        format : Cx-y-d-z waarbij x is cursus-id, y is gebruiker-id, d de startdatum en z type betaling.
			 * Abonnee       format : Ax-y waarbij x is gebruiker-id en y geeft aan of het een (her)start betreft
			 * Stooksaldo    format : Sx-d waarbij x is gebruiker-id en d de melddatum
			 * Dagdelenkaart format : Kx-d waarbij x is gebruiker-id en d de aankoopdatum
			 */
			$order_id = $betaling->metadata->order_id;
			switch ( $order_id[0] ) {
				case 'A': // Een abonnement, de vervolg betalingen als subscriptie inplannen.
					list( $gebruiker_id, $parameter ) = sscanf( $order_id, 'A%d-%s' );
					$abonnement                       = new Kleistad_Abonnement( $gebruiker_id );
					$abonnement->callback( $parameter );
					break;
				case 'C': // Een cursus.
					list( $cursus_id, $gebruiker_id, $startdatum, $parameter ) = sscanf( $order_id, 'C%d-%d-%d-%s' );
					$inschrijving = new Kleistad_Inschrijving( $gebruiker_id, $cursus_id );
					$inschrijving->callback( $parameter );
					break;
				case 'S': // Het stooksaldo.
					list ( $gebruiker_id, $datum ) = sscanf( $order_id, 'S%d-%d' );
					$saldo                         = new Kleistad_Saldo( $gebruiker_id );
					$saldo->callback( $betaling->amount->value );
					break;
				case 'K': // Een dagdelenkaart.
					list ( $gebruiker_id, $datum ) = sscanf( $order_id, 'K%d-%d' );
					$dagdelenkaart                 = new Kleistad_Dagdelenkaart( $gebruiker_id );
					$dagdelenkaart->callback();
					break;
				default:
					// Zou niet mogen.
					break;
			}
		}
		if ( $betaling->isExpired() || $betaling->isCanceled() || $betaling->isFailed() ) {
			// Doe voorlopig niets maar hier zouden acties kunnen zijn om iets terug te rollen.
			$niets = false;
		}
		return new WP_REST_response(); // Geeft default http status 200 terug.
	}
}
