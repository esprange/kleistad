<?php
/**
 * Simulatie Mollie class.
 *
 * @link       https://www.kleistad.nl
 * @since      5.5.1
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * Definitie van de mollie simulatie class.
 */
class Kleistad_MollieSimulatie {
 // phpcs:disable WordPress.NamingConventions

	/**
	 * Customers object
	 *
	 * @var object $customers De collectie customers
	 */
	public $customers;

	/**
	 * Payments object
	 *
	 * @var object $payments De collectie paymentss
	 */
	public $payments;

	/**
	 * Methods object
	 *
	 * @var object $methods De collectie methods
	 */
	public $methods;

	/**
	 * De constructor van de simulatie class
	 */
	public function __construct() {

		$this->payments = new class() {
			/**
			 * Geef een payment object terug.
			 */
			public function get() {
				return new class() {
					/**
					 * Metadata object
					 *
					 * @var object $metadata Het object waar de meta data in zit.
					 */
					public $metadata;

					/**
					 * Amount object
					 *
					 * @var object $amount Het object waarin het betaalde bedrag zit.
					 */
					public $amount;

					/**
					 * Geeft aan dat er betaald is.
					 */
					public function isPaid() {
						return true;
					}

					/**
					 * Geeft aan dat er geen sprake is refunds.
					 */
					public function hasRefunds() {
						return false;
					}

					/**
					 * Geeft aan dat er geen sprake is van chargebacks.
					 */
					public function hasChargeBacks() {
						return false;
					}

					/**
					 * De constructor.
					 */
					public function __construct() {
						$data = get_option( 'mollie_simulatie' );
						if ( isset( $data['metadata'] ) ) {
							$this->metadata = (object) $data['metadata'];
						}
						$this->amount = (object) $data['amount'];
					}
				};
			}
		};

		$this->customers = new class() {

			/**
			 * Geef een customer object terug.
			 */
			public function get() {
				return new class() {

					/**
					 * Geef de subscripties terug.
					 */
					public function subscriptions() {
						return [
							new class() {
								/**
								 * De omschrijving van de subscriptie
								 *
								 * @var string $description De omschrijving tijdens het aanmaken van de subscriptie.
								 */
								public $description = '';

								/**
								 * Het bedrag de subscriptie
								 *
								 * @var object $amount Het bedrag.
								 */
								public $amount;

								/**
								 * Het interval van de subscriptie
								 *
								 * @var string $interval Het interval.
								 */
								public $interval = 'maandelijks';

								/**
								 * De start van de subscriptie
								 *
								 * @var string $startDate de datum.
								 */
								public $startDate = '01-02-2019';

								/**
								 * Is de subscriptie actief.
								 */
								public function isActive() {
									return true;
								}

								/**
								 * De constructor.
								 */
								public function __construct() {
									$this->amount = (object) [
										'currency' => 'EUR',
										'value'    => 99.9,
									];
								}
							},
						];
					}

					/**
					 * Geef de mandates terug.
					 */
					public function mandates() {
						return [
							new class() {
								/**
								 * Mandaat id.
								 *
								 * @var string $id Het id
								 */
								public $id;

								/**
								 * Mandaat id.
								 *
								 * @var string $signatureDate De datum dat het mandaat is afgegeven.
								 */
								public $signatureDate = '16-06-2019';

								/**
								 * De details van het mandaat.
								 *
								 * @var object $details
								 */
								public $details;

								/**
								 * De constructor.
								 */
								public function __construct() {
									$this->details = (object) [
										'consumerAccount' => 'XYZ',
										'consumerName'    => 'X',
									];
								}

								/**
								 * Is het mandaat valide.
								 */
								public function isValid() {
									return true;
								}

							},
						];
					}

					/**
					 * Heeft een valide mandaat
					 */
					public function hasValidMandate() {
						return true;
					}

					/**
					 * Trek het mandaat terug.
					 */
					public function revokeMandate() {
					}

					/**
					 * Cancel de subscriptie.
					 */
					public function cancelSubscription() {
					}

					/**
					 * Get de subscriptie.
					 */
					public function getSubscription() {
						return new class() {
							/**
							 * Is de subscriptie actief.
							 */
							public function isActive() {
								return true;
							}
						};
					}

					/**
					 * Maak een subscriptie aan.
					 */
					public function createSubscription() {
						return new class() {
							/**
							 * Het subscriptie id
							 *
							 * @var string $id Het id
							 */
							public $id = '_sim123456';
						};
					}

					/**
					 * Maak de betaling
					 *
					 * @param array $data De orderdata.
					 */
					public function createPayment( $data ) {
						return new class( $data ) {
							/**
							 * Payment id
							 *
							 * @var string $id Het id
							 */
							public $id = '_sim123456';

							/**
							 * De constructor
							 *
							 * @param array $data De orderdata.
							 */
							public function __construct( $data ) {
								update_option( 'mollie_simulatie', $data );
								$response = wp_remote_post(
									$data['webhookUrl'],
									[
										'body'    => [ 'id' => $this->id ],
										'timeout' => 60,
									]
								);
								if ( is_wp_error( $response ) ) {
									$error_message = $response->get_error_message();
									error_log( "Something went wrong: $error_message" ); // phpcs:ignore
								}
							}

							/**
							 * Geef de url terug.
							 */
							public function getCheckOutUrl() {
								$data = get_option( 'mollie_simulatie' );
								return 'http://localhost/molliesimulator.html?' . $data['redirectUrl'];
							}

						};
					}
				};
			}

			/**
			 * Maak een customer aan.
			 *
			 * @param array $arr wordt niet gebruikt.
			 */
			public function create( $arr ) {
				$customer = $this->get();
				return $customer;
			}
		};

		$this->methods = new class() {
			/**
			 * Deze class hoeft alleen de get te ondersteunen.
			 */
			public function get() {
				return new class() {
					/**
					 * Geef de issuers van iDeal betalingen terug
					 */
					public function issuers() {
						return [
							(object) [
								'id'   => 'ING',
								'name' => 'ING bank',
							],
							(object) [
								'id'   => 'RABO',
								'name' => 'Rabo bank',
							],
						];
					}
				};
			}
		};

	}
} // phpcs:enable
