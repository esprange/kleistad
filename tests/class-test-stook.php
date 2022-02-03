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
		$oven           = new Oven();
		$oven->naam     = 'test oven';
		$oven_id        = $oven->save();
		$hoofdstoker_id = $this->factory()->user->create();
		wp_set_current_user( $hoofdstoker_id );
		$stook = new Stook( $oven_id, strtotime( 'tomorrow' ) );
		$this->assertEquals( Stook::RESERVEERBAAR, $stook->geef_statustekst(), 'status reserveerbaar onjuist' );
		$stook->save();
		$this->assertTrue( $stook->is_gereserveerd(), 'is gereserveerd onjuist' );
	}

	/**
	 * Test meerdere stoken.
	 */
	public function test_stoken() {
		$oven           = new Oven();
		$oven->naam     = 'test oven';
		$oven_id        = $oven->save();
		$hoofdstoker_id = $this->factory()->user->create();
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
		$oven           = new Oven();
		$oven->naam     = 'test oven';
		$oven_id        = $oven->save();
		$hoofdstoker_id = $this->factory()->user->create();
		wp_set_current_user( $hoofdstoker_id );
		$stook = new Stook( $oven_id, strtotime( 'tomorrow' ) );
		$stook->save();
		$stook->verwijder();
		$this->assertFalse( $stook->is_gereserveerd(), 'is gereserveerd onjuist' );
	}

	/**
	 * Test de geef statustekst functie
	 */
	public function test_geef_statustekst() {
		$oven           = new Oven();
		$oven->naam     = 'test oven';
		$oven_id        = $oven->save();
		$hoofdstoker_id = $this->factory()->user->create();
		wp_set_current_user( $hoofdstoker_id );
		$stook1 = new Stook( $oven_id, strtotime( 'tomorrow' ) );
		$this->assertEquals( Stook::RESERVEERBAAR, $stook1->geef_statustekst(), 'status reserveerbaar onjuist' );
		$stook2 = new Stook( $oven_id, strtotime( '-1 week' ) );
		$this->assertEquals( Stook::ONGEBRUIKT, $stook2->geef_statustekst(), 'status ongebruikt onjuist' );
		$stook2->save();
		$this->assertEquals( Stook::WIJZIGBAAR, $stook2->geef_statustekst(), 'status wijzigbaar onjuist' );
		$stook2->verwerkt = true;
		$this->assertEquals( Stook::DEFINITIEF, $stook2->geef_statustekst(), 'status definitief onjuist' );
	}

	/**
	 * Test de aantal actieve stook functie
	 */
	public function test_aantal_actieve_stook() {
		$oven           = new Oven();
		$oven->naam     = 'test oven';
		$oven_id        = $oven->save();
		$hoofdstoker_id = $this->factory()->user->create();
		wp_set_current_user( $hoofdstoker_id );
		$stook1 = new Stook( $oven_id, strtotime( 'tomorrow' ) );
		$stook1->save();
		$stook2 = new Stook( $oven_id, strtotime( '+ 1 week' ) );
		$stook2->save();
		$this->assertEquals( 2, Stook::aantal_actieve_stook( $hoofdstoker_id ), 'aantal stook 2 onjuist' );
		$stook3 = new Stook( $oven_id, strtotime( '- 1 week' ) );
		$stook3->save();
		$this->assertEquals( 2, Stook::aantal_actieve_stook( $hoofdstoker_id ), 'aantal stook 3 onjuist' );
	}
}
