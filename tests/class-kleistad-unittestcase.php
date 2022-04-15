<?php
/**
 * Class Kleistad_UnitTestCase
 *
 * @package Kleistad
 */

namespace Kleistad;

use WP_UnitTestCase;
use WP_UnitTest_Factory;
use MockPHPMailer;
use ReflectionObject;
use ReflectionException;

/**
 * Mock filter input array function
 *
 * @param int       $type      Type input Post of Get.
 * @param array|int $options   Filter opties.
 * @param bool      $add_empty Afwezige keys als null tonen.
 */
function filter_input_array( int $type, array|int $options = FILTER_DEFAULT, bool $add_empty = true ): bool|array|null {
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
function filter_input( int $type, string $var_name, int $filter = FILTER_DEFAULT, array|int $options = 0 ): mixed {
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
	 * Fetches the factory object for generating WordPress fixtures.
	 *
	 * @return WP_UnitTest_Factory The fixture factory.
	 */
	protected static function factory() {

		static $factory = null;
		if ( ! $factory ) {
			$factory = new Kleistad_Factory();
		}
		return $factory;
	}

	/**
	 * Mock de protected display actie
	 *
	 * @param string $shortcode_tag De shortcode die getest wordt.
	 * @param array  $atts          De eventuele attributes meegegeven aan de shortcode.
	 * @param string $display_actie De display actie.
	 *
	 * @return mixed
	 * @throws Kleistad_Exception  De Kleistad exceptie.
	 * @throws ReflectionException De Reflectie exceptie.
	 */
	protected function public_display_actie( string $shortcode_tag, array $atts, string $display_actie = Shortcode::STANDAARD_ACTIE ): mixed {
		$_GET['actie'] = $display_actie;
		$shortcode     = $this->geef_shortcode_object( $shortcode_tag, $atts );
		$refobject     = new ReflectionObject( $shortcode );
		$refmethod     = $refobject->getMethod( 'display' );
		$refmethod->setAccessible( true );
		return $refmethod->invoke( $shortcode );
	}

	/**
	 * Mock de protected form process actie
	 *
	 * @param string $shortcode_tag De shortcode die getest wordt.
	 * @param array  $atts          De eventuele attributes meegegeven aan de shortcode.
	 * @param string $form_actie    De formulier actie.
	 *
	 * @return mixed
	 * @throws Kleistad_Exception  De Kleistad exceptie.
	 * @throws ReflectionException De Reflectie exceptie.
	 */
	protected function public_form_actie( string $shortcode_tag, array $atts, string $form_actie = '' ): mixed {
		$shortcode = $this->geef_shortcode_object( $shortcode_tag, $atts );
		$refobject = new ReflectionObject( $shortcode );
		$refmethod = $refobject->getMethod( 'process' );
		$refactie  = $refobject->getProperty( 'form_actie' );
		$refmethod->setAccessible( true );
		$refactie->setAccessible( true );
		$refactie->setValue( $shortcode, $form_actie );
		return $refmethod->invoke( $shortcode );
	}

	/**
	 * Mock een file download
	 *
	 * @param string   $shortcode_tag De shortcode.
	 * @param array    $atts          Shortcode attributen.
	 * @param string   $method        De gevraagde file functie.
	 * @param resource $file_handle   De file handle.
	 *
	 * @return mixed
	 * @throws Kleistad_Exception  De Kleistad exceptie.
	 * @throws ReflectionException De Reflectie exceptie.
	 */
	protected function public_download_actie( string $shortcode_tag, array $atts, string $method, $file_handle ): mixed {
		$shortcode     = $this->geef_shortcode_object( $shortcode_tag, $atts );
		$refobject     = new ReflectionObject( $shortcode );
		$refmethod     = $refobject->getMethod( $method );
		$reffilehandle = $refobject->getProperty( 'filehandle' );
		$reffilehandle->setAccessible( true );
		$reffilehandle->setValue( $shortcode, $file_handle );
		$refmethod->setAccessible( true );
		return $refmethod->invoke( $shortcode );
	}

	/**
	 * Initieer het shortcode object
	 *
	 * @param string $shortcode_tag De shortcode.
	 * @param array  $atts          De shortcode attributen.
	 *
	 * @return Shortcode
	 * @throws Kleistad_Exception  De Kleistad exceptie.
	 * @throws ReflectionException De Reflectie exceptie.
	 */
	private function geef_shortcode_object( string $shortcode_tag, array $atts ) : Shortcode {
		$shortcode = Shortcode::get_instance( $shortcode_tag, $atts );
		$refobject = new ReflectionObject( $shortcode );
		$refdata   = $refobject->getProperty( 'data' );
		$refdata->setAccessible( true );
		$refdata->setValue( $refobject, $atts );
		return $shortcode;
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
				 * @param string $email_address Email adress waarop gezocht moet worden.
				 * @param int    $index         Email die gevonden moet worden, null = laatste, 1 = voorlaatste etc.
				 *
				 * @return false|object
				 */
				public function get_last_sent( string $email_address = '', int $index = 0 ): object|bool {
					if ( empty( $email_address ) ) {
						$sent = array_reverse( $this->mock_sent )[ $index ];
						return false === $sent ? false : (object) $sent;
					}
					$last = count( $this->mock_sent );
					while ( $last > 0 ) {
						$last --;
						/**
						 * Een beetje dirty, kijk of het email adres voorkomt in het array.
						 */
						if ( str_contains( serialize( $this->mock_sent[ $last ] ), $email_address ) ) { // phpcs:ignore
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
				public function get_last_recipient( string $address_type ): object|bool {
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
	 * @param int $day   Dag van de maand.
	 * @param int $month Aantal maanden in toekomst of verleden, huidige maand if null.
	 * @return int
	 */
	protected function set_date( int $day, int $month = 0 ) : int {
		return mktime( 0, 0, 0, $month + (int) date( 'n' ), $day, (int) date( 'Y' ) );
	}

	/**
	 * Activate the plugin which includes the kleistad specific tables if not present.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'kleistad-database-versie', 0 );
		new Kleistad();
		$upgrade = new Admin_Upgrade();
		$upgrade->run();
		global $wpdb;
		foreach ( [ 'orders', 'inschrijvingen', 'cursussen', 'reserveringen', 'ovens', 'workshops' ] as $tabel ) {
			$wpdb->query( "DELETE FROM {$wpdb->prefix}kleistad_$tabel WHERE TRUE" ); // phpcs:ignore
		}
		update_option( 'kleistad_email_actief', 1 );
		$losnr = get_option( 'kleistad_losnr' );
		update_option( 'kleistad_losnr', $losnr++ );
		$this->reset_mockmailer_instance();
		$_GET  = [];
		$_POST = [];
	}

	/**
	 * Reset the email index.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$this->reset_mockmailer_instance();
		parent::tearDown();
	}

}
