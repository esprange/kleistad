<?php
/**
 * Class Abonnement Test
 *
 * @package Kleistad
 */

namespace Kleistad;

/**
 * Abonnement test case.
 */
class Test_Abonnement extends Kleistad_UnitTestCase {

	/**
	 * Maak een abonnement
	 *
	 * @return Abonnement
	 */
	private function maak_abonnement(): Abonnement {

		$role = add_role( LID, 'Kleistad abonnee' );
		if ( is_object( $role ) ) {
			$role->add_cap( RESERVEER, true );
		}

		$abonnee_id = $this->factory->user->create();
		$abonnement = $this->getMockBuilder( Abonnement::class )->setMethods( [ 'maak_factuur' ] )->setConstructorArgs(
			[
				$abonnee_id,
			]
		)->getMock();
		$abonnement->method( 'maak_factuur' )->willReturn( __FILE__ );

		return $abonnement;
	}

	/**
	 * Test creation and modification of an abonnement.
	 */
	public function test_abonnement() {
		$abonnement = $this->maak_abonnement();
		$this->assertTrue( $abonnement->actie->starten( strtotime( 'today' ), 'beperkt', 'dinsdag', 'dit is een test', 'bank' ), 'abonnement start bank incorrect' );

		$this->assertTrue( user_can( $abonnement->klant_id, LID ), 'abonnement rol incorrect' );
		$this->assertNotEmpty( tests_retrieve_phpmailer_instance()->get_recipient( 'to' )->address, 'abonnement email incorrect' );
	}

	/**
	 * Test function erase
	 */
	public function test_erase() {
		$abonnement = $this->maak_abonnement();
		$abonnement->actie->starten( strtotime( 'today' ), 'beperkt', 'dinsdag', 'dit is een test', 'bank' );

		$abonnement->erase();
		$this->assertFalse( user_can( $abonnement->klant_id, LID ), 'erase rol incorrect' );
	}

	/**
	 * Test function is_gepauzeerd
	 */
	public function test_is_gepauzeerd() {
		$abonnement = $this->maak_abonnement();

		$this->assertFalse( $abonnement->is_gepauzeerd(), 'is_gepauzeerd tijdens actief incorrect' );
		$abonnement->pauze_datum    = strtotime( 'yesterday' );
		$abonnement->herstart_datum = strtotime( 'tomorrow' );
		$this->assertTrue( $abonnement->is_gepauzeerd(), 'is_gepauzeerd tijdens pauze incorrect' );
	}

	/**
	 * Test function is_geannuleerd
	 */
	public function test_is_geannuleerd() {
		$abonnement = $this->maak_abonnement();

		$this->assertFalse( $abonnement->is_geannuleerd(), 'is_geannuleerd tijdens actief incorrect' );

		$abonnement->eind_datum = strtotime( 'yesterday' );
		$this->assertTrue( $abonnement->is_geannuleerd(), 'is_geannuleerd na einde incorrect' );
	}

	/**
	 * Test function geef_referentie
	 */
	public function test_geef_referentie() {
		$abonnement = $this->maak_abonnement();

		$abonnement->actie->starten( strtotime( 'today' ), 'beperkt', 'dinsdag', 'dit is een test', 'bank' );

		$this->assertRegExp( '~A[0-9]+-start-20[0-9]{4}~', $abonnement->geef_referentie(), 'referentie incorrect' );
		$abonnement->artikel_type = 'regulier';
		$this->assertRegExp( '~A[0-9]+-regulier-20[0-9]{4}~', $abonnement->geef_referentie(), 'referentie incorrect' );
	}

	/**
	 * Test function geef_overbrugging_fractie en geef_pauze_fractie
	 */
	public function test_geef_fractie() {
		$abonnement = $this->maak_abonnement();
		$abonnement->actie->starten( strtotime( 'first day of this month 00:00' ), 'beperkt', 'dinsdag', 'dit is een test', 'bank' );

		$this->assertTrue( 0.0 < $abonnement->geef_overbrugging_fractie(), 'overbrugging fractie incorrect' );

		$abonnement->pauze_datum    = strtotime( 'first day of this month 00:00' );
		$abonnement->herstart_datum = strtotime( '+ 10 days 00:00', $abonnement->pauze_datum );
		$this->assertTrue( 0.0 < $abonnement->geef_pauze_fractie(), 'pauze fractie incorrect' );

		$abonnement->herstart_datum = strtotime( 'first day of next month 00:00' );
		$this->assertTrue( 0.0 === $abonnement->geef_pauze_fractie(), 'pauze fractie incorrect' );
	}

