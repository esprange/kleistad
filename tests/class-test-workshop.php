<?php
/**
 * Class Workshop Test
 *
 * Test de classen workshop, workshops, worabonnement, abonnementen, abonnee, abonnementactie
 * Test van classen abonnees en abonnementbetaling ontbreken nog
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Workshop, \Kleistad\Workshops, \Kleistad\WorkshopActie, \Kleistad\WorkshopBetaling
 * @noinspection PhpPossiblePolymorphicInvocationInspection, PhpUndefinedFieldInspection
 */

namespace Kleistad;

/**
 * Workshop test case.
 */
class Test_Workshop extends Kleistad_UnitTestCase {

	/**
	 * Maak een workshop
	 *
	 * @return Workshop
	 */
	private function maak_workshop(): Workshop {

		$workshop = $this->getMockBuilder( Workshop::class )->onlyMethods( [ 'maak_factuur' ] )->getMock();
		$workshop->method( 'maak_factuur' )->willReturn( __FILE__ );
		$workshop->kosten     = 120;
		$workshop->naam       = 'workshop';
		$workshop->email      = 'workshop_test@example.com';
		$workshop->datum      = strtotime( '20 days' );
		$workshop->start_tijd = strtotime( '12:00' );
		$workshop->eind_tijd  = strtotime( '15:00' );
		$workshop->save();
		return $workshop;
	}

	/**
	 * Test creation and modification of a workshop.
	 */
	public function test_workshop() {
		$mailer   = tests_retrieve_phpmailer_instance();
		$workshop = $this->maak_workshop();

		$this->assertTrue( $workshop->actie->bevestig(), 'bevestig actie incorrect' );
		$this->assertMatchesRegularExpression( '~[WS#[0-9]{8}] Bevestiging van workshop~', $mailer->get_last_sent()->subject, 'bevestig email incorrect' );

		$workshop->datum = strtotime( '21 days' );
		$this->assertTrue( $workshop->actie->bevestig(), 'workshop herbevestig incorrect' );
		$this->assertMatchesRegularExpression( '~[WS#[0-9]{8}] Bevestiging na correctie van workshop~', $mailer->get_last_sent()->subject, 'herbevestig email incorrect' );
	}

	/**
	 * Test of de docent_naam correct wordt weergegeven.
	 */
	public function test_docent_naam() {
		$workshop = $this->maak_workshop();

		$workshop->docent = 'Test tester';
		$this->assertEquals( 'Test tester', $workshop->docent_naam(), 'enkele docent naam onjuist' );

		$docent_id_1      = $this->factory->user->create();
		$docent_naam_1    = get_user_by( 'ID', $docent_id_1 )->display_name;
		$workshop->docent = "$docent_id_1";
		$this->assertEquals( $docent_naam_1, $workshop->docent_naam(), 'enkele docent id onjuist' );

		$docent_id_2      = $this->factory->user->create();
		$docent_naam_2    = get_user_by( 'ID', $docent_id_2 )->display_name;
		$workshop->docent = "$docent_id_1;$docent_id_2";
		$this->assertEquals( "$docent_naam_1, $docent_naam_2", $workshop->docent_naam(), 'meerdere docent id onjuist' );
	}

	/**
	 * Test function erase
	 */
	public function test_erase() {
		$workshop = $this->maak_workshop();
		$workshop->save();
		$workshops        = new Workshops();
		$aantal_workshops = count( $workshops );
		$workshop->erase();
		$this->assertEquals( $aantal_workshops - 1, count( new Workshops() ), 'erase workshop incorrect' );
	}

	/**
	 * Test function geef_referentie
	 */
	public function test_geef_referentie() {
		$workshop = $this->maak_workshop();
		$workshop->save();
		$this->assertMatchesRegularExpression( '~W[0-9]+~', $workshop->geef_referentie(), 'referentie incorrect' );
	}

	/**
	 * Test geef artikel naam
	 */
	public function test_geef_artikelnaam() {
		$workshop       = $this->maak_workshop();
		$workshop->naam = 'kinderfeest';
		$workshop->save();
		$this->assertEquals( 'kinderfeest', $workshop->geef_artikelnaam(), 'artikel naam incorrect' );
	}

	/**
	 * Test is_betaald
	 */
	public function test_is_betaald() {
		$workshop = $this->maak_workshop();
		$workshop->save();
		$workshop->actie->bevestig();
		$this->assertFalse( $workshop->is_betaald(), 'onbetaald workshop incorrect' );
		$workshop->actie->vraag_betaling();
		$order = new Order( $workshop->geef_referentie() );
		$workshop->betaling->verwerk( $order, $workshop->kosten, true, 'bank' );
		$this->assertTrue( $workshop->is_betaald(), 'betaald workshop incorrect' );
	}

