<?php
/**
 * Class Public Abonnee Wijziging Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Abonnee_Wijziging
 */

namespace Kleistad;

/**
 * Inschrijving test case.
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
		$abonnee_id = $this->factory->user->create();
		wp_set_current_user( $abonnee_id );
		$abonnement = new Abonnement( $abonnee_id );
		$abonnement->actie->starten(
			strtotime( '- 5 month' ),
			'beperkt',
			'dinsdag',
			'',
			'stort'
		);
		$this->input = [
			'abonnee_id'     => $abonnee_id,
			'wijziging'      => $wijziging,
			'dag'            => 'maandag',
			'soort'          => $beperkt ? 'beperkt' : 'onbeperkt',
			'betaal'         => $betaling_per_bank ? 'stort' : 'ideal',
			'pauze_datum'    => date( 'd-m-Y', strtotime( '+ 2 weeks' ) ),
			'herstart_datum' => date( 'd-m-Y', strtotime( '+ 5 weeks' ) ),
			'per_datum'      => date( 'd-m-Y', strtotime( 'first day of next month' ) ),
			'extras'         => [ 'sleutel' ],
		];
	}

	/**
	 * Test prepare functie;
	 */
	public function test_prepare() {
		$this->maak_wijziging( 'test', false, false );
		$data   = [];
		$result = $this->public_actie( self::SHORTCODE, 'prepare', $data );
		if ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $error ) {
				echo $error . "\n"; // phpcs:ignore
			}
		}
		$this->assertFalse( is_wp_error( $result ), 'prepare incorrect' );
	}

	/**
	 * Test validate functie.
	 */
	public function test_validate() {
		$this->maak_wijziging( 'test', false, false );
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
	 * Test functie save.
	 */
	public function test_save() {
		/**
		 * Test eerst de betaalwijze wijziging naar ideal
		 */
		$this->maak_wijziging( 'betaalwijze', false, false );
		$data = [ 'input' => $this->input ];
		foreach ( [ 'herstart_datum', 'pauze_datum', 'per_datum' ] as $datum ) {
			$data['input'][ $datum ] = strtotime( $data['input'][ $datum ] );
		}
		$result = $this->public_actie( self::SHORTCODE, 'save', $data );
		$this->assertTrue( isset( $result['redirect_uri'] ), 'geen ideal verwijzing na wijzigen betaal wijze' );

		/**
		 * Test nu de overige wijzigingen.
		 */
		foreach ( [ 'betaalwijze', 'pauze', 'soort', 'extras', 'dag', 'einde' ] as $wijziging ) {
			$this->maak_wijziging( $wijziging, true, false );
			$data = [ 'input' => $this->input ];
			foreach ( [ 'herstart_datum', 'pauze_datum', 'per_datum' ] as $datum ) {
				$data['input'][ $datum ] = strtotime( $data['input'][ $datum ] );
			}
			$result = $this->public_actie( self::SHORTCODE, 'save', $data );
			$this->assertTrue( false !== strpos( $result['status'], 'De wijziging is verwerkt' ), 'geen succes na wijzigen ' . $wijziging );
		}
	}


}
