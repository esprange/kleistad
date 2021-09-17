<?php
/**
 * Class Public Cursus Overzicht Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Cursus_Overzicht
 */

namespace Kleistad;

/**
 * Cursus Beheer test case.
 */
class Test_Public_Cursus_Overzicht extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'cursus_overzicht';

	/**
	 * Test de prepare functie.
	 */
	public function test_prepare() {
		$cursus = new Cursus();
		$cursus->save();
		$cursist_id   = $this->factory->user->create();
		$inschrijving = new Inschrijving( $cursus->id, $cursist_id );
		$inschrijving->save();

		$data['actie'] = '';
		$this->assertTrue( $this->public_actie( self::SHORTCODE, 'prepare', $data ), 'prepare default incorrect' );
		$this->assertTrue( 0 < count( $data['cursus_info'] ), 'prepare default data incorrect' );

		$data['id']    = $cursus->id;
		$data['actie'] = 'cursisten';
		$this->assertTrue( $this->public_actie( self::SHORTCODE, 'prepare', $data ), 'prepare toevoegen incorrect' );
		$this->assertTrue( isset( $data['cursisten'] ), 'prepare tonen cursisten data incorrect' );

		$data['id']    = "C$cursus->id-$cursist_id";
		$data['actie'] = 'indelen';
		$this->assertTrue( $this->public_actie( self::SHORTCODE, 'prepare', $data ), 'prepare toevoegen incorrect' );
		$this->assertTrue( isset( $data['cursist'] ), 'prepare indelen cursist data incorrect' );

		$data['id']    = "C$cursus->id-$cursist_id";
		$data['actie'] = 'uitschrijven';
		$this->assertTrue( $this->public_actie( self::SHORTCODE, 'prepare', $data ), 'prepare toevoegen incorrect' );
		$this->assertTrue( isset( $data['cursist'] ), 'prepare uitschrijven cursist data incorrect' );
	}

	/**
	 * Test validate functie.
	 */
	public function test_validate() {
		$data                = [];
		$cursus              = new Cursus();
		$cursus->start_datum = strtotime( '-1 week' );
		$cursus->eind_datum  = strtotime( '+2 week' );
		$cursus->save();
		$cursist_id   = $this->factory->user->create();
		$inschrijving = new Inschrijving( $cursus->id, $cursist_id );
		$inschrijving->save();

		$_POST = [
			'cursist_id' => $cursist_id,
			'cursus_id'  => $cursus->id,
			'kosten'     => 35.0,
		];
		$this->assertTrue( $this->public_actie( self::SHORTCODE, 'validate', $data ), 'validate incorrect' );
	}

	/**
	 * Test functie cursisten.
	 */
	public function test_cursisten() {
		$cursus              = new Cursus();
		$cursus->start_datum = strtotime( '-1 week' );
		$cursus->eind_datum  = strtotime( '+2 week' );
		$cursus->save();

		$cursist_id   = $this->factory->user->create();
		$inschrijving = new Inschrijving( $cursus->id, $cursist_id );
		$inschrijving->save();

		$_GET       = [ 'cursus_id' => $cursus->id ];
		$filehandle = fopen( 'php://memory', 'wb' );
		$data       = [ 'filehandle' => $filehandle ];
		$result     = $this->public_actie( self::SHORTCODE, 'cursisten', $data );
		$size       = ftell( $filehandle );
		fclose( $filehandle );
		$this->assertTrue( 0 < $size, 'Er is geen bestand aangemaakt' );
	}

}
