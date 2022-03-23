<?php
/**
 * De kleistad versie van list table.
 *
 * @link https://www.kleistad.nl
 * @since 6.19.0
 *
 * @package Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

use WP_List_Table;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Admin list table
 */
abstract class Admin_List_Table extends WP_List_Table {

	/**
	 * Het aantal te tonen items per pagina.
	 *
	 * @var int $per_page Aantal items.
	 */
	protected int $per_page = 15;

	/**
	 * De sorteer volgorde.
	 *
	 * @var string $orderby_default Volgorde.
	 */
	protected string $orderby_default = 'naam';

	/**
	 * Per pagina specifieke functie voor het ophalen van de items.
	 *
	 * @param string $search  Zoekterm.
	 * @param string $order   Sorteer volgorde.
	 * @param string $orderby Element waarop gesorteerd moet worden.
	 *
	 * @return array
	 */
	abstract protected function get_items( string $search, string $order, string $orderby ) : array;

	/**
	 * Prepareer de te tonen items
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$search      = filter_input( INPUT_GET, 's' ) ?? '';
		$paged_val   = filter_input( INPUT_GET, 'paged' );
		$paged       = ! is_null( $paged_val ) ? max( 0, intval( $paged_val ) - 1 ) : 0;
		$orderby_val = filter_input( INPUT_GET, 'orderby' );
		$orderby     = in_array( $orderby_val, array_keys( $sortable ), true ) ? $orderby_val : $this->orderby_default;
		$order_val   = filter_input( INPUT_GET, 'order' );
		$order       = in_array( $order_val, [ 'asc', 'desc' ], true ) ? $order_val : 'asc';
		$items       = $this->get_items( $search, $order, $orderby );
		$this->items = array_slice( $items, $paged * $this->per_page, $this->per_page, true );
		$total_items = count( $items );
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $total_items / $this->per_page ),
			]
		);
	}

	/**
	 * Default tonen van de kolommen
	 *
	 * @param object $item - row (key, value).
	 * @param string $column_name - string (key).
	 * @return string
	 */
	public function column_default( $item, $column_name ) : string {
		return $item[ $column_name ];
	}

}
