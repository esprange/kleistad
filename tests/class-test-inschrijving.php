<?php
/**
 * Class Inschrijving Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Inschrijving, \Kleistad\Inschrijvingen, \Kleistad\Cursist
 */

namespace Kleistad;

/**
 * Inschrijving test case.
 */
class Test_Inschrijving extends Kleistad_UnitTestCase {

	private const CURSUSNAAM = 'Testcursus';

	/**
	 * Maak een inschrijving
	 *
	 * @return Inschrijving
	 */
	private function maak_inschrijving(): Inschrijving {
		$cursist_id              = $this->factory->user->create();
		$cursus                  = new Cursus();
		$cursus->naam            = self::CURSUSNAAM;
		$cursus->start_datum     = strtotime( '+1 month' );
		$cursus->inschrijfkosten = 25.0;
		$cursus->cursuskosten    = 100.0;
		$cursus_id               = $cursus->save();

		$inschrijving = $this->getMockBuilder( Inschrijving::class )->setMethods( [ 'maak_factuur' ] )->setConstructorArgs(
			[
				$cursus_id,
				$cursist_id,
			]
		)->getMock();
		$inschrijving->method( 'maak_factuur' )->willReturn( __FILE__ );

		return $inschrijving;
	}

	/**
	 * Test creation and modification of an inschrijving.
	 */
	public function test_inschrijving() {
		$cursist_id          = $this->factory->user->create();
		$cursus              = new Cursus();
		$cursus->start_datum = strtotime( 'now' );
		$cursus_id           = $cursus->save();

		$inschrijving1                  = new Inschrijving( $cursus_id, $cursist_id );
		$inschrijving1->opmerking       = 'test inschrijving';
		$inschrijving1->ingedeeld       = true;
		$inschrijving1->technieken      = [ 'draaien' ];
		$inschrijving1->aantal          = 3;
		$inschrijving1->wacht_datum     = strtotime( 'today' );
		$inschrijving1->extra_cursisten = [ 2, 3 ];
		$inschrijving1->save();

		$inschrijving2 = new Inschrijving( $cursus_id, $cursist_id );
		$this->assertEquals( $inschrijving1->opmerking, $inschrijving2->opmerking, 'opmerking inschrijving not equal' );
		$this->assertEquals( $inschrijving1->ingedeeld, $inschrijving2->ingedeeld, 'ingedeeld inschrijving not equal' );
		$this->assertEquals( $inschrijving1->technieken, $inschrijving2->technieken, 'technieken inschrijving not equal' );
		$this->assertEquals( $inschrijving1->aantal, $inschrijving2->aantal, 'aantal inschrijving not equal' );
		$this->assertEquals( $inschrijving1->wacht_datum, $inschrijving2->wacht_datum, 'wacht_datum inschrijving not equal' );
		$this->assertEquals( $inschrijving1->extra_cursisten, $inschrijving2->extra_cursisten, 'extra_cursisten inschrijving not equal' );
	}

	/**
	 * Test function erase
	 */
	public function test_erase() {
		$inschrijving1 = $this->maak_inschrijving();
		$inschrijving1->save();
		$this->assertTrue( $inschrijving1->erase(), 'erase inschrijving incorrect' );
	}

	/**
	 * Test geef artikel naam.
	 */
	public function test_geef_artikelnaam() {
		$inschrijving1 = $this->maak_inschrijving();
		$this->assertEquals( self::CURSUSNAAM, $inschrijving1->geef_artikelnaam(), 'geef_artikelnaam incorrect' );
	}

	/**
	 * Test heeft_restant function
	 */
	public function test_heeft_restant() {
		$inschrijving1 = $this->maak_inschrijving();
		$this->assertFalse( empty( $inschrijving1->heeft_restant() ), 'heeft restant toekomst incorrect' );

		$inschrijving1->cursus->start_datum = strtotime( 'tomorrow' );
		$this->assertTrue( empty( $inschrijving1->heeft_restant() ), 'heeft restant morgen incorrect' );
	}

