<?php
/**
 * Class Public Cursus Extra Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Cursus_Extra
 * @noinspection PhpPossiblePolymorphicInvocationInspection, PhpUndefinedFieldInspection, PhpUnhandledExceptionInspection
 */

namespace Kleistad;

/**
 * Cursus inschrijving test case.
 */
class Test_Public_Cursus_Extra extends Kleistad_UnitTestCase {

	private const CURSUSNAAM = 'Testcursus';
	private const SHORTCODE  = 'cursus_extra';

	/**
	 * Maak een cursist inschrijving aan.
	 *
	 * @return Inschrijving De inschrijving.
	 */
	private function maak_inschrijving() : Inschrijving {
		$cursist_id              = $this->factory->user->create();
		$cursus                  = new Cursus();
		$cursus->naam            = self::CURSUSNAAM;
		$cursus->start_datum     = strtotime( '+1 month' );
		$cursus->inschrijfkosten = 25.0;
		$cursus->cursuskosten    = 100.0;
		$cursus->maximum         = 10;
		$cursus->save();
		/**
		 * Schrijf nu de cursist in.
		 */
		return new Inschrijving( $cursus->id, $cursist_id );
	}

	/**
	 * Test de prepare functie.
	 */
	public function test_prepare() {
		$inschrijving         = $this->maak_inschrijving();
		$inschrijving->aantal = 3;
		$inschrijving->save();
		$_GET = [
			'code' => $inschrijving->code,
			'hsh'  => $inschrijving->controle(),
		];
		$data = [ 'actie' => Shortcode::STANDAARD_ACTIE ];
		$this->assertFalse( is_wp_error( $this->public_actie( self::SHORTCODE, 'display', $data ) ), 'prepare extra incorrect' );
		$this->assertEquals( $inschrijving->aantal - 1, count( $data['input']['extra'] ), 'prepare extra aantal incorrect' );

		$extra_cursist_id_1            = $this->factory->user->create();
		$extra_cursist_id_2            = $this->factory->user->create();
		$inschrijving->extra_cursisten = [ $extra_cursist_id_1, $extra_cursist_id_2 ];
		$inschrijving->save();
		$data = [ 'actie' => Shortcode::STANDAARD_ACTIE ];
		$this->assertFalse( is_wp_error( $this->public_actie( self::SHORTCODE, 'display', $data ) ), 'prepare extra met cursisten incorrect' );
		$this->assertTrue(
			false !== array_search(
				get_user_by( 'ID', $extra_cursist_id_1 )->user_email,
				array_column( (array) $data['input']['extra'], 'user_email' ),
				true
			),
			'prepare extra met aanwezige cursist incorrect'
		);
	}

	/**
	 * Test validate functie.
	 */
	public function test_validate() {
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
		$mailer               = tests_retrieve_phpmailer_instance();
		$inschrijving         = $this->maak_inschrijving();
		$inschrijving->aantal = 3;
		$extra_email_address  = 'extra1@test.nl';
		$inschrijving->save();
		$data   = [
			'input'        => [
				'code'          => $inschrijving->code,
				'extra_cursist' => [
					[
						'user_email' => $extra_email_address,
						'first_name' => 'extra',
						'last_name'  => 'extracursist',
					],
				],
			],
			'inschrijving' => $inschrijving,
		];
		$result = $this->public_actie( self::SHORTCODE, 'save', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'De gegevens zijn opgeslagen en welkomst email is verstuurd' ), 'geen bevestiging save' );
		$this->assertEquals( 'Welkom cursus', $mailer->get_last_sent()->subject, 'email onderwerp incorrect' );
	}

}
