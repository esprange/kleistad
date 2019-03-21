<?php
/**
 * De admin-specifieke functies voor beheer van regelingen.
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
 * List table voor regelingen.
 */
class Kleistad_Admin_Regelingen extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'regeling',
				'plural'   => 'regelingen',
			]
		);
	}

	/**
	 * Toon de default kolommen
	 *
	 * @param object $item - row (key, value).
	 * @param string $column_name - (key).
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Toon de gebruiker_naam kolom met acties
	 *
	 * @param object $item - row (key, value).
	 * @return string
	 */
	public function column_gebruiker_naam( $item ) {
		$actions = [
			'edit'   => sprintf( '<a href="?page=regelingen_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
			'delete' => sprintf( '<a href="?page=%s&action=delete&id=%s">%s</a>', filter_input( INPUT_GET, 'page' ), $item['id'], 'Verwijderen' ),
		];

		return sprintf( '%s %s', $item['gebruiker_naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kosten kolom
	 *
	 * @param object $item - row (key, value).
	 * @return string
	 */
	public function column_kosten( $item ) {
		return sprintf( '%.2f', $item['kosten'] );
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'gebruiker_naam' => 'Naam gebruiker',
			'oven_naam'      => 'Oven',
			'kosten'         => 'Regeling',
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
			'gebruiker_naam' => [ 'gebruiker_naam', true ],
		];
		return $sortable_columns;
	}

	/**
	 * Prepareer de te tonen items
	 */
	public function prepare_items() {
		$per_page              = 5; // constant, how much records will be shown per page.
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$paged_val             = filter_input( INPUT_GET, 'paged' );
		$paged                 = ! is_null( $paged_val ) ? max( 0, intval( $paged_val ) - 1 ) : 0;
		$order_val             = filter_input( INPUT_GET, 'order' );
		$order                 = ! is_null( $order_val ) && in_array( $order_val, [ 'asc', 'desc' ], true ) ? $order_val : 'asc';
		$gebruiker_query       = new WP_User_Query(
			[
				'fields'   => [ 'ID', 'display_name' ],
				'orderby'  => [ 'display_name' ],
				'order'    => $order,
				'meta_key' => Kleistad_Regelingen::META_KEY,
			]
		);
		$gebruikers_regelingen = new Kleistad_Regelingen();
		$ovens                 = Kleistad_Oven::all();
		$regelingen            = [];

		foreach ( $gebruiker_query->get_results() as $gebruiker ) {
			$kosten_ovens = $gebruikers_regelingen->get( $gebruiker->ID );
			foreach ( $kosten_ovens as $oven_id => $kosten_oven ) {
				$regelingen[] = [
					'id'             => $gebruiker->ID . '-' . $oven_id,
					'gebruiker_naam' => $gebruiker->display_name,
					'oven_naam'      => $ovens[ $oven_id ]->naam,
					'kosten'         => $kosten_oven,
				];
			}
		}
		$total_items = count( $regelingen );
		$this->items = array_slice( $regelingen, $paged * $per_page, $per_page, true );
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

}
