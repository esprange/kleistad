<?php
/**
 * Class Abonnement Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Abonnement, \Kleistad\Abonnementen, \Kleistad\Abonnee, \Kleistad\Abonnees, \Kleistad\AbonnementActie, \Kleistad\AbonnementBetaling
 */

namespace Kleistad;

/**
 * Abonnement test case.
 */
class Test_Abonnement extends Kleistad_UnitTestCase {

	const EXTRA = [
		'naam'  => 'test_extra1',
		'prijs' => 12.34,
	];

	/**
	 * Maak een abonnement
	 *
	 * @return Abonnement
	 */
	private function maak_abonnement(): Abonnement {
		$opties            = get_option( 'kleistad-opties' );
		$opties['extra'][] = self::EXTRA;
		update_option( 'kleistad-opties', $opties );

		$role = get_role( LID );
		if ( is_object( $role ) ) {
			$role->add_cap( RESERVEER, true );
		}
		$abonnee_id = $this->factory()->user->create();
		return new Abonnement( $abonnee_id ); // $abonnement;
	}

	/**
	 * Test start per bank.
	 */
	public function test_starten_bank() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();

		$this->assertTrue( $abonnement->actie->starten( strtotime( 'today' ), 'beperkt', 'dit is een test', 'stort' ), 'abonnement start bank incorrect' );
		$this->assertEquals( 'Welkom bij Kleistad', $mailer->get_last_sent()->subject, 'start bank email incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent()->attachment, 'verwerk start bank attachment incorrect' );

		$abonnement->betaling->verwerk( new Order( $abonnement->get_referentie() ), 10, true, 'stort' );
		$this->assertEquals( 1, $mailer->get_sent_count(), 'start bank aantal email onjuist' );

		$this->assertTrue( user_can( $abonnement->klant_id, LID ), 'abonnement rol incorrect' );
	}

	/**
	 * Test abonnement start met ideal betaling.
	 */
	public function test_starten_ideal() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();

		$this->assertIsString( $abonnement->actie->starten( strtotime( 'today' ), 'beperkt', 'dit is een test', 'ideal' ), 'abonnement start bank incorrect' );
		$this->assertEquals( 0, $mailer->get_sent_count(), 'start ideal aantal email onjuist' );

		$abonnement->betaling->verwerk( new Order( $abonnement->get_referentie() ), 90, true, 'ideal', 'transactie' );
		$this->assertEquals( 'Welkom bij Kleistad', $mailer->get_last_sent()->subject, 'start ideal email incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent()->attachment, 'verwerk start bank attachment incorrect' );
		$this->assertTrue( user_can( $abonnement->klant_id, LID ), 'abonnement rol incorrect' );
	}

