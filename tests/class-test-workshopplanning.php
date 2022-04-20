<?php
/**
 * Class Test Workshopplanning
 *
 * @package Kleistad
 * @noinspection PhpUndefinedFieldInspection
 */

namespace Kleistad;

use ReflectionObject;

/**
 * Cursus test case.
 */
class Test_Workshopplanning extends Kleistad_UnitTestCase {

	/**
	 * Maak een docent aan.
	 *
	 * @return Docent
	 */
	private function maak_docent(): Docent {
		$docent_id = $this->factory()->user->create();
		$docent    = new Docent( $docent_id );
		$docent->add_role( DOCENT );

		return $docent;
	}

	/**
	 * Geef standaard beschikbaarheid, een dag voor elke week.
	 *
	 * @return array[]
	 */
	private function standaard_beschikbaarheid() : array {
		return [
			[
				'datum'   => intval( date( 'N', strtotime( 'today' ) ) - 1 ),
				'dagdeel' => MIDDAG,
				'status'  => Docent::STANDAARD,
			],
		];
	}

	/**
	 * Geef een beschikbaarheid, 3 stuks.
	 *
	 * @return array[]
	 */
	private function individuele_beschikbaarheid() : array {
		return [
			[
				'datum'   => strtotime( 'tomorrow' ),
				'dagdeel' => OCHTEND,
				'status'  => Docent::BESCHIKBAAR,
			],
			[
				'datum'   => strtotime( 'tomorrow + 1 day' ),
				'dagdeel' => MIDDAG,
				'status'  => Docent::BESCHIKBAAR,
			],
			[
				'datum'   => strtotime( 'tomorrow + 2 day' ),
				'dagdeel' => NAMIDDAG,
				'status'  => Docent::BESCHIKBAAR,
			],
		];
	}

	/**
	 * Maak een planning aan, reset steeds de planning vooraf.
	 *
	 * @return array
	 */
	private function maak_planning() : array {
		$workshopplanning = new Workshopplanning();
		$refobject        = new ReflectionObject( $workshopplanning );
		$refplanning      = $refobject->getProperty( 'planning' );
		$refplanning->setAccessible( true );
		$refplanning->setValue( null );
		return $workshopplanning->get_beschikbaarheid();
	}

	/**
	 * Test geef beschikbaarheid leeg.
	 */
	public function test_geef_beschikbaarheid_leeg() {
		/**
		 * Als er nog geen beschikbaarheid aangegeven is moet dit false zijn
		 */
		$this->assertEquals( 0, count( $this->maak_planning() ), 'initiële geef beschikbaarheid fout' );
	}

	/**
	 * Test geef beschikbaarheid een docent.
	 */
	public function test_geef_beschikbaarheid_een_docent() {
		$docent = $this->maak_docent();
		$docent->set_beschikbaarlijst( $this->individuele_beschikbaarheid() );
		$this->assertEquals( 3, count( $this->maak_planning() ), 'Na vulling geef beschikbaarheid fout' );
	}

	/**
	 * Test geef beschikbaarheid standaard en individu.
	 */
	public function test_geef_beschikbaarheid_twee_docent() {
		$aantal_weken = (int) floor( ( strtotime( '+ 3 month' ) - strtotime( 'tomorrow' ) ) / WEEK_IN_SECONDS );
		$docent1      = $this->maak_docent();
		$docent2      = $this->maak_docent();
		$docent1->set_beschikbaarlijst( $this->individuele_beschikbaarheid() );
		$docent2->set_beschikbaarlijst( $this->standaard_beschikbaarheid() );
		$this->assertGreaterThanOrEqual( 2 + $aantal_weken, count( $this->maak_planning() ), 'Na vulling met standaard geef beschikbaarheid fout' );
	}

	/**
	 * Test geef beschikbaarheid met workshop.
	 */
	public function test_geef_beschikbaarheid_workshop() {
		$aantal_weken         = (int) floor( ( strtotime( '+ 3 month' ) - strtotime( 'tomorrow' ) ) / WEEK_IN_SECONDS );
		$docent               = $this->maak_docent();
		$workshop             = new Workshop();
		$workshop->naam       = 'Test';
		$workshop->datum      = strtotime( 'tomorrow' );
		$workshop->start_tijd = strtotime( '10:00' );
		$workshop->eind_tijd  = strtotime( '12:00' );
		$workshop->contact    = 'tester';
		$workshop->email      = 'tester@test.nl';
		$workshop->save();
		$docent->set_beschikbaarlijst( $this->standaard_beschikbaarheid() );
		$this->assertGreaterThanOrEqual( $aantal_weken - 1, count( $this->maak_planning() ), 'Na nieuwe workshop geef beschikbaarheid fout' );
	}

	/**
	 * Test geef beschikbaarheid als er een cursus is.
	 */
	public function test_geef_beschikbaarheid_cursus() {
		$aantal_weken        = (int) floor( ( strtotime( '+ 3 month' ) - strtotime( 'tomorrow' ) ) / WEEK_IN_SECONDS );
		$docent              = $this->maak_docent();
		$cursus              = new Cursus();
		$cursus->start_datum = strtotime( '+ 1 week' );
		$cursus->eind_datum  = strtotime( '+ 2 weeks' );
		$cursus->lesdatums   = [ $cursus->start_datum, $cursus->eind_datum ];
		$cursus->start_tijd  = strtotime( '13:00' );
		$cursus->eind_tijd   = strtotime( '15:30' );
		$cursus->save();
		$docent->set_beschikbaarlijst( $this->standaard_beschikbaarheid() );
		$this->assertGreaterThanOrEqual( $aantal_weken - 3, count( $this->maak_planning() ), 'Na nieuwe cursus geef beschikbaarheid fout' );
	}

}
