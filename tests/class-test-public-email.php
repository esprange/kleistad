<?php
/**
 * Class Public Email Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Email
 * @noinspection PhpPossiblePolymorphicInvocationInspection, PhpUndefinedFieldInspection, PhpArrayWriteIsNotUsedInspection, PhpUnhandledExceptionInspection
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
		$bestuurder = new WP_User( $this->factory->user->create() );
		$docent     = new WP_User( $this->factory->user->create() );
		$bestuurder->add_role( BESTUUR );
		$docent->add_role( DOCENT );
		wp_set_current_user( $bestuurder->ID );
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( $docent->display_name, $result, 'prepare incorrect' );
		$this->assertStringContainsString( $bestuurder->display_name, $result, 'prepare incorrect' );
	}

	/**
	 * Test test_mail functie.
	 */
	public function test_test_email() {
		$bestuurder = new WP_User( $this->factory->user->create() );
		$docent     = new WP_User( $this->factory->user->create() );
		$bestuurder->add_role( BESTUUR );
		$docent->add_role( DOCENT );
		$_POST  = [
			'gebruikerids'  => "$docent->ID,$bestuurder->ID",
			'onderwerp'     => 'Test',
			'email_content' => 'Dit is een test email',
			'aanhef'        => 'Best cursist',
			'namens'        => 'de Tester',
		];
		$result = $this->public_form_actie( self::SHORTCODE, [], 'test_email' );
		$this->assertStringContainsString( 'De test email is verzonden', $result['status'], 'geen succes na test_email' );
	}

	/**
	 * Test verzenden
	 */
	public function test_verzenden() {
		$mailer     = tests_retrieve_phpmailer_instance();
		$bestuurder = new WP_User( $this->factory->user->create() );
		$docent     = new WP_User( $this->factory->user->create() );
		$bestuurder->add_role( BESTUUR );
		$docent->add_role( DOCENT );
		$_POST  = [
			'gebruikerids'  => "$docent->ID,$bestuurder->ID",
			'onderwerp'     => 'Test',
			'email_content' => 'Dit is een test email',
			'aanhef'        => 'Best cursist',
			'namens'        => 'de Tester',
		];
		$result = $this->public_form_actie( self::SHORTCODE, [], 'verzenden' );
		$this->assertStringContainsString( 'De email is naar 3 personen verzonden', $result['status'], 'geen succes na test_email' );
		$this->assertEquals( 'Test', $mailer->get_last_sent( $docent->user_email )->subject, 'email onderwerp incorrect' );
	}

}
