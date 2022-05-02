<?php
/**
 * Definieer de saldo actie class
 *
 * @link       https://www.kleistad.nl
 * @since      6.14.7
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad SaldoActie class.
 *
 * @since 6.14.7
 */
class SaldoActie {

	/**
	 * Het Saldo object
	 *
	 * @var Saldo $saldo Het saldo.
	 */
	private Saldo $saldo;

	/**
	 * Constructor
	 *
	 * @param Saldo $saldo Het saldo.
	 */
	public function __construct( Saldo $saldo ) {
		$this->saldo = $saldo;
	}

	/**
	 * Voeg een nieuw saldo toe.
	 *
	 * @param float  $bedrag      Het toe te voegen bedrag.
	 * @param string $betaalwijze De betaalwijze, ideal of bank.
	 * @return bool|string redirect url of true.
	 */
	public function nieuw( float $bedrag, string $betaalwijze ): bool|string {
		$datum                     = wp_date( 'ymd', strtotime( 'today' ) );
		$volgnr                    = count( $this->saldo->storting );
		$this->saldo->storting     = [
			'code'  => "S{$this->saldo->klant_id}-$datum-$volgnr",
			'datum' => date( 'Y-m-d', strtotime( 'today' ) ),
			'prijs' => $bedrag,
		];
		$this->saldo->artikel_type = 'saldo';
		$this->saldo->save();
		if ( 'ideal' === $betaalwijze ) {
			return $this->saldo->betaling->doe_ideal( 'Bedankt voor de betaling! Het saldo wordt aangepast en er wordt een email verzonden met bevestiging', $bedrag, $this->saldo->get_referentie() );
		}
		$order = new Order( $this->saldo->get_referentie() );
		$this->saldo->verzend_email( '_bank', $order->bestel() );
		return true;
	}

	/**
	 * Annulering van de storting.
	 */
	public function afzeggen() {
		$this->saldo->update_storting( $this->saldo->get_referentie(), 'geannuleerd' );
		$this->saldo->save();
	}

	/**
	 * Correctie van het uitstaand saldo door een beheerder/
	 *
	 * @param float $nieuw_saldo Nieuw saldo.
	 */
	public function correctie( float $nieuw_saldo ) {
		$verschil              = $nieuw_saldo - $this->saldo->bedrag;
		$corrector             = wp_get_current_user()->display_name;
		$this->saldo->bedrag   = $nieuw_saldo;
		$this->saldo->reden    = "correctie door $corrector";
		$this->saldo->storting = [
			'code'   => "S{$this->saldo->klant_id}-correctie",
			'datum'  => date( 'Y-m-d', strtotime( 'today' ) ),
			'prijs'  => $verschil,
			'status' => "correctie door $corrector",
		];
		$this->saldo->save();
	}
}
