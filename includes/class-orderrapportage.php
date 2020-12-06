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
	 * @param int $maand Het maand nummer.
	 * @param int $jaar  Het jaar nummer.
	 * @return array
	 */
	public static function maandrapportage( $maand, $jaar ) {
		global $wpdb;
		$omzet = [];
		foreach ( Artikel::$artikelen as $key => $artikel ) {
			$omzet[ $artikel['naam'] ] = [
				'netto'   => 0.0,
				'btw'     => 0.0,
				'key'     => $key,
				'details' => false,
			];
		}
		if ( strtotime( '1-1-2020' ) < mktime( 0, 0, 0, $maand + 1, 1, $jaar ) ) { // Vanaf 2020 wordt gefactureerd.
			$order_ids = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}kleistad_orders WHERE YEAR(datum) = $jaar AND MONTH(datum) = $maand ORDER BY datum", ARRAY_A ); // phpcs:ignore
			foreach ( $order_ids as $order_id ) {
				$order = new Order( intval( $order_id['id'] ) );
				$naam  = Artikel::$artikelen[ $order->referentie[0] ]['naam'];
				if ( '@' !== $order->referentie[0] ) {
					$omzet[ $naam ]['netto']  += $order->orderregels->netto();
					$omzet[ $naam ]['btw']    += $order->orderregels->btw();
					$omzet[ $naam ]['details'] = true;
				} else {
					$omzet[ $naam ]['netto']  -= $order->orderregels->netto();
					$omzet[ $naam ]['btw']    -= $order->orderregels->btw();
					$omzet[ $naam ]['details'] = true;
				}
			}
		}
		return $omzet;
	}

	/**
	 * Return de rapportage.
	 *
	 * @param int    $maand Het maand nummer.
	 * @param int    $jaar  Het jaar nummer.
	 * @param string $artikelcode De artikelcode.
	 * @return array
	 */
	public static function maanddetails( $maand, $jaar, $artikelcode ) {
		global $wpdb;
		$details   = [];
		$order_ids = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}kleistad_orders WHERE YEAR(datum) = $jaar AND MONTH(datum) = $maand AND referentie LIKE '$artikelcode%' ORDER BY datum", ARRAY_A ); // phpcs:ignore
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
