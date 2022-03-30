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
	 * @param Order $order De te annuleren order.
	 *
	 * @internal Action for kleistad_annuleer_order.
	 */
	public function order_annulering( Order $order ) {
		$artikel = $this->register->get_object( $order->referentie );
		if ( property_exists( $artikel, 'actie' ) && method_exists( $artikel->actie, 'afzeggen' ) ) {
			$artikel->actie->afzeggen();
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
