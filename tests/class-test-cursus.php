<?php
/**
 * Class CursusTest
 *
 * @package Kleistad
 */

namespace Kleistad;

/**
 * Cursus test case.
 */
class Test_Cursus extends Kleistad_UnitTestCase {

	/**
	 * Test creation and modification of a cursus.
	 */
	public function test_cursus() {
		$cursus1                  = new Cursus();
		$cursus1->naam            = 'test cursus';
		$cursus1->start_datum     = strtotime( 'today' );
		$cursus1->eind_datum      = strtotime( '+ 1 month' );
		$cursus1->lesdatums       = [ $cursus1->start_datum, strtotime( 'tomorrow' ), $cursus1->eind_datum ];
		$cursus1->start_tijd      = strtotime( '12:00' );
		$cursus1->eind_tijd       = strtotime( '14:00' );
		$cursus1->docent          = 'De docent';
		$cursus1->technieken      = [ 'draaien' ];
		$cursus1->techniekkeuze   = true;
		$cursus1->inschrijfkosten = 25;
		$cursus1->cursuskosten    = 100;
		$cursus1->maximum         = 10;
		$cursus1->meer            = true;
		$cursus1->tonen           = true;

		$cursus_id = $cursus1->save();
		$this->assertTrue( $cursus_id > 0, 'save cursus no id' );

		$cursus2 = new Cursus( $cursus_id );
		$this->assertEquals( $cursus1->naam, $cursus2->naam, 'naam cursus not equal' );
		$this->assertEqualSets( $cursus1->technieken, $cursus2->technieken );
		$this->assertEquals( $cursus1->start_datum, $cursus2->start_datum, 'start datum not equal' );
		$this->assertEquals( $cursus1->eind_datum, $cursus2->eind_datum, 'eind datum not equal' );
		$this->assertEquals( $cursus1->lesdatums, $cursus2->lesdatums, 'lesdatums not equal' );
		$this->assertEquals( $cursus1->start_tijd, $cursus2->start_tijd, 'start tijd not equal' );
		$this->assertEquals( $cursus1->eind_tijd, $cursus2->eind_tijd, 'eind tijd not equal' );
		$this->assertEquals( $cursus1->docent, $cursus2->docent, 'docent naam tijd not equal' );
		$this->assertEquals( $cursus1->techniekkeuze, $cursus2->techniekkeuze, 'techniek keuze not equal' );
		$this->assertEquals( $cursus1->inschrijfkosten, $cursus2->inschrijfkosten, 'inschrijfkosten not equal' );
		$this->assertEquals( $cursus1->cursuskosten, $cursus2->cursuskosten, 'cursuskosten not equal' );
		$this->assertEquals( $cursus1->maximum, $cursus2->maximum, 'maximum not equal' );
		$this->assertEquals( $cursus1->meer, $cursus2->meer, 'meer not equal' );
		$this->assertEquals( $cursus1->tonen, $cursus2->tonen, 'tonen not equal' );

	}

	/**
	 * Test ruimte function.
	 */
	public function test_ruimte() {
		$cursus                  = new Cursus();
		$cursus->maximum         = 5;
		$cursus_id               = $cursus->save();
		$cursist_id              = $this->factory->user->create();
		$inschrijving            = new Inschrijving( $cursus_id, $cursist_id );
		$inschrijving->ingedeeld = true;
		$inschrijving->save();
		$this->assertEquals( 4, $cursus->ruimte(), 'ruimte incorrect na 1 indeling' );
		$inschrijving->aantal = 3;
		$inschrijving->save();
		$this->assertEquals( 2, $cursus->ruimte(), 'ruimte incorrect na 3 indeling' );
		$inschrijving->geannuleerd = true;
		$inschrijving->save();
		$this->assertEquals( 5, $cursus->ruimte(), 'ruimte incorrect na annulering' );
	}

	/**
	 * Test erase function.
	 */
	public function test_erase() {
		$cursus1         = new Cursus();
		$cursus1->docent = 'Test';
		$cursus_id       = $cursus1->save();
		$cursus1->erase();
		$cursus2 = new Cursus( $cursus_id );
		$this->assertNotEquals( 'Test', $cursus2->docent, 'erase incorrect' );
	}

	/**
	 * Test is_binnenkort function
	 */
	public function test_is_binnenkort() {
		$cursus = new Cursus();

		$cursus->start_datum = strtotime( 'tomorrow' );
		$this->assertTrue( $cursus->is_binnenkort(), 'is_binnenkort morgen incorrect' );

		$cursus->start_datum = strtotime( '+ 1 month' );
		$this->assertFalse( $cursus->is_binnenkort(), 'is_binnenkort komende maand incorrect' );

		$cursus->start_datum = strtotime( '- 1 month' );
		$this->assertTrue( $cursus->is_binnenkort(), 'is_binnenkort vorige maand incorrect' );
	}

