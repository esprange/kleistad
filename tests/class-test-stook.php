<?php
/**
 * Class Stook
 *
 * @package Kleistad
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace Kleistad;

/**
 * Stook test case.
 */
class Test_Stook extends Kleistad_UnitTestCase {

	/**
	 * Test het reserveren van een stook.
	 */
	public function test_stook() {
		$hoofdstoker_id = $this->factory()->user->create();
		$oven_id        = $this->factory()->oven->create();
		wp_set_current_user( $hoofdstoker_id );
		$stook = new Stook( $oven_id, strtotime( 'tomorrow' ) );
		$this->assertEquals( Stook::RESERVEERBAAR, $stook->get_statustekst(), 'status reserveerbaar onjuist' );
		$stook->save();
		$this->assertTrue( $stook->is_gereserveerd(), 'is gereserveerd onjuist' );
	}

	/**
	 * Test meerdere stoken.
	 */
	public function test_stoken() {
		$hoofdstoker_id = $this->factory()->user->create();
		$oven_id        = $this->factory()->oven->create();
		wp_set_current_user( $hoofdstoker_id );
		$aantal = 5;
		for ( $i = 0; $i < $aantal; $i++ ) {
			$stook = new Stook( $oven_id, strtotime( "+ $i days" ) );
			$stook->save();
		}

		$stoken = new Stoken( $oven_id, strtotime( 'today' ), strtotime( "+ $aantal days" ) );
		$this->assertEquals( $aantal, count( $stoken ), 'aantal stoken onjuist' );
	}

	/**
	 * Test de verwijder functie.
	 */
	public function test_verwijder() {
		$hoofdstoker_id = $this->factory()->user->create();
		$oven_id        = $this->factory()->oven->create();
		wp_set_current_user( $hoofdstoker_id );
		$stook = new Stook( $oven_id, strtotime( 'tomorrow' ) );
		$stook->save();
		$stook->verwijder();
		$this->assertFalse( $stook->is_gereserveerd(), 'is gereserveerd onjuist' );
	}

	/**
	 * Test de geef statustekst functie
	 */
	public function test_get_statustekst() {
		$hoofdstoker_id = $this->factory()->user->create();
		$oven_id        = $this->factory()->oven->create();
		wp_set_current_user( $hoofdstoker_id );
		$stook1 = new Stook( $oven_id, strtotime( 'tomorrow' ) );
		$this->assertEquals( Stook::RESERVEERBAAR, $stook1->get_statustekst(), 'status reserveerbaar onjuist' );
		$stook2 = new Stook( $oven_id, strtotime( '-1 week' ) );
		$this->assertEquals( Stook::ONGEBRUIKT, $stook2->get_statustekst(), 'status ongebruikt onjuist' );
		$stook2->save();
		$this->assertEquals( Stook::WIJZIGBAAR, $stook2->get_statustekst(), 'status wijzigbaar onjuist' );
		$stook2->verwerkt = true;
		$this->assertEquals( Stook::DEFINITIEF, $stook2->get_statustekst(), 'status definitief onjuist' );
	}

}
