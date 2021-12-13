<?php
/**
 * Class Public Abonnement Overzicht Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Abonnement_Overzicht
 * @noinspection PhpUndefinedFieldInspection, PhpUnhandledExceptionInspection
 */

namespace Kleistad;

/**
 * Cursus Beheer test case.
 */
class Test_Public_Abonnement_Overzicht extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'abonnement_overzicht';

	/**
	 * Test de prepare functie.
	 */
	public function test_prepare() {
		$abonnee_id = $this->factory->user->create();
		$abonnee    = new Abonnee( $abonnee_id );
		$abonnee->abonnement->save();

		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( $abonnee->display_name, $result, 'prepare incorrect' );
	}

	/**
	 * Test functie abonnementen.
	 */
	public function test_abonnementen() {
		$abonnee_id = $this->factory->user->create();
		$abonnement = new Abonnement( $abonnee_id );
		$abonnement->save();

		$filehandle = fopen( 'php://memory', 'wb' );
		$this->public_download_actie( self::SHORTCODE, [], 'abonnementen', $filehandle );
		$size = ftell( $filehandle );
		fclose( $filehandle );
		$this->assertGreaterThan( 0, $size, 'Er is geen bestand aangemaakt' );
	}

}
