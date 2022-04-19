<?php
/**
 * Class Test_Recept
 *
 * @package Kleistad
 * @noinspection PhpUndefinedFieldInspection
 */

namespace Kleistad;

use ReflectionClass;

/**
 * Recept test case.
 */
class Test_Recept extends Kleistad_UnitTestCase {

	private array $termen;

	/**
	 * Zorg voor wat termen.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->termen = [];
		$class        = new ReflectionClass( '\\' . __NAMESPACE__ . '\\ReceptTermen' );
		$class->setStaticPropertyValue( 'hoofdtermen', [] );
		$recepttermen = new ReceptTermen();
		foreach ( $recepttermen->lijst() as $eigenschap => $hoofdterm ) {
			for ( $index = 1; $index <= 3; $index++ ) {
				$result                        = wp_insert_term(
					"test_$index.$eigenschap",
					Recept::CATEGORY,
					[
						'parent' => $hoofdterm->term_id,
					]
				);
				$this->termen[ $eigenschap ][] = $result['term_id'];
			}
		}

	}

	/**
	 * Test creation and modification of an recept.
	 */
	public function test_recept() {
		$recept1             = new Recept();
		$recept1->titel      = 'test recept 1';
		$recept1->kenmerk    = 'test recept kenmerk';
		$recept1->foto       = 'test foto.jpg';
		$recept1->glazuur    = $this->termen[ ReceptTermen::GLAZUUR ][ wp_rand( 0, 2 ) ];
		$recept1->kleur      = $this->termen[ ReceptTermen::KLEUR ][ wp_rand( 0, 2 ) ];
		$recept1->uiterlijk  = $this->termen[ ReceptTermen::UITERLIJK ][ wp_rand( 0, 2 ) ];
		$recept1->basis      = [
			[
				'component' => 'test1',
				'gewicht'   => 20.0,
			],
			[
				'component' => 'test2',
				'gewicht'   => 30.0,
			],
		];
		$recept1->toevoeging = [
			[
				'component' => 'test3',
				'gewicht'   => 5.0,
			],
		];
		$recept1->save();
		$this->assertGreaterThan( 0, $recept1->id, 'geen id na save' );
		$recept2 = new Recept( $recept1->id );
		$this->assertEquals( $recept1->titel, $recept2->titel, 'titel niet opgeslagen' );
		$this->assertEquals( $recept1->foto, $recept2->foto, 'foto niet opgeslagen' );
		$this->assertEquals( $recept1->kenmerk, $recept2->kenmerk, 'kenmerk niet opgeslagen' );
		$this->assertEquals( $recept1->glazuur, $recept2->glazuur, 'glazuur niet opgeslagen' );
		$this->assertEquals( $recept1->kleur, $recept2->kleur, 'kleur niet opgeslagen' );
		$this->assertEquals( $recept1->uiterlijk, $recept2->uiterlijk, 'kleur niet opgeslagen' );
		$this->assertEquals( $recept1->basis, $recept2->basis, 'basis onjuist' );
		$this->assertEquals( 10.0, $recept2->toevoeging[0]['norm_gewicht'], 'normering onjuist' );
	}

	/**
	 * Test creation and modification of multiple recepten.
	 */
	public function test_recepten() {
		$teststring = 'test recepten';
		$recepten   = [];
		for ( $i = 0; $i < 10; $i ++ ) {
			$recepten[ $i ]          = new Recept();
			$recepten[ $i ]->titel   = "$teststring$i";
			$recepten[ $i ]->glazuur = $this->termen[ ReceptTermen::GLAZUUR ][0];
			$recepten[ $i ]->save();
		}
		foreach ( new Recepten() as $recept ) {
			if ( str_starts_with( $recept->titel, $teststring ) ) {
				$this->assertEquals( $recept->glazuur, $this->termen[ ReceptTermen::GLAZUUR ][0], 'glazuue equal' );
			}
		}
	}

}
