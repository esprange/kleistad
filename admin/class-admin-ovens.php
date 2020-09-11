<?php
/**
 * De admin-specifieke functies voor het management van ovens.
 *
 * @link https://www.kleistad.nl
 * @since 4.0.87
 *
 * @package Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Ovens list table
 */
class Admin_Ovens extends \WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'oven',
				'plural'   => 'ovens',
			]
		);
	}

	/**
	 * De defaults voor de kolommen
	 *
	 * @param object $item row (key, value).
	 * @param string $column_name key.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Toon de kolom naam en de acties
	 *
	 * @param object $item row (key, value).
	 * @return string
	 */
	public function column_naam( $item ) {
		$actions = [
			'edit' => sprintf( '<a href="?page=ovens_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
		];

		return sprintf( '<strong>%s</strong> %s', $item['naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kolom beschikbaarheid
	 *
	 * @param object $item   row (key, value array).
	 * @return string
	 */
	public function column_beschikbaarheid( $item ) {
		$beschikbaarheid = json_decode( $item['beschikbaarheid'], true );
		return implode( ', ', $beschikbaarheid );
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'naam'            => 'Naam',
			'kosten'          => 'Tarief',
			'beschikbaarheid' => 'Beschikbaarheid',
			'id'              => 'Id',
		];
		return $columns;
	}

	/**
	 * Definieer de sorteerbare kolommen
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = [
			'naam' => [ 'naam', true ],
		];
		return $sortable_columns;
	}

	/**
	 * Prepareer de te tonen items
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page = 5;

		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}kleistad_ovens" ); // phpcs:ignore

		$paged_val   = filter_input( INPUT_GET, 'paged' );
		$paged       = ! is_null( $paged_val ) ? max( 0, (int) $paged_val - 1 ) : 0;
		$orderby_val = filter_input( INPUT_GET, 'orderby' );
		$orderby     = ! is_null( $orderby_val ) && in_array( $orderby_val, array_keys( $sortable ), true ) ? $orderby_val : 'naam';
		$order_val   = filter_input( INPUT_GET, 'order' );
		$order       = ! is_null( $order_val ) && in_array( $order_val, [ 'asc', 'desc' ], true ) ? $order_val : 'asc';
		$this->items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_ovens ORDER BY $orderby $order LIMIT $per_page OFFSET $paged", ARRAY_A ); // phpcs:ignore
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

}
