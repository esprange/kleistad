<?php
/**
 * Class Public Abonnee Inschrijving Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Abonnee_Inschrijving
 * @noinspection PhpUndefinedFieldInspection, PhpUnhandledExceptionInspection
 */

namespace Kleistad;

/**
 * Abonnee inschrijving test case.
 */
class Test_Public_Abonnee_Inschrijving extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'abonnee_inschrijving';

	/**
	 * Formulier data.
	 *
	 * @var array $input De ingevoerde data.
	 */
	private array $input;

	/**
	 * Maak een cursist inschrijving aan.
	 *
	 * @param bool $beperkt Of het een beperkt abonnement betreft.
	 * @return Abonnement Het abonnement.
	 */
	private function maak_inschrijving( bool $beperkt ) : Abonnement {
		$abonnee_id  = $this->factory->user->create();
		$abonnee     = get_user_by( 'ID', $abonnee_id );
		$this->input = [
			'user_email'       => $abonnee->user_email,
			'email_controle'   => $abonnee->user_email,
			'first_name'       => $abonnee->first_name,
			'last_name'        => $abonnee->last_name,
			'straat'           => 'straat',
			'huisnr'           => '12345',
			'pcode'            => '1234AB',
			'plaats'           => 'plaats',
			'telnr'            => '0123456789',
			'abonnement_keuze' => $beperkt ? 'beperkt' : 'onbeperkt',
			'extras'           => [],
			'dag'              => 'maandag',
			'start_datum'      => date( 'd-m-Y', strtotime( '+ 1 month' ) ),
			'gebruiker_id'     => $abonnee_id,
			'opmerking'        => '',
			'betaal'           => 'ideal',
			'mc4wp-subscribe'  => null,
		];

		/**
		 * Schrijf nu de abonnee in.
		 */
		return new Abonnement( $abonnee_id );
	}

	/**
	 * Test prepare functie;
	 */
	public function test_prepare() {
		$data   = [ 'actie' => '-' ];
		$result = $this->public_actie( self::SHORTCODE, 'display', $data, '', [ 'verklaring' => 'test' ] );
		if ( is_wp_error( $result ) ) {
			foreach ( $result->get_error_messages() as $error ) {
				echo $error . "\n"; // phpcs:ignore
			}
		}
		$this->assertFalse( is_wp_error( $result ), 'prepare incorrect' );
		$this->assertTrue( 'test' === ( $data['verklaring'] ?? '' ), 'prepare verklaring incorrect' );
	}

	/**
	 * Test validate functie.
	 */
	public function test_validate() {
		$this->maak_inschrijving( true );
		$_POST  = $this->input;
		$data   = [];
		$result = $this->public_actie( self::SHORTCODE, 'validate', $data );
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
		$abonnement = $this->maak_inschrijving( false );
		$data       = [ 'input' => $this->input ];
		$result     = $this->public_actie( self::SHORTCODE, 'save', $data );
		$this->assertTrue( isset( $result['redirect_uri'] ), 'geen ideal verwijzing na inschrijven' );

		/**
		 * Inschrijving moet herhaald kunnen worden.
		 */
		$result = $this->public_actie( self::SHORTCODE, 'save', $data );
		$this->assertTrue( isset( $result['redirect_uri'] ), 'geen ideal verwijzing na inschrijven' );

		/**
		 * Inschrijving na eerste facturatie kan niet.
		 */
		$abonnement->artikel_type = 'start'; // Dit is nodig omdat de order referentie normaliter via Mollie terugkomt.
		$order                    = new Order( $abonnement->geef_referentie() );
		$abonnement->betaling->verwerk( $order, 25, true, 'ideal' ); // Bedrag klopt niet maar dat maar nu niet uit.

		$result = $this->public_actie( self::SHORTCODE, 'save', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'Het is niet mogelijk om een bestaand abonnement' ), 'geen ideal verwijzing na inschrijven' );
	}


}
