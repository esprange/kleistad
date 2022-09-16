<?php
/**
 * Class ShowcaseTest
 *
 * @package Kleistad
 * @noinspection PhpUndefinedFieldInspection
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
	 * Test ruimte function.
	 */
	public function test_tentoonstellen() {
		$showcase1        = new Showcase();
		$showcase1->titel = 'Dit is een test';
		$showcase1->prijs = 123;
		$showcase_id      = $showcase1->save();
		$this->assertNotEquals( Showcase::TENTOONGESTELD, $showcase1->show_status(), 'tentoonstelling status onjuist 1' );
		$show_datums      = Showcase::show_datums();
		$showcase1->shows = [ $show_datums[0] ];
		$showcase1->save();
		$showcase2 = new Showcase( $showcase_id );
		$this->assertEquals( Showcase::TENTOONGESTELD, $showcase2->show_status(), 'tentoonstelling status onjuist 2' );
		$showcase2->shows = [ $show_datums[2] ];
		$showcase2->save();
		$showcase3 = new Showcase( $showcase_id );
		$this->assertEquals( Showcase::INGEPLAND, $showcase3->show_status(), 'tentoonstelling status onjuist 2' );
	}

	/**
	 * Test email versturen.
	 */
	public function test_dagelijks() {
		$mailer      = tests_retrieve_phpmailer_instance();
		$keramist_id = $this->factory()->user->create();
		wp_set_current_user( $keramist_id );

		$show_datums      = Showcase::show_datums();
		$showcase1        = new Showcase();
		$showcase1->titel = 'Dit is een test';
		$showcase1->prijs = 123;
		$showcase1->shows = [ $show_datums[0] ];
		$showcase1->save();

		Showcases::doe_dagelijks();
		$this->assertEquals( 'Tentoonstellen werkstukken', $mailer->get_last_sent()->subject, 'mail tentoonstellen incorrect' );
	}
}
