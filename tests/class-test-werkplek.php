<?php
/**
 * Class Werkplek Test
 *
 * Test de classen werkplekconfig, werkplekconfigs, werkplekgebruik, werkplekmeesters
 *
 * @package Kleistad
 *
 * @covers \Kleistad\WerkplekConfig, \Kleistad\WerkplekConfigs, \Kleistad\Werkplek, \Kleistad\WerkplekMeesters
 */

namespace Kleistad;

/**
 * Workshop test case.
 */
class Test_Werkplek extends Kleistad_UnitTestCase {

	const DUMP = false;

	/**
	 * Reset de werkplekconfigs voorafgaand elke test.
	 */
	public function setUp(): void {
		delete_option( WerkplekConfigs::META_KEY );
		parent::setUp();
	}

	/**
	 * Test creation of werkplek configs. Default is een empty config.
	 */
	public function test_werkplekconfigs() {
		$werkplekconfigs = new WerkplekConfigs();
		$this->assertEquals( 0, count( $werkplekconfigs ), 'initieel aantal configs incorrect' );
	}

	/**
	 * Test toevoegen of werkplek configs.
	 */
	public function test_toevoegen_werkplekconfigs() {

		// Voeg een empty config toe. Deze vervangt alleen maar de initiële (omdat dat ook een empty is).
		$werkplekconfigs0 = new WerkplekConfigs();
		$werkplekconfigs0->toevoegen( new WerkplekConfig() );
		$basis = $werkplekconfigs0->current()->start_datum;
		$this->assertEquals( 1, count( $werkplekconfigs0 ), 'aantal configs na empty toevoeging incorrect' );
		$this->dump_config();

		// Voeg een tijds gelimiteerde toe. Dan de initiële plus de limitering is 2.
		$werkplekconfigs1             = new WerkplekConfigs();
		$werkplekconfig1              = new WerkplekConfig();
		$werkplekconfig1->start_datum = strtotime( 'next Monday', $basis );
		$werkplekconfig1->eind_datum  = strtotime( '+ 6 days', $werkplekconfig1->start_datum );
		$werkplekconfigs1->toevoegen( $werkplekconfig1 );
		$this->dump_config();
		$this->assertEquals( 3, count( $werkplekconfigs1 ), 'aantal configs na 1e tijdsbeperking incorrect' );

		// Voeg nog een tijds gelimiteerde toe. Dus nu een initiële, de 1e, de default, 2e testcase en weer de default = 5.
		$werkplekconfigs2             = new WerkplekConfigs();
		$werkplekconfig2              = new WerkplekConfig();
		$werkplekconfig2->start_datum = strtotime( '+ 28 days', $basis );
		$werkplekconfig2->eind_datum  = strtotime( '+ 140 days', $werkplekconfig2->start_datum );
		$werkplekconfigs2->toevoegen( $werkplekconfig2 );
		$this->dump_config();
		$this->assertEquals( 5, count( $werkplekconfigs2 ), 'aantal configs na 2e tijdsbeperking incorrect' );

		// Voeg een tijds gelimiteerde toe die de eerdere van 4 maanden in twee breekt en zichzelf toevoegt dus = 7.
		$werkplekconfigs3             = new WerkplekConfigs();
		$werkplekconfig3              = new WerkplekConfig();
		$werkplekconfig3->start_datum = strtotime( '+ 46 days', $basis );
		$werkplekconfig3->eind_datum  = strtotime( '+ 28 days', $werkplekconfig3->start_datum );
		$werkplekconfigs3->toevoegen( $werkplekconfig3 );
		$this->dump_config();
		$this->assertEquals( 7, count( $werkplekconfigs3 ), 'aantal configs na 3e tijdsbeperking incorrect' );

		// Voeg een tijds gelimiteerde toe die de eerdere twee configs vervangt. Dan de default, 1 week, 5 maanden en de rest = 4.
		$werkplekconfigs4             = new WerkplekConfigs();
		$werkplekconfig4              = new WerkplekConfig();
		$werkplekconfig4->start_datum = strtotime( '+ 28 days', $basis );
		$werkplekconfig4->eind_datum  = strtotime( '+ 168 days', $werkplekconfig4->start_datum );
		$werkplekconfigs4->toevoegen( $werkplekconfig4 );
		$this->dump_config();
		$this->assertEquals( 5, count( $werkplekconfigs4 ), 'aantal configs na 4e tijdsbeperking incorrect' );

		// Voeg een ongelimiteerde beperking toe, die de rest vervangt.
		$werkplekconfigs5             = new WerkplekConfigs();
		$werkplekconfig5              = new WerkplekConfig();
		$werkplekconfig5->start_datum = strtotime( '+ 46 days', $basis );
		$werkplekconfig5->eind_datum  = 0;
		$werkplekconfigs5->toevoegen( $werkplekconfig5 );
		$this->dump_config();
		$this->assertEquals( 5, count( $werkplekconfigs5 ), 'aantal configs na 5e tijdsbeperking incorrect' );
	}

	/**
	 * Test het verwijderen.
	 */
	public function test_verwijder_werkplekconfigs() {
		$werkplekconfigs = new WerkplekConfigs();
		$werkplekconfigs->toevoegen( new WerkplekConfig() );
		$this->dump_config();

		$werkplekconfig              = new WerkplekConfig();
		$werkplekconfig->start_datum = strtotime( 'monday' );
		$werkplekconfig->eind_datum  = strtotime( '+ 1 month', $werkplekconfig->start_datum );
		$werkplekconfigs->toevoegen( $werkplekconfig );
		$this->dump_config();

		$werkplekconfigs->verwijder( $werkplekconfig );
		$this->dump_config();
		$this->assertEquals( 2, count( $werkplekconfigs ), 'aantal configs na verwijdering incorrect' );
	}

	/**
	 * Help functie voor eventuele controle.
	 */
	private function dump_config() {
		if ( self::DUMP ) {
			foreach ( new WerkplekConfigs() as $werkplekconfig ) {
				echo date( 'd-m-Y', $werkplekconfig->start_datum ) . ' '; // phpcs:ignore
				echo $werkplekconfig->eind_datum ? date( 'd-m-Y', $werkplekconfig->eind_datum ) . "\n" : "\n"; // phpcs:ignore
			}
			echo "\n"; // phpcs:ignore
		}
	}
}
