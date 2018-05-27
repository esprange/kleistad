<?php
/**
 * The admin-specific functionality for management of abonnees of the plugin.
 *
 * @link www.sprako.nl/wordpress/eric
 * @since 4.3.0
 *
 * @package Kleistad
 * @subpackage Kleistad/admin
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Ovens list table
 */
class Kleistad_Admin_Abonnees extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'abonnee',
				'plural'   => 'abonnees',
			]
		);
	}

	/**
	 * Set the defaults for columns
	 *
	 * @param array  $item row (key, value).
	 * @param string $column_name key.
	 * @return HTML
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Render the column naam with the actions
	 *
	 * @param array $item row (key, value).
	 * @return HTML
	 */
	public function column_naam( $item ) {
		$actions = [
			'edit' => sprintf( '<a href="?page=abonnees_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
		];

		return sprintf(
			'%s %s', $item['naam'], $this->row_actions( $actions )
		);
	}

	/**
	 * Return the column titles
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'naam'   => 'Naam',
			'status' => 'Status',
			'soort'  => 'Soort abonnement',
			'dag'    => 'Dag',
			'code'   => 'Code',
		];
		return $columns;
	}

	/**
	 * Define the sortable columns
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = [
			'naam'   => [ 'naam', true ],
			'status' => [ 'status', true ],
			'soort'  => [ 'soort', true ],
			'dag'    => [ 'dag', true ],
			'code'   => [ 'code', true ],
		];
		return $sortable_columns;
	}

	/**
	 *
	 * It will get rows from database and prepare them to be showed in table
	 */
	public function prepare_items() {
		$per_page = 25; // constant, how much records will be shown per page.

		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$paged_val = filter_input( INPUT_GET, 'paged' );
		$paged   = ! is_null( $paged_val ) ? max( 0, intval( $paged_val ) - 1 ) : 0;
		$orderby_val = filter_input( INPUT_GET, 'orderby' );
		$orderby = ! is_null( $orderby_val ) && in_array( $orderby_val, array_keys( $sortable ), true ) ? $orderby_val : 'naam';
		$order_val = filter_input( INPUT_GET, 'order' );
		$order = ! is_null( $order_val ) && in_array( $order_val, [ 'asc', 'desc' ], true ) ? $order_val : 'asc';

		$abonnementen_store = new Kleistad_Abonnementen();
		$abonnementen = $abonnementen_store->get();

		$abonnees = [];
		foreach ( $abonnementen as $abonnee_id => $abonnement ) {
			$abonnee = get_userdata( $abonnee_id );
			$abonnees[] = [
				'id'              => $abonnee_id,
				'naam'            => $abonnee->display_name,
				'status'          => ( $abonnement->geannuleerd ? 'geannuleerd' :
										( $abonnement->gepauzeerd ? 'gepauzeerd' :
											( Kleistad_Roles::reserveer( $abonnee_id ) ? 'actief' : 'aangemeld' ) ) ),
				'soort'           => $abonnement->soort,
				'dag'             => ( 'beperkt' === $abonnement->soort ? $abonnement->dag : '' ),
				'code'            => $abonnement->code,
			];
		}
		usort(
			$abonnees, function( $a, $b ) use ( $orderby, $order ) {
				return ( 'asc' === $order ) ? strcmp( $a[ $orderby ], $b[ $orderby ] ) : strcmp( $b[ $orderby ], $a[ $orderby ] );
			}
		);
		$this->items = array_slice( $abonnees, $paged * $per_page, $per_page, true );
		$total_items = count( $abonnees );

		$this->set_pagination_args(
			[
				'total_items' => $total_items, // total items defined above.
				'per_page' => $per_page, // per page constant defined at top of method.
				'total_pages' => ceil( $total_items / $per_page ), // calculate pages count.
			]
		);
	}

}
