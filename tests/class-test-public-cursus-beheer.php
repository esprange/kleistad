<?php
/**
 * Class Public Cursus Beheer Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Cursus_Beheer
 */

namespace Kleistad;

/**
 * Cursus Beheer test case.
 */
class Test_Public_Cursus_Beheer extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'cursus_beheer';

	/**
	 * Test de prepare functie.
	 */
	public function test_prepare() {
		$cursus = new Cursus();
		$cursus->save();

		$data = [ 'actie' => '-' ];
		$this->public_actie( self::SHORTCODE, 'display', $data );
		$this->assertTrue( isset( $data['cursussen'] ), 'prepare default data incorrect' );

		$data = [ 'actie' => 'toevoegen' ];
		$this->public_actie( self::SHORTCODE, 'display', $data );
		$this->assertTrue( isset( $data['cursus'] ), 'prepare toevoegen data incorrect' );

		$data = [
			'actie' => 'wijzigen',
			'id'    => $cursus->id,
		];
		$this->public_actie( self::SHORTCODE, 'display', $data );
		$this->assertTrue( isset( $data['cursus'] ), 'prepare toevoegen data incorrect' );
	}

	/**
	 * Test validate functie.
	 */
	public function test_validate() {
		$data        = [];
		$start_datum = date( 'd-m-Y', strtotime( '+1 week' ) );
		$eind_datum  = date( 'd-m-Y', strtotime( '+2 week' ) );
		wp_insert_post(
			[
				'post_title' => 'test_page',
				'post_type'  => Email::POST_TYPE,
			]
		);
		$_POST = [
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

		$data['form_actie'] = 'verwijderen';
		$this->assertTrue( $this->public_actie( self::SHORTCODE, 'validate', $data ), 'validate bij verwijderen incorrect' );

		$data['form_actie'] = 'test';
		$this->assertTrue( $this->public_actie( self::SHORTCODE, 'validate', $data ), 'validate bij invoer incorrect' );
	}

	/**
	 * Test functie verwijderen.
	 */
	public function test_verwijderen() {
		$cursus = new Cursus();
		$cursus->save();

		/**
		 * Verwijder cursus zonder inschrijvingen.
		 */
		$data   = [
			'input' => [
				'cursus_id' => $cursus->id,
			],
		];
		$result = $this->public_actie( self::SHORTCODE, 'verwijderen', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'De cursus informatie is verwijderd' ), 'verwijder zonder inschrijvng incorrect' );

		$cursus = new Cursus();
		$cursus->save();
		$cursist_id              = $this->factory->user->create();
		$inschrijving            = new Inschrijving( $cursus->id, $cursist_id );
		$inschrijving->ingedeeld = true;
		$inschrijving->save();
		/**
		 * Verwijder cursus met inschrijvingen.
		 */
		$data   = [
			'input' => [
				'cursus_id' => $cursus->id,
			],
		];
		$result = $this->public_actie( self::SHORTCODE, 'verwijderen', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'Er zijn al cursisten inschrijvingen' ), 'verwijder met verwijzing incorrect' );
	}

	/**
	 * Test functie bewaren.
	 */
	public function test_bewaren() {
		$cursus = new Cursus();
		$cursus->save();
		$start_datum = date( 'd-m-Y', strtotime( '+1 week' ) );
		$eind_datum  = date( 'd-m-Y', strtotime( '+2 week' ) );
		$data        = [
			'input' => [
				'cursus_id'       => $cursus->id,
				'naam'            => 'bewaren',
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
			],
		];
		$result      = $this->public_actie( self::SHORTCODE, 'bewaren', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'De cursus informatie is opgeslagen' ), 'bewaren incorrect' );
	}
}
