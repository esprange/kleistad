<?php
/**
 * The admin-specific functionality for management of stooksaldo of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Beheer stooksaldo van leden
 */
class Kleistad_Admin_Stooksaldo extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'stooksaldo',
				'plural' => 'stooksaldi',
			]
		);
	}

	/**
	 * Default rendering for columns
	 *
	 * @param array  $item - row (key, value).
	 * @param string $column_name - string (key).
	 * @return HTML
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Render the column naam
	 *
	 * @param array $item - row (key, value array).
	 * @return HTML
	 */
	public function column_naam( $item ) {
		$actions = [
			'edit' => sprintf( '<a href="?page=stooksaldo_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
		];

		return sprintf(
			'%s %s', $item['naam'], $this->row_actions( $actions )
		);
	}

	/**
	 * Retrieve the column titles
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'naam' => 'Naam gebruiker',
			'saldo' => 'Saldo',
		];
		return $columns;
	}

	/**
	 * Retrieve the sortable columns
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = [
			'naam' => [ 'naam', true ],
			'saldo' => [ 'saldo', false ],
		];
		return $sortable_columns;
	}

	/**
	 *
	 * It will get rows from database and prepare them to be showed in table
	 */
	public function prepare_items() {
		$per_page = 5;

		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$search_val = filter_input( INPUT_GET, 's' );
		$search = ! is_null( $search_val ) ? $search_val : false;
		$paged_val = filter_input( INPUT_GET, 'paged' );
		$paged   = ! is_null( $paged_val ) ? max( 0, intval( $paged_val ) - 1 ) : 0;
		$orderby_val = filter_input( INPUT_GET, 'orderby' );
		$orderby = ! is_null( $orderby_val ) && in_array( $orderby_val, array_keys( $sortable ), true ) ? $orderby_val : 'naam';
		$order_val = filter_input( INPUT_GET, 'order' );
		$order = ! is_null( $order_val ) && in_array( $order_val, [ 'asc', 'desc' ], true ) ? $order_val : 'asc';

		$gebruikers = get_users(
			[
				'fields' => [ 'id', 'display_name' ],
				'meta_key' => 'stooksaldo',
				'orderby' => [ 'display_name' ],
				'order' => $order,
				'search' => '*' . $search . '*',
			]
		);

		$stooksaldi = [];

		foreach ( $gebruikers as $gebruiker ) {
			$stooksaldi[] = [
				'id' => $gebruiker->id,
				'naam' => $gebruiker->display_name,
				'saldo' => get_user_meta( $gebruiker->id, 'stooksaldo', true ),
			];
		}
		if ( 'naam' !== $orderby ) {
			$bedrag = [];
			foreach ( $stooksaldi as $key => $saldo ) {
				$bedrag[ $key ] = $saldo['saldo'];
			}
			array_multisort( $bedrag, constant( 'SORT_' . strtoupper( $order ) ), $stooksaldi );
		}
		$total_items = count( $stooksaldi );
		$this->items = array_slice( $stooksaldi, $paged * $per_page, $per_page, true );
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page' => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

}
