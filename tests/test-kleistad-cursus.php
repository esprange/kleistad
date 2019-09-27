<?php
/**
 * Class CursusTest
 *
 * @package Kleistad
 */

/**
 * Cursus test case.
 */
class KleistadCursusTest extends WP_UnitTestCase {

	/**
	 * Activate the plugin which includes the kleistad specific tables if not present.
	 */
	public function setUp() {
		parent::setUp();
		activate_kleistad();
	}
	/**
	 * Test creation and modification of a cursus.
	 */
	public function test_cursus() {
		$cursus1       = new  \Kleistad\Cursus();
		$cursus1->naam = 'test cursus';
		$cursus_id     = $cursus1->save();
		$this->assertTrue( $cursus_id > 0, 'save cursus no id' );

		$cursus2 = new  \Kleistad\Cursus( $cursus_id );
		$this->assertEquals( 'test cursus', $cursus2->naam, 'naam cursus not equal' );

		$cursus2->technieken = [ 'techniek1', 'techniek2' ];
		$this->assertEquals( [ 'techniek1', 'techniek2' ], $cursus2->technieken, 'technieken cursus not equal' );

	}

	/**
	 * Test creation and modification of multiple cursussen.
	 */
	public function test_cursussen() {

		$teststring = 'test cursussen';
		$cursussen  = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$cursussen[ $i ]         = new  \Kleistad\Cursus();
			$cursussen[ $i ]->naam   = "$teststring$i";
			$cursussen[ $i ]->docent = $i;
			$cursussen[ $i ]->save();
		}

		$cursussen =  \Kleistad\Cursus::all();
		foreach ( $cursussen as $cursus ) {
			if ( substr( $cursus->naam, 0, strlen( $teststring ) ) === $teststring ) {
				$this->assertEquals( $teststring . $cursus->docent, $cursus->naam, 'naam cursussen not equal' );
			}
		}

	}

	/**
	 * Test creation and modification of an inschrijving.
	 */
	public function test_inschrijving() {
		$cursist_id          = $this->factory->user->create();
		$cursus              = new  \Kleistad\Cursus();
		$cursus->start_datum = strtotime( 'now' );
		$cursus_id           = $cursus->save();

		$inschrijving1            = new \Kleistad\Inschrijving( $cursist_id, $cursus_id );
		$inschrijving1->opmerking = 'test inschrijving';
		$inschrijving1->save();

		$inschrijving2 = new \Kleistad\Inschrijving( $cursist_id, $cursus_id );
		$this->assertEquals( 'test inschrijving', $inschrijving2->opmerking, 'opmerking inschrijving not equal' );

		$inschrijving2->technieken = [ 'techniek1', 'techniek2' ];
		$this->assertEquals( [ 'techniek1', 'techniek2' ], $inschrijving2->technieken, 'technieken inschrijving not equal' );

	}

	/**
	 * Test creation and modification of multiple inschrijvingen.
	 */
	public function test_inschrijvingen() {
		$cursist_ids = $this->factory->user->create_many( 10 );
		$cursus      = new  \Kleistad\Cursus();
		$cursus->save();

		$teststring     = 'test inschrijvingen';
		$inschrijvingen = [];
		for ( $i = 0; $i < 3; $i++ ) {
			$inschrijvingen[ $i ]            = new \Kleistad\Inschrijving( $cursist_ids[ $i ], $cursus->id );
			$inschrijvingen[ $i ]->opmerking = "$teststring{$cursist_ids[$i]}";
			$inschrijvingen[ $i ]->save();
		}

		$inschrijvingen = \Kleistad\Inschrijving::all();
		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			foreach ( $cursist_inschrijvingen as $cursus_id => $cursist_inschrijving ) {
				if ( substr( $cursist_inschrijving->opmerking, 0, strlen( $teststring ) ) === $teststring ) {
					$this->assertEquals( $teststring . $cursist_id, $cursist_inschrijving->opmerking, 'opmerking inschrijvingen not equal' );
				}
			}
		}

	}

}
