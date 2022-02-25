<?php
/**
 * Class Public Dagdelenkaart Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Dagdelenkaart
 * @noinspection PhpUndefinedFieldInspection, PhpUnhandledExceptionInspection
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
			'user_email'     => $gebruiker->user_email,
			'email_controle' => $gebruiker->user_email,
			'first_name'     => $gebruiker->first_name,
			'last_name'      => $gebruiker->last_name,
			'straat'         => 'straat',
			'huisnr'         => '12345',
			'pcode'          => '1234AB',
			'plaats'         => 'plaats',
			'telnr'          => '0123456789',
			'start_datum'    => date( 'd-m-Y', strtotime( '+ 1 month' ) ),
			'gebruiker_id'   => $gebruiker_id,
			'opmerking'      => '',
			'betaal'         => 'ideal',
		];
	}

	/**
	 * Test prepare functie;
	 */
	public function test_prepare() {
		$result = $this->public_display_actie( self::SHORTCODE, [ 'verklaring' => 'test' ] );
		$this->assertStringContainsString( 'test', $result, 'prepare verklaring incorrect' );
	}

	/**
	 * Test process functie.
	 */
	public function test_process() {
		$this->maak_dagdelenkaart();
		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [] );
		$this->assertArrayHasKey( 'redirect_uri', $result, 'geen ideal verwijzing na bestellen' );
	}

}
