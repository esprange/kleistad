<?php
/**
 * Class Public Abonnee Wijziging Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Abonnee_Wijziging
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace Kleistad;

/**
 * Abonnee wijziging test case.
 */
class Test_Public_Abonnee_Wijziging extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'abonnee_wijziging';

	/**
	 * Formulier data.
	 *
	 * @var array $input De ingevoerde data.
	 */
	private array $input;

	/**
	 * Maak een cursist wijziging.
	 *
	 * @param string $wijziging         Het type wijziging.
	 * @param bool   $betaling_per_bank Of er per bank wordt betaald.
	 * @param bool   $beperkt           Of het een beperkt abonnement betreft.
	 */
	private function maak_wijziging( string $wijziging, bool $betaling_per_bank, bool $beperkt ) {
		$abonnee_id = $this->factory()->user->create();
		wp_set_current_user( $abonnee_id );
		$abonnement = new Abonnement( $abonnee_id );
		$abonnement->actie->starten(
			strtotime( '- 5 month' ),
			'beperkt',
			'',
			'stort'
		);
		$this->input = [
			'abonnee_id'     => $abonnee_id,
			'wijziging'      => $wijziging,
			'dag'            => 'maandag',
			'soort'          => $beperkt ? 'beperkt' : 'onbeperkt',
			'betaal'         => $betaling_per_bank ? 'stort' : 'ideal',
			'pauze_datum'    => date( 'd-m-Y', strtotime( '+ 2 weeks 0:00' ) ),
			'herstart_datum' => date( 'd-m-Y', strtotime( '+ 5 weeks 0:00' ) ),
			'per_datum'      => strtotime( 'first day of next month' ),
			'extras'         => [ 'sleutel' ],
		];
	}

	/**
	 * Test prepare functie;
	 */
	public function test_prepare() {
		$this->maak_wijziging( 'test', false, true );
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		if ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $error ) {
				echo $error . "\n"; // phpcs:ignore
			}
		}
		$this->assertNotWPError( $result, 'prepare incorrect' );
	}

	/**
	 * Test functie process betaalwijze.
	 */
	public function test_process_betaalwijze() {
		$this->maak_wijziging( 'betaalwijze', false, false );
		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [], 'betaalwijze' );
		$this->assertArrayHasKey( 'redirect_uri', $result, 'geen ideal verwijzing na wijzigen betaal wijze' );
	}

	/**
	 * Test functie process pauze.
	 */
	public function test_process_pauze() {
		$this->maak_wijziging( 'pauze', true, false );
		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [], 'pauze' );
		$this->assertStringContainsString( 'De wijziging is verwerkt', $result['status'], 'geen succes na wijzigen pauze' );
	}

	/**
	 * Test functie process extras.
	 */
	public function test_process_extras() {
		$this->maak_wijziging( 'extras', true, false );
		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [], 'extras' );
		$this->assertStringContainsString( 'De wijziging is verwerkt', $result['status'], 'geen succes na wijzigen extras' );
	}

	/**
	 * Test functie process einde.
	 */
	public function test_process_einde() {
		$this->maak_wijziging( 'einde', true, false );
		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [], 'einde' );
		$this->assertStringContainsString( 'De wijziging is verwerkt', $result['status'], 'geen succes na wijzigen einde' );
	}

}
