<?php
/**
 * Class Gebruiker Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Gebruiker
 */

namespace Kleistad;

/**
 * Abonnee test case.
 */
class Test_Gebruiker extends Kleistad_UnitTestCase {

	/**
	 * Test de passwoord reset.
	 *
	 * @return void
	 */
	public function test_pwd_reset() {
		$gebruiker_id = $this->factory()->user->create();
		$gebruiker    = new Gebruiker( $gebruiker_id );
		$link         = $gebruiker->geef_pwd_reset_anchor();
		$this->assertStringContainsString( 'wp-login.php', $link, 'pwd reset link niet correct' );
	}

}
