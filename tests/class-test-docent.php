<?php
/**
 * Class Test Docent
 *
 * @package Kleistad
 * @noinspection PhpUndefinedFieldInspection
 */

namespace Kleistad;

/**
 * Cursus test case.
 */
class Test_Docent extends Kleistad_UnitTestCase {

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
	 * Test beschikbaarheid.
	 */
	public function test_beschikbaarheid() {
		$docent = $this->maak_docent();

		/**
		 * Als er nog geen beschikbaarheid aangegeven is moet dit false zijn
		 */
		$this->assertEquals( Docent::NIET_BESCHIKBAAR, $docent->beschikbaarheid( strtotime( 'today' ), OCHTEND ), 'initiële beschikbaarheid fout' );

		$lijst = [
			[
				'datum'   => strtotime( 'today' ),
				'dagdeel' => OCHTEND,
				'status'  => Docent::BESCHIKBAAR,
			],
			[
				'datum'   => intval( date( 'N', strtotime( 'tomorrow' ) ) - 1 ),
				'dagdeel' => MIDDAG,
				'status'  => Docent::STANDAARD,
			],
		];
		$docent->beschikbaarlijst( $lijst );

		/**
		 * Nu is er een beschikbaarheid dus true.
		 */
		$this->assertEquals( Docent::BESCHIKBAAR, $docent->beschikbaarheid( strtotime( 'today' ), OCHTEND ), 'beschikbaarheid vandaag fout' );

		/**
		 * Nu is er een beschikbaarheid dus true.
		 */
		$this->assertEquals( Docent::STANDAARD, $docent->beschikbaarheid( strtotime( 'tomorrow' ), MIDDAG ), 'beschikbaarheid morgen fout' );
	}

}
