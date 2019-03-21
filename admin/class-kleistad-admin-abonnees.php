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
	public function column_soort( $item ) {
		$actions = [
			'edit' => sprintf( '<a href="?page=abonnees_form&id=%s&actie=soort">%s</a>', $item['id'], 'Wijzigen' ),
		];
		$soort   = $item['soort'] . ( 'beperkt' === $item['soort'] ? ' (' . $item['dag'] . ')' : '' );
		return sprintf( '%s %s', $soort, $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kolom extras en actie
	 *
	 * @param object $item row (key, value).
	 * @return string
	 */
	public function column_extras( $item ) {
		$actions = [
			'edit' => sprintf( '<a href="?page=abonnees_form&id=%s&actie=extras">%s</a>', $item['id'], 'Wijzigen' ),
		];
		return sprintf( '%s %s', $item['extras'], $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kolom status en acties
	 *
	 * @param object $item row (key, value).
	 * @return string
	 */
	public function column_status( $item ) {
		$actions = [
			'edit' => sprintf( '<a href="?page=abonnees_form&id=%s&actie=status">%s</a>', $item['id'], 'Wijzigen' ),
		];
		return sprintf( '%s %s', $item['status'], $this->row_actions( $actions ) );
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
		} else {
			return 'nee';
		}
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'code'   => 'Code',
			'naam'   => 'Naam',
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
	 * Prepareer de te tonen items
	 */
	public function prepare_items() {
		$per_page = 15;
		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$search_val   = filter_input( INPUT_GET, 's' );
		$search       = ! is_null( $search_val ) ? $search_val : '';
		$paged_val    = filter_input( INPUT_GET, 'paged' );
		$paged        = ! is_null( $paged_val ) ? max( 0, intval( $paged_val ) - 1 ) : 0;
		$orderby_val  = filter_input( INPUT_GET, 'orderby' );
		$orderby      = ! is_null( $orderby_val ) && in_array( $orderby_val, array_keys( $sortable ), true ) ? $orderby_val : 'naam';
		$order_val    = filter_input( INPUT_GET, 'order' );
		$order        = ! is_null( $order_val ) && in_array( $order_val, [ 'asc', 'desc' ], true ) ? $order_val : 'asc';
		$abonnementen = Kleistad_Abonnement::all( $search );
		$abonnees     = [];

		foreach ( $abonnementen as $abonnee_id => $abonnement ) {
			$abonnee    = get_userdata( $abonnee_id );
			$abonnees[] = [
				'id'     => $abonnee_id,
				'naam'   => $abonnee->display_name,
				'status' => $abonnement->status(),
				'soort'  => $abonnement->soort,
				'dag'    => ( 'beperkt' === $abonnement->soort ? $abonnement->dag : '' ),
				'extras' => implode( ', ', $abonnement->extras ),
				'code'   => $abonnement->code,
				'mollie' => ( '' !== $abonnement->subscriptie_id ),
			];
		}
		usort(
			$abonnees,
			function( $a, $b ) use ( $orderby, $order ) {
				return ( 'asc' === $order ) ? strcmp( $a[ $orderby ], $b[ $orderby ] ) : strcmp( $b[ $orderby ], $a[ $orderby ] );
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
