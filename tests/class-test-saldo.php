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

	/**
	 * Maak een saldo
	 *
	 * @return Saldo
	 */
	private function maak_saldo(): Saldo {
		$stoker_id = $this->factory()->user->create();
		return new Saldo( $stoker_id );
	}

	/**
	 * Test creation and modification of an saldo.
	 */
	public function test_saldo() {
		$saldo1 = $this->maak_saldo();
		$this->assertEquals( 0.0, $saldo1->bedrag, 'saldo initieel not zero' );

		$saldo1->bedrag = $saldo1->bedrag + 123;
		$this->assertTrue( $saldo1->save(), 'saldo gewijzigd onjuiste status' );

		$saldo2 = new Saldo( $saldo1->klant_id );
		$this->assertEquals( 123, $saldo2->bedrag, 'saldo bedrag onjuist' );
		$this->assertTrue( $saldo2->save(), 'saldo ongewijzigd onjuiste status' );

		$upload_dir     = wp_upload_dir();
		$transactie_log = $upload_dir['basedir'] . '/stooksaldo.log';
		$this->assertFileExists( $transactie_log, 'transactie_log not created' );
	}

	/**
	 * Test function get_referentie
	 */
	public function test_get_referentie() {
		$saldo1 = $this->maak_saldo();
		$saldo1->actie->nieuw( 123.4, 'stort' );
		$referentie1 = $saldo1->get_referentie();
		$this->assertMatchesRegularExpression( '~S\d+-\d{6}-\d+~', $referentie1, 'referentie incorrect' );

		$saldo2 = new Saldo( $saldo1->klant_id );
		$saldo2->actie->nieuw( 567.8, 'stort' );
		$referentie2 = $saldo2->get_referentie();
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
		$result = $saldo->actie->nieuw( $bedrag, 'stort' );
		$this->assertTrue( $result, 'nieuw incorrect' );
		$order = new Order( $saldo->get_referentie() );

		$saldo = new Saldo( $stoker->ID );
		$saldo->betaling->verwerk( $order, $bedrag, true, 'stort' );
		$this->assertEquals( 'Betaling saldo per bankstorting', $mailer->get_last_sent( $stoker->user_email )->subject, 'verwerk incorrecte email' );
		$this->assertNotEmpty( $mailer->get_last_sent( $stoker->user_email )->attachment, 'verwerk mail attachment incorrect' );

		$saldo = new Saldo( $stoker->ID );
		$this->assertEquals( $bedrag, $saldo->bedrag, 'bedrag incorrect' );
		$this->assertEquals( 1, $mailer->get_sent_count(), 'verwerk aantal mail incorrect' );
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
		$this->assertEquals( 0, $mailer->get_sent_count(), 'verwerk aantal mail incorrect' );
		$order = new Order( $saldo->get_referentie() ); // Nog geen order.
		$saldo->betaling->verwerk( $order, $bedrag, true, 'ideal' ); // Verzend email 1.

		$saldo = new Saldo( $stoker->ID );
		$this->assertEquals( $bedrag, $saldo->bedrag, 'bedrag incorrect' );
		$this->assertEquals( 'Betaling saldo per ideal', $mailer->get_last_sent( $stoker->user_email )->subject, 'verwerk incorrecte email' );
		$this->assertNotEmpty( $mailer->get_last_sent( $stoker->user_email )->attachment, 'verwerk attachment incorrect' );
		$this->assertEquals( 1, $mailer->get_sent_count(), 'verwerk aantal mail incorrect' );

		$saldo->actie->nieuw( $bedrag, 'stort' ); // Verzend email 2.
		$order = new Order( $saldo->get_referentie() );
		$saldo->betaling->verwerk( $order, $bedrag, true, 'ideal' ); // Verzend email 3.

		$saldo = new Saldo( $stoker->ID );
		$this->assertEquals( 2 * $bedrag, $saldo->bedrag, 'bedrag incorrect' );
		$this->assertEquals( 'Betaling saldo per ideal', $mailer->get_last_sent( $stoker->user_email )->subject, 'verwerk incorrecte email' );
		$this->assertFalse( $mailer->get_last_sent( $stoker->user_email )->attachment, 'verwerk attachment incorrect' );
		$this->assertEquals( 3, $mailer->get_sent_count(), 'verwerk aantal mail incorrect' );

	}

	/**
	 * Test de verbruik functie
	 */
	public function test_verbruik() {
		$mailer        = tests_retrieve_phpmailer_instance();
		$saldo         = $this->maak_saldo();
		$stoker        = new Stoker( $saldo->klant_id );
		$saldo->bedrag = 10;
		$saldo->save();
		$saldo->actie->verbruik( 1000, 'test' );

		$saldo = new Saldo( $saldo->klant_id );
		$this->assertEquals( 10 - 1000 * opties()['materiaalprijs'] / 1000, $saldo->bedrag, 'verbruik onjuist' );

		$saldo->actie->verbruik( 4000, 'test' );
		$this->assertEquals( 'Saldo tekort', $mailer->get_last_sent( $stoker->user_email )->subject, 'verwerk incorrecte email' );
	}

	/**
	 * Test terugboeken
	 */
	public function test_restitutie() {
		$mailer = tests_retrieve_phpmailer_instance();
		$saldo  = $this->maak_saldo();
		$stoker = new Stoker( $saldo->klant_id );

		$saldo->actie->nieuw( 10, 'ideal' );
		$order = new Order( $saldo->get_referentie() );
		$saldo->betaling->verwerk( $order, 10, true, 'ideal' );
		$this->assertTrue( $saldo->actie->doe_restitutie( 'NL12INGB0001234567', 'test gebruiker' ), 'restitutie onjuist' );
		$this->assertEquals( 'Terugboeking restant saldo', $mailer->get_last_sent( $stoker->user_email )->subject, 'verwerk incorrecte email' );
	}
}
