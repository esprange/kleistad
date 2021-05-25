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
		$artikel     = $this->getMockForAbstractClass( Artikel::class );
		$artikel->expects( $this->any() )
				->method( 'geef_referentie' )
				->will( $this->returnValue( $artikelcode ) );
		$artikel->expects( $this->any() )
				->method( 'geef_factuurregels' )
				->will( $this->returnValue( new Orderregel( 'Testartikel', 1, 10 ) ) );
		$artikel->code = $artikelcode;

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
		$this->assertTrue( true, '' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_bestel_order() {
		$artikel = $this->maak_artikel();
		$this->assertTrue( true, '' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_korting_order() {
		$artikel = $this->maak_artikel();
		$this->assertTrue( true, '' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_ontvang_order() {
		$artikel = $this->maak_artikel();
		$this->assertTrue( true, '' );
	}

	/**
	 * Test afzeggen function
	 */
	public function test_wijzig_order() {
		$artikel = $this->maak_artikel();
		$this->assertTrue( true, '' );
	}

}
