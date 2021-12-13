<?php
/**
 * Class Public Abonnee Inschrijving Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Abonnee_Inschrijving
 * @noinspection PhpUndefinedFieldInspection, PhpUnhandledExceptionInspection
 */

namespace Kleistad;

/**
 * Abonnee inschrijving test case.
 */
class Test_Public_Abonnee_Inschrijving extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'abonnee_inschrijving';

	/**
	 * Formulier data.
	 *
	 * @var array $input De ingevoerde data.
	 */
	private array $input;

	/**
	 * Maak een cursist inschrijving aan.
	 *
	 * @param bool $beperkt Of het een beperkt abonnement betreft.
	 * @return Abonnement Het abonnement.
	 */
	private function maak_inschrijving( bool $beperkt ) : Abonnement {
		$abonnee_id  = $this->factory->user->create();
		$abonnee     = get_user_by( 'ID', $abonnee_id );
		$this->input = [
			'user_email'       => $abonnee->user_email,
			'email_controle'   => $abonnee->user_email,
			'first_name'       => $abonnee->first_name,
			'last_name'        => $abonnee->last_name,
			'straat'           => 'straat',
			'huisnr'           => '12345',
			'pcode'            => '1234AB',
			'plaats'           => 'plaats',
			'telnr'            => '0123456789',
			'abonnement_keuze' => $beperkt ? 'beperkt' : 'onbeperkt',
			'extras'           => [],
			'dag'              => 'maandag',
			'start_datum'      => date( 'd-m-Y', strtotime( '+ 1 month' ) ),
			'gebruiker_id'     => $abonnee_id,
			'opmerking'        => '',
			'betaal'           => 'ideal',
			'mc4wp-subscribe'  => null,
		];

		/**
		 * Schrijf nu de abonnee in.
		 */
		return new Abonnement( $abonnee_id );
	}

	/**
	 * Test prepare functie;
	 */
	public function test_prepare() {
		$result = $this->public_display_actie( self::SHORTCODE, [ 'verklaring' => 'test' ] );
		$this->assertStringContainsString( 'test', $result, 'prepare verklaring incorrect' );
	}

	/**
	 * Test functie process.
	 */
	public function test_process() {
		$this->maak_inschrijving( true );
		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [] );
		$this->assertArrayHasKey( 'redirect_uri', $result, 'geen ideal verwijzing na inschrijven' );
	}

	/**
	 * Test functie process na eerste facturatie.
	 */
	public function test_process_na_start() {
		$abonnement               = $this->maak_inschrijving( false );
		$_POST                    = $this->input;
		$abonnement->artikel_type = 'start'; // Dit is nodig omdat de order referentie normaliter via Mollie terugkomt.
		$order                    = new Order( $abonnement->geef_referentie() );
		$abonnement->betaling->verwerk( $order, 25, true, 'ideal' ); // Bedrag klopt niet maar dat maar nu niet uit.

		$result = $this->public_form_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( 'Het is niet mogelijk om een bestaand abonnement', $result['status'], 'geen ideal verwijzing na inschrijven' );
	}

}
