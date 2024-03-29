<?php
/**
 * Class ShowcaseTest
 *
 * @package Kleistad
 * @noinspection PhpUndefinedFieldInspection, PhpPossiblePolymorphicInvocationInspection
 */

namespace Kleistad;

/**
 * Showcase test case.
 */
class Test_Showcase extends Kleistad_UnitTestCase {

	/**
	 * Test creation and modification of a showcase.
	 */
	public function test_showcase() {
		$showcase1               = new Showcase();
		$showcase1->titel        = 'test showcase';
		$showcase1->beschrijving = "Dit is een test\nOp twee regels";
		$showcase1->positie      = 'sokkel';
		$showcase1->prijs        = 100;
		$showcase1->breedte      = 10;
		$showcase1->hoogte       = 20;
		$showcase1->diepte       = 30;
		$showcase_id             = $showcase1->save();
		$this->assertTrue( $showcase_id > 0, 'save showcase no id' );

		$showcase2 = new Showcase( $showcase_id );
		$this->assertEquals( $showcase1->titel, $showcase2->titel, 'titel showcase not equal' );
		$this->assertEquals( $showcase1->beschrijving, $showcase2->beschrijving, 'beschrijving showcase not equal' );
		$this->assertEquals( $showcase1->positie, $showcase2->positie, 'positie showcase not equal' );
		$this->assertEquals( $showcase1->prijs, $showcase2->prijs, 'prijs showcase not equal' );
		$this->assertEquals( $showcase1->lengte, $showcase2->lengte, 'lengte showcase not equal' );
		$this->assertEquals( $showcase1->breedte, $showcase2->breedte, 'breedte showcase not equal' );
		$this->assertEquals( $showcase1->diepte, $showcase2->diepte, 'diepte showcase not equal' );
	}

	/**
	 * Test erase function.
	 */
	public function test_erase() {
		$showcase1        = new Showcase();
		$showcase1->titel = 'Dit is een test';
		$showcase1->prijs = 123;
		$showcase_id      = $showcase1->save();
		$showcase1->erase();
		$showcase2 = new Showcase( $showcase_id );
		$this->assertNotEquals( 'Dit is een test', $showcase2->titel, 'erase incorrect' );
	}

	/**
	 * Helper, om show datums in tekst te krijgen.
	 *
	 * @param string $datum De test datum.
	 *
	 * @return array
	 */
	private function showdatums_as_string( string $datum ) : array {
		return array_map(
			function( $show ) {
				return [
					'start' => date( 'Y-m-d', $show['start'] ),
					'eind'  => date( 'Y-m-d', $show['eind'] ),
				];
			},
			( new Shows( strtotime( $datum ) ) )->get_datums()
		);
	}

	/**
	 * Test de showdatums function.
	 */
	public function test_showdatums() {
		$show1 = [
			'start' => '2022-09-05',
			'eind'  => '2022-11-07',
		];
		$show2 = [
			'start' => '2022-11-07',
			'eind'  => '2023-01-02',
		];
		$show3 = [
			'start' => '2023-01-02',
			'eind'  => '2023-03-06',
		];
		$show4 = [
			'start' => '2023-03-06',
			'eind'  => '2023-05-01',
		];
		$test1 = [
			$show1,
			$show2,
			$show3,
		];
		$this->assertEquals( $test1, $this->showdatums_as_string( '2022-10-15' ), 'Datums 10-11-2022 onjuist' );
		$this->assertEquals( $test1, $this->showdatums_as_string( '2022-11-02' ), 'Datums 1-11-2022 onjuist' );

		$test2 = [
			$show2,
			$show3,
			$show4,
		];
		$this->assertEquals( $test2, $this->showdatums_as_string( '2022-11-08' ), 'Datums 1-11-2022 onjuist' );
	}

	/**
	 * Test ruimte function.
	 */
	public function test_tentoonstellen() {
		$showcase1        = new Showcase();
		$showcase1->titel = 'Dit is een test';
		$showcase1->prijs = 123;
		$showcase_id      = $showcase1->save();
		$this->assertNotEquals( Showcase::TENTOONGESTELD, $showcase1->get_statustekst(), 'tentoonstelling status onjuist 1' );
		$show_datums      = ( new Shows() )->get_datums();
		$showcase1->shows = [ $show_datums[0] ];
		$showcase1->save();
		$showcase2 = new Showcase( $showcase_id );
		$this->assertEquals( Showcase::TENTOONGESTELD, $showcase2->get_statustekst(), 'tentoonstelling status onjuist 2' );
		$showcase2->shows = [ $show_datums[2] ];
		$showcase2->save();
		$showcase3 = new Showcase( $showcase_id );
		$this->assertEquals( Showcase::INGEPLAND, $showcase3->get_statustekst(), 'tentoonstelling status onjuist 2' );
	}

	/**
	 * Test email versturen.
	 */
	public function test_dagelijks() {
		$mailer      = tests_retrieve_phpmailer_instance();
		$keramist_id = $this->factory()->user->create();
		wp_set_current_user( $keramist_id );

		$show_datums      = ( new Shows() )->get_datums();
		$showcase1        = new Showcase();
		$showcase1->titel = 'Dit is een test';
		$showcase1->prijs = 123;
		$showcase1->shows = [ $show_datums[0] ];
		$showcase1->save();
		Showcases::doe_dagelijks();
		$this->assertEquals( 'Tentoonstellen werkstukken', $mailer->get_last_sent()->subject, 'mail tentoonstellen incorrect' );
	}
}
