<?php
/**
 * De definitie van de stookdeel class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Stookdeel class.
 *
 * @since 6.11.0
 */
class Stookdeel {

	/**
	 * De medestoker
	 *
	 * @var int $medestoker Het WP id van de medestoker.
	 */
	public int $medestoker;

	/**
	 * Het stookdeel percentage
	 *
	 * @var int $percentage Het percentage het stookdeel.
	 */
	public int $percentage;

	/**
	 * De prijs
	 *
	 * @var float $prijs De prijs van de stook voor de medestoker.
	 */
	public float $prijs;

	/**
	 * Constructor
	 *
	 * @param int   $medestoker Het WP id van de medestoker.
	 * @param int   $percentage    Het percentage het stookdeel.
	 * @param float $prijs       De vastgestelde prijs van het stookdeel.
	 */
	public function __construct( int $medestoker, int $percentage, float $prijs ) {
		$this->medestoker = $medestoker;
		$this->percentage = $percentage;
		$this->prijs      = $prijs;
	}

}
