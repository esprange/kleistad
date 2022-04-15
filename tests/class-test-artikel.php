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
		/**
		 * Suppress de phpstorm foutmelding
		 */
		$artikel           = $this->getMockForAbstractClass( Artikel::class, [], '', true, true, true );
		$artikel->code     = 'X' . wp_rand( 100, 999 );
		$artikel->klant_id = $this->factory()->user->create();

		/**
		 * Een stub voor de geef referentie functie die een dummy artikel code terug geeft.
		 */
		$artikel->expects( $this->any() )
				->method( 'get_referentie' )
				->will( $this->returnValue( $artikel->code ) );

		/**
		 * Een stub voor de get_factuurregels functie welke een testartikel aanlegt tegen het opgegeven bedrag.
		 */
		$orderregels = new Orderregels();
		$orderregels->toevoegen( new Orderregel( 'Testartikel', 1, $bedrag ) );
		$artikel->expects( $this->any() )
				->method( 'get_factuurregels' )
				->will( $this->returnValue( $orderregels ) );

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
			 * @param string $bericht    Het bericht bij succesvolle betaling.
			 * @param float  $bedrag     Het te betalen bedrag.
			 * @param string $referentie De referentie string.
			 *
			 * @return string De redirect url ingeval van een ideal betaling of false als het niet lukt.
			 */
			public function doe_ideal( string $bericht, float $bedrag, string $referentie ) : string {
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
		$this->assertNotEmpty( $artikel->get_controle(), 'controle fout' );
	}

	/**
	 * Test naw function
	 */
	public function test_get_naw_klant() {
		$artikel           = $this->maak_artikel( 10 );
		$artikel->klant_id = $this->factory()->user->create();
		$this->assertArrayHasKey( 'naam', $artikel->get_naw_klant(), 'naw_klant naam incorrect' );
		$this->assertArrayHasKey( 'adres', $artikel->get_naw_klant(), 'naw_klant adres incorrect' );
		$this->assertArrayHasKey( 'email', $artikel->get_naw_klant(), 'naw_klant email incorrect' );
	}

	/**
	 * Test maak link function
	 */
	public function test_get_link() {
		$artikel = $this->maak_artikel( 10 );
		$this->assertNotEmpty( $artikel->get_link( [ 'test' ], 'test' ), 'get_link incorrect' );
	}

	/**
	 * Test artikelnaam function
	 */
	public function test_get_artikelnaam() {
		$artikel = $this->maak_artikel( 10 );
		$this->assertEmpty( $artikel->get_artikelnaam(), 'get_artikelnaam incorrect' );
	}


}
