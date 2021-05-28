<?php
/**
 * Class Artikel Test
 *
 * @package Kleistad
 */

namespace Kleistad;

/**
 * Inschrijving test case.
 */
class Test_Artikel extends Kleistad_UnitTestCase {

	/**
	 * Maak een stub artikel. Artikel is een abstract class dus om deze te testen is deze stub nodig.
	 *
	 * @param float $bedrag De prijs van het artikel.
	 *
	 * @return Artikel
	 */
	private function maak_artikel( float $bedrag ): Artikel {
		$artikel           = $this->getMockForAbstractClass( Artikel::class, [], '', true, true, true, [ 'maak_factuur' ] );
		$artikel->code     = 'T' . wp_rand( 100, 999 );
		$artikel->klant_id = $this->factory->user->create();

		/**
		 * Een stub voor de geef referentie functie die een dummy artikel code terug geeft.
		 */
		$artikel->expects( $this->any() )
				->method( 'geef_referentie' )
				->will( $this->returnValue( $artikel->code ) );

		/**
		 * Een stub voor de geef_factuurregels functie welke een testartikel aanlegt tegen het opgegeven bedrag.
		 */
		$artikel->expects( $this->any() )
				->method( 'geef_factuurregels' )
				->will( $this->returnValue( new Orderregel( 'Testartikel', 1, $bedrag ) ) );

		/**
		 * Een stub voor de maak factuur functie. Deze geeft alleen het factuur type terug.
		 */
		$artikel->expects( $this->any() )->method( 'maak_factuur' )->will(
			$this->returnCallback(
				function() {
					$args = func_get_args();
					return $args[1] . 'factuur'; // Het type.
				}
			)
		);

		/**
		 * Een stub zodat er een ArtikelBetaling object aangemaakt wordt met daar in een lege implementatie van de abstracte functies.
		 */
		$artikel->betaling = new class() extends ArtikelBetaling {
			/**
			 * Verwerk een betaling. Aangeroepen vanuit de betaal callback. In geval van Artikel hoeft dit niet getest te worden.
			 *
			 * @param Order|null $order         De order als deze bestaat.
			 * @param float      $bedrag        Het betaalde bedrag, wordt hier niet gebruikt.
			 * @param bool       $betaald       Of er werkelijk betaald is.
			 * @param string     $type          Type betaling, ideal , directdebit of bank.
			 * @param string     $transactie_id De betaling id.
			 */
			public function verwerk( ?Order $order, float $bedrag, bool $betaald, string $type, string $transactie_id = '' ) {
			}

			/**
			 * Betaal het artikel met iDeal.
			 *
			 * @param string $bericht Het bericht bij succesvolle betaling.
			 * @param float  $bedrag  Het te betalen bedrag.
			 *
			 * @return string De redirect url ingeval van een ideal betaling of false als het niet lukt.
			 */
			public function doe_ideal( string $bericht, float $bedrag ) {
				return '';
			}
		};

		return $artikel;
	}

