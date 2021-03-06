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
	 */
	public function nieuw( float $bedrag, string $betaalwijze ) {
		$datum                     = strftime( '%y%m%d', strtotime( 'today' ) );
		$volgnr                    = count( $this->saldo->storting );
		$this->saldo->storting     = [
			'code'  => "S{$this->saldo->klant_id}-$datum-$volgnr",
			'datum' => date( 'Y-m-d', strtotime( 'today' ) ),
			'prijs' => $bedrag,
		];
		$this->saldo->artikel_type = 'saldo';
		$this->saldo->save();
		if ( 'ideal' === $betaalwijze ) {
			return $this->saldo->betaling->doe_ideal( 'Bedankt voor de betaling! Het saldo wordt aangepast en er wordt een email verzonden met bevestiging', $bedrag );
		}
		$this->saldo->verzend_email( '_bank', $this->saldo->bestel_order( 0.0, strtotime( '+7 days 0:00' ) ) );
		return true;
	}


}
