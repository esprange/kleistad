<?php
/**
 * De admin-specifieke functies voor beheer van de werkplek configuraties.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
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
class Admin_Werkplekken extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'werkplekconfiguratie',
				'plural'   => 'werkplekconfiguraties',
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
	 * Toon de kolom start_datum inclusief acties
	 *
	 * @param object $item - row (key, value array).
	 * @return string
	 */
	public function column_start_datum( $item ) {
		$actions = [
			'edit'   => sprintf( '<a href="?page=werkplekken_form&start_datum=%s&eind_datum=%s">%s</a>', $item['start_datum'], $item['eind_datum'], 'Wijzigen' ),
			'copy'   => sprintf( '<a href="?page=werkplekken_form&action=copy&start_datum=%s&eind_datum=%s">%s</a>', $item['start_datum'], $item['eind_datum'], 'Kopiëren' ),
			'delete' => sprintf(
				'<a href="?page=%s&action=delete&start_datum=%s&eind_datum=%s&nonce=%s" class="submitdelete">%s</a>',
				filter_input( INPUT_GET, 'page' ),
				$item['start_datum'],
				$item['eind_datum'],
				wp_create_nonce( 'kleistad_werkplek' ),
				'Verwijderen'
			),
		];
		return sprintf( '<strong>%s</strong> %s', date( 'd-m-Y', $item['start_datum'] ), $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kolom eind datum
	 *
	 * @param object $item - row (key, value array).
	 * @return string
	 */
	public function column_eind_datum( $item ) {
		return $item['eind_datum'] ? date( 'd-m-Y', $item['eind_datum'] ) : 'heden';
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() {
		return [
			'start_datum' => 'Start datum',
			'eind_datum'  => 'Eind datum',
			'werkplekken' => 'Aantal werkplekken',
		];
	}

	/**
	 * Geef de sorteerbare kolommen
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return [
			'start_datum' => [ 'start_datum', true ],
			'eind_datum'  => [ 'eind_datum', true ],
		];
	}

	/**
	 * Geef de werkplek configuraties
	 */
	private function geef_werkplek_configs() : array {
		$werkplekconfigs = [];
		$vandaag         = strtotime( 'today' );
		foreach ( new WerkplekConfigs() as $werkplekconfig ) {
			$werkplekken = 0;
			if ( $werkplekconfig->eind_datum && $vandaag > $werkplekconfig->eind_datum ) {
				continue;
			}
			foreach ( $werkplekconfig->config as $atelierdag ) {
				foreach ( $atelierdag as $dagdeel ) {
					$werkplekken += array_sum( $dagdeel );
				}
			}
			$werkplekconfigs[] = [
				'start_datum' => $werkplekconfig->start_datum,
				'eind_datum'  => $werkplekconfig->eind_datum,
				'werkplekken' => $werkplekken,
			];
		}
		return $werkplekconfigs;
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

		$paged_val   = filter_input( INPUT_GET, 'paged' );
		$paged       = ! is_null( $paged_val ) ? max( 0, intval( $paged_val - 1 ) ) : 0;
		$orderby_val = filter_input( INPUT_GET, 'orderby' );
		$orderby     = ! is_null( $orderby_val ) && in_array( $orderby_val, array_keys( $sortable ), true ) ? $orderby_val : 'start_datum';
		$order_val   = filter_input( INPUT_GET, 'order' );
		$order       = ! is_null( $order_val ) && in_array( $order_val, [ 'asc', 'desc' ], true ) ? $order_val : 'asc';

		$werkplekconfigs = $this->geef_werkplek_configs();
		usort(
			$werkplekconfigs,
			function( $links, $rechts ) use ( $orderby, $order ) {
				return ( 'asc' === $order ) ? strcmp( $links[ $orderby ], $rechts[ $orderby ] ) : strcmp( $rechts[ $orderby ], $links[ $orderby ] );
			}
		);
		$this->items = array_slice( $werkplekconfigs, $paged * $per_page, $per_page, true );
		$this->items = $werkplekconfigs;
		$total_items = count( $werkplekconfigs );
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

}
