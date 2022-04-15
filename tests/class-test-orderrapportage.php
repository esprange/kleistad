<?php
/**
 * Class Orderrapportage Test
 *
 * @package Kleistad
 */

namespace Kleistad;

/**
 * Inschrijving test case.
 */
class Test_Orderrapportage extends Kleistad_UnitTestCase {

	/**
	 * Test erase function.
	 */
	public function test_maandrapport() {
		$netto     = 0.0;
		$btw       = 0.0;
		$order_ids = $this->factory()->order->create_many( 10 );
		foreach ( $order_ids as $order_id ) {
			$order  = new Order( $order_id );
			$netto += $order->orderregels->get_netto();
			$btw   += $order->orderregels->get_btw();
		}
		$orderrapportage = new Orderrapportage();
		$rapport         = $orderrapportage->maandrapport( idate( 'm' ), idate( 'Y' ) );
		foreach ( $rapport as $artikelsoort ) {
			$netto -= $artikelsoort['netto'];
			$btw   -= $artikelsoort['btw'];
		}
		$this->assertEquals( 0.0, $netto, 'Netto niet 0' );
		$this->assertEquals( 0.0, $btw, 'BTW niet 0' );
	}

	/**
	 * Test de maanddetails functie
	 *
	 * @return void
	 */
	public function test_maanddetails() {
		$netto     = 0.0;
		$btw       = 0.0;
		$order_ids = $this->factory()->order->create_many( 10 );
		foreach ( $order_ids as $order_id ) {
			$order  = new Order( $order_id );
			$netto += $order->orderregels->get_netto();
			$btw   += $order->orderregels->get_btw();
		}
		$orderrapportage = new Orderrapportage();
		$rapport         = $orderrapportage->maanddetails( idate( 'm' ), idate( 'Y' ), substr( $order->referentie, 0, 1 ) );
		$this->assertEquals( 10, count( $rapport ), 'aantal orders in detail onjuist' );
	}
}
