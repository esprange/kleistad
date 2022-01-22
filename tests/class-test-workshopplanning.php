<?php
/**
 * Class Test Workshopplanning
 *
 * @package Kleistad
 * @noinspection PhpUndefinedFieldInspection
 */

namespace Kleistad;

/**
 * Cursus test case.
 */
class Test_Workshopplanning extends Kleistad_UnitTestCase {

	/**
	 * Maak een docent aan.
	 *
	 * @return Docent
	 */
	private function maak_docent() : Docent {
		$docent_id = $this->factory->user->create();
		$docent    = new Docent( $docent_id );
		$docent->add_role( DOCENT );
		return $docent;
	}

	/**
	 * Test geef beschikbaarheid (functie handle() wordt impliciet getest).
	 */
	public function test_geef_beschikbaarheid() {
		$planning = new Workshopplanning();

		/**
		 * Als er nog geen beschikbaarheid aangegeven is moet dit false zijn
		 */
		$this->assertEquals( 0, count( $planning->geef_beschikbaaarheid() ), 'initiële geef beschikbaarheid fout' );

		$docent1 = $this->maak_docent();
		$lijst   = [
			[
				'datum'   => strtotime( 'tomorrow' ),
				'dagdeel' => DAGDEEL[0],
				'status'  => Docent::BESCHIKBAAR,
			],
			[
				'datum'   => strtotime( 'tomorrow + 1 day' ),
				'dagdeel' => DAGDEEL[1],
				'status'  => Docent::BESCHIKBAAR,
			],
		];
		$docent1->beschikbaarlijst( $lijst );
		delete_transient( Workshopplanning::META_KEY );
		/**
		 * Nu is er wel beschikbaarheid dus twee dagen.
		 */
		$this->assertEquals( 2, count( $planning->geef_beschikbaaarheid() ), 'Na vulling geef beschikbaarheid fout' );

		$aantal_weken = (int) floor( ( strtotime( '+ 3 month' ) - strtotime( 'tomorrow' ) ) / WEEK_IN_SECONDS );

		$docent2 = $this->maak_docent();
		$lijst   = [
			[
				'datum'   => intval( date( 'N', strtotime( 'today' ) ) - 1 ),
				'dagdeel' => DAGDEEL[1],
				'status'  => Docent::STANDAARD,
			],
		];
		$docent2->beschikbaarlijst( $lijst );
		delete_transient( Workshopplanning::META_KEY );
		/**
]		 * Een docent die één dag per week beschikbaar is naast de bestaande docent.
		 */
		$this->assertEquals( 2 + $aantal_weken, count( $planning->geef_beschikbaaarheid() ), 'Na vulling met standaard geef beschikbaarheid fout' );

		$aanvraag = new WorkshopAanvraag();
		$aanvraag->start(
			[
				'contact'    => 'Tester X',
				'naam'       => 'Workshop',
				'user_email' => 'test@test.nl',
				'omvang'     => 'klein',
				'plandatum'  => strtotime( 'tomorrow + 1 day' ),
				'dagdeel'    => array_keys( WorkshopAanvraag::MOMENT )[1],
				'technieken' => [],
				'telnr'      => '01234',
				'vraag'      => 'test',
			]
		);
		delete_transient( Workshopplanning::META_KEY );
		/**
		 * Nu is er een aanvraag dus er moet nu één dag minder beschikbaar zijn.
		 */
		$this->assertEquals( 2 + $aantal_weken - 1, count( $planning->geef_beschikbaaarheid() ), 'Na nieuwe aanvraag geef beschikbaarheid fout' );

		$workshop             = new Workshop();
		$workshop->naam       = 'Test';
		$workshop->datum      = strtotime( 'tomorrow' );
		$workshop->start_tijd = strtotime( '10:00' );
		$workshop->eind_tijd  = strtotime( '12:00' );
		$workshop->contact    = 'tester';
		$workshop->email      = 'tester@test.nl';
		$workshop->save();
		delete_transient( Workshopplanning::META_KEY );
		/**
		 * Nu is er een workshop dus er moet nu weer één dag minder beschikbaar zijn.
		 */
		$this->assertEquals( 2 + $aantal_weken - 1 - 1, count( $planning->geef_beschikbaaarheid() ), 'Na nieuwe workshop geef beschikbaarheid fout' );

		$cursus              = new Cursus();
		$cursus->start_datum = strtotime( '+ 1 week' );
		$cursus->eind_datum  = strtotime( '+ 2 weeks' );
		$cursus->lesdatums   = [ $cursus->start_datum, $cursus->eind_datum ];
		$cursus->start_tijd  = strtotime( '13:00' );
		$cursus->eind_tijd   = strtotime( '16:00' );
		$cursus->save();
		delete_transient( Workshopplanning::META_KEY );
		/**
		 * Nu zijn er ook nog twee lessen, dus er moeten nu weer twee dagen minder beschikbaar zijn.
		 */
		$this->assertEquals( 2 + $aantal_weken - 1 - 1 - 2, count( $planning->geef_beschikbaaarheid() ), 'Na nieuwe cursus geef beschikbaarheid fout' );
	}

}