	/**
	 * Test function erase
	 */
	public function test_erase() {
		$abonnement = $this->maak_abonnement();
		$abonnement->actie->starten( strtotime( 'today' ), 'beperkt', 'dit is een test', 'stort' );

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
	 * Test function get_referentie
	 */
	public function test_get_referentie() {
		$abonnement = $this->maak_abonnement();

		$abonnement->actie->starten( strtotime( 'today' ), 'beperkt', 'dit is een test', 'stort' );

		$this->assertMatchesRegularExpression( '~A\d+-start-20\d~', $abonnement->get_referentie(), 'referentie incorrect' );
		$abonnement->artikel_type = 'regulier';
		$this->assertMatchesRegularExpression( '~A\d+-regulier-20\d{4}~', $abonnement->get_referentie(), 'referentie incorrect' );
	}

	/**
	 * Test function get_overbrugging_fractie en get_pauze_fractie
	 */
	public function test_geef_fractie() {
		$abonnement = $this->maak_abonnement();
		$abonnement->actie->starten( strtotime( 'first day of this month 00:00' ), 'beperkt', 'dit is een test', 'stort' );

		$this->assertTrue( 0.0 < $abonnement->get_overbrugging_fractie(), 'overbrugging fractie incorrect' );

		$abonnement->pauze_datum    = strtotime( 'first day of this month 00:00' );
		$abonnement->herstart_datum = strtotime( '+ 10 days 00:00', $abonnement->pauze_datum );
		$this->assertTrue( 0.0 < $abonnement->get_pauze_fractie(), 'pauze fractie incorrect' );

		$abonnement->herstart_datum = strtotime( 'first day of next month 00:00' );
		$this->assertTrue( 0.0 === $abonnement->get_pauze_fractie(), 'pauze fractie incorrect' );
	}

	/**
	 * Test pauzeren function
	 */
	public function test_pauzeren() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();
		$abonnement->actie->starten( strtotime( '- 4 month 00:00' ), 'beperkt', 'Dit is een test', 'stort' );

		/**
		 * Pauzeer het abonnement in de toekomst
		 */
		$abonnement->actie->pauzeren( strtotime( '+ 5 days 00:00' ), strtotime( '+ 20 days 00:00' ) );
		$this->assertMatchesRegularExpression( '~Je pauzeert~', $abonnement->bericht, 'pauzeren actief incorrect' );
		$this->assertNotEmpty( $mailer->get_recipient( 'to', 1 )->address, 'pauzeren actief email incorrect' );
		$this->assertEmpty( $mailer->get_sent( 1 )->attachment, 'pauzeren actie email attachment incorrect' );

		/**
		 * Fake alsof het abonnement al gepauzeerd is en wijzig de herstartdatum
		 */
		$abonnement->pauze_datum = strtotime( '-5 days 00:00' );
		$abonnement->actie->pauzeren( strtotime( '- 5 days 00:00' ), strtotime( '+ 10 days 00:00' ) );
		$this->assertMatchesRegularExpression( '~Je hebt aangegeven~', $abonnement->bericht, 'pauzeren herstart actief incorrect' );
		$this->assertNotEmpty( $mailer->get_recipient( 'to', 2 )->address, 'herstarten email incorrect' );
		$this->assertEmpty( $mailer->get_sent( 2 )->attachment, 'herstarten email attachement incorrect' );
	}

	/**
	 * Test stoppen function
	 */
	public function test_stoppen() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();
		$abonnement->actie->starten( strtotime( '- 4 month 00:00' ), 'beperkt', 'Dit is een test', 'stort' );

