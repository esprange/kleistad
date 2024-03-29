<?php
/**
 * Class Public Betaling Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Betaling
 * @noinspection PhpUnhandledExceptionInspection, PhpArrayWriteIsNotUsedInspection, PhpArrayIndexImmediatelyRewrittenInspection
 */

namespace Kleistad;

/**
 * Betaling test case.
 */
class Test_Public_Betaling extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'betaling';

	/**
	 * Test de prepare functie.
	 */
	public function test_prepare() {
		/**
		 * Eerst een controle zonder dat er argumenten zijn. Die doet niets.
		 */
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertEmpty( $result, 'prepare zonder argumenten incorrect' );

		/**
		 * Nu een controle van een reguliere verkoop, die moet ok gaan.
		 */
		$verkoop        = new LosArtikel();
		$verkoop->klant = [
			'naam'  => 'test',
			'adres' => 'straat 1 dorp',
			'email' => 'test@example.com',
		];
		$verkoop->bestelregel( 'testverkoop', 1, 10 );
		$verkoop->save();
		$order = new Order( $verkoop->get_referentie() );
		$order->bestel();
		$_GET   = [
			'order' => $order->referentie,
			'hsh'   => $verkoop->get_controle(),
		];
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( 'testverkoop', $result, 'prepare met argumenten result incorrect' );

		/**
		 * Nu sluiten we de order. Dan moet er een foutmelding zijn.
		 */
		$order           = new Order( $verkoop->get_referentie() );
		$order->gesloten = true;
		$order->save( 'test' );
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( 'Volgens onze informatie staat er geen te betalen bedrag open', $result, 'prepare gesloten order incorrect' );

		/**
		 * Nu nog een controle met foute hash ccode.
		 */
		$_GET['hsh'] = 'false';
		$result      = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( 'Je hebt geklikt op een ongeldige link', $result, 'prepare ongeldige link incorrect' );
	}

	/**
	 * Test process functie.
	 */
	public function test_process() {
		$verkoop        = new LosArtikel();
		$verkoop->klant = [
			'naam'  => 'test',
			'adres' => 'straat 1 dorp',
			'email' => 'test@example.com',
		];
		$verkoop->bestelregel( 'testverkoop', 1, 10 );
		$verkoop->save();
		$order = new Order( $verkoop->get_referentie() );
		$order->bestel();

		/**
		 * Test reguliere validate.
		 */
		$_POST  = [
			'order_id'     => $order->id,
			'betaal'       => 'ideal',
			'artikel_type' => $verkoop->artikel_type,
		];
		$result = $this->public_form_actie( self::SHORTCODE, [], 'betalen' );
		$this->assertArrayHasKey( 'redirect_uri', $result, 'geen ideal verwijzing na betaling' );
		/**
		 * Test alsnog of betaling al heeft plaatsgevonden.
		 */
		$order->gesloten = true;
		$order->save( 'test' );
		$result = $this->public_form_actie( self::SHORTCODE, [], 'betalen' );
		$this->assertStringContainsString( 'Volgens onze informatie staat er geen te betalen bedrag open', $result['status'], 'validate gesloten order incorrect' );

		// @todo Er zou ook nog een test moeten zijn vwb beschikbaarheid.
	}
}
