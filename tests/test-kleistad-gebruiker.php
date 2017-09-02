<?php
/**
 * Class SampleTest
 *
 * @package Kleistad
 */

/**
 * Sample test case.
 */
class KleistadGebruikerTest extends WP_UnitTestCase {

	/**
	 * Activate the plugin which includes the kleistad specific tables if not present.
	 */
	public function setUp() {
		activate_kleistad();
	}

	/**
	 * Test creation and modification of a gebruiker.
	 */
	function test_gebruiker() {
		$user_id = $this->factory->user->create(
			[
				'role' => 'subscriber',
			]
		);

		$gebruiker1 = new Kleistad_Gebruiker( $user_id );
		$gebruiker1->telnr = 'telnr';
		$gebruiker1->straat = 'straat';
		$gebruiker1->huisnr = 'huisnr';
		$gebruiker1->pcode = 'pcode';
		$gebruiker1->plaats = 'plaats';
		$gebruiker1->email = 'email';
		$gebruiker1->voornaam = 'voornaam';
		$gebruiker1->achternaam = 'achternaam';
		$gebruiker1->save();

		$gebruiker2 = new Kleistad_Gebruiker( $user_id );
		$this->assertEquals( 'telnr', $gebruiker2->telnr, 'gebruiker not equals telnr' );
		$this->assertEquals( 'straat', $gebruiker2->straat, 'gebruiker not equals straat' );
		$this->assertEquals( 'huisnr', $gebruiker2->huisnr, 'gebruiker not equals huisnr' );
		$this->assertEquals( 'pcode', $gebruiker2->pcode, 'gebruiker not equals pcode' );
		$this->assertEquals( 'plaats', $gebruiker2->plaats, 'gebruiker not equals plaats' );
		$this->assertEquals( 'email', $gebruiker2->email, 'gebruiker not equals email' );
		$this->assertEquals( 'voornaam', $gebruiker2->voornaam, 'gebruiker not equals voornaam' );
		$this->assertEquals( 'achternaam', $gebruiker2->achternaam, 'gebruiker not equals voornaam' );
	}

	/**
	 * Test creation and modification of a gebruiker.
	 */
	function test_gebruikers() {
		$user_id = $this->factory->user->create(
			[
				'role' => 'subscriber',
			]
		);

		$gebruikers_store = new Kleistad_Gebruikers();
		$gebruikers_from_store = $gebruikers_store->get();

		$this->assertGreaterThan( 0, $gebruikers_store->count(), 'gebruikers count not greater than 0' );
	}

}
