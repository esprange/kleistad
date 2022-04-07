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
	 * Verkoop een artikel om een order aan te maken.
	 *
	 * @return LosArtikel
	 */
	private function maak_order() : LosArtikel {
		$verkoop        = new LosArtikel();
		$verkoop->klant = [
			'naam'  => 'test',
			'adres' => 'straat 1 dorp',
			'email' => 'test@example.com',
		];
		$verkoop->bestelregel( 'testverkoop', 1, 10 );
		$verkoop->save();
		return $verkoop;
	}

	/**
	 * Test erase function.
	 */
	public function test_erase() {
		$order1           = new Order( 'X1' );
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
		$verkoop         = $this->maak_order();
		$order1          = new Order( $verkoop->get_referentie() );
		$order1->betaald = 4;
		$order1->orderregels->toevoegen( new Orderregel( 'artikel', 1, 10 ) );
		$order1->afboeken();
		$this->assertEquals( 10, $order1->betaald, 'afboeken originele order incorrect' );
		$order2 = new Order( '@-' . $verkoop->get_referentie() );
		$this->assertEquals( 6, $order2->betaald, 'afboeken originele order incorrect' );
	}

	/**
	 * Test bestellen function
	 * 1. Normale bestelling.
	 * 2. Bestelling hergebruiken.
	 *
	 * @return void
	 */
	public function test_bestel() {
		$verkoop = $this->maak_order();

		$order1 = new Order( $verkoop->get_referentie() );
		$order1->bestel( 4.0 );
		$this->assertEquals( 4.0, $order1->betaald, 'betaald status incorrect' );
		$this->assertEquals( 6.0, $order1->get_te_betalen(), 'te betalen status  incorrect' );

		$verkoop->bestelregel( 'ander artikel', 1, 20.0 );
		$verkoop->save();
		$order2 = new Order( $verkoop->get_referentie() );
		$this->assertEmpty( $order2->bestel(), 'hergebruik order onjuist' );
	}

	/**
	 * Test annuleren  function
	 * 1. Normale bestelling.
	 * 2. Bestelling annuleren.
	 *
	 * @return void
	 */
	public function test_annuleer() {
		$verkoop = $this->maak_order();

		$order1 = new Order( $verkoop->get_referentie() );
		$order1->bestel();
		$factuur = $order1->annuleer( 2.50, 'test' );
		$order2  = new Order( $verkoop->get_referentie() );
		$this->assertEquals( 2.50, $order2->get_te_betalen(), 'te betalen bij annulering incorrect' );
		$this->assertStringContainsString( 'creditfactuur', $factuur, 'credit factuur ontbreekt' );
	}

	/**
	 * Test annuleren  function
	 * 1. Normale bestelling.
	 * 2. Bestelling korting geven.
	 *
	 * @return void
	 */
	public function test_korting() {
		$verkoop = $this->maak_order();

		$order1 = new Order( $verkoop->get_referentie() );
		$order1->bestel();
		$factuur = $order1->korting( 3.0, 'test 1' );

		$order2 = new Order( $verkoop->get_referentie() );
		$this->assertEquals( 7.00, $order2->get_te_betalen(), 'te betalen bij korting incorrect' );
		$this->assertStringContainsString( 'factuur', $factuur, 'correctie factuur ontbreekt' );

		$order2->korting( 2.0, 'test 2' );
		$order3 = new Order( $verkoop->get_referentie() );
		$this->assertEquals( 5.00, $order3->get_te_betalen(), 'te betalen bij korting incorrect' );
	}

	/**
	 * Test annuleren  function
	 * 1. Normale bestelling.
	 * 2. Bestelling wijziging.
	 *
	 * @return void
	 */
	public function test_wijzig() {
		$verkoop = $this->maak_order();

		$order1 = new Order( $verkoop->get_referentie() );
		$order1->bestel();
		$verkoop->bestelregel( 'ander artikel', 1, 20.0 );
		$verkoop->save();
		$factuur = $order1->wijzig( $verkoop->get_referentie() );

		$order2 = new Order( $verkoop->get_referentie() );
		$this->assertEquals( 30.00, $order2->get_te_betalen(), 'te betalen bij wijzig incorrect' );
		$this->assertStringContainsString( 'factuur', $factuur, 'correctie factuur ontbreekt' );
	}

	/**
	 * Test ontvang  function
	 * 1. Normale bestelling.
	 * 2. Ontvang betaling.
	 *
	 * @return void
	 */
	public function test_ontvang() {
		$verkoop = $this->maak_order();

		$order1 = new Order( $verkoop->get_referentie() );
		$order1->bestel();
		$order1->ontvang( 6.0, 'test' );
		$this->assertEquals( 4.00, $order1->get_te_betalen(), 'te betalen bij wijzig incorrect' );
	}

	/**
	 * Test is_annuleerbaar function
	 */
	public function test_is_annuleerbaar() {
		$order             = new Order( 'X1' );
		$order->referentie = 'TEST';
		$this->assertTrue( $order->is_annuleerbaar(), 'is_annuleerbaar gewone order incorrect' );
		$order->credit_id = 1;
		$this->assertFalse( $order->is_annuleerbaar(), 'is_annuleerbaar gecrediteerde order incorrect' );
		$order->credit_id  = 0;
		$order->referentie = '@-12345';
		$this->assertFalse( $order->is_annuleerbaar(), 'is_annuleerbaar afgeboekte order incorrect' );
	}

	/**
	 * Test is_afboekbaar function
	 */
	public function test_is_afboekbaar() {
		$verkoop = new LosArtikel();
		$verkoop->bestelregel( 'Artikel !', 1, 12.50 );
		$verkoop->save();
		$order = new Order( $verkoop->get_referentie() );
		$order->bestel();
		$order->verval_datum = strtotime( 'yesterday' );
		$this->assertFalse( $order->is_afboekbaar(), 'is afboekbaar na vervallen incorrect' );
		$order->verval_datum = strtotime( '- 60 day' );
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
	public function test_get_factuurnummer() {
		$order            = new Order( 'dummy' );
		$order->factuurnr = 123;
		$this->assertMatchesRegularExpression( '~20[0-9]{2}-\d{6}~', $order->get_factuurnummer(), 'factuurnummer incorrect' );
	}

	/**
	 * Test te_betalen function
	 */
	public function test_get_te_betalen() {
		$order1 = new Order( 'X' . wp_rand( 1000 ) );
		$order1->orderregels->toevoegen( new Orderregel( 'artikel', 1, 10 ) );
		$order1->betaald = 4;
		$this->assertEquals( 6, $order1->get_te_betalen(), 'te_betalen regulier incorrect' );
		$order1->gesloten = true;
		$this->assertEquals( 0, $order1->get_te_betalen(), 'te_betalen gesloten incorrect' );
	}
}
