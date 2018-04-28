<?php
/**
 * The public-facing functionality of the plugin, betalen via Mollie.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * Include de Mollie API
 */
require plugin_dir_path( dirname( __FILE__ ) ) . 'mollie/API/autoloader.php';

/**
 * Interface naar Mollie betalen.
 */
class Kleistad_Betalen {

	/**
	 * Het mollie object.
	 *
	 * @var object het mollie service object.
	 */
	public $mollie;

	/**
	 * De constructor
	 */
	public function __construct() {
		$options = get_option( 'kleistad-opties' );

		$this->mollie = new Mollie_API_Client();

		if ( '1' === $options['betalen'] ) {
			$this->mollie->setApiKey( $options['sleutel'] );
		} else {
			$this->mollie->setApiKey( $options['sleutel_test'] );
		}
	}

	/**
	 * Bereid de order informatie voor.
	 *
	 * @param string $gebruiker_id de gebruiker die de betaling uitvoert.
	 * @param string $order_id     de externe order referentie, maximaal 35 karakters.
	 * @param real   $bedrag       het bedrag.
	 * @param string $bank         de bank.
	 * @param string $beschrijving de externe order referentie, maximaal 35 karakters.
	 * @param string $bericht      het bericht bij succesvolle beraling.
	 */
	public function order( $gebruiker_id, $order_id, $bedrag, $bank, $beschrijving, $bericht ) {
		$betaling = $this->mollie->payments->create(
			[
				'amount'      => $bedrag,
				'description' => $beschrijving,
				'redirectUrl' => add_query_arg( 'betaald', $gebruiker_id, get_permalink() ),
				'webhookUrl'  => Kleistad_Public::base_url() . '/betaling/',
				'method'      => Mollie_API_Object_Method::IDEAL,
				'issuer'      => ! empty( $bank ) ? $bank : null,
				'metadata'    => [
					'order_id' => $order_id,
					'bericht'  => $bericht,
				],
			]
		);
		update_user_meta( $gebruiker_id, 'betaling', $betaling->id );
		wp_redirect( $betaling->getPaymentUrl(), 303 );
		exit;
	}

	/**
	 * Controleer of de betaling gelukt is.
	 *
	 * @param  int $gebruiker_id de gebruiker die zojuist betaald heeft.
	 * @return mixed de status van de betaling als tekst of een error object.
	 */
	public function controleer( $gebruiker_id ) {
		$error = new WP_Error();
		$mollie_id = get_user_meta( $gebruiker_id, 'betaling', true );

		if ( '' === $mollie_id ) {
			$error( 'betaling', 'Er is iets misgegaan met een betaling. Probeer het opnieuw.' );
			return $error;
		}

		$betaling = $this->mollie->payments->get( $mollie_id );
		if ( $betaling->isPaid() ) {
			return $betaling->metadata->bericht;
		}
		$error->add( 'betaling', 'De betaling via iDeal heeft niet plaatsgevonden. Probeer het opnieuw.' );
		return $error;
	}

	/**
	 * Toon deelnemende banken.
	 */
	public static function issuers() {
		$object = new static();

		$issuers = $object->mollie->issuers->all();
		echo '<option value="" >&nbsp;</option>';
		foreach ( $issuers as $issuer ) {
			if ( Mollie_API_Object_Method::IDEAL === $issuer->method ) {
				echo '<option value=' . esc_attr( $issuer->id ) . '>' . esc_attr( $issuer->name ) . '</option>';
			}
		}
	}

	/**
	 * Webhook functie om betaling status te verwerken. Wordt aangeroepen door Mollie.
	 *
	 * @param WP_REST_Request $request het request.
	 * @return \WP_REST_response de response.
	 */
	public static function callback_betaling_verwerkt( WP_REST_Request $request ) {
		$mollie_id = $request->get_param( 'id' );

		$object = new static();
		$betaling = $object->mollie->payments->get( $mollie_id );
		$order_id = $betaling->metadata->order_id;

		/**
		 * Cursus format : Cx-y-z waarbij x is cursus-id, y is gebruiker-id en z is startdatum in ymd.
		 * Abonnee format : Ax waarbij x is gebruiker-id
		 * Stooksaldo format : Sx waarbij x is gebruiker-id
		 */
		switch ( $order_id[0] ) {
			case 'A': // Geen verdere acties.
				list( $gebruiker_id ) = sscanf( $order_id, 'A%d' );
				break;
			case 'C': // Een cursus.
				list( $cursus_id, $gebruiker_id ) = sscanf( $order_id, 'C%d-%d-%d' );
				break;
			case 'S': // Het stooksaldo.
				list ( $gebruiker_id ) = sscanf( $order_id, 'S%d' );
				break;
			default:
				break;
		}

		if ( isset( $gebruiker_id ) ) {
			$gebruiker = get_userdata( $gebruiker_id );
			if ( $betaling->isPaid() ) {
				switch ( $order_id[0] ) {
					case 'A': // Abonnement.
						$abonnement = new Kleistad_Inschrijving( $gebruiker_id );
						if ( '' === $gebruiker->role ) {
							wp_update_user(
								[
									'ID' => $gebruiker_id,
									'role' => 'subscriber',
								]
							);
						}
						$abonnement->email();
						break;
					case 'C': // Een cursus.
						$inschrijving = new Kleistad_Inschrijving( $gebruiker_id, $cursus_id );
						$inschrijving->i_betaald = true;
						$inschrijving->ingedeeld = true;
						$inschrijving->save();
						$inschrijving->email( 'indeling' );
						break;
					case 'S': // Het stooksaldo.
						$saldo = new Kleistad_Saldo( $gebruiker_id );
						$saldo->bedrag = $saldo->bedrag + $betaling->amount;
						$saldo->save( 'betaling per iDeal' );
						$saldo->email( 'ideal',  $betaling->amount );
						break;
					default:
						break;
				}
			}
		}
		return new WP_REST_response(); // Geeft default http status 200 terug.
	}
}

