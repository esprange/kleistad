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
class Test_Order extends Kleistad_UnitTestCase {

	/**
	 * Test erase function.
	 */
	public function test_erase() {
		$order1           = new Order();
		$order1->gesloten = true;
		$order1_id        = $order1->save( 'dit is een test' );
		$order1->erase();
		$order2 = new Order( $order1_id );
		$this->assertFalse( $order2->gesloten, 'erase incorrect' );
	}

	/**
	 * Test afboeken function
	 * 1. maak een order aan met waarde x waarvoor betaald is y
	 * 2. voer de afboeking uit
	 * 3. controleer dat de order volledig betaald is
	 * 4. controleer dat de dubieuze debiteuren order aangemaakt is met een waard x - y
	 */
	public function test_afboeken() {
		$referentie         = 'X' . wp_rand( 10, 99 );
		$order1             = new Order();
		$order1->betaald    = 4;
		$order1->referentie = $referentie;
		$order1->orderregels->toevoegen( new Orderregel( 'artikel', 1, 10 ) );
		$order1->afboeken();
		$this->assertEquals( 10, $order1->betaald, 'afboeken originele order incorrect' );
		$order2 = new Order( "@-$referentie" );
		$this->assertEquals( 6, $order2->betaald, 'afboeken originele order incorrect' );
	}

	/**
	 * Test is_geblokkeerd function
	 *
	 * @TODO uitbreiden.
	 */
	public function test_is_geblokkeerd() {
		$this->assertTrue( true, 'is_geblokkeerd incorrect' );
	}

	/**
	 * Test is_annuleerbaar function
	 */
	public function test_is_annuleerbaar() {
		$order             = new Order();
		$order->referentie = 'TEST';
		$this->assertTrue( $order->is_annuleerbaar(), 'is_annuleerbaar gewone order incorrect' );
		$order->credit_id = 1;
		$this->assertFalse( $order->is_annuleerbaar(), 'is_annuleerbaar gecrediteerde order incorrect' );
		$order->credit_id  = 0;
		$order->referentie = '@-12345';
		$this->assertFalse( $order->is_annuleerbaar(), 'is_annuleerbaar afgeboekte order incorrect' );
	}

	/**
	 * Test is_credit function
	 */
	public function test_is_credit() {
		$order = new Order();
		$this->assertFalse( $order->is_credit(), 'is_credit gewone order incorrect' );
		$order->origineel_id = 1;
		$this->assertTrue( $order->is_credit(), 'is_credit credit order incorrect' );
	}

	/**
	 * Test is_afboekbaar function
	 */
	public function test_is_afboekbaar() {
		$order = new Order();
		$order->orderregels->toevoegen( new Orderregel( 'artikel', 1, 10 ) );
		$order->verval_datum = strtotime( 'yesterday' );
		$this->assertFalse( $order->is_afboekbaar(), 'is afboekbaar na vervallen incorrect' );
		$order->verval_datum = strtotime( '- 2 month' );
		$this->assertTrue( $order->is_afboekbaar(), 'is afboekbaar na vervallen en afboektermijn incorrect' );
	}

	/**
	 * Test is_terugstorting actief function
	 *
	 * @TODO Nog uit te werken.
	 */
	public function test_is_terugstorting_actief() {
		$this->assertTrue( true, 'is_terugstorting_actief incorrect' );
	}

	/**
	 * Test factuurnummer function
	 */
	public function test_factuurnummer() {
		$order            = new Order();
		$order->factuurnr = 123;
		$this->assertRegExp( '~20[0-9]{2}-\d{6}~', $order->factuurnummer(), 'factuurnummer incorrect' );
	}

	/**
	 * Test te_betalen function
	 */
	public function test_te_betalen() {
		$order1 = new Order();
		$order1->orderregels->toevoegen( new Orderregel( 'artikel', 1, 10 ) );
		$order1->betaald = 4;
		$this->assertEquals( 6, $order1->te_betalen(), 'te_betalen regulier incorrect' );
		$order1->gesloten = true;
		$this->assertEquals( 0, $order1->te_betalen(), 'te_betalen gesloten incorrect' );

		$order2 = new Order();
		$order2->orderregels->toevoegen( new Orderregel( 'artikel', 1, 10 ) );
		$order3               = new Order();
		$order2->credit_id    = $order3->id;
		$order3->origineel_id = $order2->id;

	}
}