	/**
	 * Test pauzeren function
	 */
	public function test_pauzeren() {
		$abonnement = $this->maak_abonnement();
		$abonnement->actie->starten( strtotime( '- 4 month 00:00' ), 'beperkt', 'dinsdag', 'Dit is een test', 'bank' );

		/**
		 * Pauzeer het abonnement in de toekomst
		 */
		$abonnement->actie->pauzeren( strtotime( '+ 5 days 00:00' ), strtotime( '+ 20 days 00:00' ) );
		$this->assertRegExp( '~Je pauzeert~', $abonnement->bericht, 'pauzeren actief incorrect' );
		$this->assertNotEmpty( tests_retrieve_phpmailer_instance()->get_recipient( 'to', 1 )->address, 'pauzeren actief email incorrect' );
		$this->assertEmpty( tests_retrieve_phpmailer_instance()->get_sent( 1 )->attachment, 'pauzeren actie email attachment incorrect' );

		/**
		 * Fake alsof het abonnement al gepauzeerd is en wijzig de herstartdatum
		 */
		$abonnement->pauze_datum = strtotime( '-5 days 00:00' );
		$abonnement->actie->pauzeren( strtotime( '- 5 days 00:00' ), strtotime( '+ 10 days 00:00' ) );
		$this->assertRegExp( '~Je hebt aangegeven~', $abonnement->bericht, 'pauzeren herstart actief incorrect' );
		$this->assertNotEmpty( tests_retrieve_phpmailer_instance()->get_recipient( 'to', 2 )->address, 'herstarten email incorrect' );
		$this->assertEmpty( tests_retrieve_phpmailer_instance()->get_sent( 2 )->attachment, 'herstarten email attachement incorrect' );
	}

	/**
	 * Test stoppen function
	 */
	public function test_stoppen() {
		$abonnement = $this->maak_abonnement();
		$abonnement->actie->starten( strtotime( '- 4 month 00:00' ), 'beperkt', 'dinsdag', 'Dit is een test', 'bank' );

		/**
		 * Stop het abonnement. Dan moet er alleen eem bevestiging uitgezonden worden.
		 */
		$abonnement->actie->stoppen( strtotime( 'first day of next month 00:00' ) );
		$this->assertRegExp( '~Je hebt het~', $abonnement->bericht, 'stoppen actief bericht incorrect' );
		$this->assertEquals( strtotime( 'first day of next month 00:00' ), $abonnement->eind_datum, 'stoppen datum incorrect' );
		$this->assertNotEmpty( tests_retrieve_phpmailer_instance()->get_recipient( 'to', 1 )->address, 'stoppen email incorrect' );
		$this->assertEmpty( tests_retrieve_phpmailer_instance()->get_sent( 1 )->attachment, 'stoppen email attachment incorrect' );
	}

	/**
	 * Test wijzigen function
	 */
	public function test_wijzigen() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();
		$abonnee_id = $abonnement->klant_id;
		$abonnement->actie->starten( strtotime( '- 4 month 00:00' ), 'beperkt', 'dinsdag', 'Dit is een test', 'bank' );

		$abonnee = new Abonnee( $abonnee_id );

