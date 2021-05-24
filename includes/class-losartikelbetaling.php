<?php
/**
 * Definieer de losartikel betaling class
 *
 * @link       https://www.kleistad.nl
 * @since      6.14.7
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad LosArtikelBetaling class.
 *
 * @since 6.14.7
 */
class LosArtikelBetaling implements ArtikelBetaling {

	/**
	 * Het losartikel object
	 *
	 * @var LosArtikel $losartikel Het losartikel.
	 */
	private LosArtikel $losartikel;

	/**
	 * Het betaal object
	 *
	 * @var Betalen $betalen Het betalen object.
	 */
	private Betalen $betalen;

	/**
	 * Constructor
	 *
	 * @param LosArtikel $losartikel Het losartikel.
	 */
	public function __construct( LosArtikel $losartikel ) {
		$this->losartikel = $losartikel;
		$this->betalen    = new Betalen();
	}

	/**
	 * Betalen functie, wordt niet gebruikt.
	 *
	 * @param  string $bericht Dummy variable.
	 * @param  float  $bedrag  Het bedrag dat openstaat.
	 * @return string|bool De redirect url ingeval van een ideal betaling of false als het mislukt.
	 */
	public function doe_ideal( string $bericht, float $bedrag ) {
		$order = new Order( $this->losartikel->geef_referentie() );
		return $this->betalen->order(
			[
				'naam'     => $order->klant['naam'],
				'email'    => $order->klant['email'],
				'order_id' => $this->losartikel->code,
			],
			$this->losartikel->geef_referentie(),
			$bedrag,
			sprintf( 'Kleistad bestelling %s', $this->losartikel->code ),
			$bericht,
			false
		);
	}

	/**
	 * Verwerk een betaling. Wordt aangeroepen vanuit de betaal callback
	 *
	 * @since      6.2.0
	 *
	 * @param Order|null $order         De order, als die al bekend is.
	 * @param float      $bedrag        Het bedrag dat betaald is.
	 * @param bool       $betaald       Of er werkelijk betaald is.
	 * @param string     $type          Type betaling, ideal , directdebit of bank.
	 * @param string     $transactie_id De betaling id.
	 */
	public function verwerk( ?Order $order, float $bedrag, bool $betaald, string $type, string $transactie_id = '' ) {
		if ( $betaald ) {
			if ( is_object( $order ) ) {
				$this->losartikel->klant = $order->klant;
				$this->losartikel->ontvang_order( $order, $bedrag, $transactie_id );
				if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting.
					$this->losartikel->verzend_email( '_ideal_betaald' );
				}
			}
		}
	}


}
