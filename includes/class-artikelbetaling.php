<?php
/**
 * De] definitie van de artikelbetaling class
 *
 * @link       https://www.kleistad.nl
 * @since      6.7.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Artikel class.
 *
 * @property string code
 * @property int    datum
 *
 * @since 6.1.0
 */
abstract class ArtikelBetaling {

	/**
	 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
	 *
	 * @since        6.7.0
	 *
	 * @param Order  $order         De order als deze bestaat.
	 * @param float  $bedrag        Het betaalde bedrag, wordt hier niet gebruikt.
	 * @param bool   $betaald       Of er werkelijk betaald is.
	 * @param string $type          Type betaling, ideal , directdebit of bank.
	 * @param string $transactie_id De betaling id.
	 */
	abstract public function verwerk( Order $order, float $bedrag, bool $betaald, string $type, string $transactie_id = '' );

	/**
	 * Betaal het artikel met iDeal.
	 *
	 * @param string $bericht    Het bericht bij succesvolle betaling.
	 * @param float  $bedrag     Het te betalen bedrag.
	 * @param string $referentie De referentie behorende bij de bestelling.
	 *
	 * @return string|bool De redirect url ingeval van een ideal betaling of false als het niet lukt.
	 */
	abstract public function doe_ideal( string $bericht, float $bedrag, string $referentie );

}
