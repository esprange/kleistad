<?php
/**
 * Class Public Cursus Extra Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Cursus_Extra
 * @noinspection PhpPossiblePolymorphicInvocationInspection, PhpUndefinedFieldInspection, PhpUnhandledExceptionInspection, PhpArrayWriteIsNotUsedInspection
 */

namespace Kleistad;

/**
 * Cursus inschrijving test case.
 */
class Test_Public_Cursus_Extra extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'cursus_extra';

	/**
	 * Maak een cursist inschrijving aan.
	 *
	 * @return Inschrijving De inschrijving.
	 */
	private function maak_inschrijving() : Inschrijving {
		$cursist_id = $this->factory()->user->create();
		$cursus_id  = $this->factory()->cursus->create( [ 'start_datum' => strtotime( '+1 month' ) ] );
		/**
		 * Schrijf nu de cursist in.
		 */
		return new Inschrijving( $cursus_id, $cursist_id );
	}

	/**
	 * Test de prepare functie.
	 */
	public function test_prepare() {
		$inschrijving         = $this->maak_inschrijving();
		$inschrijving->aantal = 3;
		$inschrijving->save();
		$_GET   = [
			'code' => $inschrijving->code,
			'hsh'  => $inschrijving->get_controle(),
		];
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( $inschrijving->cursus->naam, $result, 'prepare extra incorrect' );
		$this->assertStringContainsString( 'Medecursist 2', $result, 'prepare extra aantal incorrect' );

		$extra_cursist_ids             = $this->factory()->user->create_many( 2 );
		$inschrijving->extra_cursisten = $extra_cursist_ids;
		$inschrijving->save();

		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( get_user_by( 'ID', $extra_cursist_ids[0] )->user_email, $result, 'prepare extra incorrect' );
	}

	/**
	 * Test validate functie.
	 */
	public function test_process() {
		$mailer               = tests_retrieve_phpmailer_instance();
		$inschrijving         = $this->maak_inschrijving();
		$inschrijving->aantal = 3;
		$inschrijving->save();
		$_POST  = [
			'code'          => $inschrijving->code,
			'extra_cursist' => [
				[
					'user_email' => 'extra1@test.nl',
					'first_name' => 'extra',
					'last_name'  => 'extracursist',
				],
			],
		];
		$result = $this->public_form_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( 'De gegevens zijn opgeslagen en welkomst email is verstuurd', $result['status'], 'geen bevestiging save' );
		$this->assertEquals( 'Welkom cursus', $mailer->get_last_sent()->subject, 'email onderwerp incorrect' );
	}

}
