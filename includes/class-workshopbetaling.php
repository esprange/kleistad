<?php
/**
 * Definieer de workshop betaling class
 *
 * @link       https://www.kleistad.nl
 * @since      6.14.7
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad WorkshopBetaling class.
 *
 * @since 6.14.7
 */
class WorkshopBetaling extends ArtikelBetaling {

	/**
	 * Het workshop object
	 *
	 * @var Workshop $workshop De workshop.
	 */
	private Workshop $workshop;

	/**
	 * Het betaal object
	 *
	 * @var Betalen $betalen Het betaal object.
	 */
	private Betalen $betalen;

	/**
	 * Constructor
	 *
	 * @param Workshop $workshop Het workshop.
	 */
	public function __construct( Workshop $workshop ) {
		$this->workshop = $workshop;
		$this->betalen  = new Betalen();
	}

	/**
	 * Betaal de workshop met iDeal.
	 *
	 * @since        5.0.0
	 *
	 * @param  string $bericht Het bericht bij succesvolle betaling.
	 * @param  float  $bedrag  Het bedrag dat openstaat.
	 * @return string|bool De redirect url ingeval van een ideal betaling of false als het mislukt.
	 */
	public function doe_ideal( string $bericht, float $bedrag ) {
		return $this->betalen->order(
			[
				'naam'     => $this->workshop->contact,
				'email'    => $this->workshop->email,
				'order_id' => $this->workshop->code,
			],
			$this->workshop->geef_referentie(),
			$bedrag,
			sprintf( 'Kleistad workshop %s op %s', $this->workshop->code, strftime( '%d-%m-%Y', $this->workshop->datum ) ),
			$bericht,
			false
		);
	}

	/**
	 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
	 *
	 * @since        5.0.0
	 *
	 * @param Order  $order        De order, als deze al bestaat.
	 * @param float  $bedrag       Het betaalde bedrag.
	 * @param bool   $betaald      Of er werkelijk betaald is.
	 * @param string $type         Type betaling, ideal , directdebit of bank.
	 * @param string $transactie_id De betaling id.
	 */
	public function verwerk( Order $order, float $bedrag, bool $betaald, string $type, string $transactie_id = '' ) {
		if ( $betaald && $order->id ) {
			/**
			 * Bij workshops is er altijd eerst een factuur verstuurd
			 */
			$this->workshop->ontvang_order( $order, $bedrag, $transactie_id );
			if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting.
				$this->workshop->verzend_email( '_ideal' );
			}
		}
	}

}
