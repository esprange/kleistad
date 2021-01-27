<?php
/**
 * De admin-specific functies voor beheer abonnees.
 *
 * @link https://www.kleistad.nl
 * @since 4.3.0
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
 * Abonnees list table
 */
class Admin_Abonnees extends WP_List_Table {

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
	 * Toon de kolom soort en actie
	 *
	 * @param object $item row (key, value).
	 * @return string
	 */
	public function column_naam( $item ) {
		$actions = [
			'edit'     => sprintf( '<a href="?page=abonnees_form&id=%s&actie=status">%s</a>', $item['id'], 'Wijzigen' ),
			'historie' => sprintf( '<a href="?page=abonnees_form&id=%s&actie=historie">%s</a>', $item['id'], 'Historie inzien' ),
		];
		return sprintf( '<strong>%s</strong> %s', $item['naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kolom mollie en actie
	 *
	 * @param object $item row (key, value).
	 * @return string
	 */
	public function column_mollie( $item ) {
		if ( $item['mollie'] ) {
			$actions = [
				'edit' => sprintf( '<a href="?page=abonnees_form&id=%s&actie=mollie">%s</a>', $item['id'], 'Wijzigen' ),
			];
			return sprintf( 'ja %s', $this->row_actions( $actions ) );
		}
		return 'nee';
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'naam'   => 'Naam',
			'code'   => 'Code',
			'status' => 'Status',
			'soort'  => 'Soort abonnement',
			'extras' => 'Extras',
			'mollie' => 'Incasso',
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
			'naam'   => [ 'naam', true ],
			'status' => [ 'status', true ],
			'soort'  => [ 'soort', true ],
			'extras' => [ 'extras', false ],
			'code'   => [ 'code', true ],
			'mollie' => [ 'mollie', true ],
		];
		return $sortable_columns;
	}

	/**
	 * Haal de abonnee infom op
	 *
	 * @param string $search Eventuele zoek parameter.
	 * @return array;
	 */
	private function geef_abonnees( string $search ) : array {
		$abonnees = [];
		$betalen  = new Betalen();
		foreach ( new Abonnees() as $abonnee ) {
			if ( ! empty( $search ) && false === strpos( $abonnee->display_name . $abonnee->user_email, $search ) ) {
				continue;
			}
			$abonnees[] = [
				'id'     => $abonnee->ID,
				'naam'   => $abonnee->display_name,
				'status' => $abonnee->abonnement->geef_statustekst( false ),
				'soort'  => $abonnee->abonnement->soort,
				'dag'    => ( 'beperkt' === $abonnee->abonnement->soort ? $abonnee->abonnement->dag : '' ),
				'extras' => implode( ', ', $abonnee->abonnement->extras ),
				'code'   => $abonnee->abonnement->code,
				'mollie' => $betalen->heeft_mandaat( $abonnee->ID ),
			];
		}
		return $abonnees;
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
		$abonnees    = $this->geef_abonnees( $search );
		usort(
			$abonnees,
			function( $links, $rechts ) use ( $orderby, $order ) {
				return ( 'asc' === $order ) ? strcmp( $links[ $orderby ], $rechts[ $orderby ] ) : strcmp( $rechts[ $orderby ], $links[ $orderby ] );
			}
		);
		$this->items = array_slice( $abonnees, $paged * $per_page, $per_page, true );
		$total_items = count( $abonnees );

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

}
