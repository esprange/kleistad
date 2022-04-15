<?php
/**
 * Class Public Cursus Overzicht Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Cursus_Overzicht
 * @noinspection PhpUndefinedFieldInspection, PhpUnhandledExceptionInspection, PhpArrayWriteIsNotUsedInspection
 */

namespace Kleistad;

/**
 * Cursus Beheer test case.
 */
class Test_Public_Cursus_Overzicht extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'cursus_overzicht';

	/**
	 * Test de prepare overzicht functie.
	 */
	public function test_prepare() {
		$cursist_id              = $this->factory()->user->create();
		$cursus                  = $this->factory()->cursus->create_and_get();
		$inschrijving            = new Inschrijving( $cursus->id, $cursist_id );
		$inschrijving->ingedeeld = true;
		$inschrijving->save();

		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( $cursus->naam, $result, 'prepare default data incorrect' );
		$this->assertStringContainsString( 'toon cursisten', $result, 'prepare default geen cursisten' );
	}

	/**
	 * Test functie prepare cursisten
	 */
	public function test_prepare_cursisten() {
		$cursist_id              = $this->factory()->user->create();
		$cursus_id               = $this->factory()->cursus->create();
		$inschrijving            = new Inschrijving( $cursus_id, $cursist_id );
		$inschrijving->ingedeeld = true;
		$inschrijving->save();

		$result = $this->public_display_actie( self::SHORTCODE, [ 'id' => $cursus_id ], 'cursisten' );
		$this->assertStringContainsString( get_user_by( 'ID', $cursist_id )->display_name, $result, 'prepare tonen cursisten naam ontbreekt' );
	}

	/**
	 * Test functie prepare indelen
	 */
	public function test_prepare_indelen() {
		$cursist_id   = $this->factory()->user->create();
		$cursus_id    = $this->factory()->cursus->create();
		$inschrijving = new Inschrijving( $cursus_id, $cursist_id );
		$inschrijving->save();

		$result = $this->public_display_actie( self::SHORTCODE, [ 'id' => "C$cursus_id-$cursist_id" ], 'indelen' );
		$this->assertStringContainsString( 'Prijs advies', $result, 'prepare indelen cursist incorrect' );
		$this->assertStringContainsString( get_user_by( 'ID', $cursist_id )->display_name, $result, 'prepare indelen cursist naam ontbreekt' );
	}

	/**
	 * Test function prepare uitschrijven
	 */
	public function test_prepare_uitschrijven() {
		$cursist_id   = $this->factory()->user->create();
		$cursus_id    = $this->factory()->cursus->create();
		$inschrijving = new Inschrijving( $cursus_id, $cursist_id );
		$inschrijving->save();

		$result = $this->public_display_actie( self::SHORTCODE, [ 'id' => "C$cursus_id-$cursist_id" ], 'uitschrijven' );
		$this->assertStringContainsString( 'Verwijderen uit cursus wachtlijst', $result, 'prepare uitschrijven cursist incorrect' );
		$this->assertStringContainsString( get_user_by( 'ID', $cursist_id )->display_name, $result, 'prepare uitschrijven cursist naam ontbreekt' );
	}

	/**
	 * Test indelen functie.
	 */
	public function test_indelen() {
		$cursus_id    = $this->factory()->cursus->create(
			[
				'start_datum' => strtotime( '-1 week' ),
				'eind_datum'  => strtotime( '+2 week' ),
			]
		);
		$cursist_id   = $this->factory()->user->create();
		$inschrijving = new Inschrijving( $cursus_id, $cursist_id );
		$inschrijving->save();

		$_POST  = [
			'cursist_id' => $cursist_id,
			'cursus_id'  => $cursus_id,
			'kosten'     => 35.0,
		];
		$result = $this->public_form_actie( self::SHORTCODE, [], 'indelen' );
		$this->assertStringContainsString( 'De order is aangemaakt en een email met factuur is naar de cursist verstuurd', $result['status'], 'indelen incorrect' );
	}

	/**
	 * Test functie cursisten.
	 */
	public function test_cursisten() {
		$cursus_id    = $this->factory()->cursus->create(
			[
				'start_datum' => strtotime( '-1 week' ),
				'eind_datum'  => strtotime( '+2 week' ),
			]
		);
		$cursist_id   = $this->factory()->user->create();
		$inschrijving = new Inschrijving( $cursus_id, $cursist_id );
		$inschrijving->save();

		$_GET       = [ 'cursus_id' => $cursus_id ];
		$filehandle = fopen( 'php://memory', 'wb' );
		$this->public_download_actie( self::SHORTCODE, [], 'cursisten', $filehandle );
		$size = ftell( $filehandle );
		fclose( $filehandle );
		$this->assertGreaterThan( 0, $size, 'Er is geen bestand aangemaakt' );
	}

}
