<?php
/**
 * Class Inschrijving Scenario
 *
 * @package Kleistad
 */

namespace Kleistad;

use Mollie\Api\Types\PaymentMethod;

/**
 * Inschrijving scenario test case.
 */
class Test_Inschrijving_Scenario extends Kleistad_UnitTestCase {

	/**
	 * Test scenario:
	 *
	 * Maak 2 cursussen, startend 1 maand in de toekomst, elk met capaciteit van 4 cursisten.
	 * En maak 1 cursus, reeds gestart, met capaciteit van 4 cursisten
	 *
	 * Schrijf cursist 0 in op cursus 0 via iDeal betaling voor één persoon
	 *    Cursist 0 is ingedeeld,
	 *    Cursus 0 heeft ruimte voor 3 cursisten
	 *    Cursist 0 heeft rol cursist en heeft indelings mail ontvangen
	 * Schrijf cursist 1 in op cursus 0 via iDeal betaling voor 3 personen
	 *    Cursist 1 is ingedeeld,
	 *    Cursus 0 is vol
	 *    Cursist 1 heeft rol cursist en heeft indelings mail ontvangen
	 * Schrijf 2 extra cursisten 6 en 7 in
	 *    Cursist 6 en 7 zijn ingedeeld met cursist 2 als hoofdcursist
	 *    Cursus 0 is vol
	 *    Cursist 6 en 7 hebben rol cursist en hebben welkomst mail ontvangen
	 * Schrijf cursist 2 en 3 in op cursus 0, naar wachtlijst
	 *    Cursist 2 en 3 zijn ingeschreven maar niet ingedeeld
	 *    Cursus 0 is vol
	 *    Cursist 2  en 3 hebben geen rol en hebben wachtlijst email ontvangen
	 * Wijzig cursist 0 naar cursus 1
	 *    Cursist 0 is ingedeeld, nu op cursus 1
	 *    Cursist 0 heeft correctie email ontvangen
	 *    Cursus 1 heeft ruimte voor 3 cursisten
	 *    Cursus 0 heeft ruimte voor 1 cursist
	 *    Cursist 2 en 3 hebben ruimte email ontvangen
	 * Wijzig wachtlijst cursist 2 naar indeling
	 *    Cursist 2 is ingedeeld
	 *    Cursus 0 is vol
	 *    Cursist 2 heeft rol cursist en heeft indelings mail ontvangen
	 * Schrijf cursist 4 in op cursus 1 via Bank betaling
	 *    Cursist 4 is niet ingedeeld
	 *    Cursus 1 ruimte voor 3 cursisten
	 *    Cursist 4 heeft geen rol en heeft inschrijvings mail ontvangen
	 * Wijzig cursist 1 naar cursus 1
	 *    Cursist 1, 6 en 7 zijn ingedeeld op cursus 1
	 *    Cursist 1 heeft correctie email ontvangen
	 *    Cursist 1, 6 en 7 hebben rol cursist
	 *    Cursus 1 is vol
	 *    Cursus 0 heeft ruimte voor 3 cursisten
	 * Schrijf cursist 6 in op cursus 0 via Ideal
	 *    Cursist 6 is ingedeeld op cursus 0
	 *    Cursist 6 heeft rol cursist
	 *    Cursist 6 heeft indeling email ontvangen
	 * Annuleer cursist 1
	 *    Cursist 1, 6 en 7 zijn niet meer ingedeeld
	 *    Cursist 1 en 7 hebben geen rol, cursist 6 wel
	 *    Cursus 1 heeft ruimte voor 3 cursisten
	 * Schrijf cursist 5 in op cursus  2
	 *    Cursist 5 is ingedeeld
	 *    Cursus 2 heeft ruimte voor 3 cursisten
	 *    Cursist 5 heeft rol cursist en heeft indelings mail ontvangen
	 */
	public function test_scenario() {
		$mailer = tests_retrieve_phpmailer_instance();

		$cursus_start_datums = [
			strtotime( '+ 1 month' ),
			strtotime( '+ 1 month' ),
			strtotime( '- 1 month' ),
		];
		foreach ( $cursus_start_datums as $cursus_start_datum ) {
			$cursus[] = $this->factory()->cursus->create_and_get(
				[
					'start_datum' => $cursus_start_datum,
					'eind_datum'  => $cursus_start_datum + 8 * WEEK_IN_SECONDS,
					'maximum'     => 4,
				]
			);
		}
		for ( $index = 0; $index < 8; $index++ ) {
			$cursist[ $index ] = get_user_by( 'id', $this->factory()->user->create() );
		}

		$inschrijving_0 = new Inschrijving( $cursus[0]->id, $cursist[0]->ID );
		$inschrijving_0->actie->aanvraag( 'ideal', 1, [], '' );
		$order_0 = new Order( $inschrijving_0->get_referentie() );
		$inschrijving_0->betaling->verwerk( $order_0, 25, true, 'ideal' );
		$inschrijving_0_1 = new Inschrijving( $cursus[0]->id, $cursist[0]->ID );
		$cursus[0]        = new Cursus( $cursus[0]->id );
		$this->assertTrue( $inschrijving_0_1->ingedeeld, 'step 0, niet ingedeeld' );
		$this->assertTrue( user_can( $cursist[0]->ID, CURSIST ), 'step 0, cursist rol ontbreekt' );
		$this->assertEquals( 3, $cursus[0]->get_ruimte(), 'step 0, ruimte onjuist' );
		$this->assertEquals( 'Indeling cursus', $mailer->get_last_sent( $cursist[0]->user_email )->subject, 'step 0, email onjuist' );

		$inschrijving_1 = new Inschrijving( $cursus[0]->id, $cursist[1]->ID );
		$inschrijving_1->actie->aanvraag( 'ideal', 3, [], '' );
		$order_1 = new Order( $inschrijving_1->get_referentie() );
		$inschrijving_1->betaling->verwerk( $order_1, 75, true, 'ideal' );
		$inschrijving_1_1 = new Inschrijving( $cursus[0]->id, $cursist[1]->ID );
		$cursus[0]        = new Cursus( $cursus[0]->id );
		$this->assertTrue( $inschrijving_1_1->ingedeeld, 'step 1, niet ingedeeld' );
		$this->assertTrue( user_can( $cursist[1]->ID, CURSIST ), 'step 1, cursist rol ontbreekt' );
		$this->assertEquals( 0, $cursus[0]->get_ruimte(), 'step 1, ruimte onjuist' );
		$this->assertTrue( $cursus[0]->vol, 'step 1, vol indicatie onjuist' );
		$this->assertEquals( 'Indeling cursus', $mailer->get_last_sent( $cursist[1]->user_email )->subject, 'step 1, email onjuist' );

		$inschrijving_2   = new Inschrijving( $cursus[0]->id, $cursist[1]->ID );
		$inschrijving_2_1 = new Inschrijving( $cursus[0]->id, $cursist[6]->ID );
		$inschrijving_2_1->actie->indelen_extra( $inschrijving_2 );
		$inschrijving_2_2 = new Inschrijving( $cursus[0]->id, $cursist[7]->ID );
		$inschrijving_2_2->actie->indelen_extra( $inschrijving_2 );
		$inschrijving_2_3 = new Inschrijving( $cursus[0]->id, $cursist[1]->ID );
		$cursus[0]        = new Cursus( $cursus[0]->id );
		$this->assertTrue( $inschrijving_2_1->ingedeeld, 'step 2, niet ingedeeld' );
		$this->assertTrue( user_can( $cursist[6]->ID, CURSIST ), 'step 2, cursist rol ontbreekt' );
		$this->assertTrue( $inschrijving_2_2->ingedeeld, 'step 2, niet ingedeeld' );
		$this->assertTrue( user_can( $cursist[7]->ID, CURSIST ), 'step 2, cursist rol ontbreekt' );
		$this->assertEquals( 0, $cursus[0]->get_ruimte(), 'step 2, ruimte onjuist' );
		$this->assertEquals( 2, count( $inschrijving_2_3->extra_cursisten ), 'step 2, registratie onjuist' );
		$this->assertTrue( $cursus[0]->vol, 'step 2, vol indicatie onjuist' );
		$this->assertEquals( 'Welkom cursus', $mailer->get_last_sent( $cursist[6]->user_email )->subject, 'step 2, email onjuist' );
		$this->assertEquals( 'Welkom cursus', $mailer->get_last_sent( $cursist[7]->user_email )->subject, 'step 2, email onjuist' );

		$inschrijving_3_1 = new Inschrijving( $cursus[0]->id, $cursist[2]->ID );
		$inschrijving_3_1->actie->aanvraag( 'ideal', 1, [], '' );
		$inschrijving_3_2 = new Inschrijving( $cursus[0]->id, $cursist[3]->ID );
		$inschrijving_3_2->actie->aanvraag( 'ideal', 1, [], '' );
		$cursus[0] = new Cursus( $cursus[0]->id );
		$this->assertFalse( $inschrijving_3_1->ingedeeld, 'step 3, ingedeeld' );
		$this->assertFalse( user_can( $cursist[2]->ID, CURSIST ), 'step 3, cursist rol aanwezig' );
		$this->assertFalse( $inschrijving_3_2->ingedeeld, 'step 3, ingedeeld' );
		$this->assertFalse( user_can( $cursist[3]->ID, CURSIST ), 'step 3, cursist rol aanwezig' );
		$this->assertEquals( 'Plaatsing op wachtlijst cursus', $mailer->get_last_sent( $cursist[2]->user_email )->subject, 'step 3, email onjuist' );
		$this->assertEquals( 'Plaatsing op wachtlijst cursus', $mailer->get_last_sent( $cursist[3]->user_email )->subject, 'step 3, email onjuist' );

		$inschrijving_4 = new Inschrijving( $cursus[0]->id, $cursist[0]->ID );
		$inschrijving_4->actie->correctie( $cursus[1]->id, 1, [] );
		$cursus[0]        = new Cursus( $cursus[0]->id );
		$cursus[1]        = new Cursus( $cursus[1]->id );
		$inschrijving_4_1 = new Inschrijving( $cursus[0]->id, $cursist[0]->ID );
		$inschrijving_4_2 = new Inschrijving( $cursus[1]->id, $cursist[0]->ID );
		$this->assertTrue( $inschrijving_4_1->geannuleerd, 'step 4, niet geannuleerd' );
		$this->assertTrue( $inschrijving_4_2->ingedeeld, 'step 4, niet ingedeeld' );
		$this->assertTrue( user_can( $cursist[0]->ID, CURSIST ), 'step 4, cursist rol ontbreekt' );
		$this->assertEquals( 1, $cursus[0]->get_ruimte(), 'step 4, ruimte onjuist' );
		$this->assertEquals( 3, $cursus[1]->get_ruimte(), 'step 4, ruimte onjuist' );
		$this->assertTrue( $cursus[0]->vol, 'step 4, vol indicatie onjuist' );
		$this->assertFalse( $cursus[1]->vol, 'step 4, vol indicatie onjuist' );
		$this->assertEquals( 'Wijziging inschrijving cursus', $mailer->get_last_sent( $cursist[0]->user_email )->subject, 'step 4, email onjuist' );
		sleep( 1 );
		Cursussen::doe_dagelijks();
		Inschrijvingen::doe_dagelijks();
		$cursus[0] = new Cursus( $cursus[0]->id );
		$cursus[1] = new Cursus( $cursus[1]->id );
		$this->assertFalse( $cursus[0]->vol, 'step 4, vol indicatie onjuist' );
		$this->assertFalse( $cursus[1]->vol, 'step 4, vol indicatie onjuist' );
		$this->assertEquals( 'Er is een cursusplek vrijgekomen', $mailer->get_last_sent( $cursist[2]->user_email )->subject, 'step 4, email onjuist' );
		$this->assertEquals( 'Er is een cursusplek vrijgekomen', $mailer->get_last_sent( $cursist[3]->user_email )->subject, 'step 4, email onjuist' );

		$inschrijving_5 = new Inschrijving( $cursus[0]->id, $cursist[2]->ID );
		$inschrijving_5->actie->indelen_na_wachten();
		$order_2 = new Order( $inschrijving_5->get_referentie() );
		$inschrijving_5->betaling->verwerk( $order_2, 75, true, 'ideal' );
		$cursus[0]        = new Cursus( $cursus[0]->id );
		$inschrijving_5_1 = new Inschrijving( $cursus[0]->id, $cursist[2]->ID );
		$this->assertTrue( $inschrijving_5_1->ingedeeld, 'step 5, niet ingedeeld' );
		$this->assertTrue( user_can( $cursist[2]->ID, CURSIST ), 'step 5, cursist rol ontbreekt' );
		$this->assertTrue( $cursus[0]->vol, 'step 5, vol indicatie onjuist' );
		$this->assertEquals( 'Indeling cursus', $mailer->get_last_sent( $cursist[2]->user_email )->subject, 'step 5, email onjuist' );

		$inschrijving_6 = new Inschrijving( $cursus[1]->id, $cursist[4]->ID );
		$inschrijving_6->actie->aanvraag( 'stort', 1, [], '' );
		$inschrijving_6_1 = new Inschrijving( $cursus[1]->id, $cursist[4]->ID );
		$this->assertFalse( $inschrijving_6_1->ingedeeld, 'step 6, ingedeeld' );
		$this->assertFalse( user_can( $cursist[4]->ID, CURSIST ), 'step 6, cursist rol aanwezig' );
		$this->assertEquals( 'Inschrijving cursus', $mailer->get_last_sent( $cursist[4]->user_email )->subject, 'step 6, email onjuist' );

		$inschrijving_7 = new Inschrijving( $cursus[0]->id, $cursist[1]->ID );
		$inschrijving_7->actie->correctie( $cursus[1]->id, 3, $inschrijving_7->extra_cursisten );
		$inschrijving_7_1 = new Inschrijving( $cursus[1]->id, $cursist[1]->ID );
		$inschrijving_7_2 = new Inschrijving( $cursus[1]->id, $cursist[6]->ID );
		$inschrijving_7_3 = new Inschrijving( $cursus[1]->id, $cursist[7]->ID );
		$this->assertTrue( $inschrijving_7_1->ingedeeld, 'step 7, niet ingedeeld' );
		$this->assertTrue( $inschrijving_7_2->ingedeeld, 'step 7, niet ingedeeld' );
		$this->assertTrue( $inschrijving_7_3->ingedeeld, 'step 7, niet ingedeeld' );
		$this->assertTrue( user_can( $cursist[1]->ID, CURSIST ), 'step 7, cursist rol ontbreekt' );
		$this->assertTrue( user_can( $cursist[6]->ID, CURSIST ), 'step 7, cursist rol ontbreekt' );
		$this->assertTrue( user_can( $cursist[7]->ID, CURSIST ), 'step 7, cursist rol ontbreekt' );
		$cursus[0] = new Cursus( $cursus[0]->id );
		$cursus[1] = new Cursus( $cursus[1]->id );
		$this->assertTrue( $cursus[1]->vol, 'step 7, vol indicatie onjuist' );
		$this->assertEquals( 'Wijziging inschrijving cursus', $mailer->get_last_sent( $cursist[1]->user_email )->subject, 'step 6, email onjuist' );
		$this->assertEquals( 3, $cursus[0]->get_ruimte(), 'step 7, ruimte onjuist' );

		$inschrijving_8 = new Inschrijving( $cursus[0]->id, $cursist[6]->ID );
		$inschrijving_8->actie->aanvraag( 'ideal', 1, [], '' );
		$order_8 = new Order( $inschrijving_8->get_referentie() );
		$inschrijving_8->betaling->verwerk( $order_8, 25, true, 'ideal' );
		$this->assertTrue( $inschrijving_8->ingedeeld, 'step 8, niet ingedeeld' );
		$this->assertTrue( user_can( $cursist[6]->ID, CURSIST ), 'step 8 cursist rol ontbreekt' );
		$this->assertEquals( 'Indeling cursus', $mailer->get_last_sent( $cursist[6]->user_email )->subject, 'step 8, email onjuist' );

		$inschrijving_9 = new Inschrijving( $cursus[1]->id, $cursist[1]->ID );
		$inschrijving_9->actie->afzeggen();
		$inschrijving_9_1 = new Inschrijving( $cursus[1]->id, $cursist[1]->ID );
		$inschrijving_9_2 = new Inschrijving( $cursus[1]->id, $cursist[6]->ID );
		$inschrijving_9_3 = new Inschrijving( $cursus[1]->id, $cursist[7]->ID );
		$this->assertTrue( $inschrijving_9_1->geannuleerd, 'step 9, niet geannuleerd' );
		$this->assertTrue( $inschrijving_9_2->geannuleerd, 'step 9, niet geannuleerd' );
		$this->assertTrue( $inschrijving_9_3->geannuleerd, 'step 9, niet geannuleerd' );
		$this->assertEquals( 3, $cursus[1]->get_ruimte(), 'step 9, ruimte onjuist' );
		Cursisten::doe_dagelijks();
		$this->assertTrue( user_can( $cursist[6]->ID, CURSIST ), 'step 9, cursist rol ontbreekt' );
		$this->assertFalse( user_can( $cursist[7]->ID, CURSIST ), 'step 9, cursist rol aanwezig' );
		$this->assertFalse( user_can( $cursist[1]->ID, CURSIST ), 'step 9, cursist rol aanwezig' );

		$inschrijving_10 = new Inschrijving( $cursus[2]->id, $cursist[5]->ID );
		$inschrijving_10->actie->indelen_lopend( 50 );
		$this->assertTrue( $inschrijving_10->ingedeeld, 'step 10, niet ingedeeld' );
		$this->assertTrue( user_can( $cursist[5]->ID, CURSIST ), 'step 10, cursist rol ontbreekt' );
		$this->assertEquals( 3, $cursus[2]->get_ruimte(), 'step 10, ruimte onjuist' );
		$this->assertEquals( 'Betaling bedrag voor reeds gestarte cursus', $mailer->get_last_sent( $cursist[5]->user_email )->subject, 'step 8, email onjuist' );
	}

}
