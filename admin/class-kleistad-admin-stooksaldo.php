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

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
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
	 * @suppress PhanTypeArraySuspicious
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Toon de kolom naam inclusief acties
	 *
	 * @param object $item - row (key, value array).
	 * @return string
	 * @suppress PhanTypeArraySuspicious
	 */
	public function column_naam( $item ) {
		$actions = [
			'edit' => sprintf( '<a href="?page=stooksaldo_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
		];

		return sprintf( '%s %s', $item['naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kolom saldo
	 *
	 * @param object $item - row (key, value array).
	 * @return string
	 * @suppress PhanTypeArraySuspicious
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
			'saldo' => [ 'saldo', false ],
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

		$search_val  = filter_input( INPUT_GET, 's' );
		$search      = ! is_null( $search_val ) ? $search_val : '';
		$paged_val   = filter_input( INPUT_GET, 'paged' );
		$paged       = ! is_null( $paged_val ) ? max( 0, intval( $paged_val ) - 1 ) : 0;
		$orderby_val = filter_input( INPUT_GET, 'orderby' );
		$orderby     = ! is_null( $orderby_val ) && in_array( $orderby_val, array_keys( $sortable ), true ) ? $orderby_val : 'naam';
		$order_val   = filter_input( INPUT_GET, 'order' );
		$order       = ! is_null( $order_val ) && in_array( $order_val, [ 'asc', 'desc' ], true ) ? $order_val : 'asc';

		$gebruikers = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => [ 'display_name' ],
				'order'   => $order,
				'search'  => '*' . $search . '*',
			]
		);

		$stooksaldi = [];

		foreach ( $gebruikers as $gebruiker ) {
			if ( Kleistad_Roles::reserveer( $gebruiker->ID ) ) {
				$saldo        = new Kleistad_Saldo( $gebruiker->ID );
				$stooksaldi[] = [
					'id'    => $gebruiker->ID,
					'naam'  => $gebruiker->display_name,
					'saldo' => $saldo->bedrag,
				];
			}
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
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

}
