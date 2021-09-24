<?php
/**
 * Class Public Dagdelenkaart Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Dagdelenkaart
 */

namespace Kleistad;

/**
 * Dagdelenkaart test case.
 */
class Test_Public_Dagdelenkaart extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'dagdelenkaart';

	/**
	 * Formulier data.
	 *
	 * @var array $input De ingevoerde data.
	 */
	private array $input;

	/**
	 * Maak een dagdelenkaart aan.
	 */
	private function maak_dagdelenkaart() {
		$gebruiker_id = $this->factory->user->create();
		$gebruiker    = get_user_by( 'ID', $gebruiker_id );
		$this->input  = [
			'user_email'      => $gebruiker->user_email,
			'email_controle'  => $gebruiker->user_email,
			'first_name'      => $gebruiker->first_name,
			'last_name'       => $gebruiker->last_name,
			'straat'          => 'straat',
			'huisnr'          => '12345',
			'pcode'           => '1234AB',
			'plaats'          => 'plaats',
			'telnr'           => '0123456789',
			'start_datum'     => date( 'd-m-Y', strtotime( '+ 1 month' ) ),
			'gebruiker_id'    => $gebruiker_id,
			'opmerking'       => '',
			'betaal'          => 'ideal',
			'mc4wp-subscribe' => null,
		];
	}

	/**
	 * Test prepare functie;
	 */
	public function test_prepare() {
		$data   = [ 'actie' => '-' ];
		$result = $this->public_actie( self::SHORTCODE, 'display', $data, [ 'verklaring' => 'test' ] );
		if ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $error ) {
				echo $error . "\n"; // phpcs:ignore
			}
		}
		$this->assertFalse( is_wp_error( $result ), 'prepare incorrect' );
		$this->assertTrue( 'test' === ( $data['verklaring'] ?? '' ), 'prepare verklaring incorrect' );
	}

	/**
	 * Test validate functie.
	 */
	public function test_validate() {
		$this->maak_dagdelenkaart();
		$_POST  = $this->input;
		$data   = [];
		$result = $this->public_actie( self::SHORTCODE, 'validate', $data );
		if ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $error ) {
				echo $error . "\n"; // phpcs:ignore
			}
		}
		$this->assertFalse( is_wp_error( $result ), 'validate incorrect' );
	}

	/**
	 * Test functie save.
	 */
	public function test_save() {
		$this->maak_dagdelenkaart();
		$data   = [ 'input' => $this->input ];
		$result = $this->public_actie( self::SHORTCODE, 'save', $data );
		$this->assertTrue( isset( $result['redirect_uri'] ), 'geen ideal verwijzing na dagdelenkaart bewaren' );

		/**
		 * Dagdelenkaart moet herhaald kunnen worden.
		 */
		$result = $this->public_actie( self::SHORTCODE, 'save', $data );
		$this->assertTrue( isset( $result['redirect_uri'] ), 'geen ideal verwijzing na dagdelenkaart bewaren' );
	}

}
