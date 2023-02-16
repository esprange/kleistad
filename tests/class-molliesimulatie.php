<?php
/**
 * Simulatie Mollie class.
 *
 * @link       https://www.kleistad.nl
 * @since      5.5.1
 *
 * @package    Kleistad
 * @subpackage Kleistad/tests
 */

/**
 * Mollie database simulatie.
 */
$mollie_sim = '';

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
		global $mollie_sim;
		if ( ! defined( 'KLEISTAD_TEST' ) ) {
			$mollie_sim = $_SERVER['DOCUMENT_ROOT'] . '/mollie.db';
			if ( ! file_exists( $mollie_sim ) ) {
				$this->create_db( $mollie_sim );
			}
		} else {
			$mollie_sim = sys_get_temp_dir() . '/mollie.db';
			$this->create_db( $mollie_sim );
		}

		$this->payments = new class() {
			/**
			 * Geef een payment object terug.
			 *
			 * @param string $id Het Mollie id.
			 */
			public function get( string $id ) : object {
				return new class( $id ) {

					/**
					 * Id of the object
					 *
					 * @var string $id De id string.
					 */
					public string $id;

					/**
					 * Metadata object
					 *
					 * @var object $metadata Het object waar de meta data in zit.
					 */
					public object $metadata;

					/**
					 * Amount object
					 *
					 * @var object $amount Het object waarin het betaalde bedrag zit.
					 */
					public object $amount;

					/**
					 * Geeft aan hoeveel er nog openstaat.
					 *
					 * @var object $amountRemaining Het object waarin het openstaande bedrag zit.
					 */
					public object $amountRemaining;

					/**
					 * Method property
					 *
					 * @var string $method Kan o.a. ideal en directdebit bevatten.
					 */
					public string $method;

					/**
					 * Description property
					 *
					 * @var string $description Beschrijving.
					 */
					public string $description;

					/**
					 * Create datum
					 *
					 * @var string $createdAt Datum.
					 */
					public string $createdAt;

					/**
					 * Status property
					 *
					 * @var string $status De statustekst.
					 */
					public string $status;

					/**
					 * Sequencetype property
					 *
					 * @var string $sequenceType Type one-off of recurring.
					 */
					public string $sequenceType;

					/**
					 * Webhook property
					 *
					 * @var string $webhookUrl De webhook.
					 */
					public string $webhookUrl;

					/**
					 * HasRefunds property
					 *
					 * @var bool $_hasRefunds Of er een refund bestaat.
					 */
					private bool $_hasRefunds = false;

					/**
					 * Geeft aan dat er betaald is.
					 */
					public function isPaid() : bool {
						return 'paid' === $this->status;
					}

					/**
					 * Geeft aan dat er niet betaald is.
					 */
					public function isFailed() : bool {
						return 'failed' === $this->status;
					}

					/**
					 * Geeft aan dat de betaling afgebroken is.
					 */
					public function isCanceled() : bool {
						return 'canceled' === $this->status;
					}

					/**
					 * Geeft aan dat de betaling afgebroken is.
					 */
					public function isExpired() : bool {
						return 'expired' === $this->status;
					}

					/**
					 * Geeft aan dat er geen sprake is refunds.
					 */
					public function hasRefunds() : bool {
						return $this->_hasRefunds;
					}

					/**
					 * Geeft aan dat er geen sprake is chargebacks.
					 */
					public function hasChargebacks() : bool {
						return false;
					}

					/**
					 * Geeft aan dat er geen sprake is refunds.
					 */
					public function canBeRefunded() : bool {
						return true;
					}

					/**
					 * Geef een refund.
					 *
					 * @param string $refund_id Het id van de refund.
					 */
					public function getRefund( string $refund_id ) : object {
						return new class( $refund_id ) {

							/**
							 * Status property
							 *
							 * @var string $status De statustekst.
							 */
							public string $status;

							/**
							 * Id of the object
							 *
							 * @var string $id De id string.
							 */
							public string $id;

							/**
							 * Amount object
							 *
							 * @var object $amount Het object waarin het betaalde bedrag zit.
							 */
							public object $amount;

							/**
							 * Metadata object
							 *
							 * @var object $metadata Het object waar de meta data in zit.
							 */
							public object $metadata;

							/**
							 * Description property
							 *
							 * @var string $description Beschrijving.
							 */
							public string $description;

							/**
							 * Create datum
							 *
							 * @var string $createdAt Datum.
							 */
							public string $createdAt;

							/**
							 * De constructor.
							 *
							 * @param string $id Het refund id.
							 */
							public function __construct( string $id ) {
								global $mollie_sim;
								$this->id = $id;
								$db       = new SQLite3( $mollie_sim );
								$res      = $db->query( "SELECT data FROM refunds WHERE id = '$this->id'" );
								$row      = $res->fetchArray();
								if ( false !== $row ) {
									$data              = json_decode( $row['data'] );
									$this->metadata    = $data->metadata;
									$this->amount      = $data->amount;
									$this->description = $data->description;
									$this->status      = $data->status;
									$this->createdAt   = date( 'c' );
									$db->close();
									unset( $db );
								}
							}

							/**
							 * Is refunded.
							 */
							public function isTransferred() : bool {
								return 'refunded' === $this->status;
							}

							/**
							 * Is pending.
							 */
							public function isPending() : bool {
								return 'pending' === $this->status;
							}

							/**
							 * Is processed.
							 */
							public function isProcessing() : bool {
								return 'processing' === $this->status;
							}

							/**
							 * Is queued.
							 */
							public function isQueued() : bool {
								return 'queued' === $this->status;
							}

							/**
							 * Cancel the refund.
							 */
							public function cancel() {
								global $mollie_sim;
								$db = new SQLite3( $mollie_sim );
								$db->query( "DELETE FROM refunds WHERE id = '$this->id'" );
								$db->close();
								unset( $db );
							}
						};
					}

					/**
					 * Geef de refunds terug (in simulatie maar één ).
					 */
					public function refunds() : array {
						global $mollie_sim;
						$db      = new SQLite3( $mollie_sim );
						$res     = $db->query( "SELECT * FROM refunds WHERE payment_id='$this->id'" );
						$refunds = [];
						$row     = $res->fetchArray();
						if ( false !== $row ) {
							$refunds[] = json_decode( $row['data'] );
						}
						$db->close();
						unset( $db );
						return $refunds;
					}

					/**
					 * Voer een refund uit
					 *
					 * @param array $data De data.
					 */
					public function refund( array $data ) : object {
						global $mollie_sim;
						$data['status'] = 'queued';
						$id             = uniqid( 're_' );
						$db             = new SQLite3( $mollie_sim );
						$db->exec( "INSERT INTO refunds (id, payment_id, data) VALUES ( '$id','$this->id','" . /** @scrutinizer ignore-type */ wp_json_encode( $data ) . "')" ); //phpcs:ignore
						$db->exec( "UPDATE payments set data='" . /** @scrutinizer ignore-type */ wp_json_encode( $this ) . "' WHERE id='$this->id'" ); //phpcs:ignore
						$db->close();
						unset( $db );
						return $this->getRefund( $id );
					}

					/**
					 * De constructor.
					 *
					 * @param string $id Het payment id.
					 */
					public function __construct( string $id ) {
						global $mollie_sim;
						$this->id = $id;
						$db       = new SQLite3( $mollie_sim );
						$res      = $db->query( "SELECT data FROM payments WHERE id = '$this->id'" );
						$row      = $res->fetchArray();
						if ( false !== $row ) {
							$data                  = json_decode( $row['data'] );
							$this->metadata        = $data->metadata;
							$this->amount          = $data->amount;
							$this->amountRemaining = $data->amount;
							$this->description     = $data->description;
							$this->createdAt       = date( 'c' );
							$this->status          = $data->status;
							$this->method          = property_exists( $data, 'method' ) ? $data->method : 'directdebit';
							$this->sequenceType    = $data->sequenceType;
							$this->webhookUrl      = $data->webhookUrl;

							$res = $db->query( "SELECT data FROM refunds WHERE payment_id = '$this->id'" );
							$row = $res->fetchArray();
							if ( false !== $row ) {
								$data                         = json_decode( $row['data'] );
								$this->amountRemaining->value = $this->amount->value - $data->amount->value;
								$this->_hasRefunds            = true;
							}
						}
						$db->close();
						unset( $db );
					}
				};
			}
		};

		$this->customers = new class() {

			/**
			 * De id ingeval van een create.
			 *
			 * @var string $id De customer id.
			 */
			public string $id;

			/**
			 * Geef een customer object terug.
			 *
			 * @param string $id Het customer id.
			 */
			public function get( string $id ) : object {
				return new class( $id ) {

					/**
					 * Customer id
					 *
					 * @var string $id De customer ID.
					 */
					public string $id;

					/**
					 * Naam van de klant.
					 *
					 * @var string
					 */
					public string $name;

					/**
					 * Email adres van de klant.
					 *
					 * @var string
					 */
					public string $email;

					/**
					 * De constructor.
					 *
					 * @param string $id Het customer id.
					 */
					public function __construct( string $id ) {
						global $mollie_sim;
						$this->id = $id;
						$db       = new SQLite3( $mollie_sim );
						$res      = $db->query( "SELECT data FROM customers WHERE id = '$this->id'" );
						$row      = $res->fetchArray();
						$db->close();
						unset( $db );
						if ( false !== $row ) {
							$data        = json_decode( $row['data'] );
							$this->name  = $data->name;
							$this->email = $data->email;
							return $this;
						}
						return null;
					}

					/**
					 * Geef de mandates terug.
					 */
					public function mandates() : array {
						global $mollie_sim;
						$mandates = [];
						$db       = new SQLite3( $mollie_sim );
						$res      = $db->query( "SELECT * FROM mandates WHERE customer_id='$this->id'" );
						while ( $row = $res->fetchArray() ) { //phpcs:ignore
							$data       = json_decode( $row['data'] );
							$mandates[] = new class( $row['id'], $data ) {

								/**
								 * Het mandaat id
								 *
								 * @var string $id Het id.
								 */
								public string $id;

								/**
								 * De mandaat status
								 *
								 * @var string $status De status.
								 */
								public string $status;

								/**
								 * Klant details
								 *
								 * @var object $detaild De klant details.
								 */
								public object $details;

								/**
								 * Validatie datum
								 *
								 * @var string $signatureDate Datum.
								 */
								public string $signatureDate;

								/**
								 * Creatie datum
								 *
								 * @var string $createdAt Datum.
								 */
								public string $createdAt;

								/**
								 * De constructor
								 *
								 * @param string $id   Het mandaat id.
								 * @param object $data De data.
								 */
								public function __construct( string $id, object $data ) {
									$this->status        = $data->status;
									$this->id            = $id;
									$this->details       = $data->details;
									$this->signatureDate = $data->signatureDate;
									$this->createdAt     = $data->createdAt;
								}

								/**
								 * Is het mandaat valide.
								 */
								public function isValid() : bool {
									return 'valid' === $this->status;
								}
							};
						}
						$db->close();
						return $mandates;
					}

					/**
					 * Heeft een valide mandaat
					 */
					public function hasValidMandate() : bool {
						global $mollie_sim;
						$db    = new SQLite3( $mollie_sim );
						$res   = $db->query( "SELECT * FROM mandates WHERE customer_id='$this->id'" );
						$valid = false;
						while ( $row = $res->fetchArray() ) { //phpcs:ignore
							$mandaat = json_decode( $row['data'] );
							$valid   = $valid || 'valid' === $mandaat->status;
						}
						$db->close();
						unset( $db );
						return $valid;
					}

					/**
					 * Trek het mandaat terug.
					 *
					 * @param string $id Het mandaat id.
					 */
					public function revokeMandate( string $id ) {
						global $mollie_sim;
						$db  = new SQLite3( $mollie_sim );
						$res = $db->query( "SELECT * FROM mandates WHERE id='$id'" );
						$row = $res->fetchArray();
						if ( false !== $row ) {
							$mandaat         = json_decode( $row['data'] );
							$mandaat->status = 'invalid';
							$db->exec( "UPDATE mandates set data='" . /** @scrutinizer ignore-type */ wp_json_encode( $mandaat ) . "' WHERE id='$id'" ); //phpcs:ignore
						}
						$db->close();
						unset( $db );
					}

					/**
					 * Maak de betaling
					 *
					 * @param array $data De orderdata.
					 */
					public function createPayment( array $data ) : object {
						return new class( $data, $this->id, $this->name ) {
							/**
							 * Payment id
							 *
							 * @var string $id Het id
							 */
							public string $id;

							/**
							 * De constructor
							 *
							 * @param array  $data          De orderdata.
							 * @param string $customer_id   Het klant id.
							 * @param string $customer_name De klant naam.
							 */
							public function __construct( array $data, string $customer_id, string $customer_name ) {
								global $mollie_sim;
								$this->id          = uniqid( 'tr_' );
								$db                = new SQLite3( $mollie_sim );
								$data['status']    = 'pending';
								$data['mandateId'] = '';
								if ( 'first' === $data['sequenceType'] ) {
									$mandaat_id = uniqid( 'mdt_' );
									$mandaat    = [
										'signatureDate' => '',
										'details'       => [
											'consumerName' => $customer_name,
											'consumerAccount' => 'NL55INGB0114443333',
											'consumerBic'  => 'INGBNL2A',
										],
										'createdAt'     => date( 'c' ),
										'status'        => 'pending',
									];
									$db->exec( "INSERT INTO mandates (id, customer_id, data) VALUES ( '$mandaat_id','$customer_id','" . /** @scrutinizer ignore-type */ wp_json_encode( $mandaat ) . "')" ); //phpcs:ignore
									$data['mandateId'] = $mandaat_id;
								}
								$db->exec( "INSERT INTO payments (id, customer_id, data) VALUES ( '$this->id','$customer_id','" . /** @scrutinizer ignore-type */ wp_json_encode( $data ) . "')" ); //phpcs:ignore
								$db->close();
								unset( $db );
							}

							/**
							 * Geef de url terug.
							 */
							public function getCheckOutUrl() : string {
								if ( ! defined( 'KLEISTAD_TEST' ) ) {
									return add_query_arg( 'id', $this->id, plugin_dir_url( __DIR__ ) . 'tests/molliesimulator.php' );
								}
								return add_query_arg( 'id', $this->id, 'https://www.example.com/test.php' );
							}

						};
					}
				};
			}

			/**
			 * Maak een customer aan.
			 *
			 * @param array $data De klant data.
			 */
			public function create( array $data ) : object {
				global $mollie_sim;
				$this->id = uniqid( 'cst_' );
				$db       = new SQLite3( $mollie_sim );
				$db->exec( "INSERT INTO customers (id, data) VALUES ( '$this->id','" . /** @scrutinizer ignore-type */ wp_json_encode( $data ) . "')" ); //phpcs:ignore
				$db->close();
				unset( $db );
				return $this->get( $this->id );
			}
		};

		$this->methods = new class() {
			/**
			 * Deze class hoeft alleen de get te ondersteunen.
			 */
			public function get() : object {
				return new class() {
					/**
					 * Geef de issuers van iDeal betalingen terug
					 */
					public function issuers() : object {
						return (object) [
							(object) [
								'id'    => 'ideal_INGBNL2A',
								'name'  => 'ING',
								'image' => (object) [
									'size1x' => 'https://www.mollie.com/external/icons/ideal-issuers/INGBNL2A.png',
									'size2x' => 'https://www.mollie.com/external/icons/ideal-issuers/INGBNL2A%402x.png',
									'svg'    => 'https://www.mollie.com/external/icons/ideal-issuers/INGBNL2A.svg',
								],
							],
							(object) [
								'id'    => 'ideal_RABONL2U',
								'name'  => 'Rabobank',
								'image' => (object) [
									'size1x' => 'https://www.mollie.com/external/icons/ideal-issuers/RABONL2U.png',
									'size2x' => 'https://www.mollie.com/external/icons/ideal-issuers/RABONL2U%402x.png',
									'svg'    => 'https://www.mollie.com/external/icons/ideal-issuers/RABONL2U.svg',
								],
							],
						];
					}
				};
			}
		};

	}

	/**
	 * Maak de db file aan.
	 *
	 * @param string $db_file Het sqlite bestand.
	 */
	private function create_db( string $db_file ) {
		$db  = new SQLite3( $db_file );
		$res = $db->query( "SELECT name FROM sqlite_master WHERE type='table' and name='customers'" );
		if ( ! $res->fetchArray() ) {
			$db->exec( 'CREATE TABLE customers(  intern_id INTEGER primary key, id text, data text )' );
		}
		$res = $db->query( "SELECT name FROM sqlite_master WHERE type='table' AND name='payments'" );
		if ( ! $res->fetchArray() ) {
			$db->exec( 'CREATE TABLE payments( intern_id INTEGER primary key, id TEXT, customer_id TEXT, data TEXT )' );
		}
		$res = $db->query( "SELECT name FROM sqlite_master WHERE type='table' and name='refunds'" );
		if ( ! $res->fetchArray() ) {
			$db->exec( 'CREATE TABLE refunds(  intern_id INTEGER primary key, id text, payment_id TEXT, data text )' );
		}
		$res = $db->query( "SELECT name FROM sqlite_master WHERE type='table' and name='mandates'" );
		if ( ! $res->fetchArray() ) {
			$db->exec( 'CREATE TABLE mandates(  intern_id INTEGER primary key, id text, customer_id TEXT, data text )' );
		}
		$db->exec( 'PRAGMA journal_mode = wal;' );
		$db->close();
		unset( $db );

	}


} // phpcs:enable
