<?php
/**
 * Class OvenTest
 *
 * @package Kleistad
 * @phpcs:disable WordPress.Files, Generic.Files
 */

namespace Kleistad\Tests;

use Kleistad\Oven;
use Kleistad\Ovens;
use Kleistad\Saldo;

/**
 * Oven test case.
 */
class TestOven extends Kleistad_UnitTestCase {

	/**
	 * Test creation and modification of an oven.
	 */
	public function test_oven() {
		$oven1              = new Oven();
		$oven1->naam        = 'test oven';
		$oven1->kosten_laag = 10;
		$oven_id            = $oven1->save();
		$this->assertTrue( $oven_id > 0, 'save oven no id' );

		$oven2 = new Oven( $oven_id );
		$this->assertEquals( 10, $oven2->kosten_laag, 'kosten oven not equal' );
		$this->assertEquals( 'test oven', $oven2->naam, 'naam oven not equal' );

		$oven2->kosten = 12;
		$this->assertEquals( 12, $oven2->kosten, 'kosten oven not equal' );
	}

	/**
	 * Test creation and modification of multiple ovens.
	 */
	public function test_ovens() {

		$teststring = 'test ovens';
		$ovens      = [];
		for ( $i = 0; $i < 10; $i ++ ) {
			$ovens[ $i ]              = new Oven();
			$ovens[ $i ]->naam        = "$teststring$i";
			$ovens[ $i ]->kosten_laag = $i;
			$ovens[ $i ]->save();
		}

		$ovens = new Ovens();
		foreach ( $ovens as $oven ) {
			if ( substr( $oven->naam, 0, strlen( $teststring ) ) === $teststring ) {
				$this->assertEquals( $teststring . intval( $oven->kosten_laag ), $oven->naam, 'naam ovens not equal' );
			}
		}
	}

	/**
	 * Test de stooksaldo.
	 */
	public function test_saldo() {
		$user_id = $this->factory->user->create(
			[
				'role' => 'subscriber',
			]
		);
		$bedrag  = 123.45;

		$saldo = new Saldo( $user_id );
		$this->assertEquals( 0.0, $saldo->bedrag, 'saldo initieel not zero' );
		$saldo->bedrag = $bedrag;
		$saldo->reden  = 'test 1';
		$saldo->save();

		$saldo2 = new Saldo( $user_id );
		$this->assertEquals( $bedrag, $saldo2->bedrag, 'saldo not equal to ' . $bedrag );

		$saldo2->bedrag = $saldo2->bedrag + $bedrag;
		$saldo2->reden  = 'test 2';
		$saldo2->save();

		$saldo3 = new Saldo( $user_id );
		$this->assertEquals( $bedrag + $bedrag, $saldo3->bedrag, 'saldo not equal to ' . ( $bedrag + $bedrag ) );

		$upload_dir     = wp_upload_dir();
		$transactie_log = $upload_dir['basedir'] . '/stooksaldo.log';

		$this->assertFileExists( $transactie_log, 'transactie_log not created' );
	}

}
