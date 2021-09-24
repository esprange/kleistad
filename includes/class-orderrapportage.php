<?php
/**
 * De definitie van de order rapportage class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Order Rapportage class.
 *
 * @since 6.1.0
 */
class Orderrapportage {

	/**
	 * Return de rapportage.
	 *
	 * @param int $maand De maand waar de rapportage op betrekking heeft.
	 * @param int $jaar  Het jaar waar de rapportage op betrekking heeft.
	 * @return array
	 */
	public function maandrapport( int $maand, int $jaar ) : array {
		global $wpdb;
		$omzet           = [];
		$artikelregister = new Artikelregister();
		foreach ( $artikelregister as $artikel ) {
			$omzet[ $artikel['naam'] ] = [
				'netto'   => 0.0,
				'btw'     => 0.0,
				'key'     => $artikel['prefix'],
				'details' => false,
			];
		}
		$maand_selectie = $maand ? "AND MONTH(datum) = $maand" : '';
		$order_ids = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}kleistad_orders WHERE YEAR(datum) = $jaar $maand_selectie ORDER BY datum", ARRAY_A ); // phpcs:ignore
		foreach ( $order_ids as $order_id ) {
			$order                     = new Order( intval( $order_id['id'] ) );
			$naam                      = $artikelregister->geef_naam( $order->referentie );
			$factor                    = '@' !== $order->referentie[0] ? 1 : -1;
			$omzet[ $naam ]['netto']  += $factor * $order->orderregels->netto();
			$omzet[ $naam ]['btw']    += $factor * $order->orderregels->btw();
			$omzet[ $naam ]['details'] = true;
		}
		return $omzet;
	}

	/**
	 * Return de rapportage.
	 *
	 * @param int    $maand       De maand waar de rapportage op betrekking heeft.
	 * @param int    $jaar        Het jaar waar de rapportage op betrekking heeft.
	 * @param string $artikelcode De artikelcode.
	 * @return array
	 */
	public function maanddetails( int $maand, int $jaar, string $artikelcode ) : array {
		global $wpdb;
		$details        = [];
		$maand_selectie = $maand ? "AND MONTH(datum) = $maand" : '';
		$order_ids = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}kleistad_orders WHERE YEAR(datum) = $jaar $maand_selectie AND referentie LIKE '$artikelcode%' ORDER BY datum", ARRAY_A ); // phpcs:ignore
		foreach ( $order_ids as $order_id ) {
			$order     = new Order( intval( $order_id['id'] ) );
			$details[] = [
				'datum' => $order->datum,
				'netto' => $order->orderregels->netto(),
				'btw'   => $order->orderregels->btw(),
				'klant' => $order->klant['naam'],
				'code'  => $order->referentie,
			];
		}
		return $details;
	}
}
