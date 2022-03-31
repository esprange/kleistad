<?php
/**
 * Definitie van de shop functies class van de plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      7.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Mollie\Api\Exceptions\ApiException;

/**
 * De kleistad class voor de shop functies.
 */
class Shop {

	/**
	 * De artikelen van de shop
	 *
	 * @var Artikelregister $register De artikelen.
	 */
	private Artikelregister $register;

	/**
	 * Initialisatie acties voor de shop
	 *
	 * @internal Action for init.
	 */
	public function shop_init() {
		$this->register = new Artikelregister( [ 'Abonnement', 'Afboeking', 'Dagdelenkaart', 'Inschrijving', 'LosArtikel', 'Saldo', 'Workshop' ] );
	}

	/**
	 * Kijk of er aanvullende acties nodig zijn bij de annulering van een order
	 *
	 * @param string $referentie Referentie naar de te annuleren order.
	 *
	 * @internal Action for kleistad_order_annulering.
	 */
	public function order_annulering( string $referentie ) {
		$artikel = $this->register->get_object( $referentie );
		if ( property_exists( $artikel, 'actie' ) && method_exists( $artikel->actie, 'afzeggen' ) ) {
			$artikel->actie->afzeggen();
		}
		$this->order_stornering( $referentie );
	}

	/**
	 * Kijk of er een terugstorting moet plaatsvinden.
	 *
	 * @param string $referentie Referentie naar de te storneren order.
	 *
	 * @internal Action for kleistad_order_stornering.
	 */
	public function order_stornering( string $referentie ) {
		$order = new Order( $referentie );
		if ( $order->transactie_id && -0.01 > $order->get_te_betalen() ) {
			// Er staat een negatief bedrag open. Dat kan worden terugbetaald.
			try {
				$betalen = new Betalen();
				$betalen->terugstorting( $order->transactie_id, $order->referentie, - $order->get_te_betalen(), 'Kleistad: zie factuur ' . $order->get_factuurnummer() );
			} catch ( ApiException $e ) {
				fout( __CLASS__, 'terugstorting niet mogelijk : ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Update betaalinfo
	 *
	 * @param int $gebruiker_id Gebruiker id.
	 *
	 * @return void
	 *
	 * @internal Action for kleistad_betaalinfo_update
	 */
	public function betaalinfo_update( int $gebruiker_id ) {
		if ( $gebruiker_id ) {
			$profiel = new Profiel();
			$profiel->reset( $gebruiker_id );
		}
	}

}
