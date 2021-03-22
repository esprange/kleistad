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
	 * @param float $betaald Het toe te voegen bedrag.
	 */
	public function nieuw( float $betaald ) {
		$datum                   = strftime( '%y%m%d', strtotime( 'today' ) );
		$volgnr                  = count( $this->saldo->storting );
		$this->saldo->storting[] = [
			'code'  => "S{$this->saldo->klant_id}-$datum-$volgnr",
			'datum' => date( 'Y-m-d', strtotime( 'today' ) ),
			'prijs' => $betaald,
		];
		$this->saldo->save();
	}

}
