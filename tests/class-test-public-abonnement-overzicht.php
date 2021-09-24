<?php
/**
 * Class Public Abonnement Overzicht Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Abonnement_Overzicht
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
		$abonnement = new Abonnement( $abonnee_id );
		$abonnement->save();

		$data = [ 'actie' => '-' ];
		$this->public_actie( self::SHORTCODE, 'display', $data );
		$this->assertTrue( isset( $data['abonnee_info'] ), 'prepare incorrect' );
		$this->assertTrue( 0 < count( $data['abonnee_info'] ), 'prepare default data incorrect' );
	}

	/**
	 * Test functie abonnementen.
	 */
	public function test_abonnementen() {
		$abonnee_id = $this->factory->user->create();
		$abonnement = new Abonnement( $abonnee_id );
		$abonnement->save();

		$filehandle = fopen( 'php://memory', 'wb' );
		$data       = [ 'filehandle' => $filehandle ];
		$this->public_actie( self::SHORTCODE, 'abonnementen', $data );
		$size = ftell( $filehandle );
		fclose( $filehandle );
		$this->assertTrue( 0 < $size, 'Er is geen bestand aangemaakt' );
	}

}
