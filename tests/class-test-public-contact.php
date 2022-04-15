<?php
/**
 * Class Public Contact Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Contact
 * @noinspection PhpPossiblePolymorphicInvocationInspection, PhpUndefinedFieldInspection, PhpUnhandledExceptionInspection, PhpArrayWriteIsNotUsedInspection
 */

namespace Kleistad;

/**
 * Contact test case.
 */
class Test_Public_Contact extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'contact';

	/**
	 * Test de prepare functie.
	 */
	public function test_prepare() {
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertNotEmpty( $result, 'prepare incorrect' );
	}

	/**
	 * Test prepare maar nu met de simulatie van een bestaande gebruiker.
	 */
	public function test_prepare_ingelogd() {
		$gebruiker = $this->factory()->user->create_and_get();
		wp_set_current_user( $gebruiker->ID );
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( $gebruiker->user_email, $result, 'prepare ingelogde gebruiker leeg' );
	}

	/**
	 * Test process functie.
	 */
	public function test_process() {
		$mailer = tests_retrieve_phpmailer_instance();
		$_POST  = [
			'email'     => 'test@example.com',
			'naam'      => 'test naam',
			'telnr'     => '',
			'onderwerp' => 'test',
			'vraag'     => 'testvraag',
		];
		$result = $this->public_form_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( 'Jouw vraag is ontvangen', $result['status'], 'geen succes na save' );
		$this->assertEquals( 'Vraag over test', $mailer->get_last_sent( 'test@example.com' )->subject, 'contact email onderwerp incorrect' );
	}

}
