<?php
/**
 * Class Kleistad Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Kleistad
 */

namespace Kleistad;

/**
 * Kleistad test case.
 */
class Test_Kleistad extends Kleistad_UnitTestCase {

	/**
	 * Test dagdelen functie.
	 */
	public function test_dagdelen() {
		$vandaag  = strtotime( 'today' );
		$dagdelen = bepaal_dagdelen( strtotime( '9:00', $vandaag ), strtotime( '11:00', $vandaag ) );
		$this->assertEquals( [ OCHTEND ], $dagdelen, 'alleen ochtend is fout' );

		$dagdelen = bepaal_dagdelen( strtotime( '9:00', $vandaag ), strtotime( '14:00', $vandaag ) );
		$this->assertEquals( [ OCHTEND, MIDDAG ], $dagdelen, 'ochtend en middag is fout' );

		$dagdelen = bepaal_dagdelen( strtotime( '9:00', $vandaag ), strtotime( '17:00', $vandaag ) );
		$this->assertEquals( [ OCHTEND, MIDDAG, NAMIDDAG ], $dagdelen, 'ochtend, middag en namiddag is fout' );

		$dagdelen = bepaal_dagdelen( strtotime( '13:00', $vandaag ), strtotime( '15:00', $vandaag ) );
		$this->assertEquals( [ MIDDAG ], $dagdelen, 'middag is fout' );

		$dagdelen = bepaal_dagdelen( strtotime( '17:00', $vandaag ), strtotime( '19:00', $vandaag ) );
		$this->assertEquals( [ NAMIDDAG ], $dagdelen, 'namiddag is fout' );

		$dagdelen = bepaal_dagdelen( strtotime( '19:45', $vandaag ), strtotime( '22:00', $vandaag ) );
		$this->assertEquals( [ AVOND ], $dagdelen, 'avond is fout' );
	}
}
