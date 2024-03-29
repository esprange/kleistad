<?php
/**
 * De definitie van de orderregel class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Orderregel class.
 *
 * @since 6.11.0
 */
class Orderregel {

	const KORTING = 'korting';

	/**
	 * De orderregel tekst
	 *
	 * @var string $artikel De titel van het artikel of bijvoorbeeld korting.
	 */
	public string $artikel;

	/**
	 * Het aantal artikelen in de orderregel
	 *
	 * @var float $aantal Het aantal.
	 */
	public float $aantal;

	/**
	 * De netto prijs van de orderregel
	 *
	 * @var float $prijs De prijs exclusief BTW.
	 */
	public float $prijs;

	/**
	 * De BTW van de order regel
	 *
	 * @var float $btw De BTW.
	 */
	public float $btw;

	/**
	 * Constructor
	 *
	 * @param string     $artikel De artikel titel.
	 * @param float      $aantal  Het aantal artikelen.
	 * @param float      $bedrag  Het bedrag inclusief btw als btw is null.
	 * @param float|null $btw     Als ongelijk null dan de btw.
	 */
	public function __construct( string $artikel, float $aantal, float $bedrag, ?float $btw = null ) {
		$btw_percentage = BTW / 100;
		$this->artikel  = $artikel;
		$this->aantal   = $aantal;
		$this->prijs    = round( is_null( $btw ) ? $bedrag / ( 1 + $btw_percentage ) : $bedrag, 2 );
		$this->btw      = round( is_null( $btw ) ? $bedrag - $this->prijs : $btw, 2 );
	}

}
