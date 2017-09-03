<?php
/**
 * The admin-specific functionality for management of stooksaldo of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
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
	function __construct() {
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
	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Render the column naam
	 *
	 * @param array $item - row (key, value array).
	 * @return HTML
	 */
	function column_naam( $item ) {
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
	function get_columns() {
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
	function get_sortable_columns() {
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
	function prepare_items() {
		$per_page = 5;

		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$paged = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] ) - 1 ) : 0;
		$orderby = (isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) )) ? $_REQUEST['orderby'] : 'naam';
		$order = (isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], [ 'asc', 'desc' ] )) ? $_REQUEST['order'] : 'asc';

		$gebruikers = get_users(
			[
				'fields' => [ 'id', 'display_name' ],
				'meta_key' => 'stooksaldo',
				'orderby' => [ 'display_name' ],
				'order' => $order,
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
		if ( 'naam' == $orderby ) {
		} else {
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
