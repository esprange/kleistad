<?php
/**
 * Class Public Cursus Inschrijving Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Cursus_Inschrijving
 */

namespace Kleistad;

/**
 * Inschrijving test case.
 */
class Test_Public_Cursus_Inschrijving extends Kleistad_UnitTestCase {

	private const CURSUSNAAM = 'Testcursus';
	private const SHORTCODE  = 'cursus_inschrijving';

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
		$cursist_id              = $this->factory->user->create();
		$cursist                 = get_user_by( 'ID', $cursist_id );
		$cursus                  = new Cursus();
		$cursus->naam            = self::CURSUSNAAM;
		$cursus->start_datum     = strtotime( '+1 month' );
		$cursus->inschrijfkosten = 25.0;
		$cursus->cursuskosten    = 100.0;
		$cursus->maximum         = 3;
		$cursus->save();
		$this->input = [
			'user_email'      => $cursist->user_email,
			'email_controle'  => $cursist->user_email,
			'first_name'      => $cursist->first_name,
			'last_name'       => $cursist->last_name,
			'straat'          => 'straat',
			'huisnr'          => '12345',
			'pcode'           => '1234AB',
			'plaats'          => 'plaats',
			'telnr'           => '0123456789',
			'cursus_id'       => $cursus->id,
			'gebruiker_id'    => $cursist_id,
			'technieken'      => [],
			'aantal'          => 1,
			'opmerking'       => '',
			'betaal'          => 'ideal',
			'mc4wp-subscribe' => null,
		];
		if ( $wachtlijst ) {
			/**
			 * Maak eerst de cursus vol zodat er geen ruimte meer is.
			 */
			$cursist_ids = $this->factory->user->create_many( $cursus->maximum );
			for ( $i = 0; $i < 3; $i ++ ) {
				$inschrijvingen[ $i ] = new Inschrijving( $cursus->id, $cursist_ids[ $i ] );
				$inschrijvingen[ $i ]->actie->aanvraag( 'ideal' );
				$order = new Order( $inschrijvingen[ $i ]->geef_referentie() );
				$inschrijvingen[ $i ]->betaling->verwerk( $order, 25, true, 'ideal' );
			}
		}

		/**
		 * Schrijf nu de cursist in.
		 */
		return new Inschrijving( $cursus->id, $cursist_id );
	}

	/**
	 * Test validate functie.
	 */
	public function test_validate() {
		$this->maak_inschrijving( false );
		$_POST  = $this->input;
		$data   = [];
		$result = $this->public_actie( self::SHORTCODE, 'validate', $data );
		if ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $error ) {
				echo $error . "\n"; // phpcs:ignore
			}
		}
		$this->assertFalse( is_wp_error( $result ), 'validate incorrect' );
	}

	/**
	 * Test functie stop_wachten.
	 */
	public function test_stop_wachten() {
		$inschrijving = $this->maak_inschrijving( true );
		$inschrijving->actie->aanvraag( 'ideal' );
		/**
		 * Na inschrijving kan de cursist zich uitschrijven van de wachtlijst.
		 */
		$data   = [ 'input' => $this->input ];
		$result = $this->public_actie( self::SHORTCODE, 'stop_wachten', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'De inschrijving is verwijderd uit de wachtlijst' ), 'geen bevestigin stop wachten' );
		/**
		 * Controleer ook de inschrijving zelf.
		 */
		$inschrijving2 = new Inschrijving( $inschrijving->cursus->id, $inschrijving->klant_id );
		$this->assertTrue( $inschrijving2->geannuleerd, 'stop wachten incorrect' );
		/**
		 * Deel de cursist nu alsnog in. Stoppen mag dan niet meer.
		 */
		$inschrijving2->ingedeeld   = true;
		$inschrijving2->geannuleerd = false;
		$inschrijving2->save();
		$result = $this->public_actie( self::SHORTCODE, 'stop_wachten', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'Volgens onze administratie ben je al ingedeeld op deze cursus' ), 'na ingedeeld toch bevestiging stop wachten' );
	}

	/**
	 * Test functie indelen na wachten.
	 */
	public function test_indelen_na_wachten() {
		$inschrijving = $this->maak_inschrijving( true );
		$inschrijving->actie->aanvraag( 'ideal' );

		$inschrijving->cursus->maximum += 1;
		$inschrijving->cursus->save();
		Inschrijvingen::doe_dagelijks();
		/**
		 * Na inschrijving en als er ruimte is ontstaan in de cursus, dan kan er ingedeeld worden.
		 */
		$data   = [ 'input' => $this->input ];
		$result = $this->public_actie( self::SHORTCODE, 'indelen_na_wachten', $data );
		$this->assertTrue( isset( $result['redirect_uri'] ), 'geen ideal verwijzing na wachten' );
		/**
		 * Na betaling moet er ingedeeld worden. Dan kan er niet opnieuw indelen na wachten uitgevoerd worden.
		 */
		$order = new Order( $inschrijving->geef_referentie() );
		$inschrijving->betaling->verwerk( $order, 25, true, 'ideal' );
		$result = $this->public_actie( self::SHORTCODE, 'indelen_na_wachten', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'Volgens onze administratie ben je al ingedeeld' ), 'geen ideal verwijzing na inschrijven' );
	}

	/**
	 * Test functie inschrijven.
	 */
	public function test_inschrijven() {
		$inschrijving = $this->maak_inschrijving( false );
		$data         = [ 'input' => $this->input ];
		$result       = $this->public_actie( self::SHORTCODE, 'inschrijven', $data );
		$this->assertTrue( isset( $result['redirect_uri'] ), 'geen ideal verwijzing na inschrijven' );

		/**
		 * Inschrijving moet herhaald kunnen worden.
		 */
		$result = $this->public_actie( self::SHORTCODE, 'inschrijven', $data );
		$this->assertTrue( isset( $result['redirect_uri'] ), 'geen ideal verwijzing na inschrijven' );

		/**
		 * Inschrijving na indeling kan niet.
		 */
		$order = new Order( $inschrijving->geef_referentie() );
		$inschrijving->betaling->verwerk( $order, 25, true, 'ideal' );
		$result = $this->public_actie( self::SHORTCODE, 'inschrijven', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'Volgens onze administratie ben je al ingedeeld' ), 'geen ideal verwijzing na inschrijven' );
	}


}
