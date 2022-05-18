<?php
/**
 * Definieer de saldo betaling class
 *
 * @link       https://www.kleistad.nl
 * @since      6.14.7
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad SaldoBetaling class.
 *
 * @since 6.14.7
 */
class SaldoBetaling extends ArtikelBetaling {

	/**
	 * Het saldo object
	 *
	 * @var Saldo $saldo Het saldo.
	 */
	private Saldo $saldo;

	/**
	 * Het betaal object
	 *
	 * @var Betalen $betalen Het betaal object.
	 */
	private Betalen $betalen;

	/**
	 * Constructor
	 *
	 * @param Saldo $saldo Het saldo.
	 */
	public function __construct( Saldo $saldo ) {
		$this->saldo   = $saldo;
		$this->betalen = new Betalen();
	}

	/**
	 * Betaal het saldo met iDeal.
	 *
	 * @param  string $bericht    Het bericht bij succesvolle betaling.
	 * @param  float  $bedrag     Het bedrag dat openstaat.
	 * @param  string $referentie De referentie.
	 * @return bool|string De redirect url ingeval van een ideal betaling of false als het niet lukt.
	 */
	public function doe_ideal( string $bericht, float $bedrag, string $referentie ): bool|string {
		return $this->betalen->order(
			$this->saldo->klant_id,
			$referentie,
			$bedrag,
			sprintf( 'Kleistad saldo %s', $this->saldo->code ),
			$bericht,
			false
		);
	}

	/**
	 * Stort het saldo terug.
	 *
	 * @return bool
	 */
	public function doe_terugboeken() : bool {
		$referentie = '';
		foreach ( $this->saldo->storting as $storting ) {
			$code_elementen = explode( '-', $storting['code'] );
			if ( isset( $code_elementen[2] ) && is_numeric( $code_elementen[2] ) ) {
				$referentie = $storting['code'];
			}
		}
		$order = new Order( $referentie );
		if ( $order->id ) {
			$result = $this->saldo->verzend_email(
				'_terugboeking',
				$order->terugboeken( $this->saldo->bedrag, opties()['administratiekosten'], 'terugstorting restant saldo' )
			);
			if ( $result ) {
				$this->saldo->storting = [
					'code'   => "S{$this->saldo->klant_id}-terugboeking",
					'datum'  => date( 'Y-m-d', strtotime( 'today' ) ),
					'prijs'  => - $this->saldo->bedrag,
					'status' => 'terugboeking saldo',
				];
				$this->saldo->bedrag   = 0.0;
				$this->saldo->save();
			}
			return true;
		}
		return false;
	}

	/**
	 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
	 *
	 * @since        4.2.0
	 *
	 * @param Order  $order         De order als deze bestaat.
	 * @param float  $bedrag        Het betaalde bedrag, wordt hier niet gebruikt.
	 * @param bool   $betaald       Of er werkelijk betaald is.
	 * @param string $type          Type betaling, ideal , directdebit of bank.
	 * @param string $transactie_id De betaling id.
	 */
	public function verwerk( Order $order, float $bedrag, bool $betaald, string $type, string $transactie_id = '' ) {
		if ( $betaald ) {
			$this->saldo->bedrag = round( $this->saldo->bedrag + $bedrag, 2 );
			$this->saldo->reden  = $bedrag > 0 ? 'storting' : 'terugboeking';
			$this->saldo->update_storting( $this->saldo->get_referentie(), "{$this->saldo->reden} per $type" );
			$this->saldo->save();
			if ( $order->id ) {
				/**
				 * Er bestaat al een order dus dit is een betaling o.b.v. een email link of per bank.
				 */
				$order->ontvang( $bedrag, $transactie_id );
				if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting.
					$this->saldo->verzend_email( '_ideal_betaald' );
				}
				return;
			}
			/**
			 * Een betaling vanuit het formulier
			 */
			$order = new Order( $this->saldo->get_referentie() );
			$this->saldo->verzend_email( '_ideal', $order->bestel( $bedrag, '', $transactie_id ) );
		}
	}

}
