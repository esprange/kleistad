<?php
/**
 * Class Public Betaling Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Cursus_Betaling
 */

namespace Kleistad;

/**
 * Inschrijving test case.
 */
class Test_Public_Betaling extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'betaling';

	/**
	 * Formulier data.
	 *
	 * @var array $input De ingevoerde data.
	 */
	private array $input;

	/**
	 * Test de prepare functie.
	 */
	public function test_prepare() {
		/**
		 * Eerst een controle zonder dat er argumenten zijn. Die doet niets.
		 */
		$data   = [];
		$result = $this->public_actie( self::SHORTCODE, 'prepare', $data );
		$this->assertFalse( is_wp_error( $result ), 'prepare zonder argumenten incorrect' );

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
		$verkoop->bestel_order( 0.0, strtotime( '+14 days 0:00' ) );

		$_GET = [
			'order' => $verkoop->code,
			'hsh'   => $verkoop->controle(),
			'art'   => $verkoop->artikel_type,
		];
		$this->assertTrue( $this->public_actie( self::SHORTCODE, 'prepare', $data ), 'prepare met argumenten result incorrect' );
		$this->assertTrue( isset( $data['actie'] ), 'prepare met argumenten incorrect' );

		/**
		 * Nu sluiten we de order. Dan moet er een foutmelding zijn.
		 */
		$order           = new Order( $verkoop->geef_referentie() );
		$order->gesloten = true;
		$order->save( 'test' );
		$result = $this->public_actie( self::SHORTCODE, 'prepare', $data );
		$this->assertTrue( false !== strpos( $result->get_error_message(), 'Volgens onze informatie is er reeds betaald' ), 'prepare gesloten order incorrect' );

		/**
		 * Nu nog een controle met foute hash ccode.
		 */
		$_GET['hsh'] = 'false';
		$result      = $this->public_actie( self::SHORTCODE, 'prepare', $data );
		$this->assertTrue( false !== strpos( $result->get_error_message(), 'Je hebt geklikt op een ongeldige link' ), 'prepare ongeldige link incorrect' );
	}

	/**
	 * Test validate functie.
	 */
	public function test_validate() {
		$verkoop        = new LosArtikel();
		$verkoop->klant = [
			'naam'  => 'test',
			'adres' => 'straat 1 dorp',
			'email' => 'test@example.com',
		];
		$verkoop->bestelregel( 'testverkoop', 1, 10 );
		$verkoop->bestel_order( 0.0, strtotime( '+14 days 0:00' ) );
		$order = new Order( $verkoop->geef_referentie() );

		/**
		 * Test reguliere validate.
		 */
		$_POST  = [
			'order_id'     => $order->id,
			'betaal'       => 'ideal',
			'artikel_type' => $verkoop->artikel_type,
		];
		$data   = [];
		$result = $this->public_actie( self::SHORTCODE, 'validate', $data );
		if ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $error ) {
				echo $error . "\n"; // phpcs:ignore
			}
		}
		$this->assertFalse( is_wp_error( $result ), 'validate incorrect' );

		/**
		 * Test alsnog of betaling al heeft plaatsgevonden.
		 */
		$order->gesloten = true;
		$order->save( 'test' );
		$result = $this->public_actie( self::SHORTCODE, 'validate', $data );
		$this->assertTrue( false !== strpos( $result->get_error_message(), 'Volgens onze informatie is er reeds betaald' ), 'validate gesloten order incorrect' );

		// @todo Er zou ook nog een test moeten zijn vwb beschikbaarheid.
	}

	/**
	 * Test functie save.
	 */
	public function test_save() {
		$verkoop        = new LosArtikel();
		$verkoop->klant = [
			'naam'  => 'test',
			'adres' => 'straat 1 dorp',
			'email' => 'test@example.com',
		];
		$verkoop->bestelregel( 'testverkoop', 1, 10 );
		$verkoop->bestel_order( 0.0, strtotime( '+14 days 0:00' ) );
		$order = new Order( $verkoop->geef_referentie() );

		$data   = [
			'input'   =>
			[
				'order_id'     => $order->id,
				'betaal'       => 'ideal',
				'artikel_type' => $verkoop->artikel_type,
			],
			'order'   => $order,
			'artikel' => $verkoop,
		];
		$result = $this->public_actie( self::SHORTCODE, 'save', $data );
		$this->assertTrue( isset( $result['redirect_uri'] ), 'geen ideal verwijzing na betaling' );
	}


}
