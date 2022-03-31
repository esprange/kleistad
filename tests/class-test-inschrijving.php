<?php
/**
 * Class Inschrijving Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Inschrijving, \Kleistad\Inschrijvingen, \Kleistad\Cursist
 * @noinspection PhpPossiblePolymorphicInvocationInspection, PhpUndefinedFieldInspection
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
		$inschrijving            = new Inschrijving( $cursus_id, $cursist_id );
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
		$inschrijving1->wacht_datum     = time();
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
	public function test_get_artikelnaam() {
		$inschrijving1 = $this->maak_inschrijving();
		$this->assertEquals( self::CURSUSNAAM, $inschrijving1->get_artikelnaam(), 'get_artikelnaam incorrect' );
	}

	/**
	 * Test heeft_restant function
	 */
	public function test_get_restant_melding() {
		$inschrijving1 = $this->maak_inschrijving();
		$this->assertNotEmpty( $inschrijving1->get_restant_melding(), 'heeft restant toekomst incorrect' );

		$inschrijving1->cursus->start_datum = strtotime( 'tomorrow' );
		$this->assertEmpty( $inschrijving1->get_restant_melding(), 'heeft restant morgen incorrect' );
	}

	/**
	 * Test toon_aantal function
	 */
	public function test_toon_aantal() {
		$inschrijving = $this->maak_inschrijving();

		$inschrijving->aantal          = 3;
		$inschrijving->extra_cursisten = [];
		$this->assertNotEmpty( $inschrijving->toon_aantal(), 'toon_aantal > 1, extra 0 incorrect' );

		$inschrijving->aantal          = 3;
		$inschrijving->extra_cursisten = [ 2 ];
		$this->assertNotEmpty( $inschrijving->toon_aantal(), 'toon_aantal > 1, extra 1 incorrect' );

		$inschrijving->aantal          = 3;
		$inschrijving->extra_cursisten = [ 2, 3 ];
		$this->assertEmpty( $inschrijving->toon_aantal(), 'toon_aantal > 1, extra 2 incorrect' );

		$inschrijving->aantal          = 1;
		$inschrijving->extra_cursisten = [];
		$this->assertEmpty( $inschrijving->toon_aantal(), 'toon_aantal 1 incorrect' );
	}

	/**
	 * Test get_referentie function
	 */
	public function test_get_referentie() {
		$inschrijving = $this->maak_inschrijving();
		$this->assertEquals( "C{$inschrijving->cursus->id}-$inschrijving->klant_id", $inschrijving->get_referentie(), 'geef referentie incorrect' );
	}

	/**
	 * Test geef status tekst
	 */
	public function test_get_statustekst() {
		$inschrijving = $this->maak_inschrijving();

		$inschrijving->geannuleerd = false;
		$inschrijving->ingedeeld   = false;
		$this->assertEquals( 'ingeschreven', $inschrijving->get_statustekst(), 'get_statustekst ingeschreven incorrect' );

		$inschrijving->ingedeeld = true;
		$this->assertEquals( 'ingedeeld', $inschrijving->get_statustekst(), 'get_statustekst ingedeeld incorrect' );

		$inschrijving->geannuleerd = true;
		$this->assertEquals( 'geannuleerd', $inschrijving->get_statustekst(), 'get_statustekst geannuleerd incorrect' );
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
	public function test_bestel() {
		$inschrijving1 = $this->maak_inschrijving();
		$inschrijving1->save();
		$order   = new Order( $inschrijving1->get_referentie() );
		$factuur = $order->bestel( 0, strtotime( 'today' ) );
		$this->assertFileExists( $factuur, 'bestel order incorrect' );
		$this->assertTrue( $order->id > 0, 'bestel order incorrect' );
	}

	/**
	 * Test annuleer_order function
	 */
	public function test_annuleer() {
		$inschrijving1 = $this->maak_inschrijving();
		$inschrijving1->save();
		$order = new Order( $inschrijving1->get_referentie() );
		$order->bestel( 0, strtotime( 'today' ), '', '', false );
		$order->annuleer( 24.0, '' );
		$this->assertTrue( $order->id > 0, 'bestel_order incorrect' );
		$inschrijving2 = new Inschrijving( $inschrijving1->cursus->id, $inschrijving1->klant_id );
		$this->assertTrue( $inschrijving2->geannuleerd, 'annuleer status incorrect' );
	}

	/**
	 * Test correctie cursus inschrijving
	 */
	public function test_correctie() {
		$mailer       = tests_retrieve_phpmailer_instance();
		$inschrijving = $this->maak_inschrijving();
		$cursist      = new Cursist( $inschrijving->klant_id );
		$inschrijving->actie->aanvraag( 'bank', 1, [], '' );

		$cursus_nieuw               = new Cursus();
		$cursus_nieuw->naam         = 'Nieuwe cursus';
		$cursus_nieuw->cursuskosten = 67.00;
		$cursus_nieuw->save();

		$inschrijving->actie->correctie( $cursus_nieuw->id, 1 );

		$inschrijving = new Inschrijving( $cursus_nieuw->id, $cursist->ID );
		$order        = new Order( $inschrijving->get_referentie() );
		$this->assertEquals( 'Wijziging inschrijving cursus', $mailer->get_last_sent( $cursist->user_email )->subject, 'correctie email incorrect' );
		$this->assertEquals( 25.00 + 67.00, $order->get_te_betalen(), 'correctie kosten te betalen onjuist' );
		$this->assertNotEmpty( $mailer->get_last_sent( $cursist->user_email )->attachment, 'correctie email attachment ontbreekt' );

		$inschrijving->betaling->verwerk( $order, 25.00, true, 'bank' );
		$inschrijving->actie->correctie( $cursus_nieuw->id, 2 );

		$inschrijving = new Inschrijving( $cursus_nieuw->id, $cursist->ID );
		$order        = new Order( $inschrijving->get_referentie() );
		$this->assertEquals( 'Wijziging inschrijving cursus', $mailer->get_last_sent( $cursist->user_email )->subject, 'correctie email incorrect' );
		$this->assertEquals( 2 * ( 25.00 + 67.00 ) - 25.00, $order->get_te_betalen(), 'correctie kosten te betalen onjuist' );
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
		$order = new Order( $inschrijving->get_referentie() );
		$this->assertEquals( 123.45, $order->get_te_betalen(), 'prijs lopende cursus incorrect' );
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
	 * Test de omzetting van een bestaande order naar de wachtlijst
	 */
	public function test_naar_wachtlijst() {
		$mailer       = tests_retrieve_phpmailer_instance();
		$inschrijving = $this->maak_inschrijving();
		$cursist      = new Cursist( $inschrijving->klant_id );
		$inschrijving->actie->aanvraag( 'bank', 1, [], '' );
		/**
		 * Zet nu de cursus op vol.
		 */
		$inschrijving->cursus->maximum = 0;
		$inschrijving->cursus->save();
		/**
		 * Doe de dagelijkse run, dan moet de cursist naar de wachtlijst omdat er nog niet betaald is.
		 */
		Cursussen::doe_dagelijks();
		Inschrijvingen::doe_dagelijks();
		$this->assertEquals( 'De cursus is vol, aanmelding verplaatst naar wachtlijst', $mailer->get_last_sent( $cursist->user_email )->subject, 'onderwerp email naar wachtlijst cursus incorrect' );
		$inschrijving2 = new Inschrijving( $inschrijving->cursus->id, $inschrijving->klant_id );
		$this->assertFalse( $inschrijving2->geannuleerd, 'incorrecte annulering status' );
		$this->assertGreaterThan( 0, $inschrijving2->wacht_datum, 'incorrecte wacht status' );
		/**
		 * Zorg dat er nieuwe indeling mogelijk is.
		 */
		$cursus = new Cursus( $inschrijving->cursus->id );
		$cursus->maximum++;
		$cursus->save();
		/**
		 * Dit loopt normaliter de volgende ochtend, maar nu dus even 1 seconde later.
		 */
		sleep( 1 );
		Cursussen::doe_dagelijks();
		Inschrijvingen::doe_dagelijks();
		$this->assertEquals( 'Er is een cursusplek vrijgekomen', $mailer->get_last_sent( $cursist->user_email )->subject, 'Wachtlijst ruimte incorrecte email' );
		/**
		 *  Betaling per ideal vanuit het wachtlijst formulier.
		 */
		$inschrijving2 = new $inschrijving( $inschrijving->cursus->id, $inschrijving->klant_id );
		$order         = new Order( $inschrijving2->get_referentie() );
		$inschrijving2->betaling->verwerk( $order, 25, true, 'ideal' );
		$this->assertEquals( 'Indeling cursus', $mailer->get_last_sent( $cursist->user_email )->subject, 'verwerk bank indeling incorrecte email' );

	}

	/**
	 * Test beschikbaar controle
	 */
	public function test_get_beschikbaarheid() {
		$inschrijving1                  = $this->maak_inschrijving();
		$inschrijving1->cursus->maximum = 1;
		$inschrijving1->cursus->save();
		$inschrijving1->actie->aanvraag( 'bank', 1, [], '' );
		$this->assertEmpty( $inschrijving1->actie->get_beschikbaarheid(), 'get_beschikbaarheid open cursus incorrect' );
		$inschrijving1->ingedeeld = true;
		$inschrijving1->save();

		$wachtlijst_cursist_id      = $this->factory->user->create();
		$inschrijving2              = new Inschrijving( $inschrijving1->cursus->id, $wachtlijst_cursist_id );
		$inschrijving2->cursus->vol = true;
		$inschrijving2->actie->aanvraag( '', 1, [], '' );
		$this->assertStringContainsString( 'Helaas is de cursus nu vol', $inschrijving2->actie->get_beschikbaarheid(), 'get_beschikbaarheid gesloten cursus incorrect' );
	}

	/**
	 * Test verwerk bank function
	 */
	public function test_verwerk_bank() {
		$mailer       = tests_retrieve_phpmailer_instance();
		$inschrijving = $this->maak_inschrijving();
		$cursist      = new Cursist( $inschrijving->klant_id );

		$inschrijving->actie->aanvraag( 'bank', 1, [], '' );
		$this->assertEquals( 'Inschrijving cursus', $mailer->get_last_sent( $cursist->user_email )->subject, 'verwerk bank inschrijving incorrecte email' );

		$order = new Order( $inschrijving->get_referentie() );
		$inschrijving->betaling->verwerk( $order, 25, true, 'bank' );
		$this->assertEquals( 2, $mailer->get_sent_count(), 'verwerk bank aantal email incorrect' );
		$this->assertEquals( 'Indeling cursus', $mailer->get_last_sent( $cursist->user_email )->subject, 'verwerk bank indeling incorrecte email' );

		$order = new Order( $inschrijving->get_referentie() );
		$inschrijving->betaling->verwerk( $order, $order->get_te_betalen(), true, 'ideal' );
		$order = new Order( $inschrijving->get_referentie() );
		$this->assertEquals( 0.0, $order->get_te_betalen(), 'verwerk bank saldo incorrect' );
		$this->assertEquals( 'Betaling cursus', $mailer->get_last_sent( $cursist->user_email )->subject, 'verwerk bank restant incorrecte email' );

	}

	/**
	 * Test verwerk ideal
	 */
	public function test_verwerk_ideal() {
		$mailer       = tests_retrieve_phpmailer_instance();
		$inschrijving = $this->maak_inschrijving();
		$cursist      = new Cursist( $inschrijving->klant_id );

		$inschrijving->actie->aanvraag( 'ideal', 1, [], '' );
		$this->assertEquals( 0, $mailer->get_sent_count(), 'verwerk bank aantal email incorrect' );

		$order = new Order( $inschrijving->get_referentie() );
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
			$inschrijvingen[ $i ]->opmerking = $teststring . $cursist_ids[ $i ];
			$inschrijvingen[ $i ]->save();
		}

		$inschrijvingen = new Inschrijvingen( $cursus->id );
		foreach ( $inschrijvingen as $inschrijving ) {
			if ( str_starts_with( $inschrijving->opmerking, $teststring ) ) {
				$this->assertEquals( $teststring . $inschrijving->klant_id, $inschrijving->opmerking, 'opmerking inschrijvingen not equal' );
			}
		}

	}

	/**
	 * Test de wachtlijst functie.
	 */
	public function test_plaatsbeschikbaar() {
		$mailer               = tests_retrieve_phpmailer_instance();
		$cursus1              = new Cursus();
		$cursus1->maximum     = 3;
		$cursus1->start_datum = strtotime( '+1 month' );
		$cursus1->save();

		/**
		 * Maak eerst de cursus vol zodat er geen ruimte meer is.
		 */
		$cursist_ids = $this->factory->user->create_many( $cursus1->maximum );
		for ( $i = 0; $i < 3; $i ++ ) {
			$inschrijvingen[ $i ] = new Inschrijving( $cursus1->id, $cursist_ids[ $i ] );
			$inschrijvingen[ $i ]->actie->aanvraag( 'ideal', 1, [], '' );
			$order = new Order( $inschrijvingen[ $i ]->get_referentie() );
			$inschrijvingen[ $i ]->betaling->verwerk( $order, 25, true, 'ideal' );
		}

		/**
		 * Als gevolg van de inschrijvingen wordt de cursus op vol gezet.
		 */
		$cursus2 = new Cursus( $cursus1->id );
		$this->assertTrue( $cursus2->vol, 'vol indicatie incorrect' );

		/**
		 * Een nieuwe cursist moet dus op de wachtlijst geplaatst worden.
		 */
		$wachtlijst_cursist      = new Cursist( $this->factory->user->create() );
		$inschrijving_wachtlijst = new Inschrijving( $cursus2->id, $wachtlijst_cursist->ID );
		$inschrijving_wachtlijst->actie->aanvraag( '', 1, [], '' );
		$this->assertTrue( 0 < $inschrijving_wachtlijst->wacht_datum, 'Wacht datum incorrect' );
		$this->assertEquals( 'Plaatsing op wachtlijst cursus', $mailer->get_last_sent( $wachtlijst_cursist->user_email )->subject, 'Wachtlijst vol incorrecte email' );

		/**
		 * Maak een plek vrij door een annulering.
		 */
		$inschrijvingen[2]->actie->afzeggen();

		/**
		 * Dit loopt normaliter de volgende ochtend, maar nu dus even 1 seconde later.
		 */
		sleep( 1 );
		Cursussen::doe_dagelijks();
		Inschrijvingen::doe_dagelijks();

		/**
		 * Er zou nu weer plaats moeten zijn en de cursist op de wachtlijst moet een email krijgen.
		 */
		$inschrijving_wachtlijst = new Inschrijving( $cursus2->id, $wachtlijst_cursist->ID );
		$this->assertFalse( $inschrijving_wachtlijst->cursus->vol, 'vol indicatie incorrect' );
		$this->assertEquals( $inschrijving_wachtlijst->wacht_datum, $inschrijving_wachtlijst->cursus->ruimte_datum, 'incorrecte wachtdatum' );
		$this->assertEquals( 'Er is een cursusplek vrijgekomen', $mailer->get_last_sent( $wachtlijst_cursist->user_email )->subject, 'Wachtlijst ruimte incorrecte email' );

		/**
		 * Als opnieuw het dagelijks proces loopt, 1 seconde later, mag er niets verstuurd worden.
		 */
		$emails_sent1 = $mailer->get_sent_count();
		sleep( 1 );
		Cursussen::doe_dagelijks();
		Inschrijvingen::doe_dagelijks();
		$this->assertEquals( $emails_sent1, $mailer->get_sent_count(), 'incorrecte email verzonden' );

		/**
		 * Nu schrijft iemand anders in, dan wordt de cursus weer vol.
		 */
		$andere_cursist     = new Cursist( $this->factory->user->create() );
		$inschrijving_ander = new Inschrijving( $cursus2->id, $andere_cursist->ID );
		$inschrijving_ander->actie->aanvraag( 'ideal', 1, [], '' );
		$order = new Order( $inschrijving_ander->get_referentie() );
		$inschrijving_ander->betaling->verwerk( $order, 25, true, 'ideal' );
		$inschrijving_wachtlijst = new Inschrijving( $cursus2->id, $wachtlijst_cursist->ID );
		$this->assertTrue( $inschrijving_wachtlijst->cursus->vol, 'vol indicatie incorrect' );

		/**
		 * Wijzig nu de status door het aantal toegestane cursisten te verhogen.
		 */
		$emails_sent2                              = $mailer->get_sent_count();
		$inschrijving_wachtlijst->cursus->maximum += 1;
		$inschrijving_wachtlijst->cursus->save();
		sleep( 1 );
		Cursussen::doe_dagelijks();
		Inschrijvingen::doe_dagelijks();

		/**
		 * Nu zou er wel opnieuw een email verzonden moeten worden.
		 */
		$inschrijving_wachtlijst = new Inschrijving( $cursus2->id, $wachtlijst_cursist->ID );
		$this->assertFalse( $inschrijving_wachtlijst->cursus->vol, 'vol indicatie incorrect' );
		$this->assertEquals( $emails_sent2 + 1, $mailer->get_sent_count(), 'email niet verzonden' );
		$this->assertEquals( 'Er is een cursusplek vrijgekomen', $mailer->get_last_sent( $wachtlijst_cursist->user_email )->subject, 'Wachtlijst ruimte incorrecte email' );
	}

}
