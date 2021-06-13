<?php
/**
 * Class Saldo Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Saldo, \Kleistad\SaldoActie, \Kleistad\SaldoBetaling
 */

namespace Kleistad;

/**
 * Inschrijving test case.
 */
class Test_Saldo extends Kleistad_UnitTestCase {

	private const CURSUSNAAM = 'Testcursus';

	/**
	 * Maak een saldo
	 *
	 * @return Saldo
	 */
	private function maak_saldo(): Saldo {
		$stoker_id = $this->factory->user->create();
		$saldo     = $this->getMockBuilder( Saldo::class )->setMethods( [ 'maak_factuur' ] )->setConstructorArgs(
			[
				$stoker_id,
			]
		)->getMock();
		$saldo->method( 'maak_factuur' )->willReturn( __FILE__ );

		return $saldo;
	}

	/**
	 * Test creation and modification of an saldo.
	 */
	public function test_saldo() {
		$saldo = $this->maak_saldo();
		$this->assertEquals( 0.0, $saldo->bedrag, 'saldo initieel not zero' );

		$saldo->bedrag = $saldo->bedrag + 123;
		$saldo->reden  = 'test';
		$saldo->save();
		$upload_dir     = wp_upload_dir();
		$transactie_log = $upload_dir['basedir'] . '/stooksaldo.log';
		$this->assertFileExists( $transactie_log, 'transactie_log not created' );
	}

	/**
	 * Test function geef_referentie
	 */
	public function test_geef_referentie() {
		$saldo = $this->maak_saldo();
		$saldo->actie->nieuw( 123.4, 'bank' );
		$referentie1 = $saldo->geef_referentie();
		$this->assertRegExp( '~S\d+-\d{6}-\d+~', $referentie1, 'referentie incorrect' );
		$saldo->actie->nieuw( 567.8, 'bank' );
		$referentie2 = $saldo->geef_referentie();
		$this->assertNotEquals( $referentie1, $referentie2, 'referentie wijziging incorrect' );
	}

	/**
	 * Test verwerk function for bank payment
	 */
	public function test_verwerk_bank() {
		$mailer = tests_retrieve_phpmailer_instance();
		$saldo  = $this->maak_saldo();
		$stoker = new Stoker( $saldo->klant_id );
		$bedrag = 123.45;
		$result = $saldo->actie->nieuw( $bedrag, 'bank' );
		$this->assertTrue( $result, 'nieuw incorrect' );
		$order = new Order( $saldo->geef_referentie() );

		$saldo = new Saldo( $stoker->ID );
		$saldo->betaling->verwerk( $order, $bedrag, true, 'bank' );
		$this->assertEquals( 'Bijstorting stooksaldo', $mailer->get_last_sent( $stoker->user_email )->subject, 'verwerk incorrecte email' );
		$this->assertNotEmpty( $mailer->get_last_sent( $stoker->user_email )->attachment, 'verwerk mail attachment incorrect' );

		$saldo = new Saldo( $stoker->ID );
		$this->assertEquals( $bedrag, $saldo->bedrag, 'bedrag incorrect' );
		$this->assertEquals( 1, $mailer->get_sent_count( $stoker->user_email ), 'verwerk aantal mail incorrect' );
	}

	/**
	 * Test verwerk function for ideal payment
	 */
	public function test_verwerk_ideal() {
		$mailer = tests_retrieve_phpmailer_instance();
		$saldo  = $this->maak_saldo();
		$stoker = new Stoker( $saldo->klant_id );

		$bedrag = 12.45;

		$result = $saldo->actie->nieuw( $bedrag, 'ideal' ); // Verzend geen email.
		$this->assertTrue( false !== filter_var( $result, FILTER_VALIDATE_URL, [ 'options' => FILTER_FLAG_QUERY_REQUIRED ] ), 'ideal url incorrect' );
		$this->assertEquals( 0, $mailer->get_sent_count( $stoker->user_email ), 'verwerk aantal mail incorrect' );
		$order = new Order(); // Nog geen order.
		$saldo = new Saldo( $stoker->ID );
		$saldo->betaling->verwerk( $order, $bedrag, true, 'ideal' ); // Verzend email 1.

		$saldo = new Saldo( $stoker->ID );
		$this->assertEquals( $bedrag, $saldo->bedrag, 'bedrag incorrect' );
		$this->assertEquals( 'Bijstorting stooksaldo', $mailer->get_last_sent( $stoker->user_email )->subject, 'verwerk incorrecte email' );
		$this->assertNotEmpty( $mailer->get_last_sent( $stoker->user_email )->attachment, 'verwerk attachment incorrect' );
		$this->assertEquals( 1, $mailer->get_sent_count( $stoker->user_email ), 'verwerk aantal mail incorrect' );

		$bedrag = 12.45;
		$saldo->actie->nieuw( $bedrag, 'bank' ); // Verzend email 2.
		$order = new Order( $saldo->geef_referentie() );
		$saldo->betaling->verwerk( $order, $bedrag, true, 'ideal' ); // Verzend email 3.

		$saldo = new Saldo( $stoker->ID );
		$this->assertEquals( 2 * $bedrag, $saldo->bedrag, 'bedrag incorrect' );
		$this->assertEquals( 'Bijstorting stooksaldo', $mailer->get_last_sent( $stoker->user_email )->subject, 'verwerk incorrecte email' );
		$this->assertFalse( $mailer->get_last_sent( $stoker->user_email )->attachment, 'verwerk attachment incorrect' );
		$this->assertEquals( 3, $mailer->get_sent_count( $stoker->user_email ), 'verwerk aantal mail incorrect' );

	}
}
