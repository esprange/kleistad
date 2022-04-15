<?php
/**
 * Class Abonnee Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Abonnee
 *
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace Kleistad;

/**
 * Abonnee test case.
 */
class Test_Abonnee extends Kleistad_UnitTestCase {

	/**
	 * Test de aantal actieve stook functie
	 */
	public function test_aantal_actieve_stook() {
		$oven_id        = $this->factory()->oven->create();
		$hoofdstoker_id = $this->factory()->user->create();
		$abonnee        = new Abonnee( $hoofdstoker_id );
		wp_set_current_user( $hoofdstoker_id );
		$stook1 = new Stook( $oven_id, strtotime( 'tomorrow' ) );
		$stook1->save();
		$stook2 = new Stook( $oven_id, strtotime( '+ 1 week' ) );
		$stook2->save();
		$this->assertEquals( 2, $abonnee->aantal_actieve_stook(), 'aantal stook 2 onjuist' );
		$stook3 = new Stook( $oven_id, strtotime( '- 1 week' ) );
		$stook3->save();
		$this->assertEquals( 2, $abonnee->aantal_actieve_stook(), 'aantal stook 3 onjuist' );
	}

}
