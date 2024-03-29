<?php
/**
 * Definieer de dagdelenkaart betaling class
 *
 * @link       https://www.kleistad.nl
 * @since      6.14.7
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad DagdelenkaartBetaling class.
 *
 * @since 6.14.7
 */
class DagdelenkaartBetaling extends ArtikelBetaling {

	/**
	 * Het dagdelenkaart object
	 *
	 * @var Dagdelenkaart $dagdelenkaart De dagdelenkaart.
	 */
	private Dagdelenkaart $dagdelenkaart;

	/**
	 * Het betaal object
	 *
	 * @var Betalen $betalen Het betaal object.
	 */
	private Betalen $betalen;

	/**
	 * Constructor
	 *
	 * @param Dagdelenkaart $dagdelenkaart Het dagdelenkaart.
	 */
	public function __construct( Dagdelenkaart $dagdelenkaart ) {
		$this->dagdelenkaart = $dagdelenkaart;
		$this->betalen       = new Betalen();
	}

	/**
	 * Start de betaling van een nieuw dagdelenkaart.
	 *
	 * @param  string $bericht    Te tonen melding als betaling gelukt.
	 * @param  float  $bedrag     Het bedrag dat openstaat.
	 * @param  string $referentie De referentie.
	 * @return bool|string redirect url van een ideal betaling of false als het niet lukt.
	 */
	public function doe_ideal( string $bericht, float $bedrag, string $referentie ): bool|string {
		return $this->betalen->order(
			$this->dagdelenkaart->klant_id,
			$referentie,
			$bedrag,
			sprintf( 'Kleistad dagdelenkaart %s', $this->dagdelenkaart->code ),
			$bericht,
			false
		);
	}

	/**
	 * Activeer een dagdelenkaart. Wordt aangeroepen vanuit de betaal callback.
	 *
	 * @param Order  $order         De order, als die al bestaat.
	 * @param float  $bedrag        Het bedrag dat betaald is.
	 * @param bool   $betaald       Of er werkelijk betaald is.
	 * @param string $type          Het type betaling.
	 * @param string $transactie_id De betaling id.
	 */
	public function verwerk( Order $order, float $bedrag, bool $betaald, string $type, string $transactie_id = '' ) {
		if ( $betaald ) {
			if ( $order->id ) { // Factuur is eerder al aangemaakt. Betaling vanuit betaal link of bank.
				$order->ontvang( $bedrag, $transactie_id );
				if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting.
					$this->dagdelenkaart->verzend_email( '_ideal_betaald' );
				}
				return;
			}
			// Betaling vanuit inschrijvingformulier.
			$order = new Order( $this->dagdelenkaart->get_referentie() );
			$this->dagdelenkaart->verzend_email( '_ideal', $order->bestel( $bedrag, '', $transactie_id ) );
		} elseif ( 'ideal' === $type && ! $order->id ) {
			$this->dagdelenkaart->erase( false );
		}
	}

}
