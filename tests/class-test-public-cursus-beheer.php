<?php
/**
 * Class Public Cursus Beheer Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Cursus_Beheer
 * @noinspection PhpUndefinedFieldInspection, PhpUnhandledExceptionInspection
 */

namespace Kleistad;

/**
 * Cursus Beheer test case.
 */
class Test_Public_Cursus_Beheer extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'cursus_beheer';

	/**
	 * De input voor een cursus
	 *
	 * @var array De input
	 */
	private array $input;

	/**
	 * Maak een cursus met input aan
	 *
	 * @return Cursus
	 */
	private function maak_cursus() : Cursus {
		wp_insert_post(
			[
				'post_title' => 'test_page',
				'post_type'  => Email::POST_TYPE,
			]
		);
		$start_datum = date( 'd-m-Y', strtotime( '+1 week' ) );
		$eind_datum  = date( 'd-m-Y', strtotime( '+2 week' ) );
		$this->input = [
			'cursus_id'       => 0,
			'naam'            => 'toevoeging',
			'docent'          => 'test docent',
			'start_datum'     => $start_datum,
			'eind_datum'      => $eind_datum,
			'lesdatums'       => "$start_datum;$eind_datum",
			'start_tijd'      => '10:00',
			'eind_tijd'       => '13:00',
			'techniekkeuze'   => '',
			'vervallen'       => '0',
			'inschrijfkosten' => 20.0,
			'cursuskosten'    => 120.0,
			'inschrijfslug'   => 'test_page',
			'indelingslug'    => 'test_page',
			'technieken'      => '',
			'maximum'         => 10,
			'meer'            => '',
			'tonen'           => '',
		];
		return new Cursus();
	}

	/**
	 * Test de prepare overzicht functie.
	 */
	public function test_prepare_overzicht() {
		$cursus       = $this->maak_cursus();
		$cursus->naam = 'Testcursus';
		$cursus->save();
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( 'Testcursus', $result, 'prepare default data incorrect' );
	}

	/**
	 * Test de prepare toevoegen functie.
	 */
	public function test_prepare_toevoegen() {
		$result = $this->public_display_actie( self::SHORTCODE, [], 'toevoegen' );
		$this->assertStringContainsString( 'Publiceer de cursus', $result, 'prepare toevoegen data incorrect' );
	}

	/**
	 * Test de prepare wijzigen functie.
	 */
	public function test_prepare_wijzigen() {
		$cursus       = $this->maak_cursus();
		$cursus->naam = 'Testcursus';
		$cursus->save();

		$result = $this->public_display_actie( self::SHORTCODE, [ 'id' => $cursus->id ], 'wijzigen' );
		$this->assertStringContainsString( 'Testcursus', $result, 'prepare wijzigen cursus incorrect' );
	}

	/**
	 * Test functie verwijderen.
	 */
	public function test_verwijderen() {
		$cursus = new Cursus();
		$cursus->save();
		$_POST  = [ 'id' => $cursus->id ];
		$result = $this->public_form_actie( self::SHORTCODE, [], 'verwijderen' );
		$this->assertStringContainsString( 'De cursus informatie is verwijderd', $result['status'], 'verwijder zonder inschrijvng incorrect' );
	}

	/**
	 * Test functie verwijderen met inschrijving.
	 */
	public function test_verwijderen_met_inschrijving() {
		$cursus = new Cursus();
		$cursus->save();
		$cursist_id              = $this->factory->user->create();
		$inschrijving            = new Inschrijving( $cursus->id, $cursist_id );
		$inschrijving->ingedeeld = true;
		$inschrijving->save();
		/**
		 * Verwijder cursus met inschrijvingen.
		 */
		$_POST  = [ 'id' => $cursus->id ];
		$result = $this->public_form_actie( self::SHORTCODE, [], 'verwijderen' );
		$this->assertStringContainsString( 'Er zijn al cursisten inschrijvingen', $result['status'], 'verwijder met verwijzing incorrect' );
	}

	/**
	 * Test functie bewaren.
	 */
	public function test_bewaren() {
		$cursus = $this->maak_cursus();
		$cursus->save();
		$_POST  = $this->input;
		$result = $this->public_form_actie( self::SHORTCODE, [], 'bewaren' );
		$this->assertStringContainsString( 'De cursus informatie is opgeslagen', $result['status'], 'bewaren incorrect' );
	}
}
