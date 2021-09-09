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
		return [
			'naam'  => 'Naam gebruiker',
			'saldo' => 'Saldo',
		];
	}

	/**
	 * Geef de sorteerbare kolommen
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return [
			'naam'  => [ 'naam', true ],
			'saldo' => [ 'saldo', true ],
		];
	}

	/**
	 * Haal de stoker info op
	 *
	 * @param string $search Eventuele zoek parameter.
	 * @return array;
	 */
	private function geef_stokers( string $search ) : array {
		$stokers = [];
		foreach ( new Stokers() as $stoker ) {
			if ( ! empty( $search ) && false === strpos( $stoker->display_name . $stoker->user_email, $search ) ) {
				continue;
			}
			$stokers[] = [
				'id'    => $stoker->ID,
				'naam'  => $stoker->display_name,
				'saldo' => $stoker->saldo->bedrag,
			];
		}
		return $stokers;
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
		$stokers     = $this->geef_stokers( $search );
		usort(
			$stokers,
			function( $links, $rechts ) use ( $orderby, $order ) {
				if ( is_float( $links[ $orderby ] ) ) {
					return ( 'asc' === $order ) ? $links[ $orderby ] <=> $rechts[ $orderby ] : $rechts[ $orderby ] <=> $links[ $orderby ];
				}
				return ( 'asc' === $order ) ? strcasecmp( $links[ $orderby ], $rechts[ $orderby ] ) : strcasecmp( $rechts[ $orderby ], $links[ $orderby ] );
			}
		);
		$this->items = array_slice( $stokers, $paged * $per_page, $per_page, true );
		$total_items = count( $stokers );

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

}
