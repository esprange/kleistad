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
		$array   = [];
		$array[] = new Orderregel( 'artikel', 1, 11 );
		$array[] = new Orderregel( Orderregel::KORTING, 1, -2 );
		$orderregels->toevoegen( $array );
		$this->assertEquals( 3, $orderregels->count(), 'toevoegen tweede en derde regel incorrect' );
		$orderregels->toevoegen( new Orderregel( Orderregel::KORTING, 1, -5 ) );
		$this->assertEquals( 3, $orderregels->count(), 'toevoegen vierde regel incorrect' );
		$this->assertEquals( 10 + 11 - 2 - 5, $orderregels->bruto(), 'toevoegen bedrag incorrect' );
	}

	/**
	 * Test vervangen function
	 */
	public function test_vervangen() {
		$orderregels = new Orderregels();
		$array1      = [];
		$array1[]    = new Orderregel( 'artikel', 1, 21 );
		$array1[]    = new Orderregel( 'artikel', 1, 22 );
		$array1[]    = new Orderregel( Orderregel::KORTING, 1, -3 );
		$orderregels->toevoegen( $array1 );
		$array2   = [];
		$array2[] = new Orderregel( 'artikel', 1, 11 );
		$array2[] = new Orderregel( 'artikel', 1, 12 );
		$orderregels->vervangen( $array2 );
		$this->assertEquals( 3, $orderregels->count(), 'vervangen incorrect' );
		$this->assertEquals( 11 + 12 - 3, $orderregels->bruto(), 'vervangen bedrag incorrect' );
	}

	/**
	 * Test bruto, netto, btw functions
	 */
	public function test_bruto_netto_btw() {
		$orderregels = new Orderregels();
		$array1      = [];
		$array1[]    = new Orderregel( 'artikel', 1, 11 );
		$array1[]    = new Orderregel( 'artikel', 1, 12 );
		$array1[]    = new Orderregel( Orderregel::KORTING, 1, -3 );
		$orderregels->toevoegen( $array1 );
		$this->assertEquals( 11 + 12 - 3, $orderregels->bruto(), 'bruto incorrect' );
		$this->assertEquals( round( ( 11 + 12 - 3 ) / 1.21, 2 ), $orderregels->netto(), 'netto incorrect' );
		$this->assertEquals( round( 11 + 12 - 3 - ( 11 + 12 - 3 ) / 1.21, 2 ), $orderregels->btw(), 'netto incorrect' );
	}

}
