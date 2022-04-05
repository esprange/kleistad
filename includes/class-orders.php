<?php
/**
 * De definitie van de orders class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Countable;
use Iterator;

/**
 * Kleistad Orders class.
 *
 * @since 6.11.0
 */
class Orders implements Countable, Iterator {

	/**
	 * De orders
	 *
	 * @var array $orders De orders.
	 */
	private array $orders = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param array $search Selectie mogelijkheden.
	 */
	public function __construct( array $search = [] ) {
		global $wpdb;
		$where = [ 'true=true' ];
		foreach ( $search as $key => $value ) {
			$where[] = "$key='$value'";
		}
		$conditie = 'WHERE ' . implode( ' AND ', $where );
		$data  = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_orders $conditie", ARRAY_A ); // phpcs:ignore
		foreach ( $data as $row ) {
			$this->orders[] = new Order( $row['id'], $row );
		}
	}

	/**
	 * Geef het te betalen bedrag van alle orders
	 *
	 * @return array
	 */
	public function get_summary() : array {
		$summary = [
			'te_betalen'  => 0.0,
			'betaald'     => 0.0,
			'klant'       => $this->orders[0]->klant ?? [],
			'gesloten'    => true,
			'referentie'  => $this->orders[0]->referentie ?? '',
			'orderregels' => new Orderregels(),
		];
		foreach ( $this->orders as $order ) {
			$summary['te_betalen'] += $order->get_te_betalen();
			$summary['betaald']    += $order->betaald;
			if ( ! $order->gesloten ) {
				$summary['gesloten'] = false;
			}
			foreach ( $order->orderregels as $orderregel ) {
				$summary['orderregels']->toevoegen( $orderregel );
			}
		}
		return $summary;
	}

	/**
	 * Geef het aantal orders terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->orders );
	}

	/**
	 * Geef de huidige order terug.
	 *
	 * @return Order De order.
	 */
	public function current(): Order {
		return $this->orders[ $this->current_index ];
	}

	/**
	 * Geef de sleutel terug.
	 *
	 * @return int De sleutel.
	 */
	public function key(): int {
		return $this->current_index;
	}

	/**
	 * Ga naar de volgende in de lijst.
	 */
	public function next(): void {
		$this->current_index++;
	}

	/**
	 * Ga terug naar het begin.
	 */
	public function rewind(): void {
		$this->current_index = 0;
	}

	/**
	 * Bepaal of het element bestaat.
	 *
	 * @return bool Of het bestaat of niet.
	 */
	public function valid(): bool {
		return isset( $this->orders[ $this->current_index ] );
	}
}
