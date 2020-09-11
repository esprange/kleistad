<?php
/**
 * De admin-specifieke functies voor het management van recept termen.
 *
 * @link https://www.kleistad.nl
 * @since 6.4.0
 *
 * @package Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Receptermen list table
 */
class Admin_Recepttermen extends \WP_List_Table {

	/**
	 * De hoofdterm waarvoor de tabel getoond moet worden.
	 *
	 * @var int $hoofdterm_id Het id van de hoofd term
	 */
	private $hoofdterm_id;

	/**
	 * Constructor
	 *
	 * @param int $hoofdterm_id De parent van de termen.
	 */
	public function __construct( $hoofdterm_id ) {
		$this->hoofdterm_id = intval( $hoofdterm_id );
		parent::__construct(
			[
				'singular' => 'term',
				'plural'   => 'termen',
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
			'edit'   => sprintf( '<a href="?page=recepttermen_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
			'delete' => sprintf( '<a class="submitdelete" href="?page=%s&action=delete&id=%s">%s</a>', filter_input( INPUT_GET, 'page' ), $item['id'], 'Verwijderen' ),
		];
		return sprintf( '<strong>%s</strong> %s', $item['naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'naam'   => 'Naam',
			'aantal' => 'Aantal recepten gepubliceerd',
		];
		return $columns;
	}

	/**
	 * Geef de kolom titels die verborgen worden.
	 *
	 * @return array
	 */
	public function get_hidden() {
		$columns = [
			'id' => 'Id',
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

		$per_page = 10;

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$paged_val   = filter_input( INPUT_GET, 'paged' );
		$paged       = ! is_null( $paged_val ) ? max( 0, intval( $paged_val ) - 1 ) : 0;
		$orderby_val = filter_input( INPUT_GET, 'orderby' );
		$orderby     = ! is_null( $orderby_val ) && in_array( $orderby_val, array_keys( $sortable ), true ) ? $orderby_val : 'naam';
		$order_val   = filter_input( INPUT_GET, 'order' );
		$order       = ! is_null( $order_val ) && in_array( $order_val, [ 'asc', 'desc' ], true ) ? $order_val : 'asc';
		$termen      = [];
		foreach ( get_terms(
			[
				'taxonomy'   => \Kleistad\Recept::CATEGORY,
				'orderby'    => $orderby,
				'order'      => strtoupper( $order ),
				'hide_empty' => false,
				'parent'     => $this->hoofdterm_id,
			]
		) as $term ) {
			$termen[] = [
				'naam'   => $term->name,
				'id'     => $term->term_id,
				'aantal' => $term->count,
			];
		}
		$this->items = array_slice( $termen, $paged * $per_page, $per_page, true );
		$total_items = count( $termen );
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

}
