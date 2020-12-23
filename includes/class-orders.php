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
	private $orders = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 */
	public function __construct() {
		global $wpdb;
		$order_ids = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}kleistad_orders", ARRAY_A );
		foreach ( array_column( $order_ids, 'id' ) as $order_id ) {
			$this->orders[] = new Order( $order_id );
		}
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
	public function next() {
		$this->current_index++;
	}

	/**
	 * Ga terug naar het begin.
	 */
	public function rewind() {
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