	/**
	 * Test is_wachtbaar function
	 */
	public function test_is_wachtbaar() {
		$cursus = new Cursus();

		$cursus->start_datum = strtotime( '+ 1 month' );
		$this->assertTrue( $cursus->is_wachtbaar(), 'is_wachtbaar toekomst incorrect' );

		$cursus->start_datum = strtotime( 'yesterday' );
		$this->assertFalse( $cursus->is_wachtbaar(), 'is_wachtbaar verleden incorrect' );
	}

	/**
	 * Test is_lopend function
	 */
	public function test_is_lopend() {
		$cursus = new Cursus();

		$cursus->start_datum = strtotime( '- 1 month' );
		$this->assertTrue( $cursus->is_lopend(), 'is_lopend verleden incorrect' );

		$cursus->start_datum = strtotime( 'tomorrow' );
		$this->assertFalse( $cursus->is_lopend(), 'is_lopend toekomst incorrect' );

	}

	/**
	 * Test bedrag function
	 */
	public function test_bedrag() {
		$cursus                  = new Cursus();
		$cursus->inschrijfkosten = 25.0;
		$cursus->cursuskosten    = 100.0;

		$cursus->start_datum = strtotime( '+ 1 month' );
		$this->assertEquals( 25.0, $cursus->bedrag(), 'bedrag cursus toekomst is incorrect' );

		$cursus->start_datum = strtotime( 'tomorrow' );
		$this->assertEquals( 125.0, $cursus->bedrag(), 'bedrag cursus morgen is incorrect' );

		$workshop                  = new Cursus();
		$workshop->inschrijfkosten = 0.0;
		$workshop->cursuskosten    = 35.0;
		$workshop->start_datum     = strtotime( '+ 1 month' );
		$this->assertEquals( 35.0, $workshop->bedrag(), 'bedrag workshop toekomst is incorrect' );

		$workshop->start_datum = strtotime( 'tomorrow' );
		$this->assertEquals( 35.0, $workshop->bedrag(), 'bedrag workshop morgen is incorrect' );

	}

	/**
	 * Test docent_naam function
	 */
	public function test_docent_naam() {
		$cursus      = new Cursus();
		$docent_id   = $this->factory->user->create();
		$docent_naam = get_user_by( 'ID', $docent_id )->display_name;

		$cursus->docent = $docent_id;
		$this->assertEquals( $docent_naam, $cursus->docent_naam(), 'numeriek docent is incorrect' );

		$cursus->docent = 'Test';
		$this->assertEquals( 'Test', $cursus->docent_naam(), 'alphanumeriek docent is incorrect' );
	}

	/**
	 * Test lopend function
	 */
	public function test_lopend() {
		$cursus              = new Cursus();
		$cursus->start_datum = strtotime( '-1 week' );
		$cursus->eind_datum  = strtotime( '+5 week' );
		$lesdatum            = $cursus->start_datum;
		$lesdatums           = [];
		while ( $lesdatum <= $cursus->eind_datum ) {
			$lesdatums[] = $lesdatum;
			$lesdatum    = strtotime( '+1 week', $lesdatum );
		}
		$cursus->lesdatums       = $lesdatums;
		$cursus->inschrijfkosten = 20;
		$cursus->cursuskosten    = 120;

		$this->assertEquals(
			[
				'lessen'      => 7,
				'lessen_rest' => 6,
				'kosten'      => 120.0,
			],
			$cursus->lopend( strtotime( 'today' ) ),
			'lopend is incorrect'
		);
	}

	/**
	 * Test creation and modification of multiple cursussen.
	 */
	public function test_cursussen() {
		$teststring = 'test cursussen';
		$cursussen  = [];
		for ( $i = 0; $i < 10; $i ++ ) {
			$cursussen[ $i ]         = new Cursus();
			$cursussen[ $i ]->naam   = "$teststring$i";
			$cursussen[ $i ]->docent = $i;
			$cursussen[ $i ]->save();
		}

		$cursussen = new Cursussen();
		foreach ( $cursussen as $cursus ) {
			if ( substr( $cursus->naam, 0, strlen( $teststring ) ) === $teststring ) {
				$this->assertEquals( $teststring . $cursus->docent, $cursus->naam, 'naam cursussen not equal' );
			}
		}

	}
}
