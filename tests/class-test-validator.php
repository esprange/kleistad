<?php
/**
 * Class Test Validator
 *
 * @package Kleistad
 */

namespace Kleistad;

/**
 * Validator test case.
 */
class Test_Validator extends Kleistad_UnitTestCase {

	/**
	 * Test gebruiker validator function.
	 */
	public function test_gebruiker() {
		$validator = new Validator();
		$gebruiker = [
			'user_email'     => 'example@test.nl',
			'email_controle' => 'example@test.nl',
			'telnr'          => '0123456789',
			'pcode'          => '1234AB',
			'first_name'     => 'Test',
			'last_name'      => 'Case',
		];
		$this->assertTrue( $validator->gebruiker( $gebruiker ), 'validatie gebruiker incorrect' );

		$gebruiker['email_controle'] = 'anders@test.nl';
		$this->assertTrue( is_wp_error( $validator->gebruiker( $gebruiker ) ), 'validatie foute email gebruiker incorrect' );

		$gebruiker = [
			'user_email'     => 'example@test.nl',
			'email_controle' => 'anders@test.nl',
			'telnr'          => '0123456',
			'pcode'          => '1234',
			'first_name'     => '1 Test',
			'last_name'      => '2 Case',
		];
		$errors    = $validator->gebruiker( $gebruiker );
		$this->assertTrue( is_wp_error( $errors ), 'validatie foute gebruiker incorrect' );
		$this->assertEquals( 5, count( $errors->get_error_messages() ), 'validatie foute gebruiker aantal fout incorrect' );

		$gebruiker = [
			'user_email'     => 'example test.nl',
			'email_controle' => 'anders@test.nl',
			'telnr'          => '0123456',
			'pcode'          => '1234',
			'first_name'     => '1 Test',
			'last_name'      => '2 Case',
		];
		$errors    = $validator->gebruiker( $gebruiker );
		$this->assertTrue( is_wp_error( $errors ), 'validatie foute email gebruiker incorrect' );
		$this->assertEquals( 5, count( $errors->get_error_messages() ), 'validatie foute email gebruiker aantal fout incorrect' );

		$gebruiker = [
			'user_email'     => '',
			'email_controle' => '',
			'telnr'          => '0123456',
			'pcode'          => '1234',
			'first_name'     => '1 Test',
			'last_name'      => '2 Case',
		];
		$errors    = $validator->gebruiker( $gebruiker );
		$this->assertTrue( is_wp_error( $errors ), 'validatie lege email gebruiker incorrect' );
		$this->assertEquals( 5, count( $errors->get_error_messages() ), 'validatie lege email gebruiker aantal fout incorrect' );
	}

	/**
	 * Test telnr validator function.
	 */
	public function test_telnr() {
		$validator = new Validator();
		$telnr     = '0123456789';
		$this->assertTrue( $validator->telnr( $telnr ), 'happy flow telnr incorrect' );
		$telnr = '';
		$this->assertTrue( $validator->telnr( $telnr ), 'leeg telnr incorrect' );
		$telnr = '1234567890';
		$this->assertFalse( $validator->telnr( $telnr ), 'onjuist telnr incorrect' );
		$telnr = '012345678901';
		$this->assertFalse( $validator->telnr( $telnr ), 'te lang telnr incorrect' );
		$telnr = '0123';
		$this->assertFalse( $validator->telnr( $telnr ), 'te kort telnr incorrect' );
		$telnr = '0123abc4';
		$this->assertFalse( $validator->telnr( $telnr ), 'foute karakters telnr incorrect' );
	}

	/**
	 * Test postcode validator function.
	 */
	public function test_pcode() {
		$validator = new Validator();
		$pcode     = '1234AB';
		$this->assertTrue( $validator->pcode( $pcode ), 'happy flow pcode incorrect' );
		$pcode = '';
		$this->assertTrue( $validator->pcode( $pcode ), 'leeg pcode incorrect' );
		$pcode = '123AB';
		$this->assertFalse( $validator->pcode( $pcode ), 'te kort pcode incorrect' );
		$pcode = '12345';
		$this->assertFalse( $validator->pcode( $pcode ), 'te lang pcode incorrect' );
		$pcode = '1234ABC';
		$this->assertFalse( $validator->pcode( $pcode ), 'te lang pcode incorrect' );
	}

	/**
	 * Test naam validator function
	 */
	public function test_naam() {
		$validator = new Validator();
		$naam      = 'Abcdefgh';
		$this->assertTrue( $validator->naam( $naam ), 'happy flow naam incorrect' );
		$naam = 'Abcd Efgh';
		$this->assertTrue( $validator->naam( $naam ), 'dubbele naam incorrect' );
		$naam = 'Abcd-Efgh';
		$this->assertTrue( $validator->naam( $naam ), 'dubbele naam met koppelteken incorrect' );
		$naam = '\'s Abcdü-EfghÂ';
		$this->assertTrue( $validator->naam( $naam ), 'naam met speciale tekens incorrect' );
		$naam = '1';
		$this->assertFalse( $validator->naam( $naam ), 'numerieke naam incorrect' );
		$naam = '';
		$this->assertFalse( $validator->naam( $naam ), 'lege naam flow incorrect' );
	}

	/**
	 * Test email validator function
	 */
	public function test_email() {
		$validator = new Validator();
		$email     = 'example@test.nl';
		$this->assertTrue( $validator->email( $email ), 'happy flow email incorrect' );
		$email = 'exampletest.nl';
		$this->assertFalse( $validator->email( $email ), 'email zonder @ incorrect' );
		$email = 'nieuw example@test.a';
		$this->assertFalse( $validator->email( $email ), 'email fout prefix incorrect' );
		$email = '';
		$this->assertFalse( $validator->email( $email ), 'lege email incorrect' );
	}

}