	/**
	 * Test vraag betaling
	 */
	public function test_vraag_betaling() {
		$mailer   = tests_retrieve_phpmailer_instance();
		$workshop = $this->maak_workshop();
		$workshop->save();
		$workshop->actie->vraag_betaling();
		$this->assertEquals( 'Betaling van workshop', $mailer->get_last_sent()->subject, 'onderwerk betaling incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent()->attachment, 'factuur betaling incorrect' );
	}

	/**
	 * Test function afzeggen
	 */
	public function test_afzeggen() {
		$mailer   = tests_retrieve_phpmailer_instance();
		$workshop = $this->maak_workshop();
		$workshop->save();
		$workshop->actie->afzeggen();
		$this->assertEquals( 0, $mailer->get_sent_count(), 'afzeggen niet bevestigd mail incorrect' );

		$workshop        = $this->maak_workshop();
		$workshop->datum = strtotime( '10 days' );
		$workshop->actie->bevestig();
		$workshop->actie->afzeggen();
		$this->assertMatchesRegularExpression( '~[WS#[0-9]{8}] Annulering van workshop~', $mailer->get_last_sent()->subject, 'afzeggen bevestigd mail incorrect' );

		$workshop        = $this->maak_workshop();
		$workshop->datum = strtotime( '10 days' );
		$workshop->actie->bevestig();
		Workshops::doe_dagelijks();

		$workshop->actie->afzeggen();
		$this->assertMatchesRegularExpression( '~[WS#[0-9]{8}] Annulering van workshop~', $mailer->get_last_sent()->subject, 'afzeggen bevestigd mail incorrect' );

		$workshop        = $this->maak_workshop();
		$workshop->datum = strtotime( '5 days' );
		$workshop->actie->bevestig();
		Workshops::doe_dagelijks();
		$this->assertEquals( 'Betaling van workshop', $mailer->get_last_sent()->subject, 'mail betalen incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent()->attachment, 'mail betalen factuur ontbreekt' );
		/**
		 * Hier zit nog iets scheef. De controle of afzeggen mogelijk is, zit nu in de front-end van workshop beheer en hoort daar niet thuis
		 * Nu wordt daar gecontroleerd of er al gefactureerd is.
		 */
		$workshop->actie->afzeggen();
		$this->assertMatchesRegularExpression( '~[WS#[0-9]{8}] Annulering van workshop~', $mailer->get_last_sent()->subject, 'afzeggen bevestigd mail incorrect' );
	}

	/**
	 * Test annuleer_order function
	 */
	public function test_annuleer_order() {
		$workshop1        = $this->maak_workshop();
		$workshop1->datum = strtotime( '5 days' );
		$workshop1->actie->bevestig();
		Workshops::doe_dagelijks();

		$order = new Order( $workshop1->geef_referentie() );
		$workshop1->annuleer_order( $order, 24.0, '' );
		$this->assertTrue( $order->id > 0, 'bestel_order incorrect' );
		$workshop2 = new Workshop( $workshop1->id );
		$this->assertTrue( $workshop2->vervallen, 'vervallen status incorrect' );
	}

	/**
	 * Test verwerk aanvraag
	 */
	public function test_verwerk() {
		$mailer   = tests_retrieve_phpmailer_instance();
		$workshop = $this->maak_workshop();
		$workshop->actie->reactie( 'reactie 1 op vraag' );

		$email = [
			'subject'   => 'RE:' . $mailer->get_last_sent()->subject,
			'from'      => $workshop->email,
			'from-name' => $workshop->contact,
			'content'   => 'Een vraag van de klant',
			'tijd'      => current_time( 'd-m-Y H:i' ),
		];
		WorkshopActie::verwerk( $email );
		$this->assertEquals( 'Workshop vraag', $mailer->get_last_sent()->subject, 'email verwerk incorrect' );

		$workshop->actie->reactie( 'reactie 2 op vraag' );

		$email = [
			'subject'   => 'eigen onderwerp',
			'from'      => $workshop->email,
			'from-name' => $workshop->contact,
			'content'   => 'Een tweede vraag van de klant',
			'tijd'      => current_time( 'd-m-Y H:i' ),
		];
		WorkshopActie::verwerk( $email );
		$this->assertEquals( 'FW: eigen onderwerp', $mailer->get_last_sent()->subject, 'email verwerk incorrect' );
	}

	/**
	 * Test reactie aanvraag
	 */
	public function test_reactie_aanvraag() {
		$mailer   = tests_retrieve_phpmailer_instance();
		$workshop = $this->maak_workshop();
		$workshop->actie->reactie( 'dit is een reactie' );
		$this->assertMatchesRegularExpression( '~[WS#[0-9]{8}] Reactie op workshop~', $mailer->get_last_sent()->subject, 'email reactie aanvraag incorrect' );
	}
}
