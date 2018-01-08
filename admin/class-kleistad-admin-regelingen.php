<?php
/**
 * The admin-specific functionality for management of regelingen of the plugin.
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
 * List table for regelingen.
 */
class Kleistad_Admin_Regelingen extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'regeling',
				'plural' => 'regelingen',
			]
		);
	}

	/**
	 * Render default columns
	 *
	 * @param array  $item - row (key, value).
	 * @param string $column_name - (key).
	 * @return HTML
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Render the gebruiker_naam column with action
	 *
	 * @param array $item - row (key, value).
	 * @return HTML
	 */
	public function column_gebruiker_naam( $item ) {
		$actions = [
			'edit' => sprintf( '<a href="?page=regelingen_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
			'delete' => sprintf( '<a href="?page=%s&action=delete&id=%s">%s</a>', filter_input( INPUT_GET, 'page' ), $item['id'], 'Verwijderen' ),
		];

		return sprintf(
			'%s %s', $item['gebruiker_naam'], $this->row_actions( $actions )
		);
	}

	/**
	 * Get the column titles
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'gebruiker_naam' => 'Naam gebruiker',
			'oven_naam' => 'Oven',
			'kosten' => 'Regeling',
		];
		return $columns;
	}

	/**
	 * Get the sortable columns
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = [
			'gebruiker_naam' => [ 'gebruiker_naam', true ],
		];
		return $sortable_columns;
	}

	/**
	 * Prepare the items
	 * It will get rows from database and prepare them to be showed in table
	 */
	public function prepare_items() {
		$per_page = 5; // constant, how much records will be shown per page.

		$columns = $this->get_columns();
		$hidden = [];
		$sortable = $this->get_sortable_columns();

		// here we configure table headers, defined in our methods.
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		// prepare query params, as usual current page, order by and order direction.
		$paged_val = filter_input( INPUT_GET, 'paged' );
		$paged   = ! is_null( $paged_val ) ? max( 0, intval( $paged_val ) - 1 ) : 0;
		$orderby_val = filter_input( INPUT_GET, 'orderby' );
		$orderby = ! is_null( $orderby_val ) && in_array( $orderby_val, array_keys( $sortable ), true ) ? $orderby_val : 'naam';
		$order_val = filter_input( INPUT_GET, 'order' );
		$order = ! is_null( $order_val ) && in_array( $order_val, [ 'asc', 'desc' ], true ) ? $order_val : 'asc';

		// will be used in pagination settings.
		$gebruikers = get_users(
			[
				'fields' => [
					'id',
					'display_name',
				],
				'orderby' => [
					'display_name',
				],
				'order' => $order,
			]
		);

		$gebruikers_regelingen = new Kleistad_Regelingen();

		$ovens_store = new Kleistad_Ovens();
		$ovens = $ovens_store->get();
		$regelingen = [];

		foreach ( $gebruikers as $gebruiker ) {
			$kosten_ovens = $gebruikers_regelingen->get( $gebruiker->id );
			if ( is_null( $kosten_ovens ) ) {
				continue;
			}
			foreach ( $kosten_ovens as $oven_id => $kosten_oven ) {
				$regelingen[] = [
					'id' => $gebruiker->id . ' ' . $oven_id,
					'gebruiker_naam' => $gebruiker->display_name,
					'oven_naam' => $ovens[ $oven_id ]->naam,
					'kosten' => $kosten_oven,
				];
			}
		}
		$total_items = count( $regelingen );

		$this->items = array_slice( $regelingen, $paged, $per_page, true );
		$this->set_pagination_args(
			[
				'total_items' => $total_items, // total items defined above.
			'per_page' => $per_page, // per page constant defined at top of method.
			'total_pages' => ceil( $total_items / $per_page ), // calculate pages count.
			]
		);
	}

}
