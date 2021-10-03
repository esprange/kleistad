<?php
/**
 * Class Public Email Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Email
 */

namespace Kleistad;

use WP_User;

/**
 * Contact test case.
 */
class Test_Public_Email extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'email';

	/**
	 * Test de prepare functie.
	 */
	public function test_prepare() {
		$data       = [ 'actie' => '-' ];
		$bestuurder = new WP_User( $this->factory->user->create() );
		$docent     = new WP_User( $this->factory->user->create() );
		$bestuurder->add_role( BESTUUR );
		$docent->add_role( DOCENT );
		wp_set_current_user( $bestuurder->ID );
		$result = $this->public_actie( self::SHORTCODE, 'display', $data );
		$this->assertFalse( is_wp_error( $result ), 'prepare incorrect' );
		$this->assertNotEmpty( $data['input']['tree'], 'prepare geen data' );
		$this->assertEquals( 2, count( $data['input']['tree'] ), 'prepare docent ontbreekt' );
	}

	/**
	 * Test validate functie.
	 */
	public function test_validate() {
		/**
		 * Test reguliere validate.
		 */
		$_POST              = [
			'gebruikerids'  => '1,2',
			'onderwerp'     => 'Test',
			'email_content' => 'Dit is een test email',
			'aanhef'        => 'Best cursist',
			'namens'        => 'de Tester',
		];
		$data['form_actie'] = 'verzenden';
		$result             = $this->public_actie( self::SHORTCODE, 'validate', $data );
		if ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $error ) {
				echo $error . "\n"; // phpcs:ignore
			}
		}
		$this->assertFalse( is_wp_error( $result ), 'validate incorrect' );
	}

	/**
	 * Test functie test_email.
	 */
	public function test_test_email() {
		$mailer        = tests_retrieve_phpmailer_instance();
		$tester        = new WP_User( $this->factory->user->create() );
		$gebruiker_1   = $this->factory->user->create();
		$gebruiker_2   = $this->factory->user->create();
		$data['input'] = [
			'gebruikerids'  => "$gebruiker_1,$gebruiker_2",
			'onderwerp'     => 'Test',
			'email_content' => 'Dit is een test email',
			'aanhef'        => 'Best cursist',
			'namens'        => 'de Tester',
		];
		wp_set_current_user( $tester->ID );
		$result = $this->public_actie( self::SHORTCODE, 'test_email', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'De test email is verzonden' ), 'geen succes na test_email' );
		$this->assertEquals( 'TEST: Test', $mailer->get_last_sent( $tester->user_email )->subject, 'email onderwerp incorrect' );
	}

	/**
	 * Test functie test_email.
	 */
	public function test_verzenden() {
		$mailer        = tests_retrieve_phpmailer_instance();
		$tester        = new WP_User( $this->factory->user->create() );
		$gebruiker     = new WP_User( $this->factory->user->create() );
		$gebruiker_2   = $this->factory->user->create();
		$data['input'] = [
			'gebruikerids'  => "$gebruiker->ID,$gebruiker_2",
			'onderwerp'     => 'Verzenden',
			'email_content' => 'Dit is een test email',
			'aanhef'        => 'Best cursist',
			'namens'        => 'de Tester',
		];
		wp_set_current_user( $tester->ID );
		$result = $this->public_actie( self::SHORTCODE, 'verzenden', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'De email is naar 3 personen verzonden' ), 'geen succes na verzenden' );
		$this->assertEquals( 'Verzenden', $mailer->get_last_sent( $gebruiker->user_email )->subject, 'email onderwerp incorrect' );
	}

}
