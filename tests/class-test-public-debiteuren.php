<?php
/**
 * Class Public Debiteuren Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Debiteuren
 * @noinspection PhpPossiblePolymorphicInvocationInspection, PhpUnhandledExceptionInspection, PhpArrayWriteIsNotUsedInspection, PhpArrayIndexImmediatelyRewrittenInspection
 */

namespace Kleistad;

/**
 * Debiteuren test case.
 */
class Test_Public_Debiteuren extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'debiteuren';

	/**
	 * De verkopen
	 *
	 * @var array LosArtikels
	 */
	private array $verkoop;

	/**
	 * Maak debiteuren aan.
	 *
	 * @param int $aantal Het aantal debiteuren.
	 * @return float Openstaand bedrag.
	 */
	private function maak_debiteuren( int $aantal ) : float {
		/**
		 * Maak 5 verkopen.
		 */
		$openstaand    = 0.0;
		$this->verkoop = [];
		for ( $index = 0; $index < $aantal; $index++ ) {
			$this->verkoop[ $index ]        = new LosArtikel();
			$random                         = wp_rand( 1, 10 );
			$openstaand                    += $random * 10;
			$this->verkoop[ $index ]->klant = [
				'naam'  => "test$index",
				'adres' => "straat $random dorp",
				'email' => "test$random@example.com",
			];
			$this->verkoop[ $index ]->bestelregel( "testverkoop $random", $random, 10 );
			$this->verkoop[ $index ]->bestel_order( 0.0, strtotime( '+14 days 0:00' ) );
		}
		return $openstaand;
	}

	/**
	 * Test de prepare functie.
	 */
	public function test_prepare() {
		$totaal = $this->maak_debiteuren( 5 );
		$result = $this->public_display_actie( self::SHORTCODE, [] );
		$this->assertStringContainsString( $totaal, $result, 'openstaand bedrag overzicht incorrect' );
	}

	/**
	 * Test prepare debiteur.
	 */
	public function test_prepare_debiteur() {
		$this->maak_debiteuren( 5 );
		$order  = new Order( $this->verkoop[1]->geef_referentie() );
		$result = $this->public_display_actie( self::SHORTCODE, [ 'id' => $order->id ], 'debiteur' );
		$this->assertStringContainsString( $order->te_betalen(), $result, 'openstaand bedrag debiteur incorrect' );
	}

	/**
	 * Test prepare zoek een debiteur
	 */
	public function test_zoek() {
		$this->maak_debiteuren( 5 );
		$result = $this->public_display_actie( self::SHORTCODE, [ 'id' => $this->verkoop[2]->klant['naam'] ], 'zoek' );
		$order  = new Order( $this->verkoop[2]->geef_referentie() );
		$this->assertStringContainsString( $order->te_betalen(), $result, 'openstaand bedrag debiteur incorrect' );
	}

	/**
	 * Test functie bankbetaling.
	 */
	public function test_bankbetaling() {
		$this->maak_debiteuren( 2 );
		$orders = new Orders();
		$_POST  = [
			'id'                   => $orders->current()->id,
			'bedrag_betaald'       => $orders->current()->te_betalen(),
			'bedrag_gestort'       => 0,
			'korting'              => 0,
			'restant'              => 0,
			'opmerking_korting'    => 'test korting',
			'opmerking_annulering' => 'test annulering',
		];
		$result = $this->public_form_actie( self::SHORTCODE, [], 'bankbetaling' );
		$this->assertStringContainsString( 'betaling is verwerkt', $result['status'], 'bankbetaling incorrect' );
		$order = new Order( $orders->current()->id );
		$this->assertTrue( $order->gesloten, 'bankbetaling incorrect verwerkt' );

		$_POST['bedrag_betaald'] = 0;
		$_POST['bedrag_gestort'] = $orders->current()->te_betalen();
		$result                  = $this->public_form_actie( self::SHORTCODE, [], 'bankbetaling' );
		$this->assertStringContainsString( 'betaling is verwerkt', $result['status'], 'bankbetaling incorrect' );
		$order = new Order( $orders->current()->id );
		$this->assertTrue( $order->gesloten, 'bankbetaling incorrect verwerkt' );
	}

	/**
	 * Test functie annulering.
	 */
	public function test_annulering() {
		$this->maak_debiteuren( 2 );
		$mailer         = tests_retrieve_phpmailer_instance();
		$orders         = new Orders();
		$order          = $orders->current();
		$order->betaald = 0.5 * $order->te_betalen();
		$order->save( 'test' );
		$_POST = [
			'id'                   => $order->id,
			'bedrag_betaald'       => 0,
			'bedrag_gestort'       => 0,
			'korting'              => 0,
			'restant'              => 0.1 * $order->betaald,
			'opmerking_korting'    => 'test korting',
			'opmerking_annulering' => 'test annulering',
		];

		/**
		 * Annulering van reeds betaalde order.
		 */
		$result = $this->public_form_actie( self::SHORTCODE, [], 'annulering' );
		$this->assertStringContainsString( 'De annulering is verwerkt en een bevestiging is verstuurd', $result['status'], 'annulering incorrect' );
		$this->assertEquals( 'Order geannuleerd', $mailer->get_last_sent( $order->klant['email'] )->subject, 'annulering email onderwerp incorrect' );

		/**
		 * Annuliering van nog niet betaalde order met restant
		 */
		$orders->next();
		$order       = $orders->current();
		$_POST['id'] = $order->id;
		$result      = $this->public_form_actie( self::SHORTCODE, [], 'annulering' );
		$this->assertStringContainsString( 'De annulering is verwerkt en een bevestiging is verstuurd.', $result['status'], 'annulering incorrect' );

		/**
		 * Repeated annulering.
		 */
		$result = $this->public_form_actie( self::SHORTCODE, [], 'annulering' );
		$this->assertStringContainsString( 'Er bestaat al een creditering dus mogelijk een interne fout', $result['status'], 'herhaalde annulering incorrect' );
	}

	/**
	 * Test functie korting.
	 */
	public function test_korting() {
		$this->maak_debiteuren( 2 );
		$mailer = tests_retrieve_phpmailer_instance();
		$orders = new Orders();
		$order  = $orders->current();
		$_POST  = [
			'id'                   => $order->id,
			'bedrag_betaald'       => 0,
			'bedrag_gestort'       => 0,
			'korting'              => 0.2 * $order->te_betalen(),
			'restant'              => 1,
			'opmerking_korting'    => 'test korting',
			'opmerking_annulering' => 'test annulering',
		];
		$result = $this->public_form_actie( self::SHORTCODE, [], 'korting' );
		$this->assertStringContainsString( 'De korting is verwerkt en een correctie is verstuurd', $result['status'], 'annulering incorrect' );
		$this->assertEquals( 'Order gecorrigeerd', $mailer->get_last_sent( $order->klant['email'] )->subject, 'annulering email onderwerp incorrect' );
	}

	/**
	 * Test functie factuur.
	 */
	public function test_factuur() {
		$this->maak_debiteuren( 2 );
		$mailer = tests_retrieve_phpmailer_instance();
		$orders = new Orders();
		$order  = $orders->current();
		$_POST  = [
			'id'                   => $order->id,
			'bedrag_betaald'       => 0,
			'bedrag_gestort'       => 0,
			'korting'              => 0.2 * $order->te_betalen(),
			'restant'              => 1,
			'opmerking_korting'    => 'test korting',
			'opmerking_annulering' => 'test annulering',
		];
		$result = $this->public_form_actie( self::SHORTCODE, [], 'factuur' );
		$this->assertStringContainsString( 'Een email met factuur is opnieuw verzonden', $result['status'], 'annulering incorrect' );
		$this->assertEquals( 'Herzending factuur', $mailer->get_last_sent( $order->klant['email'] )->subject, 'annulering email onderwerp incorrect' );
	}

	/**
	 * Test functie afboeken.
	 */
	public function test_afboeken() {
		$this->maak_debiteuren( 1 );
		$orders = new Orders();
		$order  = $orders->current();
		$_POST  = [
			'id'                   => $order->id,
			'bedrag_betaald'       => 0,
			'bedrag_gestort'       => 0,
			'korting'              => 0,
			'restant'              => 0,
			'opmerking_korting'    => '',
			'opmerking_annulering' => '',
		];
		$result = $this->public_form_actie( self::SHORTCODE, [], 'afboeken' );
		$this->assertStringContainsString( 'De order is afgeboekt', $result['status'], 'afboeking incorrect' );
	}

	/**
	 * Test functie blokkade
	 */
	public function test_blokkade() {
		$result = $this->public_form_actie( self::SHORTCODE, [], 'blokkade' );
		$this->assertStringContainsString( 'De blokkade datum is gewijzigd', $result['status'], 'blokkade incorrect' );
	}
}
