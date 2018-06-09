<?php
/**
 * Class GebruikerTest
 *
 * @package Kleistad
 */

/**
 * Gebruiker test case.
 */
class KleistadGebruikerTest extends WP_UnitTestCase {

	/**
	 * User id
	 *
	 * @var int $user_id The user.
	 */
	private $user_id;

	/**
	 * Activate the plugin which includes the kleistad specific tables if not present.
	 */
	public function setUp() {
		parent::setUp();
		activate_kleistad();
		$this->user_id = $this->factory->user->create();
	}

	/**
	 * Test creation and modification of a gebruiker.
	 */
	public function test_gebruiker() {
		$gebruiker1             = new Kleistad_Gebruiker( $this->user_id );
		$gebruiker1->telnr      = 'telnr';
		$gebruiker1->straat     = 'straat';
		$gebruiker1->huisnr     = 'huisnr';
		$gebruiker1->pcode      = 'pcode';
		$gebruiker1->plaats     = 'plaats';
		$gebruiker1->voornaam   = 'voornaam';
		$gebruiker1->achternaam = 'achternaam';
		$gebruiker1->save();

		$gebruiker2 = new Kleistad_Gebruiker( $this->user_id );
		$this->assertEquals( 'telnr', $gebruiker2->telnr, 'gebruiker not equals telnr' );
		$this->assertEquals( 'straat', $gebruiker2->straat, 'gebruiker not equals straat' );
		$this->assertEquals( 'huisnr', $gebruiker2->huisnr, 'gebruiker not equals huisnr' );
		$this->assertEquals( 'pcode', $gebruiker2->pcode, 'gebruiker not equals pcode' );
		$this->assertEquals( 'plaats', $gebruiker2->plaats, 'gebruiker not equals plaats' );
		$this->assertEquals( 'voornaam', $gebruiker2->voornaam, 'gebruiker not equals voornaam' );
		$this->assertEquals( 'achternaam', $gebruiker2->achternaam, 'gebruiker not equals voornaam' );
	}

	/**
	 * Test creation and modification of a gebruiker.
	 */
	public function test_gebruikers() {
		$gebruikers = Kleistad_Gebruiker::all();

		$this->assertGreaterThan( 0, count( $gebruikers ), 'gebruikers count not greater than 0' );

	}

}
