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

		$upload_dir      = wp_upload_dir();
		$transactie_log  = $upload_dir['basedir'] . '/stooksaldo.log';

		Kleistad_Oven::log_saldo( 'test' );
		$this->assertFileExists( $transactielog );
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

	/**
	 * Test creation and modification of a reservering.
	 */
	function test_reservering() {
		$user_id = $this->factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);
		$datum = time();

		$oven = new Kleistad_Oven();
		$oven->naam = 'test';
		$oven->kosten = 5;
		$oven_id = $oven->save();
		$reservering1 = new Kleistad_Reservering( $oven_id );
		$reservering1->datum = $datum;
		$reservering1->gebruiker_id = $user_id;
		$reservering1->temperatuur = 999;
		$reservering1->soortstook = 'test';
		$reservering1->programma = 1;
		$reservering1->verdeling = [
			[
				'id'     => $user_id,
				'perc'   => 100,
			],
			[
				'id'     => 0,
				'perc'   => 0,
			],
			[
				'id'     => 0,
				'perc'   => 0,
			],
			[
				'id'     => 0,
				'perc'   => 0,
			],
			[
				'id'     => 0,
				'perc'   => 0,
			],
		];
		$reservering1->opmerking = 'test remark';
		$reservering1->save();

		$reservering2 = new Kleistad_Reservering( $oven_id );
		$this->assertTrue( $reservering2->find( date( 'Y', $datum ), date( 'm', $datum ), date( 'd', $datum ) ) );

	}
}
