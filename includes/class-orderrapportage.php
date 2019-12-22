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
		foreach ( \Kleistad\Artikel::$artikelen as $key => $artikel ) {
			$omzet[ $artikel['naam'] ] = [
				'netto' => 0.0,
				'btw'   => 0.0,
				'key'   => $key,
			];
		}
		$options = \Kleistad\Kleistad::get_options();
		if ( strtotime( $options['factureren'] ) < mktime( 0, 0, 0, $maand + 1, 1, $jaar ) ) {
			$order_ids = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}kleistad_orders WHERE YEAR(datum) = $jaar AND MONTH(datum) = $maand ORDER BY datum", ARRAY_A ); // phpcs:ignore
			foreach ( $order_ids as $order_id ) {
				$order = new \Kleistad\Order( $order_id );
				$omzet[ \Kleistad\Artikel::$artikelen[ $order->referentie[0] ]['naam'] ]['netto'] += $order->netto();
				$omzet[ \Kleistad\Artikel::$artikelen[ $order->referentie[0] ]['naam'] ]['btw']   += $order->btw();
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
			$order     = new \Kleistad\Order( $order_id );
			$details[] = [
				'datum' => $order->datum,
				'netto' => $order->netto(),
				'btw'   => $order->btw(),
				'klant' => $order->klant['naam'],
				'code'  => $order->referentie,
			];
		}
		return $details;
	}
}