	/**
	 * Test toon_aantal function
	 */
	public function test_toon_aantal() {
		$inschrijving = $this->maak_inschrijving();

		$inschrijving->aantal          = 3;
		$inschrijving->extra_cursisten = [];
		$this->assertFalse( empty( $inschrijving->toon_aantal() ), 'toon_aantal > 1, extra 0 incorrect' );

		$inschrijving->aantal          = 3;
		$inschrijving->extra_cursisten = [ 2 ];
		$this->assertFalse( empty( $inschrijving->toon_aantal() ), 'toon_aantal > 1, extra 1 incorrect' );

		$inschrijving->aantal          = 3;
		$inschrijving->extra_cursisten = [ 2, 3 ];
		$this->assertTrue( empty( $inschrijving->toon_aantal() ), 'toon_aantal > 1, extra 2 incorrect' );

		$inschrijving->aantal          = 1;
		$inschrijving->extra_cursisten = [];
		$this->assertTrue( empty( $inschrijving->toon_aantal() ), 'toon_aantal 1 incorrect' );
	}

	/**
	 * Test geef_referentie function
	 */
	public function test_geef_referentie() {
		$inschrijving = $this->maak_inschrijving();
		$this->assertEquals( "C{$inschrijving->cursus->id}-{$inschrijving->klant_id}", $inschrijving->geef_referentie(), 'geef referentie incorrect' );
	}

	/**
	 * Test geef status tekst
	 */
	public function test_geef_statustekst() {
		$inschrijving = $this->maak_inschrijving();

		$inschrijving->geannuleerd = false;
		$inschrijving->ingedeeld   = false;
		$this->assertEquals( 'ingeschreven', $inschrijving->geef_statustekst(), 'geef_statustekst ingeschreven incorrect' );

		$inschrijving->ingedeeld = true;
		$this->assertEquals( 'ingedeeld', $inschrijving->geef_statustekst(), 'geef_statustekst ingedeeld incorrect' );

		$inschrijving->geannuleerd = true;
		$this->assertEquals( 'geannuleerd', $inschrijving->geef_statustekst(), 'geef_statustekst geannuleerd incorrect' );
	}

	/**
	 * Test actie afzeggen function
	 */
	public function test_afzeggen() {
		$inschrijving1                  = $this->maak_inschrijving();
		$inschrijving1->aantal          = 1;
		$inschrijving1->geannuleerd     = false;
		$inschrijving1->extra_cursisten = [];
		$inschrijving1->save();

		$inschrijving1->actie->afzeggen();
		$this->assertTrue( $inschrijving1->geannuleerd, 'afzeggen enkele cursist incorrect' );

		$inschrijving2                  = $this->maak_inschrijving();
		$inschrijving2->aantal          = 2;
		$inschrijving1->geannuleerd     = false;
		$inschrijving2->extra_cursisten = [];
		$inschrijving2->save();

		$inschrijving2->actie->afzeggen();
		$this->assertTrue( $inschrijving2->geannuleerd, 'afzeggen meerdere cursisten incorrect' );

		$inschrijving3                        = $this->maak_inschrijving();
		$inschrijving3->aantal                = 2;
		$inschrijving3->geannuleerd           = false;
		$extra_cursist_id                     = $this->factory->user->create();
		$inschrijving_extra                   = new Inschrijving( $inschrijving3->cursus->id, $extra_cursist_id );
		$inschrijving_extra->hoofd_cursist_id = $inschrijving3->klant_id;
		$inschrijving_extra->save();
		$inschrijving3->extra_cursisten = [ $extra_cursist_id ];
		$inschrijving3->save();

		$inschrijving3->actie->afzeggen();
		$this->assertTrue( $inschrijving3->geannuleerd, 'afzeggen meerdere cursisten incorrect' );
		$inschrijving_extra = new Inschrijving( $inschrijving3->cursus->id, $extra_cursist_id );
		$this->assertTrue( $inschrijving_extra->geannuleerd, 'afzeggen extra cursisten incorrect' );
	}

