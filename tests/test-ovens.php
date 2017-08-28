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

	public function setUp() {
		activate_kleistad();
	}
	/**
	 * A single example test.
	 */
	function test_oven() {
		// Replace this with some actual testing code.
		$oven = new Kleistad_Oven();
		$oven->naam = 'test oven';
		$oven->kosten = 10;
		$oven_id = $oven->save();
		$this->assertTrue( $oven_id > 0 );
	}
}
