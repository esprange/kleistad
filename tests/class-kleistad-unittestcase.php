<?php
/**
 * Class Kleistad_UnitTestCase
 *
 * @package Kleistad
 */

namespace Kleistad;

use WP_UnitTestCase;
use MockPHPMailer;

/**
 * Mock filter input array function
 *
 * @param int       $type      Type input Post of Get.
 * @param array|int $options   Filter opties.
 * @param bool      $add_empty Afwezige keys als null tonen.
 *
 * @return mixed
 */
function filter_input_array( int $type, $options = FILTER_DEFAULT, bool $add_empty = true ) {
	// @phpcs:disable
	if ( INPUT_GET === $type ) {
		return filter_var_array( $_GET, $options, $add_empty);
	}
	return filter_var_array( $_POST, $options, $add_empty );
	// @phpcs:enable
}

/**
 * Mock filter input function
 *
 * @param int       $type     Type input Post of Get.
 * @param string    $var_name Variable naam.
 * @param int       $filter   Filter.
 * @param array|int $options  Filter opties.
 *
 * @return mixed
 */
function filter_input( int $type, string $var_name, int $filter = FILTER_DEFAULT, $options = 0 ) {
	// @phpcs:disable
	if ( INPUT_GET === $type && isset( $_GET[ $var_name ] ) ) {
		return filter_var( $_GET[ $var_name ], $filter, $options );
	}
	if ( isset( $_POST[ $var_name ] ) ) {
		return filter_var( $_POST[ $var_name ], $filter, $options );
	}
	return null;
	// @phpcs:enable
}

/**
 * Kleistad Unit test case.
 *
 * phpcs:disable WordPress.NamingConventions
 */
abstract class Kleistad_UnitTestCase extends WP_UnitTestCase {

	/**
	 * Hulp functie om de protected frontend functies te kunnen uitvoeren
	 * Omdat de shortcode class een singleton is wordt een cache opgebouwd voor hergebruik
	 *
	 * @param string $shortcode_tag De shortcode die getest wordt.
	 * @param string $method        De protected class method die moet worden getest.
	 * @param array  $data          De uit te wisselen data.
	 * @param array  $atts          De eventuele attributes meegegeven aan de shortcode.
	 *
	 * @returns mixed
	 */
	protected function public_actie( string $shortcode_tag, string $method, array &$data, array $atts = [] ) {
		static $shortcodes = [];
		$class             = Shortcode::get_class_name( $shortcode_tag );
		$reflection        = new \ReflectionClass( $class );
		$action            = $reflection->getMethod( $method );
		$action->setAccessible( true );
		if ( ! isset( $shortcodes[ $shortcode_tag ] ) ) {
			$shortcodes[ $shortcode_tag ] = Shortcode::get_instance( $shortcode_tag, $atts );
		}
		return $action->invokeArgs( $shortcodes[ $shortcode_tag ], [ &$data ] );
	}

	/**
	 * Mock de phpmailer met een extensie
	 *
	 * @return bool
	 */
	protected function reset_mockmailer_instance(): bool {
		$mailer = tests_retrieve_phpmailer_instance();
		if ( $mailer ) {
			$mailer               = new class() extends MockPHPMailer {

				/**
				 * Activate the plugin which includes the kleistad specific tables if not present.
				 */
				public function postSend(): bool {
					$this->mock_sent[] = array(
						'to'         => $this->to,
						'cc'         => $this->cc,
						'bcc'        => $this->bcc,
						'header'     => $this->MIMEHeader . $this->mailHeader,
						'subject'    => $this->Subject,
						'body'       => $this->MIMEBody,
						'attachment' => $this->attachmentExists(),
					);
					return true;
				}

				/**
				 * Geef het aantal gezonden berichten
				 *
				 * @return int|void
				 */
				public function get_sent_count() {
					return count( $this->mock_sent );
				}

				/**
				 * Help functie voor zoeken specifieke email
				 *
				 * @param string   $email_address Email adress waarop gezocht moet worden.
				 * @param int|null $index         Email die gevonden moet worden, null = laatste, 1 = voorlaatste etc.
				 *
				 * @return false|object
				 */
				public function get_last_sent( string $email_address = '', int $index = 0 ) {
					if ( empty( $email_address ) ) {
						$sent = array_reverse( $this->mock_sent )[ $index ];
						return false === $sent ? $sent : (object) $sent;
					}
					$last = count( $this->mock_sent );
					while ( $last > 0 ) {
						$last --;
						if ( $email_address === $this->mock_sent[ $last ]['to'][0][0] ) {
							return (object) $this->mock_sent[ $last ];
						}
					}
					return false;
				}

				/**
				 * Geef de laatste recipient terug
				 *
				 * @param string $address_type to, from etc.
				 *
				 * @return bool|object
				 */
				public function get_last_recipient( string $address_type ) {
					return $this->get_recipient( $address_type, count( $this->mock_sent ) - 1 );
				}
			};
			$mailer::$validator   = static function ( $email ) {
				return (bool) is_email( $email );
			};
			$GLOBALS['phpmailer'] = $mailer; // phpcs:ignore
			return true;
		}
		return false;
	}

	/**
	 * Hulp functie voor datums
	 *
	 * @param int      $day   Dag van de maand.
	 * @param int|null $month Aantal maanden in toekomst of verleden, huidige maand if null.
	 * @return int
	 */
	protected function set_date( int $day, ?int $month = null ) : int {
		if ( is_null( $month ) ) {
			return mktime( 0, 0, 0, (int) date( 'n' ), $day, (int) date( 'Y' ) );
		}
		return mktime( 0, 0, 0, $month + (int) date( 'n' ), $day, (int) date( 'Y' ) );
	}

	/**
	 * Activate the plugin which includes the kleistad specific tables if not present.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'kleistad-database-versie', 0 );
		$this->class_instance = new Kleistad();
		$upgrade              = new Admin_Upgrade();
		$upgrade->run();
		update_option( 'kleistad_email_actief', 1 );
		$this->reset_mockmailer_instance();
	}

	/**
	 * Reset the email index.
	 */
	public function tearDown() {
		$this->reset_mockmailer_instance();
		parent::tearDown();
	}

}
