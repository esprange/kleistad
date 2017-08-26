<?php
/**
 * The admin-specific functionality for management of ovens of the plugin.
 *
 * @link www.sprako.nl/wordpress/eric
 * @since 4.0.0
 *
 * @package Kleistad
 * @subpackage Kleistad/admin
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Ovens list table
 */
class Kleistad_Admin_Ovens extends WP_List_Table {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct( [
			'singular' => 'oven',
			'plural' => 'ovens',
		] );
	}

	/**
	 * Set the defaults for columns
	 *
	 * @param array $item   row (key, value array).
	 * @param string $column_name  key.
	 * @return HTML
	 */
	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Render the column naam with the actions
	 *
	 * @param array $item  row (key, value array).
	 * @return HTML
	 */
	function column_naam( $item ) {
		$actions = [
			'edit' => sprintf( '<a href="?page=ovens_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
		];

		return sprintf( '%s %s', $item['naam'], $this->row_actions( $actions )
		);
	}

	/**
	 * Render the column beschikbaarheid
	 *
	 * @param array $item   row (key, value array).
	 * @return HTML
	 */
	function column_beschikbaarheid( $item ) {
		$beschikbaarheid = json_decode( $item['beschikbaarheid'], true );
		return implode( ', ', $beschikbaarheid );
	}

	/**
	 * Return the column titles
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'naam' => 'Naam',
			'kosten' => 'Tarief',
			'beschikbaarheid' => 'Beschikbaarheid',
			'id' => 'Id',
		];
		return $columns;
	}

	/**
	 * Define the sortable columns
	 *
	 * @return array
	 */
	function get_sortable_columns() {
		$sortable_columns = [
			'naam' => [ 'naam', true ],
		];
		return $sortable_columns;
	}

	/**
	 *
	 * It will get rows from database and prepare them to be showed in table
	 */
	function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'kleistad_ovens';

		$per_page = 5; // constant, how much records will be shown per page.

		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$total_items = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(id) FROM %s', $table_name ) );

		$paged = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] ) - 1 ) : 0;
		$orderby = (isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) )) ? $_REQUEST['orderby'] : 'naam';
		$order = (isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], [ 'asc', 'desc' ] )) ? $_REQUEST['order'] : 'asc';

		$this->items = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %s ORDER BY %s %s LIMIT %d OFFSET %d', $table_name, $orderby, $order, $per_page, $paged ), ARRAY_A );
		$this->set_pagination_args( [
			'total_items' => $total_items, // total items defined above.
			'per_page' => $per_page, // per page constant defined at top of method.
			'total_pages' => ceil( $total_items / $per_page ), // calculate pages count.
		] );
	}

}
