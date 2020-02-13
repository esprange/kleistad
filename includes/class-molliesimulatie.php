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

namespace Kleistad;

/**
 * Definitie van de mollie simulatie class.
 */
class MollieSimulatie {
// phpcs:disable WordPress.NamingConventions
// phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore

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
		$db  = new \SQLite3( $_SERVER['DOCUMENT_ROOT'] . '/mollie.db' );
		$res = $db->query( "SELECT name FROM sqlite_master WHERE type='table' AND name='payments'" );
		if ( ! $res->fetchArray() ) {
			$db->exec( 'CREATE TABLE payments( id TEXT PRIMARY KEY, data TEXT )' );
		}
		$res = $db->query( "SELECT name FROM sqlite_master WHERE type='table' and name='refunds'" );
		if ( ! $res->fetchArray() ) {
			$db->exec( 'CREATE TABLE refunds(  intern_id INTEGER primary key, id text, data text )' );
		}
		$db->close();
		unset( $db );

		$this->payments = new class() {
			/**
			 * Geef een payment object terug.
			 *
			 * @param string $id Het Mollie id.
			 */
			public function get( $id ) {
				return new class( $id ) {

					/**
					 * Id of the object
					 *
					 * @var string $id De id string.
					 */
					public $id;

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
					 * Geeft aan hoeveel er nog openstaat.
					 *
					 * @var object $amountRemaining Het object waarin het openstaande bedrag zit.
					 */
					public $amountRemaining;

					/**
					 * Method property
					 *
					 * @var string $method Kan o.a. ideal en directdebit bevatten.
					 */
					public $method;

					/**
					 * Description property
					 *
					 * @var string $description Beschrijving.
					 */
					public $description;

					/**
					 * Status property
					 *
					 * @var string $status De statustekst.
					 */
					public $status;

					/**
					 * Sequencetype property
					 *
					 * @var string $sequenceType Type one-off of recurring.
					 */
					public $sequenceType;

					/**
					 * Webhook property
					 *
					 * @var string $webhookUrl De webhook.
					 */
					public $webhookUrl;

					/**
					 * HasRefunds property
					 *
					 * @var bool $_hasRefunds Of er een refund bestaat.
					 */
					private $_hasRefunds = false;

					/**
					 * Geeft aan dat er betaald is.
					 */
					public function isPaid() {
						return 'paid' === $this->status;
					}

					/**
					 * Geeft aan dat er niet betaald is.
					 */
					public function isFailed() {
						return 'failed' === $this->status;
					}

					/**
					 * Geeft aan dat de betaling afgebroken is.
					 */
					public function isCanceled() {
						return 'canceled' === $this->status;
					}

					/**
					 * Geeft aan dat de betaling afgebroken is.
					 */
					public function isExpired() {
						return 'expired' === $this->status;
					}

					/**
					 * Geeft aan dat er geen sprake is refunds.
					 */
					public function hasRefunds() {
						return $this->_hasRefunds;
					}

					/**
					 * Geeft aan dat er geen sprake is refunds.
					 */
					public function canBeRefunded() {
						return true;
					}

					/**
					 * Geeft aan dat er geen sprake is van chargebacks.
					 */
					public function hasChargeBacks() {
						return false;
					}

					/**
					 * Geef de refunds terug (in simulatie maar één ).
					 */
					public function refunds() {
						$db      = new \SQLite3( $_SERVER['DOCUMENT_ROOT'] . '/mollie.db' );
						$res     = $db->query( "SELECT * FROM refunds WHERE id='{$this->id}'" );
						$refunds = [];
						$row     = $res->fetchArray();
						if ( $row ) {
							$refunds[] = json_decode( $row['data'] );
						};
						$db->close();
						unset( $db );
						return $refunds;
					}

					/**
					 * Voer een refund uit
					 *
					 * @param array $data De data.
					 */
					public function refund( $data ) {
						$data['status'] = 'pending';
						$db             = new \SQLite3( $_SERVER['DOCUMENT_ROOT'] . '/mollie.db' );
						$db->exec( "INSERT INTO refunds (id, data) VALUES ( '{$this->id}','" . /** @scrutinizer ignore-type */ wp_json_encode( $data ) . "')" ); //phpcs:ignore
						$db->exec( "UPDATE payments set data='" . /** @scrutinizer ignore-type */ wp_json_encode( $this ) . "' WHERE id='{$this->id}'" ); //phpcs:ignore
						$db->close();
						unset( $db );
					}

					/**
					 * De constructor.
					 *
					 * @param string $id Het payment id.
					 */
					public function __construct( $id ) {
						$this->id = $id;
						$db       = new \SQLite3( $_SERVER['DOCUMENT_ROOT'] . '/mollie.db' );
						$res      = $db->query( "SELECT data FROM payments WHERE id = '{$this->id}'" );
						$row      = $res->fetchArray();
						if ( $row ) {
							$data                  = json_decode( $row['data'] );
							$this->metadata        = $data->metadata;
							$this->amount          = $data->amount;
							$this->amountRemaining = $data->amount;
							$this->description     = $data->description;
							$this->status          = $data->status;
							$this->method          = property_exists( $data, 'method' ) ? $data->method : 'directdebit';
							$this->sequenceType    = $data->sequenceType;
							$this->webhookUrl      = $data->webhookUrl;
						}
						$res = $db->query( "SELECT data FROM refunds WHERE id = '{$this->id}'" );
						$row = $res->fetchArray();
						if ( $row ) {
							$data                         = json_decode( $row['data'] );
							$this->amountRemaining->value = $this->amount->value - $data->amount->value;
							$this->_hasRefunds            = true;
						};
						$db->close();
						unset( $db );
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
					 * Dummy customer id
					 *
					 * @var string $id De customer ID.
					 */
					public $id = 'sim001';

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
								 * Het id van de subscriptie.
								 *
								 * @var string $id Het id.
								 */
								public $id = 'subscriptie_id';

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
							public $id;

							/**
							 * Payment data
							 *
							 * @var array $data De payment data.
							 */
							private $data;

							/**
							 * De constructor
							 *
							 * @param array $data De orderdata.
							 */
							public function __construct( $data ) {
								$this->id   = \uniqid();
								$this->data = $data;
								$db         = new \SQLite3( $_SERVER['DOCUMENT_ROOT'] . '/mollie.db' );
								$db->exec( "INSERT INTO payments (id, data) VALUES ( '{$this->id}','" . /** @scrutinizer ignore-type */ wp_json_encode( $data ) . "')" ); //phpcs:ignore
								$db->close();
								unset( $db );
							}

							/**
							 * Geef de url terug.
							 */
							public function getCheckOutUrl() {
								return add_query_arg( 'id', $this->id, plugin_dir_url( __DIR__ ) . 'molliesimulator.php' );
							}

						};
					}
				};
			}

			/**
			 * Maak een customer aan.
			 */
			public function create() {
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
