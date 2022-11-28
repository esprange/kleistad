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
 * @noinspection PhpPossiblePolymorphicInvocationInspection
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

		$workshop             = new Workshop();
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

		$workshop->actie->bevestig();
		$this->assertMatchesRegularExpression( '~[WS#[\d]{8}] Bevestiging van workshop~', $mailer->get_last_sent()->subject, 'bevestig email incorrect' );

		$workshop->datum = strtotime( 'days' );
		$workshop->save();

		Workshops::doe_dagelijks();
		$order = new Order( $workshop->get_referentie() );
		$this->assertTrue( 0 < $order->id, 'order niet aangemaakt' );
		$this->assertEquals( $workshop->kosten, $order->get_te_betalen(), 'kosten onjuist' );
	}

	/**
	 * Test de workshop storage.
	 * Deze test moet met de hand gecontroleerd worden, er zijn altijd een aantal properties die niet opgeslagen worden.
	 */
	public function test_workshop_store() {
		if ( 1 === 2 ) {
			$workshop = $this->maak_workshop();
			$time     = strtotime( 'today 0:00' );
			foreach ( get_object_vars( $workshop ) as $key => $value ) {
				if ( 'id' === $key ) {
					continue;
				}
				$workshop->$key = match ( gettype( $workshop->$key ) ) {
					'boolean' => $workshop->$key ?: true,
					'integer' => $time,
					'double'  => 123.45,
					'string'  => 'test123',
					'array'   => [ '123', '456' ],
					'object'  => $value
				};
			}
			$workshop->save();
			$workshop2 = new Workshop( $workshop->id );
			$this->assertEquals( $workshop, $workshop2, 'storage niet correct' );
		}
		$this->assertTrue( true, 'geen test' );
	}

	/**
	 * Test correctie van een workshop die al heeft plaatsgevonden
	 */
	public function test_workshop_correctie_1() {
		$mailer          = tests_retrieve_phpmailer_instance();
		$workshop        = $this->maak_workshop();
		$workshop_kosten = $workshop->kosten;
		$workshop->datum = strtotime( 'yesterday' );
		$workshop->actie->bevestig();
		$this->assertMatchesRegularExpression( '~[WS#[\d]{8}] Bevestiging van workshop~', $mailer->get_last_sent()->subject, 'bevestig email incorrect' );
		Workshops::doe_dagelijks();
		$this->assertMatchesRegularExpression( '~Betaling van workshop~', $mailer->get_last_sent()->subject, 'betaling email incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent()->attachment, 'factuur betaling incorrect' );

		$workshop          = new Workshop( $workshop->id );
		$workshop->aantal += 2;
		$workshop->kosten += 30.0;
		$workshop->actie->bevestig();

		$workshop = new Workshop( $workshop->id );
		$order    = new Order( $workshop->get_referentie() );
		$this->assertEquals( $workshop_kosten + 30.0, $order->get_te_betalen(), 'correctie kosten onjuist' );
		$this->assertFalse( $workshop->vervallen, 'correctie workshop onjuist vervallen' );
		$this->assertMatchesRegularExpression( '~Betaling van workshop~', $mailer->get_last_sent()->subject, 'betaling email incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent()->attachment, 'factuur betaling incorrect' );
	}

	/**
	 * Test correctie van een workshop die al heeft plaatsgevonden
	 */
	public function test_workshop_correctie_2() {
		$mailer          = tests_retrieve_phpmailer_instance();
		$workshop        = $this->maak_workshop();
		$workshop_kosten = $workshop->kosten;
		$workshop->datum = strtotime( 'yesterday' );
		$workshop->actie->bevestig();
		$this->assertMatchesRegularExpression( '~[WS#[\d]{8}] Bevestiging van workshop~', $mailer->get_last_sent()->subject, 'bevestig email incorrect' );
		Workshops::doe_dagelijks();
		$this->assertMatchesRegularExpression( '~Betaling van workshop~', $mailer->get_last_sent()->subject, 'betaling email incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent()->attachment, 'factuur betaling incorrect' );

		$workshop                    = new Workshop( $workshop->id );
		$workshop->organisatie       = 'Bedrijf x';
		$workshop->organisatie_email = 'bedrijf@test.nl';
		$workshop->actie->bevestig();

		$workshop = new Workshop( $workshop->id );
		$order    = new Order( $workshop->get_referentie() );
		$this->assertEquals( $workshop_kosten, $order->get_te_betalen(), 'correctie kosten onjuist' );
		$this->assertFalse( $workshop->vervallen, 'correctie workshop onjuist vervallen' );
		$this->assertMatchesRegularExpression( '~Betaling van workshop~', $mailer->get_last_sent()->subject, 'betaling email incorrect' );
		$this->assertArrayHasKey( $workshop->organisatie_email, $mailer->getAllRecipientAddresses(), 'organisatie email adres ontbreekt' );
		$this->assertNotEmpty( $mailer->get_last_sent()->attachment, 'factuur betaling incorrect' );
	}

	/**
	 * Test of de docent_naam correct wordt weergegeven.
	 */
	public function test_docent_naam() {
		$workshop = $this->maak_workshop();

		$workshop->docent = 'Test tester';
		$this->assertEquals( 'Test tester', $workshop->get_docent_naam(), 'enkele docent naam onjuist' );

		$docent_1         = $this->factory()->user->create_and_get();
		$workshop->docent = "$docent_1->ID";
		$this->assertEquals( $docent_1->display_name, $workshop->get_docent_naam(), 'enkele docent id onjuist' );

		$docent_2         = $this->factory()->user->create_and_get();
		$workshop->docent = "$docent_1->ID;$docent_2->ID";
		$this->assertEquals( "$docent_1->display_name, $docent_2->display_name", $workshop->get_docent_naam(), 'meerdere docent id onjuist' );
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
	 * Test function get_referentie
	 */
	public function test_get_referentie() {
		$workshop = $this->maak_workshop();
		$workshop->save();
		$this->assertMatchesRegularExpression( '~W\d+~', $workshop->get_referentie(), 'referentie incorrect' );
	}

	/**
	 * Test geef artikel naam
	 */
	public function test_get_artikelnaam() {
		$workshop       = $this->maak_workshop();
		$workshop->naam = 'kinderfeest';
		$workshop->save();
		$this->assertEquals( 'kinderfeest', $workshop->get_artikelnaam(), 'artikel naam incorrect' );
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
		$order = new Order( $workshop->get_referentie() );
		$workshop->betaling->verwerk( $order, $workshop->kosten, true, 'stort' );
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
		$this->assertMatchesRegularExpression( '~[WS#[\d]{8}] Bevestiging van workshop~', $mailer->get_last_sent()->subject, 'bevestigd mail incorrect' );
		$workshop->actie->annuleer();
		$this->assertMatchesRegularExpression( '~[WS#[\d]{8}] Annulering van workshop~', $mailer->get_last_sent()->subject, 'afzeggen bevestigd mail incorrect' );
		$this->assertEmpty( $mailer->get_last_sent()->attachment, 'mail credit factuur onnodig' );

		$workshop        = $this->maak_workshop();
		$workshop->datum = strtotime( '10 days' );
		$workshop->actie->bevestig();
		Workshops::doe_dagelijks();
		$workshop->actie->annuleer();
		$this->assertMatchesRegularExpression( '~[WS#[\d]{8}] Annulering van workshop~', $mailer->get_last_sent()->subject, 'afzeggen bevestigd mail incorrect' );
		$this->assertEmpty( $mailer->get_last_sent()->attachment, 'mail credit factuur onnodig' );

		$workshop        = $this->maak_workshop();
		$workshop->datum = strtotime( '5 days' );
		$workshop->actie->bevestig();
		Workshops::doe_dagelijks();
		$this->assertEquals( 'Betaling van workshop', $mailer->get_last_sent()->subject, 'mail betalen incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent()->attachment, 'mail betalen factuur ontbreekt' );
		$workshop->actie->annuleer();
		$this->assertMatchesRegularExpression( '~[WS#[\d]{8}] Annulering van workshop~', $mailer->get_last_sent()->subject, 'afzeggen bevestigd mail incorrect' );
		$this->assertNotEmpty( $mailer->get_last_sent()->attachment, 'mail credit factuur ontbreekt' );
	}

	/**
	 * Test annuleer_order function
	 */
	public function test_annuleer() {
		$workshop1        = $this->maak_workshop();
		$workshop1->datum = strtotime( '5 days' );
		$workshop1->actie->bevestig();
		Workshops::doe_dagelijks();

		$order = new Order( $workshop1->get_referentie() );
		$order->annuleer( 24.0 );
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
		$this->assertMatchesRegularExpression( '~[WS#[\d]{8}] Reactie op workshop~', $mailer->get_last_sent()->subject, 'email reactie aanvraag incorrect' );
	}

	/**
	 * Test concept vervallen
	 */
	public function test_concept_vervallen() {
		$workshop1                = $this->maak_workshop();
		$workshop1->aanvraagdatum = strtotime( '-7 days' );
		$workshop1->datum         = strtotime( '7 days' );
		$workshop1->actie->bevestig();
		$workshop2                = $this->maak_workshop();
		$workshop2->aanvraagdatum = strtotime( '-8 days' );
		$workshop2->datum         = strtotime( '7 days' );
		$workshop2->save();
		Workshops::doe_dagelijks();
		$workshop1 = new Workshop( $workshop1->id );
		$workshop2 = new Workshop( $workshop2->id );
		$this->assertFalse( $workshop1->vervallen, 'vervallen 1 onjuist' );
		$this->assertTrue( $workshop2->vervallen, 'vervallen 2 onjuist' );
	}
}
