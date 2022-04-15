<?php
/**
 * Class LosArtikel Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\LosArtikel, \Kleistad\LosArtikelBetaling
 * @noinspection PhpPossiblePolymorphicInvocationInspection, PhpUndefinedFieldInspection
 */

namespace Kleistad;

/**
 * Inschrijving test case.
 */
class Test_LosArtikel extends Kleistad_UnitTestCase {

	/**
	 * Maak een LosArtikel
	 *
	 * @return LosArtikel
	 */
	private function maak_losartikel(): LosArtikel {
		$koper             = $this->factory()->user->create_and_get();
		$losartikel        = new LosArtikel();
		$losartikel->klant = [
			'naam'  => $koper->display_name,
			'adres' => 'nb',
			'email' => $koper->user_email,
		];
		return $losartikel;
	}

	/**
	 * Test creation and modification of an artikel.
	 */
	public function test_losartikel() {
		$bestelling = $this->maak_losartikel();
		$bestelling->bestelregel( 'artikel 1', 1, 5.00 );
		$bestelling->save();
		$bestelling2 = new LosArtikel( $bestelling->get_referentie() );
		$this->assertEquals( 5.0, $bestelling2->prijs, 'prijs 1 onjuist' );
		$bestelling2->bestelregel( 'artikel 2', 2, 2.50 );
		$bestelling2->save();
		$bestelling3 = new LosArtikel( $bestelling->get_referentie() );
		$this->assertEquals( 10.0, $bestelling3->prijs, 'prijs 2 onjuist' );
	}

	/**
	 * Test creation and modification of an artikel.
	 */
	public function test_bestelregel() {
		$bestelling = $this->maak_losartikel();
		$bestelling->bestelregel( 'artikel 1', 1, 5.00 );
		$this->assertEquals( 5.0, $bestelling->prijs, 'prijs 1 onjuist' );
		$bestelling->bestelregel( 'artikel 2', 2, 2.50 );
		$this->assertEquals( 10.0, $bestelling->prijs, 'prijs 2 onjuist' );
	}

	/**
	 * Test function get_facturatieregels
	 *
	 * @return void
	 */
	public function test_get_facturatieregels() {
		$bestelling = $this->maak_losartikel();
		$bestelling->bestelregel( 'artikel 1', 1, 5.00 );
		$bestelling->bestelregel( 'artikel 2', 2, 2.50 );
		$orderregels = $bestelling->get_factuurregels();
		$this->assertEquals( 10.0, $orderregels->get_bruto(), 'facturatieregels onjuist' );
	}

	/**
	 * Test function get_referentie
	 */
	public function test_get_referentie() {
		$bestelling  = $this->maak_losartikel();
		$referentie1 = $bestelling->get_referentie();
		$this->assertMatchesRegularExpression( '~X\d+~', $referentie1, 'referentie incorrect' );
	}

	/**
	 * Test verwerk function for bank payment
	 */
	public function test_verwerk_bank() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$bestelling = $this->maak_losartikel();
		$bestelling->bestelregel( 'artikel 1', 1, 5.00 );
		$bestelling->save();
		$order = new Order( $bestelling->get_referentie() );
		$order->bestel();

		$bestelling2 = new LosArtikel( $bestelling->get_referentie() );
		$bestelling2->betaling->verwerk( $order, 5.0, true, 'bank' );
		$this->assertEquals( 0, $mailer->get_sent_count(), 'onjuiste email verstuurd' );
	}

	/**
	 * Test verwerk function for ideal payment
	 */
	public function test_verwerk_ideal() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$bestelling = $this->maak_losartikel();
		$bestelling->bestelregel( 'artikel 1', 1, 5.00 );
		$bestelling->save();
		$order = new Order( $bestelling->get_referentie() );
		$order->bestel();

		$bestelling2 = new LosArtikel( $bestelling->get_referentie() );
		$bestelling2->betaling->verwerk( $order, 5.0, true, 'ideal' );
		$this->assertStringContainsString( 'Bestelling Kleistad op', $mailer->get_last_sent( $bestelling->klant['email'] )->subject, 'verwerk incorrecte email' );
		$this->assertEmpty( $mailer->get_last_sent( $bestelling->klant['email'] )->attachment, 'verwerk attachment incorrect' );
	}
}