	/**
	 * Test bestel_order function
	 */
	public function test_bestel_order() {
		$inschrijving1 = $this->maak_inschrijving();
		$inschrijving1->save();
		$factuur = $inschrijving1->bestel_order( 0, strtotime( 'today' ), '', '', true );
		$this->assertFileExists( $factuur, 'bestel_order incorrect' );
		$order = new Order( $inschrijving1->geef_referentie() );
		$this->assertTrue( $order->id > 0, 'bestel_order incorrect' );
	}

	/**
	 * Test annuleer_order function
	 */
	public function test_annuleer_order() {
		$inschrijving1 = $this->maak_inschrijving();
		$inschrijving1->save();
		$inschrijving1->bestel_order( 0, strtotime( 'today' ), '', '', false );
		$order = new Order( $inschrijving1->geef_referentie() );
		$inschrijving1->annuleer_order( $order, 24.0, '' );
		$this->assertTrue( $order->id > 0, 'bestel_order incorrect' );

	}

	/**
	 * Test correctie cursus inschrijving
	 */
	public function test_correctie() {
		$mailer       = tests_retrieve_phpmailer_instance();
		$inschrijving = $this->maak_inschrijving();
		$cursist      = new Cursist( $inschrijving->klant_id );
		$inschrijving->actie->aanvraag( 'bank' );

		$cursus_nieuw               = new Cursus();
		$cursus_nieuw->naam         = 'Nieuwe cursus';
		$cursus_nieuw->cursuskosten = 67.00;
		$cursus_nieuw->save();

		$inschrijving->actie->correctie( $cursus_nieuw->id, 1 );

		$inschrijving = new Inschrijving( $cursus_nieuw->id, $cursist->ID );
		$order        = new Order( $inschrijving->geef_referentie() );
		$this->assertEquals( 'Wijziging inschrijving cursus', $mailer->get_last_sent( $cursist->user_email )->subject, 'correctie email incorrect' );
		$this->assertEquals( 25.00 + 67.00, $order->te_betalen(), 'correctie kosten te betalen onjuist' );
		$this->assertNotEmpty( $mailer->get_last_sent( $cursist->user_email )->attachment, 'correctie email attachment ontbreekt' );

		$inschrijving->actie->correctie( $cursus_nieuw->id, 2 );

		$inschrijving = new Inschrijving( $cursus_nieuw->id, $cursist->ID );
		$order        = new Order( $inschrijving->geef_referentie() );
		$this->assertEquals( 'Wijziging inschrijving cursus', $mailer->get_last_sent( $cursist->user_email )->subject, 'correctie email incorrect' );
		$this->assertEquals( 2 * ( 25.00 + 67.00 ), $order->te_betalen(), 'correctie kosten te betalen onjuist' );
		$this->assertNotEmpty( $mailer->get_last_sent( $cursist->user_email )->attachment, 'correctie email attachment ontbreekt' );

	}

	/**
	 * Test indelend lopende cursus
	 */
	public function test_indelen_lopend() {
		$mailer       = tests_retrieve_phpmailer_instance();
		$inschrijving = $this->maak_inschrijving();
		$cursist      = new Cursist( $inschrijving->klant_id );

		$inschrijving->actie->indelen_lopend( 123.45 );
		$order = new Order( $inschrijving->geef_referentie() );
		$this->assertEquals( 123.45, $order->te_betalen(), 'prijs lopende cursus incorrect' );
		$this->assertEquals( 'Betaling bedrag voor reeds gestarte cursus', $mailer->get_last_sent( $cursist->user_email )->subject, 'onderwerp email lopende cursus incorrect' );
	}

	/**
	 * Test uitschrijven wachtlijst functie
	 */
	public function test_uitschrijven_wachtlijst() {
		$inschrijving = $this->maak_inschrijving();
		$inschrijving->actie->uitschrijven_wachtlijst();
		$this->assertTrue( $inschrijving->geannuleerd, 'status uitschrijven wachtlijst incorrect' );
	}