		/**
		 * Stop het abonnement. Dan moet er alleen eem bevestiging uitgezonden worden.
		 */
		$abonnement->actie->stoppen( strtotime( 'first day of next month 00:00' ) );
		$this->assertMatchesRegularExpression( '~Je hebt het~', $abonnement->bericht, 'stoppen actief bericht incorrect' );
		$this->assertEquals( strtotime( 'first day of next month 00:00' ), $abonnement->eind_datum, 'stoppen datum incorrect' );
		$this->assertNotEmpty( $mailer->get_recipient( 'to', 1 )->address, 'stoppen email incorrect' );
		$this->assertEmpty( $mailer->get_last_sent()->attachment, 'stoppen email attachment incorrect' );
	}

	/**
	 * Test wijzigen function
	 */
	public function test_wijzigen() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();
		$abonnee_id = $abonnement->klant_id;
		$abonnement->actie->starten( strtotime( '- 4 month 00:00' ), 'beperkt', 'Dit is een test', 'stort' );

		$abonnee = new Abonnee( $abonnee_id );

		/**
		 * Wijzig de dag van het beperkte abonnement. Moet bevestigd worden met bericht.
		 */
		$abonnee->abonnement->actie->wijzigen( strtotime( 'first day of next month 00:00' ), 'soort', 'onbeperkt' );
		$this->assertMatchesRegularExpression( '~Je hebt het~', $abonnee->abonnement->bericht, 'wijzigen soort bericht incorrect' );
		$this->assertEquals( 'onbeperkt', $abonnee->abonnement->soort, 'wijzigen soort incorrect' );
		$this->assertEquals( 2, $mailer->get_sent_count(), 'wijzigen soort email incorrect' );

		/**
		 * Wijzig nu de extras. Moet bevestigd worden met bericht.
		 */
		$abonnee->abonnement->actie->wijzigen( strtotime( 'first day of next month 00:00' ), 'extras', [ 'sleutel', 'kast' ] );
		$this->assertMatchesRegularExpression( '~Je gaat voortaan~', $abonnee->abonnement->bericht, 'wijzigen extras bericht incorrect' );
		$this->assertEquals( [ 'sleutel', 'kast' ], $abonnee->abonnement->extras, 'wijzigen extras incorrect' );
		$this->assertEquals( 3, $mailer->get_sent_count(), 'wijzigen extras email incorrect' );
	}

	/**
	 * Test overbrugging function
	 */
	public function test_overbrugging() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();
		$abonnee_id = $abonnement->klant_id;
		$abonnement->actie->starten( $this->set_date( 3 + (int) date( 'j' ), -3 ), 'beperkt', 'Dit is een test', 'stort' );
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
		$abonnement->actie->starten( strtotime( '- 5 month 00:00' ), 'beperkt', 'Dit is een test', 'stort' ); // Verstuurt email 0.
		$abonnement                = new Abonnement( $abonnee_id );
		$abonnement->factuur_maand = date( 'Ym', strtotime( '-2 month' ) );
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
		$abonnement->actie->starten( strtotime( '- 5 month 00:00' ), 'beperkt', 'Dit is een test', 'stort' ); // Verstuurt email 0.
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
		$abonnement->actie->starten( strtotime( '- 5 month 00:00' ), 'beperkt', 'Dit is een test', 'stort' ); // Verstuurt email 0.

		$abonnement                 = new Abonnement( $abonnee_id );
		$abonnement->pauze_datum    = strtotime( '- 1 month 00:00' );
		$abonnement->herstart_datum = $this->set_date( 10 );
		$abonnement->factuur_maand  = date( 'Ym', strtotime( '-2 month' ) );
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
	public function test_set_autorisatie() {
		$abonnement = $this->maak_abonnement();
		$abonnement->actie->starten( strtotime( '- 4 month 00:00' ), 'beperkt', 'Dit is een test', 'stort' );
		$this->assertTrue( user_can( $abonnement->klant_id, LID ), 'autoriseer na start incorrect' );

		$abonnement->actie->stoppen( strtotime( '-1 month 00:00' ) );
		Abonnementen::doe_dagelijks();
		$this->assertFalse( user_can( $abonnement->klant_id, LID ), 'autoriseer na stop incorrect' );
	}

	/**
	 * Test de betaling verwerk functie, bank betaling
	 */
	public function test_verwerk_regulier_stort() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();
		$abonnee    = new Abonnee( $abonnement->klant_id );
		$abonnement->actie->starten( strtotime( '- 1 year 00:00' ), 'beperkt', 'Dit is een test', 'stort' );

		$this->assertEquals( 'Welkom bij Kleistad', $mailer->get_last_sent( $abonnee->user_email )->subject, 'verwerk bankstorting onterechte email' );
		$this->assertNotEmpty( $mailer->get_last_sent( $abonnee->user_email )->attachment, 'verwerk start bank attachment incorrect' );

		$order = new Order( $abonnement->get_referentie() );
		$abonnement->betaling->verwerk( $order, 10, true, 'stort' );
		/**
		 * Na betaling start per bank wordt er geen email verstuurd.
		 */
		$this->assertEquals( 'Welkom bij Kleistad', $mailer->get_last_sent( $abonnee->user_email )->subject, 'verwerk bankstorting onterechte email' );

		$abonnement->factuur_maand = (int) date( 'Ym', strtotime( '- 1 month 00:00' ) );
		$abonnement->save();
		Abonnementen::doe_dagelijks();

		$abonnee = new Abonnee( $abonnement->klant_id );
		$order   = new Order( $abonnee->abonnement->get_referentie() );
		$this->assertEquals( 'Betaling abonnement per bankstorting', $mailer->get_last_sent( $abonnee->user_email )->subject, 'verwerk incasso incorrecte email' );
		$this->assertNotEmpty( $mailer->get_last_sent( $abonnee->user_email )->attachment, 'factureer eind pauze email attachment incorrect' );

		$abonnement->betaling->verwerk( $order, 10, true, 'stort' );
		$this->assertEquals( 'Betaling abonnement per bankstorting', $mailer->get_last_sent( $abonnee->user_email )->subject, 'verwerk incasso incorrecte email' );
	}

	/**
	 * Test de betaling verwerk functie, ideal betaling
	 */
	public function test_verwerk_regulier_ideal() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();
		$abonnee    = new Abonnee( $abonnement->klant_id );
		$abonnement->actie->starten( strtotime( '- 1 year 00:00' ), 'beperkt', 'Dit is een test', 'ideal' );

		$order = new Order( $abonnement->get_referentie() );
		$abonnement->betaling->verwerk( $order, 10, true, 'ideal', 'transactie' );
		$this->assertEquals( 'Welkom bij Kleistad', $mailer->get_last_sent( $abonnee->user_email )->subject, 'verwerk ideal onterechte email' );
		$this->assertNotEmpty( $mailer->get_last_sent( $abonnee->user_email )->attachment, 'verwerk start ideal attachment incorrect' );

		$abonnement->factuur_maand = (int) date( 'Ym', strtotime( '- 1 month 00:00' ) );
		$abonnement->save();
		/**
		 * Doe alsof er een mandaat is afgegeven.
		 */
		set_transient( "mollie_mandaat_$abonnement->klant_id", true );

		Abonnementen::doe_dagelijks();
		$abonnee = new Abonnee( $abonnement->klant_id );
		$order   = new Order( $abonnee->abonnement->get_referentie() );
		/**
		 * Bij incasso wordt er pas een email verstuurd als de bank uitbetaalt.
		 */
		$this->assertEquals( 'Welkom bij Kleistad', $mailer->get_last_sent( $abonnee->user_email )->subject, 'verwerk ideal onterechte email' );

		$abonnee->abonnement->betaling->verwerk( $order, 10, true, 'directdebit', 'transactie_2' );
		$this->assertEquals( 'Betaling abonnement per incasso', $mailer->get_last_sent( $abonnee->user_email )->subject, 'verwerk incasso incorrecte email' );
		$this->assertNotEmpty( $mailer->get_last_sent( $abonnee->user_email )->attachment, 'verwerk incasso attachment incorrect' );
	}

	/**
	 * Test de abonnees verzameling
	 */
	public function test_abonnees() {
		$abonnement1 = $this->maak_abonnement();
		$abonnement1->actie->starten( strtotime( '- 4 month 00:00' ), 'beperkt', 'Dit is een test', 'stort' );
		$abonnement2 = $this->maak_abonnement();
		$abonnement2->actie->starten( strtotime( '- 4 month 00:00' ), 'beperkt', 'Dit is een test', 'stort' );
		$abonnees = new Abonnees();
		$this->assertTrue( 1 < $abonnees->count(), 'aantal abonnees onjuist' );
	}

	/**
	 * Test korte en lange statustekst
	 */
	public function test_get_statustekst() {
		$abonnement = $this->maak_abonnement();
		$this->assertEquals( 'actief', $abonnement->get_statustekst( false ), 'Korte statustekst incorrect' );
		$this->assertMatchesRegularExpression( '~actief sinds+~', $abonnement->get_statustekst( true ), 'Lange statustekst incorrect' );
	}

	/**
	 * Test start_incasso en stop_incasso
	 */
	public function test_start_stop_incasso() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$abonnement = $this->maak_abonnement();
		$abonnee    = new Abonnee( $abonnement->klant_id );
		$abonnement->actie->starten( strtotime( '- 4 month 00:00' ), 'beperkt', 'Dit is een test', 'stort' );
		$abonnement->actie->start_incasso();
		$abonnement->betaling->verwerk( new Order( $abonnement->get_referentie() ), 0.01, true, 'ideal', 'incasso' );
		$this->assertEquals( 'Wijziging abonnement', $mailer->get_last_sent( $abonnee->user_email )->subject, 'start incasso email incorrect' );
		$abonnement->actie->stop_incasso();
		$this->assertEquals( 'Wijziging abonnement', $mailer->get_last_sent( $abonnee->user_email )->subject, 'start incasso email incorrect' );
		$this->assertEquals( 3, $mailer->get_sent_count(), 'incasso aantal emails onjuist' );
	}

	/**
	 * Test eventuele extra's.
	 */
	public function test_extras() {
		$abonnement        = $this->maak_abonnement();
		$abonnement->soort = 'beperkt';
		$abonnement->actie->starten( strtotime( '- 5 month 00:00' ), 'beperkt', 'Dit is een test', 'stort' );

		$abonnement                = new Abonnement( $abonnement->klant_id );
		$abonnement->extras        = [ self::EXTRA['naam'] ];
		$abonnement->factuur_maand = date( 'Ym', strtotime( '-2 month' ) );
		$abonnement->save();
		Abonnementen::doe_dagelijks(); // Voert actie->factureer uit en verstuurt email 1.

		$abonnement->artikel_type = 'regulier';
		$order                    = new Order( $abonnement->get_referentie() );
		$this->assertEquals( self::EXTRA['prijs'] + opties()['beperkt_abonnement'], $order->get_te_betalen(), 'Te betalen bij extra onjuist' );
	}
}
