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

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Ovens list table
 */
class Kleistad_Admin_Cursisten extends WP_List_Table {

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
	 * @suppress PhanTypeArraySuspicious
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Toon de kolom naam en acties
	 *
	 * @param object $item row (key, value).
	 * @return string
	 * @suppress PhanTypeArraySuspicious
	 */
	public function column_naam( $item ) {
		$actions = [
			'edit' => sprintf( '<a href="?page=cursisten_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
		];

		return sprintf( '%s %s', $item['naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kolom i_betaald
	 *
	 * @param object $item row (key, value).
	 * @return string
	 * @suppress PhanTypeArraySuspicious
	 */
	public function column_i_betaald( $item ) {
		return $item['i_betaald'];
	}

	/**
	 * Toon de kolom c_betaald
	 *
	 * @param object $item row (key, value).
	 * @return string
	 * @suppress PhanTypeArraySuspicious
	 */
	public function column_c_betaald( $item ) {
		return $item['c_betaald'];
	}

	/**
	 * Toon de kolom geannuleerd
	 *
	 * @param object $item row (key, value).
	 * @return string
	 * @suppress PhanTypeArraySuspicious
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
			'cursus'      => 'Cursus',
			'id'          => 'Code',
			'i_betaald'   => 'Inschrijving betaald',
			'c_betaald'   => 'Cursus betaald',
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
			'i_betaald'   => [ 'i_betaald', true ],
			'c_betaald'   => [ 'c_betaald', true ],
			'geannuleerd' => [ 'geannuleerd', true ],
		];
		return $sortable_columns;
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

		$search_val     = filter_input( INPUT_GET, 's' );
		$search         = ! is_null( $search_val ) ? $search_val : '';
		$paged_val      = filter_input( INPUT_GET, 'paged' );
		$paged          = ! is_null( $paged_val ) ? max( 0, intval( $paged_val ) - 1 ) : 0;
		$orderby_val    = filter_input( INPUT_GET, 'orderby' );
		$orderby        = ! is_null( $orderby_val ) && in_array( $orderby_val, array_keys( $sortable ), true ) ? $orderby_val : 'naam';
		$order_val      = filter_input( INPUT_GET, 'order' );
		$order          = ! is_null( $order_val ) && in_array( $order_val, [ 'asc', 'desc' ], true ) ? $order_val : 'asc';
		$inschrijvingen = Kleistad_Inschrijving::all();
		$cursussen      = Kleistad_Cursus::all( true );
		$cursisten      = [];

		foreach ( $inschrijvingen as $cursist_id => $inschrijving ) {
			foreach ( $cursussen as $cursus_id => $cursus ) {
				if ( array_key_exists( $cursus_id, $inschrijving ) ) {
					$cursist = get_userdata( $cursist_id );
					if ( ! empty( $search ) && false === strpos( $cursist->display_name, $search ) ) {
						continue;
					}
					$cursisten[] = [
						'id'          => $inschrijving[ $cursus_id ]->code,
						'naam'        => $cursist->display_name . ( 1 < $inschrijving[ $cursus_id ]->aantal ? ' (' . $inschrijving[ $cursus_id ]->aantal . ')' : '' ),
						'cursus'      => $cursus->naam,
						'i_betaald'   => $inschrijving[ $cursus_id ]->i_betaald ? 'X' : '',
						'c_betaald'   => $inschrijving[ $cursus_id ]->c_betaald ? 'X' : '',
						'geannuleerd' => $inschrijving[ $cursus_id ]->geannuleerd ? 'X' : '',
					];
				}
			}
		}
		usort(
			$cursisten,
			function( $a, $b ) use ( $orderby, $order ) {
				return ( 'asc' === $order ) ? strcmp( $a[ $orderby ], $b[ $orderby ] ) : strcmp( $b[ $orderby ], $a[ $orderby ] );
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