	/**
	 * Test beschikbaar controle
	 */
	public function test_beschikbaarcontrole() {
		$inschrijving = $this->maak_inschrijving();
		$this->assertEmpty( $inschrijving->actie->beschikbaarcontrole(), 'beschikbaarcontrole open cursus incorrect' );

		$inschrijving->cursus->vol = true;
		$inschrijving->cursus->save();
		$this->assertNotEmpty( $inschrijving->actie->beschikbaarcontrole(), 'beschikbaarcontrole open cursus incorrect' );
	}

	/**
	 * Test verwerk bank function
	 */
	public function test_verwerk_bank() {
		$mailer       = tests_retrieve_phpmailer_instance();
		$inschrijving = $this->maak_inschrijving();
		$cursist      = new Cursist( $inschrijving->klant_id );

		$inschrijving->actie->aanvraag( 'bank' );
		$this->assertEquals( 'Inschrijving cursus', $mailer->get_last_sent( $cursist->user_email )->subject, 'verwerk bank inschrijving incorrecte email' );

		$order = new Order( $inschrijving->geef_referentie() );
		$inschrijving->betaling->verwerk( $order, 25, true, 'bank' );
		$this->assertEquals( 2, $mailer->get_sent_count(), 'verwerk bank aantal email incorrect' );
		$this->assertEquals( 'Indeling cursus', $mailer->get_last_sent( $cursist->user_email )->subject, 'verwerk bank indeling incorrecte email' );

		$order = new Order( $inschrijving->geef_referentie() );
		$inschrijving->betaling->verwerk( $order, $order->te_betalen(), true, 'ideal' );
		$this->assertEquals( 'Betaling cursus', $mailer->get_last_sent( $cursist->user_email )->subject, 'verwerk bank restant incorrecte email' );

		$order = new Order( $inschrijving->geef_referentie() );
		$this->assertEquals( 0.0, $order->te_betalen(), 'verwerk bank saldo incorrect' );
	}

	/**
	 * Test verwerk ideal
	 */
	public function test_verwerk_ideal() {
		$mailer       = tests_retrieve_phpmailer_instance();
		$inschrijving = $this->maak_inschrijving();
		$cursist      = new Cursist( $inschrijving->klant_id );

		$inschrijving->actie->aanvraag( 'ideal' );
		$this->assertEquals( 0, $mailer->get_sent_count(), 'verwerk bank aantal email incorrect' );

		$order = new Order( $inschrijving->geef_referentie() );
		$inschrijving->betaling->verwerk( $order, 25, true, 'ideal' );
		$this->assertEquals( 'Indeling cursus', $mailer->get_last_sent( $cursist->user_email )->subject, 'verwerk bank inschrijving incorrecte email' );
	}

	/**
	 * Test creation and modification of multiple inschrijvingen.
	 */
	public function test_inschrijvingen() {
		$cursist_ids = $this->factory->user->create_many( 10 );
		$cursus      = new Cursus();
		$cursus->save();

		$teststring     = 'test inschrijvingen';
		$inschrijvingen = [];
		for ( $i = 0; $i < 3; $i ++ ) {
			$inschrijvingen[ $i ]            = new Inschrijving( $cursus->id, $cursist_ids[ $i ] );
			$inschrijvingen[ $i ]->opmerking = "$teststring{$cursist_ids[$i]}";
			$inschrijvingen[ $i ]->save();
		}

		$inschrijvingen = new Inschrijvingen( $cursus->id );
		foreach ( $inschrijvingen as $inschrijving ) {
			if ( substr( $inschrijving->opmerking, 0, strlen( $teststring ) ) === $teststring ) {
				$this->assertEquals( $teststring . $inschrijving->klant_id, $inschrijving->opmerking, 'opmerking inschrijvingen not equal' );
			}
		}

	}

}
