<?php
/**
 * Class Public Cursus Inschrijving Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Cursus_Inschrijving
 * @noinspection PhpArrayWriteIsNotUsedInspection, PhpUnhandledExceptionInspection
 */

namespace Kleistad;

/**
 * Cursus inschrijving test case.
 */
class Test_Public_Cursus_Inschrijving extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'cursus_inschrijving';

	/**
	 * Formulier data.
	 *
	 * @var array $input De ingevoerde data.
	 */
	private array $input;

	/**
	 * Maak een cursist inschrijving aan.
	 *
	 * @param bool $wachtlijst Of de cursist op de wachtlijst moet.
	 * @return Inschrijving De inschrijving.
	 */
	private function maak_inschrijving( bool $wachtlijst ) : Inschrijving {
		$cursist     = $this->factory()->user->create_and_get();
		$cursus      = $this->factory()->cursus->create_and_get(
			[
				'start_datum' => strtotime( '+1 month' ),
				'maximum'     => 3,
			]
		);
		$this->input = [
			'user_email'     => $cursist->user_email,
			'email_controle' => $cursist->user_email,
			'first_name'     => $cursist->first_name,
			'last_name'      => $cursist->last_name,
			'straat'         => 'straat',
			'huisnr'         => '12345',
			'pcode'          => '1234AB',
			'plaats'         => 'plaats',
			'telnr'          => '0123456789',
			'cursus_id'      => $cursus->id,
			'gebruiker_id'   => $cursist->ID,
			'technieken'     => [],
			'aantal'         => 1,
			'opmerking'      => '',
			'betaal'         => 'ideal',
		];
		if ( $wachtlijst ) {
			/**
			 * Maak eerst de cursus vol zodat er geen ruimte meer is.
			 */
			$cursist_ids = $this->factory()->user->create_many( $cursus->maximum );
			foreach ( $cursist_ids as $cursist_id ) {
				$inschrijving = new Inschrijving( $cursus->id, $cursist_id );
				$inschrijving->actie->aanvraag( 'ideal', 1, [], '' );
				$order = new Order( $inschrijving->get_referentie() );
				$inschrijving->betaling->verwerk( $order, 25, true, 'ideal' );
			}
		}

		/**
		 * Schrijf nu de cursist in.
		 */
		return new Inschrijving( $cursus->id, $cursist->ID );
	}

	/**
	 * Test de prepare functie als er nog geen cursus gedefinieerd is.
	 */
	public function test_prepare_zonder_cursus() {
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		/**
		 * Standaard wordt de inschrijven actie uitgevoerd. Omdat er nog geen cursussen gedefinieerd zijn moet een error afgegeven worden.
		 */
		$this->assertStringContainsString( 'Helaas is er geen cursusplek meer beschikbaar', $result, 'prepare geen cursus incorrect' );
	}

	/**
	 * Test de prepare functie met een cursus gedefinieerd.
	 */
	public function test_prepare_met_cursus() {
		$inschrijving                   = $this->maak_inschrijving( true );
		$inschrijving->cursus->tonen    = true;
		$inschrijving->cursus->maximum += 1;
		$inschrijving->cursus->save();
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( $inschrijving->cursus->naam, $result, 'prepare inschrijven geen cursus' );
	}

	/**
	 * Test prepare stoppen na wachten.
	 */
	public function prepare_stoppen_na_wachten() {
		$inschrijving                   = $this->maak_inschrijving( true );
		$inschrijving->cursus->tonen    = true;
		$inschrijving->cursus->maximum += 1;
		$inschrijving->cursus->save();
		$_GET   = [
			'code'  => $inschrijving->code,
			'hsh'   => $inschrijving->get_controle(),
			'actie' => 'stop_wachten',
		];
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( 'Afmelden voor de wachtlijst van cursus', $result, 'prepare stoppen na wachten incorrect' );
	}

	/**
	 * Test function indelen na wachten
	 */
	public function prepare_indelen_na_wachten() {
		$inschrijving                   = $this->maak_inschrijving( true );
		$inschrijving->cursus->tonen    = true;
		$inschrijving->cursus->maximum += 1;
		$inschrijving->cursus->save();
		$_GET = [
			'code'  => $inschrijving->code,
			'hsh'   => $inschrijving->get_controle(),
			'actie' => 'indelen_na_wachten',
		];
		Cursussen::doe_dagelijks(); // Zet de vol indicator uit en verstuur de email met de link.
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( 'Aanmelding voor cursus', $result, 'prepare indelen na wachten niet correct' );
	}

	/**
	 * Test validate functie.
	 */
	public function test_inschrijven() {
		$this->maak_inschrijving( false );
		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [], 'inschrijven' );
		$this->assertArrayHasKey( 'redirect_uri', $result, 'geen ideal verwijzing na inschrijven' );
	}

	/**
	 * Test functie inschrijven herhaald.
	 */
	public function test_inschrijven_herhaald() {
		$inschrijving = $this->maak_inschrijving( false );
		$inschrijving->actie->aanvraag( 'ideal', 1, [], '' );
		$inschrijving->save();

		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [], 'inschrijven' );
		$this->assertArrayHasKey( 'redirect_uri', $result, 'geen ideal verwijzing na herhaald inschrijven' );
	}

	/**
	 * Test functie inschrijven na indelen.
	 */
	public function test_inschrijven_na_indeling() {
		$inschrijving = $this->maak_inschrijving( false );
		$inschrijving->actie->aanvraag( 'ideal', 1, [], '' );
		$inschrijving->save();
		$order = new Order( $inschrijving->get_referentie() );
		$inschrijving->betaling->verwerk( $order, 25, true, 'ideal' );
		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [], 'inschrijven' );
		$this->assertStringContainsString( 'Volgens onze administratie ben je al ingedeeld', $result['status'], 'geen ideal verwijzing na inschrijven' );
	}

	/**
	 * Test functie stop_wachten.
	 */
	public function test_stop_wachten() {
		$inschrijving = $this->maak_inschrijving( true );
		$inschrijving->actie->aanvraag( 'ideal', 1, [], '' );
		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [], 'stop_wachten' );
		$this->assertStringContainsString( 'De inschrijving is verwijderd uit de wachtlijst', $result['status'], 'geen bevestigin stop wachten' );
		/**
		 * Controleer ook de inschrijving zelf.
		 */
		$inschrijving2 = new Inschrijving( $inschrijving->cursus->id, $inschrijving->klant_id );
		$this->assertTrue( $inschrijving2->geannuleerd, 'stop wachten incorrect' );
	}

	/**
	 * Test stoppen na wachten en opnieuw indelen. Stoppen mag dan niet meer.
	 */
	public function test_stoppen_na_wachten_ingedeeld() {
		$inschrijving = $this->maak_inschrijving( true );
		$inschrijving->actie->aanvraag( 'ideal', 1, [], '' );
		$inschrijving->ingedeeld   = true;
		$inschrijving->geannuleerd = false;
		$inschrijving->save();
		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [], 'stop_wachten' );
		$this->assertTrue( str_contains( $result['status'], 'Volgens onze administratie ben je al ingedeeld op deze cursus' ), 'na ingedeeld toch bevestiging stop wachten' );
	}

	/**
	 * Test functie indelen na wachten.
	 */
	public function test_indelen_na_wachten() {
		$inschrijving = $this->maak_inschrijving( true );
		$inschrijving->actie->aanvraag( 'ideal', 1, [], '' );
		$inschrijving->cursus->maximum += 1;
		$inschrijving->cursus->save();
		Inschrijvingen::doe_dagelijks();
		/**
		 * Na inschrijving en als er ruimte is ontstaan in de cursus, dan kan er ingedeeld worden.
		 */
		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [], 'indelen_na_wachten' );
		$this->assertArrayHasKey( 'redirect_uri', $result, 'geen ideal verwijzing na wachten' );
	}

	/**
	 * Test functioe indelen na wachten herhaald
	 */
	public function test_indelen_na_wachten_herhaald() {
		$inschrijving = $this->maak_inschrijving( true );
		$inschrijving->actie->aanvraag( 'ideal', 1, [], '' );
		$inschrijving->cursus->maximum += 1;
		$inschrijving->cursus->save();
		Inschrijvingen::doe_dagelijks();
		/**
		 * Na betaling moet er ingedeeld worden. Dan kan er niet opnieuw indelen na wachten uitgevoerd worden.
		 */
		$order = new Order( $inschrijving->get_referentie() );
		$inschrijving->betaling->verwerk( $order, 25, true, 'ideal' );
		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [], 'indelen_na_wachten' );
		$this->assertStringContainsString( 'Volgens onze administratie ben je al ingedeeld', $result['status'], 'geen ideal verwijzing na inschrijven' );
	}

}
