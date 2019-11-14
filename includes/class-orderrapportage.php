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
		$omzet      = [];
		$omzetnamen = [
			'A' => 'abonnement',
			'C' => 'cursus',
			'K' => 'dagdelenkaart',
			'S' => 'stook',
			'W' => 'workshop',
		];
		foreach ( array_keys( $omzetnamen ) as $key ) {
			$omzet[ $omzetnamen[ $key ] ] = [
				'netto' => 0.0,
				'btw'   => 0.0,
			];
		}
		$order_ids = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}kleistad_orders WHERE YEAR(datum) = $jaar AND MONTH(datum) = $maand ORDER BY datum", ARRAY_A ); // phpcs:ignore
		foreach ( $order_ids as $order_id ) {
			$order = new \Kleistad\Order( $order_id );
			$omzet[ $omzetnamen[ $order->referentie[0] ] ]['netto'] += $order->netto();
			$omzet[ $omzetnamen[ $order->referentie[0] ] ]['btw']   += $order->btw();
		}
		return $omzet;
	}

}
