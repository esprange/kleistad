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
	function test_cursus() {
		$cursus1 = new Kleistad_Cursus();
		$cursus1->naam = 'test cursus';
		$cursus_id = $cursus1->save();
		$this->assertTrue( $cursus_id > 0, 'save cursus no id' );

		$cursus2 = new Kleistad_Cursus( $cursus_id );
		$this->assertEquals( 'test cursus', $cursus2->naam, 'naam cursus not equal' );

		$cursus2->technieken = [ 'techniek1', 'techniek2' ];
		$this->assertEquals( [ 'techniek1', 'techniek2' ], $cursus2->technieken, 'technieken cursus not equal' );

	}

	/**
	 * Test creation and modification of multiple cursussen.
	 */
	function test_cursussen() {

		$teststring = 'test cursussen';
		$cursussen = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$cursussen[ $i ] = new Kleistad_Cursus();
			$cursussen[ $i ]->naam = "$teststring$i";
			$cursussen[ $i ]->docent = $i;
			$cursussen[ $i ]->save();
		}

		$cursussen_store = new Kleistad_Cursussen();
		$cursussen_from_store = $cursussen_store->get();
		foreach ( $cursussen_from_store as $cursus ) {
			if ( substr( $cursus->naam, 0, strlen( $teststring ) ) == $teststring ) {
				$this->assertEquals( $teststring . $cursus->docent, $cursus->naam, 'naam cursussen not equal' );
			}
		}

	}

	/**
	 * Test creation and modification of an inschrijving.
	 */
	function test_inschrijving() {
		$cursist_id = $this->factory->user->create();
		$cursus = new Kleistad_Cursus();

		$inschrijving1 = new Kleistad_Inschrijving( $cursist_id, $cursus->id );
		$inschrijving1->opmerking = 'test inschrijving';
		$inschrijving_id = $inschrijving1->save();
		$this->assertTrue( $inschrijving_id > 0, 'save inschrijving no id' );

		$inschrijving2 = new Kleistad_Cursus( $cursist_id, $cursus->id );
		$this->assertEquals( 'test inschrijving', $inschrijving2->naam, 'naam inschrijving not equal' );

		$inschrijving2->technieken = [ 'techniek1', 'techniek2' ];
		$this->assertEquals( [ 'techniek1', 'techniek2' ], $inschrijving2->technieken, 'technieken inschrijving not equal' );

	}

	/**
	 * Test creation and modification of multiple inschrijvingen.
	 */
	function test_inschrijvingen() {
		$cursist_ids = $this->factory->user->create_many( 10 );
		$cursus = new Kleistad_Cursus();

		$teststring = 'test inschrijvingen';
		$inschrijvingen = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$inschrijvingen[ $i ] = new Kleistad_Inschrijving( $cursist_ids[ $i ], $cursus->id );
			$inschrijvingen[ $i ]->opmerking = "$teststring{$cursist_ids[$i]}";
			$inschrijvingen[ $i ]->save();
		}

		$inschrijvingen_store = new Kleistad_Inschrijvingen();
		$inschrijvingen_from_store = $inschrijvingen_store->get();
		foreach ( $inschrijvingen_from_store as $cursist_id => $inschrijving ) {
			if ( substr( $inschrijving->opmerking, 0, strlen( $teststring ) ) == $teststring ) {
				$this->assertEquals( $teststring . $cursist_id, $inschrijving->opmerking, 'naam inschrijvingen not equal' );
			}
		}

	}

}
