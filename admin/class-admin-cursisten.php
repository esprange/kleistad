<?php
/**
 * De admin-specific functies voor beheer cursisten.
 *
 * @link https://www.kleistad.nl
 * @since 4.5.0
 *
 * @package Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

use WP_List_Table;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Cursisten list table
 */
class Admin_Cursisten extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'cursist',
				'plural'   => 'cursisten',
			]
		);
	}

	/**
	 * Zet de defaults voor de kolommen
	 *
	 * @param object $item row (key, value).
	 * @param string $column_name key.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Toon de kolom naam en acties
	 *
	 * @param object $item row (key, value).
	 * @return string
	 */
	public function column_naam( $item ) {
		$actions = [
			'edit' => sprintf( '<a href="?page=cursisten_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
		];

		return sprintf( '<strong>%s</strong> %s', $item['naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kolom geannuleerd
	 *
	 * @param object $item row (key, value).
	 * @return string
	 */
	public function column_geannuleerd( $item ) {
		return $item['geannuleerd'];
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'naam'        => 'Naam',
			'id'          => 'Code',
			'cursus'      => 'Cursus',
			'geannuleerd' => 'Geannuleerd',
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
			'naam'        => [ 'naam', true ],
			'cursus'      => [ 'cursus', true ],
			'id'          => [ 'id', true ],
			'geannuleerd' => [ 'geannuleerd', true ],
		];
		return $sortable_columns;
	}

	/**
	 * Haal de cursisten info op
	 *
	 * @param string $search Eventuele zoek parameter.
	 * @return array;
	 */
	private function geef_cursisten( string $search ) : array {
		$cursisten = [];
		$vandaag   = strtotime( 'today' );
		foreach ( new Cursisten() as $cursist ) {
			if ( ! empty( $search ) && false === stripos( $cursist->display_name, (string) $search ) ) {
				continue;
			}
			foreach ( $cursist->inschrijvingen as $inschrijving ) {
				if ( $vandaag > $inschrijving->cursus->eind_datum ) {
					continue;
				}
				$cursisten[] = [
					'id'          => $inschrijving->code,
					'naam'        => $cursist->display_name . ( 1 < $inschrijving->aantal ? ' (' . $inschrijving->aantal . ')' : '' ),
					'cursus'      => $inschrijving->cursus->naam,
					'geannuleerd' => $inschrijving->geannuleerd ? 'X' : '',
				];
			}
		}
		return $cursisten;
	}

	/**
	 * Prepareer de te tonen items
	 */
	public function prepare_items() {
		$per_page = 25;
		$columns  = $this->get_columns();
		$hidden   = [ 'cursist_id' ];
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
		$cursisten   = $this->geef_cursisten( $search );
		usort(
			$cursisten,
			function( $links, $rechts ) use ( $orderby, $order ) {
				return ( 'asc' === $order ) ? strcmp( $links[ $orderby ], $rechts[ $orderby ] ) : strcmp( $rechts[ $orderby ], $links[ $orderby ] );
			}
		);
		$this->items = array_slice( $cursisten, $paged * $per_page, $per_page, true );
		$total_items = count( $cursisten );

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

}
