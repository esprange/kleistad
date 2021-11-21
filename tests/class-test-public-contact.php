<?php
/**
 * Class Public Contact Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Contact
 * @noinspection PhpPossiblePolymorphicInvocationInspection, PhpUndefinedFieldInspection, PhpUnhandledExceptionInspection
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
		/**
		 * Eerst een controle zonder dat er argumenten zijn. Die doet niets.
		 */
		$data   = [ 'actie' => '-' ];
		$result = $this->public_actie( self::SHORTCODE, 'display', $data );
		$this->assertFalse( is_wp_error( $result ), 'prepare incorrect' );

		/**
		 * Nu met de simulatie van een bestaande gebruiker.
		 */
		$data         = [];
		$gebruiker_id = $this->factory->user->create();
		wp_set_current_user( $gebruiker_id );
		$result = $this->public_actie( self::SHORTCODE, 'display', $data );
		$this->assertFalse( is_wp_error( $result ), 'prepare met ingelogde gebruiker incorrect' );
		$this->assertNotEmpty( $data['input']['email'], 'prepare ingelogde gebruiker leeg' );
	}

	/**
	 * Test validate functie.
	 */
	public function test_validate() {
		/**
		 * Test reguliere validate.
		 */
		$_POST  = [
			'email'     => 'test@example.com',
			'naam'      => 'test naam',
			'telnr'     => '',
			'onderwerp' => 'test',
			'vraag'     => 'testvraag',
		];
		$data   = [];
		$result = $this->public_actie( self::SHORTCODE, 'process', $data );
		if ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $error ) {
				echo $error . "\n"; // phpcs:ignore
			}
		}
		$this->assertFalse( is_wp_error( $result ), 'validate incorrect' );
	}

	/**
	 * Test functie save.
	 */
	public function test_save() {
		$mailer        = tests_retrieve_phpmailer_instance();
		$data['input'] = [
			'email'     => 'test@example.com',
			'naam'      => 'test naam',
			'telnr'     => '',
			'onderwerp' => 'test',
			'vraag'     => 'testvraag',
		];
		$result        = $this->public_actie( self::SHORTCODE, 'save', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'Jouw vraag is ontvangen' ), 'geen succes na save' );
		$this->assertEquals( 'Vraag over test', $mailer->get_last_sent( 'test@example.com' )->subject, 'contact email onderwerp incorrect' );
	}


}
