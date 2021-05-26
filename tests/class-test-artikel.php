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
	 * Maak een artikel
	 *
	 * @return Artikel
	 */
	private function maak_artikel(): Artikel {
		$artikelcode = 'T' . wp_rand( 100, 999 );
		$artikel     = $this->getMockForAbstractClass( Artikel::class, [], '', true, true, true, [ 'maak_factuur' ] );
		$artikel->expects( $this->any() )
				->method( 'geef_referentie' )
				->will( $this->returnValue( $artikelcode ) );
		$artikel->expects( $this->any() )
				->method( 'geef_factuurregels' )
				->will( $this->returnValue( new Orderregel( 'Testartikel', 1, wp_rand( 5, 25 ) ) ) );
		$artikel->expects( $this->any() )->method( 'maak_factuur' )->willReturn( 'file' );
		$artikel->code     = $artikelcode;
		$artikel->klant_id = $this->factory->user->create();
		$artikel->betaling = new class() extends ArtikelBetaling {
			/**
			 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
			 *
			 * @since        6.7.0
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
			 * @return string|bool De redirect url ingeval van een ideal betaling of false als het niet lukt.
			 */
			public function doe_ideal( string $bericht, float $bedrag ) {
				return 'oke';
			}
		};

		return $artikel;
	}

	/**
	 * Test creation and modification of an artikel.
	 */
	public function test_controle() {
		$artikel = $this->maak_artikel();
		$this->assertFalse( empty( $artikel->controle() ), 'controle fout' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_afzeggen() {
		$artikel = $this->maak_artikel();
		$this->assertTrue( $artikel->afzeggen(), 'afzeggen incorrect' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_naw_klant() {
		$artikel           = $this->maak_artikel();
		$artikel->klant_id = $this->factory->user->create();
		$this->assertArrayHasKey( 'naam', $artikel->naw_klant(), 'naw_klant naam incorrect' );
		$this->assertArrayHasKey( 'adres', $artikel->naw_klant(), 'naw_klant adres incorrect' );
		$this->assertArrayHasKey( 'email', $artikel->naw_klant(), 'naw_klant email incorrect' );
	}

	/**
	 * Test afzeggen function
	 */
	public function maak_link() {
		$artikel = $this->maak_artikel();
		$this->assertFalse( empty( $artikel->maak_link( [ 'test' ], 'test' ) ), 'maak_link incorrect' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_geef_artikelnaam() {
		$artikel = $this->maak_artikel();
		$this->assertTrue( empty( $artikel->geef_artikelnaam() ), 'geef_artikelnaam incorrect' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_annuleer_order() {
		$artikel = $this->maak_artikel();
		$order   = new Order( $artikel->geef_referentie() );
		$order->save( 'dit is een test' );
		$this->assertEquals( 'file', $artikel->annuleer_order( $order, 5.0, 'Dit is een test' ), 'annuleer_order incorrect' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_bestel_order() {
		$artikel = $this->maak_artikel();
		$this->assertEquals( 'file', $artikel->bestel_order( 12, strtotime( '+ 1 month' ), 'Dit is een test' ), 'bestel_order incorrect' );
		$this->assertTrue( true, '' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_korting_order() {
		$artikel = $this->maak_artikel();
		$order   = new Order( $artikel->geef_referentie() );
		$order->save( 'dit is een test' );
		$this->assertEquals( 'file', $artikel->korting_order( $order, 5, 'Dit is een test' ), 'bestel_order incorrect' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_ontvang_order() {
		$artikel = $this->maak_artikel();
		$order   = new Order( $artikel->geef_referentie() );
		$order->save( 'dit is een test' );
		$this->assertEquals( 'file', $artikel->ontvang_order( $order, 10, '', true ), 'ontvang_order incorrect' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_wijzig_order() {
		$artikel = $this->maak_artikel();
		$artikel->bestel_order( 0, strtotime( '+1 month' ) );
		$order = new Order( $artikel->geef_referentie() );
		$this->assertEquals( '', $artikel->wijzig_order( $order, 'Dit is een test' ), 'wijzig_order ongewijzigd incorrect' );
		$artikel->klant_id = $this->factory->user->create();
		$this->assertEquals( 'file', $artikel->wijzig_order( $order, 'Dit is een test' ), 'wijzig_order gewijzigd incorrect' );
	}

}
