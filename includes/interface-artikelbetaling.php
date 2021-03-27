<?php
/**
 * Definieer de artikel betaling class
 *
 * @link       https://www.kleistad.nl
 * @since      6.14.17
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad ArtikelBetaling class.
 *
 * @since 6.14.7
 */
interface ArtikelBetaling {

	/**
	 * Verwerk de betaling van het artikel.
	 *
	 * @param string $referentie    De referentie van de order.
	 * @param float  $bedrag        Het betaalde bedrag.
	 * @param bool   $betaald       Of er werkelijk betaald is.
	 * @param string $type          Type betaling: ideal, directdebit of bank.
	 * @param string $transactie_id De betaling id, alleen gevuld indien per ideal of directdebit.
	 */
	public function verwerk( string $referentie, float $bedrag, bool $betaald, string $type, string $transactie_id = '' );

	/**
	 * Betaal het artikel met iDeal.
	 *
	 * @param  string $bericht Het bericht bij succesvolle betaling.
	 * @param  float  $bedrag  Het te betalen bedrag.
	 * @return string De redirect url ingeval van een ideal betaling of empty als het niet lukt.
	 */
	public function doe_ideal( string $bericht, float $bedrag ) : string;

}
