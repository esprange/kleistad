<?php
/**
 * Class Order Test
 *
 * @package Kleistad
 */

namespace Kleistad;

/**
 * Inschrijving test case.
 */
class Test_Orderregel extends Kleistad_UnitTestCase {

	/**
	 * Test toevoegen function.
	 */
	public function test_toevoegen() {
		$orderregels = new Orderregels();
		$orderregels->toevoegen( new Orderregel( 'artikel', 1, 10 ) );
		$this->assertEquals( 1, $orderregels->count(), 'toevoegen eerste regel incorrect' );
		$orderregels->toevoegen( new Orderregel( 'artikel', 1, 11 ) );
		$orderregels->toevoegen( new Orderregel( Orderregel::KORTING, 1, -2 ) );
		$this->assertEquals( 3, $orderregels->count(), 'toevoegen tweede en derde regel incorrect' );
		$orderregels->toevoegen( new Orderregel( Orderregel::KORTING, 1, -5 ) );
		$this->assertEquals( 3, $orderregels->count(), 'toevoegen vierde regel incorrect' );
		$this->assertEquals( 10 + 11 - 2 - 5, $orderregels->get_bruto(), 'toevoegen bedrag incorrect' );
	}

	/**
	 * Test bruto, netto, btw functions
	 */
	public function test_bruto_netto_get_btw() {
		$orderregels = new Orderregels();
		$orderregels->toevoegen( new Orderregel( 'artikel', 1, 11 ) );
		$orderregels->toevoegen( new Orderregel( 'artikel', 1, 12 ) );
		$orderregels->toevoegen( new Orderregel( Orderregel::KORTING, 1, -3 ) );
		$this->assertEquals( 11 + 12 - 3, $orderregels->get_bruto(), 'bruto incorrect' );
		$this->assertEquals( round( ( 11 + 12 - 3 ) / 1.21, 2 ), $orderregels->get_netto(), 'netto incorrect' );
		$this->assertEquals( round( 11 + 12 - 3 - ( 11 + 12 - 3 ) / 1.21, 2 ), $orderregels->get_btw(), 'netto incorrect' );
	}

}
