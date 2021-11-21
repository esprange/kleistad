<?php
/**
 * Class Public Debiteuren Test
 *
 * @package Kleistad
 *
 * @covers \Kleistad\Public_Debiteuren
 * @noinspection PhpPossiblePolymorphicInvocationInspection, PhpUnhandledExceptionInspection
 */

namespace Kleistad;

/**
 * Debiteuren test case.
 */
class Test_Public_Debiteuren extends Kleistad_UnitTestCase {

	private const SHORTCODE = 'debiteuren';

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
		$openstaand = 0.0;
		for ( $index = 0; $index < $aantal; $index++ ) {
			$verkoop        = new LosArtikel();
			$random         = wp_rand( 1, 10 );
			$openstaand    += $random * 10;
			$verkoop->klant = [
				'naam'  => "test$index",
				'adres' => "straat $random dorp",
				'email' => "test$random@example.com",
			];
			$verkoop->bestelregel( "testverkoop $random", $random, 10 );
			$verkoop->bestel_order( 0.0, strtotime( '+14 days 0:00' ) );
		}
		return $openstaand;
	}

	/**
	 * Test de prepare functie.
	 */
	public function test_prepare() {
		$totaal = $this->maak_debiteuren( 5 );
		/**
		 * Eerst een controle zonder dat er argumenten zijn. Die doet dan standaard actie openstaand.
		 */
		$data   = [ 'actie' => '-' ];
		$result = $this->public_actie( self::SHORTCODE, 'display', $data );
		$this->assertFalse( is_wp_error( $result ), 'prepare zonder argumenten incorrect' );
		$this->assertEquals( 'openstaand', $data['actie'], 'actie openstaand incorrect' );
		$this->assertEquals( 5, count( $data['debiteuren'] ), 'aantal openstaand incorrect' );
		$this->assertEquals( $totaal, $data['openstaand'], 'openstaand bedrag openstaand incorrect' );

		/**
		 * Nu een controle van een van de debiteuren.
		 */
		$data['actie'] = 'debiteur';
		$data['id']    = $data['debiteuren'][1]['id'];
		$result        = $this->public_actie( self::SHORTCODE, 'display', $data );
		$this->assertFalse( is_wp_error( $result ), 'prepare debiteur actie incorrect' );
		$this->assertEquals( $data['debiteuren'][1]['openstaand'], $data['debiteur']['openstaand'], 'debiteur data incorrect' );

		/**
		 * Zoek een debiteur
		 */
		$openstaand = $data['debiteuren'][2]['openstaand'];
		$data       = [
			'id'    => $data['debiteuren'][2]['naam'],
			'actie' => 'zoek',
		];
		$result     = $this->public_actie( self::SHORTCODE, 'display', $data );
		$this->assertFalse( is_wp_error( $result ), 'prepare zonder argumenten incorrect' );
		$this->assertEquals( $openstaand, $data['openstaand'], 'debiteur data incorrect' );

	}

	/**
	 * Test validate functie.
	 */
	public function test_validate() {
		$this->maak_debiteuren( 2 );
		$orders = new Orders();
		$_POST  = [
			'id'                   => $orders->current()->id,
			'bedrag_betaald'       => $orders->current()->te_betalen() / 2,
			'bedrag_gestort'       => $orders->current()->te_betalen() / 2 - 10,
			'korting'              => 10,
			'restant'              => 5,
			'opmerking_korting'    => 'test korting',
			'opmerking_annulering' => 'test annulering',
		];
		$data   = [];
		$result = $this->public_actie( self::SHORTCODE, 'process', $data, 'bankbetaling' );
		$this->assertArrayHasKey( 'content', $result, 'validate normaal incorrect' );

		$data             = [];
		$_POST['korting'] = $orders->current()->te_betalen() + 100;
		$result           = $this->public_actie( self::SHORTCODE, 'process', $data, 'korting' );
		$this->assertArrayHasKey( 'status', $result, 'validate fout bedrag incorrect' );
	}

	/**
	 * Test functie bankbetaling.
	 */
	public function test_bankbetaling() {
		$this->maak_debiteuren( 2 );
		$orders = new Orders();
		$data   = [
			'input' => [
				'id'                   => $orders->current()->id,
				'bedrag_betaald'       => $orders->current()->te_betalen(),
				'bedrag_gestort'       => 0,
				'korting'              => 0,
				'restant'              => 0,
				'opmerking_korting'    => 'test korting',
				'opmerking_annulering' => 'test annulering',
			],
			'order' => $orders->current(),
		];
		$result = $this->public_actie( self::SHORTCODE, 'bankbetaling', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'betaling is verwerkt' ), 'bankbetaling incorrect' );
		$order = new Order( $data['input']['id'] );
		$this->assertTrue( $order->gesloten, 'bankbetaling incorrect verwerkt' );

		$data['input']['bedrag_betaald'] = 0;
		$data['input']['bedrag_gestort'] = $orders->current()->te_betalen();
		$result                          = $this->public_actie( self::SHORTCODE, 'bankbetaling', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'betaling is verwerkt' ), 'bankbetaling incorrect' );
		$order = new Order( $data['input']['id'] );
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
		$data = [
			'input' => [
				'id'                   => $order->id,
				'bedrag_betaald'       => 0,
				'bedrag_gestort'       => 0,
				'korting'              => 0,
				'restant'              => 0.1 * $order->betaald,
				'opmerking_korting'    => 'test korting',
				'opmerking_annulering' => 'test annulering',
			],
			'order' => $order,
		];

		/**
		 * Annulering van reeds betaalde order.
		 */
		$result = $this->public_actie( self::SHORTCODE, 'annulering', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'De annulering is verwerkt en een bevestiging is verstuurd' ), 'annulering incorrect' );
		$this->assertEquals( 'Order geannuleerd', $mailer->get_last_sent( $order->klant['email'] )->subject, 'annulering email onderwerp incorrect' );

		/**
		 * Annuliering van nog niet betaalde order met restant
		 */
		$orders->next();
		$order               = $orders->current();
		$data['input']['id'] = $order->id;
		$data['order']       = $order;
		$result              = $this->public_actie( self::SHORTCODE, 'annulering', $data );
		$this->assertFalse( false !== strpos( $result['status'], 'Het teveel betaalde moet per bank teruggestort worden' ), 'annulering incorrect' );
		$this->assertTrue( false !== strpos( $result['status'], 'De annulering is verwerkt en een bevestiging is verstuurd.' ), 'annulering incorrect' );

		/**
		 * Repeated annulering.
		 */
		$result = $this->public_actie( self::SHORTCODE, 'annulering', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'Er bestaat al een creditering dus mogelijk een interne fout' ), 'annulering incorrect' );
	}

	/**
	 * Test functie korting.
	 */
	public function test_korting() {
		$this->maak_debiteuren( 2 );
		$mailer = tests_retrieve_phpmailer_instance();
		$orders = new Orders();
		$order  = $orders->current();
		$data   = [
			'input' => [
				'id'                   => $order->id,
				'bedrag_betaald'       => 0,
				'bedrag_gestort'       => 0,
				'korting'              => 0.2 * $order->te_betalen(),
				'restant'              => 1,
				'opmerking_korting'    => 'test korting',
				'opmerking_annulering' => 'test annulering',
			],
			'order' => $order,
		];
		$result = $this->public_actie( self::SHORTCODE, 'korting', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'De korting is verwerkt en een correctie is verstuurd' ), 'annulering incorrect' );
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
		$data   = [
			'input' => [
				'id'                   => $order->id,
				'bedrag_betaald'       => 0,
				'bedrag_gestort'       => 0,
				'korting'              => 0.2 * $order->te_betalen(),
				'restant'              => 1,
				'opmerking_korting'    => 'test korting',
				'opmerking_annulering' => 'test annulering',
			],
			'order' => $order,
		];
		$result = $this->public_actie( self::SHORTCODE, 'factuur', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'Een email met factuur is opnieuw verzonden' ), 'annulering incorrect' );
		$this->assertEquals( 'Herzending factuur', $mailer->get_last_sent( $order->klant['email'] )->subject, 'annulering email onderwerp incorrect' );
	}

	/**
	 * Test functie afboeken.
	 */
	public function test_afboeken() {
		$this->maak_debiteuren( 1 );
		$orders = new Orders();
		$order  = $orders->current();
		$data   = [
			'input' => [
				'id'                   => $order->id,
				'bedrag_betaald'       => 0,
				'bedrag_gestort'       => 0,
				'korting'              => 0,
				'restant'              => 0,
				'opmerking_korting'    => '',
				'opmerking_annulering' => '',
			],
			'order' => $order,
		];
		$result = $this->public_actie( self::SHORTCODE, 'afboeken', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'De order is afgeboekt' ), 'afboeking incorrect' );
	}

	/**
	 * Test functie blokkade
	 */
	public function test_blokkade() {
		$data   = [];
		$result = $this->public_actie( self::SHORTCODE, 'blokkade', $data );
		$this->assertTrue( false !== strpos( $result['status'], 'De blokkade datum is gewijzigd' ), 'blokkade incorrect' );
	}
}
