<?php
/**
 * Class OvenTest
 *
 * @package Kleistad
 */

/**
 * Oven test case.
 */
class KleistadOvenTest extends WP_UnitTestCase {

	/**
	 * Activate the plugin which includes the kleistad specific tables if not present.
	 */
	public function setUp() {
		parent::setUp();
		activate_kleistad();
	}
	/**
	 * Test creation and modification of an oven.
	 */
	public function test_oven() {
		$oven1         = new Kleistad_Oven();
		$oven1->naam   = 'test oven';
		$oven1->kosten = 10;
		$oven_id       = $oven1->save();
		$this->assertTrue( $oven_id > 0, 'save oven no id' );

		$oven2 = new Kleistad_Oven( $oven_id );
		$this->assertEquals( 10, $oven2->kosten, 'kosten oven not equal' );
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
		for ( $i = 0; $i < 10; $i++ ) {
			$ovens[ $i ]         = new Kleistad_Oven();
			$ovens[ $i ]->naam   = "$teststring$i";
			$ovens[ $i ]->kosten = $i;
			$ovens[ $i ]->save();
		}

		$ovens_store      = new Kleistad_Ovens();
		$ovens_from_store = $ovens_store->get();
		foreach ( $ovens_from_store as $oven ) {
			if ( substr( $oven->naam, 0, strlen( $teststring ) ) === $teststring ) {
				$this->assertEquals( $teststring . intval( $oven->kosten ), $oven->naam, 'naam ovens not equal' );
			}
		}

	}

	/**
	 * Test creation and modification of a reservering.
	 */
	public function test_reservering() {
		$user_id     = $this->factory->user->create(
			[
				'role' => 'subscriber',
			]
		);
		$now         = time();
		$datum       = strtotime( date( 'Y', $now ) . '-' . date( 'm', $now ) . '-' . date( 'd', $now ) );
		$temperatuur = 999;
		$soortstook  = 'test';
		$programma   = 1;
		$verdeling   = [
			[
				'id'   => $user_id,
				'perc' => 100,
			],
			[
				'id'   => 0,
				'perc' => 0,
			],
			[
				'id'   => 0,
				'perc' => 0,
			],
			[
				'id'   => 0,
				'perc' => 0,
			],
			[
				'id'   => 0,
				'perc' => 0,
			],
		];
		$opmerking   = 'test remark';

		$oven    = new Kleistad_Oven();
		$oven_id = $oven->save();

		$reservering1               = new Kleistad_Reservering( $oven_id );
		$reservering1->datum        = $datum;
		$reservering1->gebruiker_id = $user_id;
		$reservering1->temperatuur  = $temperatuur;
		$reservering1->soortstook   = $soortstook;
		$reservering1->programma    = $programma;
		$reservering1->verdeling    = $verdeling;
		$reservering1->opmerking    = $opmerking;
		$reservering1->save();

		$reservering2 = new Kleistad_Reservering( $oven_id );
		$this->assertTrue( $reservering2->find( date( 'Y', $datum ), date( 'm', $datum ), date( 'd', $datum ) ), 'existing reservering not found' );
		$this->assertEquals( $datum, $reservering2->datum, 'datum reservering not equal' );
		$this->assertEquals( $temperatuur, $reservering2->temperatuur, 'temperatuut reservering not equal' );
		$this->assertEquals( $soortstook, $reservering2->soortstook, 'soortstook reservering not equal' );
		$this->assertEquals( $programma, $reservering2->programma, 'progroamma reservering not equal' );
		$this->assertEquals( $verdeling, $reservering2->verdeling, 'verdeling reservering not equal' );
		$this->assertEquals( $opmerking, $reservering2->opmerking, 'opmerking reservering not equal' );
		$this->assertFalse( $reservering2->find( 2015, 1, 1 ), 'non existing reservering found' );

		$reservering2->delete();
		$this->assertFalse( $reservering2->find( date( 'Y', $datum ), date( 'm', $datum ), date( 'd', $datum ) ), 'reservering not deleted' );

	}

	/**
	 * Test retrieval of multiple reserveringen.
	 */
	public function test_reserveringen() {

		$oven    = new Kleistad_Oven();
		$oven_id = $oven->save();

		$user_id = $this->factory->user->create(
			[
				'role' => 'subscriber',
			]
		);

		$teststring    = 'test reserveringen';
		$reserveringen = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$reserveringen[ $i ]               = new Kleistad_Reservering( $oven_id );
			$reserveringen[ $i ]->datum        = date( strtotime( "+ $i day" ) );
			$reserveringen[ $i ]->gebruiker_id = $user_id;
			$reserveringen[ $i ]->programma    = $i;
			$reserveringen[ $i ]->opmerking    = "$teststring$i";
			$reserveringen[ $i ]->save();
		}

		$reserveringen_store      = new Kleistad_Reserveringen();
		$reserveringen_from_store = $reserveringen_store->get();
		foreach ( $reserveringen_from_store as $reservering ) {
			if ( substr( $reservering->opmerking, 0, strlen( $teststring ) ) === $teststring ) {
				$this->assertEquals( 'test reserveringen' . intval( $reservering->programma ), $reservering->opmerking, 'opmerking reserveringen not equal' );
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

		$saldo = new Kleistad_Saldo( $user_id );
		$this->assertEquals( 0.0, $saldo->bedrag, 'saldo initieel not zero' );
		$saldo->bedrag = $bedrag;
		$saldo->save( 'test 1' );

		$saldo2 = new Kleistad_Saldo( $user_id );
		$this->assertEquals( $bedrag, $saldo2->bedrag, 'saldo not equal to ' . $bedrag );

		$saldo2->bedrag = $saldo2->bedrag + $bedrag;
		$saldo2->save( 'test 2' );

		$saldo3 = new Kleistad_Saldo( $user_id );
		$this->assertEquals( $bedrag + $bedrag, $saldo3->bedrag, 'saldo not equal to ' . $bedrag + $bedrag );

		$upload_dir     = wp_upload_dir();
		$transactie_log = $upload_dir['basedir'] . '/stooksaldo.log';

		$this->assertFileExists( $transactie_log, 'transactie_log not created' );
	}

	/**
	 * Test the regelingen class.
	 */
	public function test_regelingen() {
		$oven    = new Kleistad_Oven();
		$oven_id = $oven->save();

		$user_id = $this->factory->user->create(
			[
				'role' => 'subscriber',
			]
		);
		$kosten  = 123;

		$regelingen = new Kleistad_Regelingen();

		$regelingen->set_and_save( $user_id, $oven_id, $kosten );

		$regelingen2 = new Kleistad_Regelingen();

		$this->assertEquals( $kosten, $regelingen2->get( $user_id, $oven_id ), 'regelingen not equal' );
	}
}
