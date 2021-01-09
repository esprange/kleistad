<?php
/**
 * De admin-specifieke functies voor beheer van de stooksaldo.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

use WP_List_Table;
use WP_User_Query;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Beheer stooksaldo van leden
 */
class Admin_Stooksaldo extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'stooksaldo',
				'plural'   => 'stooksaldi',
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
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Toon de kolom naam inclusief acties
	 *
	 * @param object $item - row (key, value array).
	 * @return string
	 */
	public function column_naam( $item ) {
		$actions = [
			'edit' => sprintf( '<a href="?page=stooksaldo_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
		];

		return sprintf( '<strong>%s</strong> %s', $item['naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kolom saldo
	 *
	 * @param object $item - row (key, value array).
	 * @return string
	 */
	public function column_saldo( $item ) {
		return sprintf( '%.2f', $item['saldo'] );
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'naam'  => 'Naam gebruiker',
			'saldo' => 'Saldo',
		];
		return $columns;
	}

	/**
	 * Geef de sorteerbare kolommen
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = [
			'naam'  => [ 'naam', true ],
			'saldo' => [ 'saldo', true ],
		];
		return $sortable_columns;
	}

	/**
	 * Prepareer de te tonen items
	 */
	public function prepare_items() {
		$per_page = 15;

		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$search_val      = filter_input( INPUT_GET, 's' );
		$search          = ! is_null( $search_val ) ? $search_val : '';
		$paged_val       = filter_input( INPUT_GET, 'paged' );
		$paged           = ! is_null( $paged_val ) ? max( 1, intval( $paged_val ) ) : 1;
		$orderby_val     = filter_input( INPUT_GET, 'orderby' );
		$orderby         = ! is_null( $orderby_val ) && in_array( $orderby_val, array_keys( $sortable ), true ) ? $orderby_val : 'naam';
		$order_val       = filter_input( INPUT_GET, 'order' );
		$order           = ! is_null( $order_val ) && in_array( $order_val, [ 'asc', 'desc' ], true ) ? $order_val : 'asc';
		$gebruiker_query = new WP_User_Query(
			[
				'fields'   => [ 'ID', 'display_name' ],
				'search'   => '*' . $search . '*',
				'meta_key' => Saldo::META_KEY,
				'paged'    => $paged,
				'number'   => $per_page,
			]
		);
		foreach ( $gebruiker_query->get_results() as $gebruiker ) {
			$saldo         = new Saldo( $gebruiker->ID );
			$this->items[] = [
				'id'    => $gebruiker->ID,
				'naam'  => $gebruiker->display_name,
				'saldo' => $saldo->bedrag,
			];
		}
		$waarden = array_column( $this->items, $orderby );
		array_multisort( $waarden, 'asc' === $order ? SORT_ASC : SORT_DESC /** @scrutinizer ignore-type */, SORT_REGULAR /** @scrutinizer ignore-type */, $this->items ); // phpcs:ignore
		$total_items = $gebruiker_query->get_total();
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

}
