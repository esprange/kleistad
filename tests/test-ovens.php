<?php
/**
 * Class SampleTest
 *
 * @package Kleistad
 */

/**
 * Sample test case.
 */
class OvensTest extends WP_UnitTestCase {

	/**
	 * Activate the plugin which includes the kleistad specific tables if not present.
	 */
	public function setUp() {
		activate_kleistad();
	}
	/**
	 * Test creation and modification of an oven.
	 */
	function test_oven() {
		$oven1 = new Kleistad_Oven();
		$oven1->naam = 'test oven';
		$oven1->kosten = 10;
		$oven_id = $oven1->save();
		$this->assertTrue( $oven_id > 0 );

		$oven2 = new Kleistad_Oven( $oven_id );
		$this->assertEquals( 10, $oven2->kosten );
		$this->assertEquals( 'test oven', $oven2->naam );

		$oven2->kosten = 12;
		$this->assertEquals( 12, $oven2->kosten );
	}

	/**
	 * Test creation and modification of multiple ovens.
	 */
	function test_ovens() {
		$ovens = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$ovens[ $i ] = new Kleistad_Oven();
			$ovens[ $i ]->naam = 'test ovens ' . $i;
			$ovens[ $i ]->kosten = $i;
			$ovens[ $i ]->save();
		}

		$ovens_store = new Kleistad_Ovens();
		$ovens_from_store = $ovens_store->get();
		foreach ( $ovens_from_store as $oven ) {
			if ( 'test ovens' == substr( $oven->naam, 0, 10 ) ) {
				$this->assertEquals( 'test ovens ' . intval( $oven->kosten ), $oven->naam );
			}
		}

	}
}