	/**
	 * Test creation and modification of an artikel.
	 */
	public function test_controle() {
		$artikel = $this->maak_artikel( 10 );
		$this->assertFalse( empty( $artikel->controle() ), 'controle fout' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_afzeggen() {
		$artikel = $this->maak_artikel( 10 );
		$this->assertTrue( $artikel->afzeggen(), 'afzeggen incorrect' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_naw_klant() {
		$artikel           = $this->maak_artikel( 10 );
		$artikel->klant_id = $this->factory->user->create();
		$this->assertArrayHasKey( 'naam', $artikel->naw_klant(), 'naw_klant naam incorrect' );
		$this->assertArrayHasKey( 'adres', $artikel->naw_klant(), 'naw_klant adres incorrect' );
		$this->assertArrayHasKey( 'email', $artikel->naw_klant(), 'naw_klant email incorrect' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_maak_link() {
		$artikel = $this->maak_artikel( 10 );
		$this->assertFalse( empty( $artikel->maak_link( [ 'test' ], 'test' ) ), 'maak_link incorrect' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_geef_artikelnaam() {
		$artikel = $this->maak_artikel( 10 );
		$this->assertTrue( empty( $artikel->geef_artikelnaam() ), 'geef_artikelnaam incorrect' );
	}

	/**
	 * Test afzeggen function
	 *
	 * 1. bestel een artikel met prijs x en betaal y.
	 * 2. annuleer de order met restant z en controleer of een credit factuur wordt aangemaakt.
	 * 3. controleer of bij de order nog bedrag z - y openstaat.
	 */
	public function test_annuleer_order() {
		$artikel = $this->maak_artikel( 123 );
		$artikel->bestel_order( 10, strtotime( '+ 1 month' ) );
		$order = new Order( $artikel->geef_referentie() );
		$this->assertEquals( 'creditfactuur', $artikel->annuleer_order( $order, 25, 'Dit is een test' ), 'annuleer_order incorrect' );
		$order = new Order( $artikel->geef_referentie() );
		$this->assertEquals( 15, $order->te_betalen(), 'bestel_order bedrag na betaling incorrect' );
	}

	/**
	 * Test bestel order function
	 *
	 * 1. bestel een artikel met prijs x en betaal meteen bedrag y.
	 * 2. controleer of er een factuur wordt aangemaakt.
	 * 3. controleer of het nog openstaande bedrag gelijk is aan x - y.
	 * 4. bestel nog een artikel met prijs z en betaal niets.
	 * 5. controleer of er een factuur wordt aangemaakt.
	 * 6. controleer of het nog openstaande bedrag gelijk is aan z.
	 */
	public function test_bestel_order() {
		$artikel1 = $this->maak_artikel( 123 );
		$this->assertEquals( 'factuur', $artikel1->bestel_order( 12, strtotime( '+ 1 month' ), 'Dit is een test' ), 'bestel_order factuur ontbreekt' );
		$order1 = new Order( $artikel1->geef_referentie() );
		$this->assertEquals( 111, $order1->te_betalen(), 'bestel_order bedrag na betaling incorrect' );

		$artikel2 = $this->maak_artikel( 345 );
		$this->assertEquals( 'factuur', $artikel2->bestel_order( 0, strtotime( '+ 1 month' ), 'Dit is een test' ), 'bestel_order factuur ontbreekt' );
		$order2 = new Order( $artikel2->geef_referentie() );
		$this->assertEquals( 345, $order2->te_betalen(), 'bestel_order bedrag incorrect' );
	}

	/**
	 * Test afzeggen function
	 *
	 * 1. bestel een artikel met prijs x.
	 * 2. geef korting y.
	 * 3. controleer of er een factuur aangemaakt is.
	 * 4. controleer of het nog te betalen bedrag gelijk is aan x - y.
	 */
	public function test_korting_order() {
		$artikel = $this->maak_artikel( 10 );
		$artikel->bestel_order( 0, strtotime( '+ 1 month' ) );
		$order = new Order( $artikel->geef_referentie() );
		$order->save( 'dit is een test' );
		$this->assertEquals( 'correctiefactuur', $artikel->korting_order( $order, 5, 'Dit is een test' ), 'bestel_order incorrect' );
		$order = new Order( $artikel->geef_referentie() );
		$this->assertEquals( 5, $order->te_betalen(), 'korting_order bedrag incorrect' );
	}

	/**
	 * Test ontvang order function
	 *
	 * 1. Bestel een artikel met prijs x.
	 * 2. Ontvang bedrag y voor de order.
	 * 3. Controleer of er een factuur aangemaakt is
	 * 4. Controleer of het nog te betalen bedrag gelijk is aan x - y.
	 */
	public function test_ontvang_order() {
		$artikel = $this->maak_artikel( 10 );
		$artikel->bestel_order( 0, strtotime( '+ 1 month' ) );
		$order = new Order( $artikel->geef_referentie() );
		$order->save( 'dit is een test' );
		$this->assertEquals( 'factuur', $artikel->ontvang_order( $order, 5, '', true ), 'ontvang_order incorrect' );
		$order = new Order( $artikel->geef_referentie() );
		$this->assertEquals( 5, $order->te_betalen(), 'ontvang_order bedrag incorrect' );
	}

	/**
	 * Test wijzig order function
	 *
	 * 1. Bestel een artikel met prijs x.
	 * 2. Geef de wijziging aan.
	 * 3. Controleer dat er geen factuur wordt aangemaakt.
	 * 4. Maak een wijziging op de bestelling.
	 * 5. Geef de wijziging aan.
	 * 6. Controleer of er een factuur aangemaakt is.
	 * 7. Controleer of de prijs nog steeds gelijk is aan x.
	 */
	public function test_wijzig_order() {
		$artikel = $this->maak_artikel( 10 );
		$artikel->bestel_order( 0, strtotime( '+1 month' ) );
		$order = new Order( $artikel->geef_referentie() );
		$this->assertEquals( '', $artikel->wijzig_order( $order, 'Dit is een test' ), 'wijzig_order ongewijzigd incorrect' );
		$artikel->klant_id = $this->factory->user->create();
		$this->assertEquals( 'correctiefactuur', $artikel->wijzig_order( $order, 'Dit is een test' ), 'wijzig_order gewijzigd incorrect' );
		$order = new Order( $artikel->geef_referentie() );
		$this->assertEquals( 10, $order->te_betalen(), 'ontvang_order bedrag incorrect' );
	}

}
