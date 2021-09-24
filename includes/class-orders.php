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
	 * @param int|null $klant_id Geef de orders van de klant.
	 */
	public function __construct( ?int $klant_id = null ) {
		global $wpdb;
		$where = is_null( $klant_id ) ? '' : "WHERE klant_id=$klant_id";
		$data  = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_orders $where", ARRAY_A ); // phpcs:ignore
		foreach ( $data as $row ) {
			$this->orders[] = new Order( $row['id'], $row );
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
