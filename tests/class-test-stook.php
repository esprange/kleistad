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
		$oven           = $this->factory()->oven->create_and_get();
		wp_set_current_user( $hoofdstoker_id );
		$stook = new Stook( $oven, strtotime( 'tomorrow' ) );
		$this->assertEquals( Stook::RESERVEERBAAR, $stook->get_statustekst(), 'status reserveerbaar onjuist' );
		$stook->save();
		$this->assertTrue( $stook->is_gereserveerd(), 'is gereserveerd onjuist' );
	}

	/**
	 * Test meerdere stoken.
	 */
	public function test_stoken() {
		$hoofdstoker_id = $this->factory()->user->create();
		$oven           = $this->factory()->oven->create_and_get();
		wp_set_current_user( $hoofdstoker_id );
		$aantal = 5;
		for ( $i = 0; $i < $aantal; $i++ ) {
			$stook = new Stook( $oven, strtotime( "+ $i days" ) );
			$stook->save();
		}

		$stoken = new Stoken( $oven, strtotime( 'today' ), strtotime( "+ $aantal days" ) );
		$this->assertEquals( $aantal, count( $stoken ), 'aantal stoken onjuist' );
	}

	/**
	 * Test de verwijder functie.
	 */
	public function test_verwijder() {
		$hoofdstoker_id = $this->factory()->user->create();
		$oven           = $this->factory()->oven->create_and_get();
		wp_set_current_user( $hoofdstoker_id );
		$stook = new Stook( $oven, strtotime( 'tomorrow' ) );
		$stook->save();
		$stook->verwijder();
		$this->assertFalse( $stook->is_gereserveerd(), 'is gereserveerd onjuist' );
	}

	/**
	 * Test de geef statustekst functie
	 */
	public function test_get_statustekst() {
		$hoofdstoker_id = $this->factory()->user->create();
		$oven           = $this->factory()->oven->create_and_get();
		wp_set_current_user( $hoofdstoker_id );
		$stook1 = new Stook( $oven, strtotime( 'tomorrow' ) );
		$this->assertEquals( Stook::RESERVEERBAAR, $stook1->get_statustekst(), 'status reserveerbaar onjuist' );
		$stook2 = new Stook( $oven, strtotime( '-1 week' ) );
		$this->assertEquals( Stook::ONGEBRUIKT, $stook2->get_statustekst(), 'status ongebruikt onjuist' );
		$stook2->save();
		$this->assertEquals( Stook::WIJZIGBAAR, $stook2->get_statustekst(), 'status wijzigbaar onjuist' );
		$stook2->verwerkt = true;
		$this->assertEquals( Stook::DEFINITIEF, $stook2->get_statustekst(), 'status definitief onjuist' );
	}

	/**
	 * Test of er een melding wordt gemaakt van de stook
	 *
	 * @return void
	 */
	public function test_meld() {
		$mailer      = tests_retrieve_phpmailer_instance();
		$hoofdstoker = $this->factory()->user->create_and_get();
		$oven        = $this->factory()->oven->create_and_get();
		wp_set_current_user( $hoofdstoker->ID );
		$stook = new Stook( $oven, strtotime( 'yesterday' ) );
		$stook->save();
		$stook->meld();
		$this->assertStringContainsString( 'Kleistad oven gebruik op', $mailer->get_last_sent( $hoofdstoker->user_email )->subject, 'meld incorrecte email' );
	}

	/**
	 * Test de verwerk functie.
	 *
	 * @return void
	 */
	public function test_verwerk() {
		$mailer      = tests_retrieve_phpmailer_instance();
		$hoofdstoker = $this->factory()->user->create_and_get();
		$medestoker  = $this->factory()->user->create_and_get();
		$oven        = $this->factory()->oven->create_and_get();
		wp_set_current_user( $hoofdstoker->ID );
		$stook = new Stook( $oven, strtotime( '-1 week' ) );
		$stook->wijzig(
			100,
			'teststook',
			1,
			[
				[
					'id'   => $hoofdstoker->ID,
					'perc' => 60,
				],
				[
					'id'   => $medestoker->ID,
					'perc' => 40,
				],
			]
		);
		$stook->verwerk();
		$this->assertStringContainsString( 'Kleistad kosten zijn verwerkt', $mailer->get_last_sent( $hoofdstoker->user_email )->subject, 'meld incorrecte email' );
		$this->assertStringContainsString( 'Kleistad kosten zijn verwerkt', $mailer->get_last_sent( $medestoker->user_email )->subject, 'meld incorrecte email' );
		$saldo1 = new Saldo( $hoofdstoker->ID );
		$saldo2 = new Saldo( $medestoker->ID );
		$this->assertEquals( - 0.6 * $oven->kosten_laag, $saldo1->bedrag, 'stookkosten niet verwerkt' );
		$this->assertEquals( - 0.4 * $oven->kosten_laag, $saldo2->bedrag, 'stookkosten niet verwerkt' );
	}
}