		/**
		 * Wijzig de dag van het beperkte abonnement. Moet bevestigd worden met bericht.
		 */
		$abonnee->abonnement->actie->wijzigen( strtotime( 'first day of next month 00:00' ), 'soort', 'beperkt', 'woensdag' );
		$this->assertRegExp( '~Je hebt het~', $abonnee->abonnement->bericht, 'wijzigen dag bericht incorrect' );
		$this->assertEquals( 'woensdag', $abonnee->abonnement->dag, 'wijzigen dag incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent( $abonnee->user_email, 1 ), 'wijzigen dag email incorrect' );

		/**
		 * Wijzig de dag van het beperkte abonnement. Moet bevestigd worden met bericht.
		 */
		$abonnee->abonnement->actie->wijzigen( strtotime( 'first day of next month 00:00' ), 'soort', 'onbeperkt' );
		$this->assertRegExp( '~Je hebt het~', $abonnee->abonnement->bericht, 'wijzigen soort bericht incorrect' );
		$this->assertEquals( 'onbeperkt', $abonnee->abonnement->soort, 'wijzigen soort incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent( $abonnee->user_email, 2 ), 'wijzigen soort email incorrect' );

		/**
		 * Wijzig nu de extras. Moet bevestigd worden met bericht.
		 */
		$abonnee->abonnement->actie->wijzigen( strtotime( 'first day of next month 00:00' ), 'extras', [ 'sleutel', 'kast' ] );
		$this->assertRegExp( '~Je gaat voortaan~', $abonnee->abonnement->bericht, 'wijzigen extras bericht incorrect' );
		$this->assertEquals( [ 'sleutel', 'kast' ], $abonnee->abonnement->extras, 'wijzigen extras incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent( $abonnee->user_email, 3 ), 'wijzigen extras email incorrect' );
	}

	/**
	 * Test overbrugging function
	 */
	public function test_overbrugging() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();
		$abonnee_id = $abonnement->klant_id;
		$abonnement->actie->starten( $this->set_date( 5 + (int) date( 'j' ), -3 ), 'beperkt', 'dinsdag', 'Dit is een test', 'bank' );
		$abonnement->factuur_maand = date( 'Ym', strtotime( '-1 month' ) );
		$abonnement->save();
		Abonnementen::doe_dagelijks(); // Voert actie->overbrugging uit en verstuurt email 1.

		$abonnee = new Abonnee( $abonnee_id );
		$this->assertTrue( $abonnee->abonnement->overbrugging_email, 'overbrugging incorrect' );
		$this->assertEquals( date( 'Ym' ), $abonnee->abonnement->factuur_maand, 'factureer overbrugging incorrect' );
		$this->assertEquals( 'Verlenging abonnement', $mailer->get_last_sent( $abonnee->user_email )->subject, 'overbrugging email onderwerp incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent( $abonnee->user_email )->attachment, 'overbrugging email attachment incorrect' );
	}

	/**
	 * Test factureer regulier function
	 */
	public function test_factureer_regulier() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();
		$abonnee_id = $abonnement->klant_id;
		$abonnement->actie->starten( strtotime( '- 4 month 00:00' ), 'beperkt', 'dinsdag', 'Dit is een test', 'bank' ); // Verstuurt email 0.
		$abonnement->factuur_maand = date( 'Ym', strtotime( '-1 month' ) );
		$abonnement->save();

		Abonnementen::doe_dagelijks(); // Voert actie->factureer uit en verstuurt email 1.

		$abonnee = new Abonnee( $abonnee_id );
		$this->assertEquals( date( 'Ym' ), $abonnee->abonnement->factuur_maand, 'factureer regulier incorrect' );
		$this->assertEquals( 'Betaling abonnement per bankstorting', $mailer->get_last_sent( $abonnee->user_email )->subject, 'factureer email onderwerp incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent( $abonnee->user_email )->attachment, 'factureer regulier email attachment incorrect' );
	}

	/**
	 * Test factureer lange pauze function
	 */
	public function test_factureer_lange_pauze() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();
		$abonnee_id = $abonnement->klant_id;
		$abonnement->actie->starten( strtotime( '- 5 month 00:00' ), 'beperkt', 'dinsdag', 'Dit is een test', 'bank' ); // Verstuurt email 0.
		$abonnement->pauze_datum    = $this->set_date( 15, -1 );
		$abonnement->herstart_datum = $this->set_date( 15, +1 );
		$abonnement->factuur_maand  = date( 'Ym', strtotime( '-1 month' ) );
		$abonnement->save();
		Abonnementen::doe_dagelijks(); // Voert actie->factureer uit en verstuurt geen email.

		$abonnee = new Abonnee( $abonnee_id );
		$this->assertEquals( date( 'Ym', strtotime( '-1 month' ) ), $abonnee->abonnement->factuur_maand, 'factureer pauze maand incorrect' );
		$this->assertNotEquals( 'Betaling abonnement per bankstorting', $mailer->get_last_sent( $abonnee->user_email )->subject, 'factureer email onderwerp incorrect' );
	}

	/**
	 * Test factureer korte pauze function
	 */
	public function test_factureer_korte_pauze() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();
		$abonnee_id = $abonnement->klant_id;
		$abonnement->actie->starten( strtotime( '- 4 month 00:00' ), 'beperkt', 'dinsdag', 'Dit is een test', 'bank' ); // Verstuurt email 0.
		$abonnement->pauze_datum    = strtotime( '- 1 month 00:00' );
		$abonnement->herstart_datum = $this->set_date( 10 );
		$abonnement->factuur_maand  = date( 'Ym', strtotime( '-1 month' ) );
		$abonnement->save();

		Abonnementen::doe_dagelijks(); // Voert actie->factureer uit en verstuurt email 1.

		$abonnee = new Abonnee( $abonnee_id );
		$this->assertEquals( date( 'Ym' ), $abonnee->abonnement->factuur_maand, 'factureer eind pauze maand incorrect' );
		$this->assertEquals( 'Betaling abonnement per bankstorting', $mailer->get_last_sent( $abonnee->user_email )->subject, 'factureer eind pauze email onderwerp incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent( $abonnee->user_email )->attachment, 'factureer eind pauze email attachment incorrect' );
	}

	/**
	 * Test autoriseer function
	 */
	public function test_autoriseer() {
		$abonnement = $this->maak_abonnement();
		$abonnement->actie->starten( strtotime( '- 4 month 00:00' ), 'beperkt', 'dinsdag', 'Dit is een test', 'bank' );
		$this->assertTrue( user_can( $abonnement->klant_id, LID ), 'autoriseer na start incorrect' );

		$abonnement->actie->stoppen( strtotime( '-1 month 00:00' ) );
		Abonnementen::doe_dagelijks();
		$this->assertFalse( user_can( $abonnement->klant_id, LID ), 'autoriseer na stop incorrect' );
	}
}
