<?php
/**
 * De admin-specifieke functies voor het management van ovens.
 *
 * @link https://www.kleistad.nl
 * @since 4.0.87
 *
 * @package Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * Ovens list table
 */
class Admin_Ovens extends Admin_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'oven',
				'plural'   => 'ovens',
			]
		);
	}

	/**
	 * Toon de kolom naam en de acties
	 *
	 * @param array $item row (key, value).
	 * @return string
	 */
	public function column_naam( array $item ) : string {
		$actions = [
			'edit' => sprintf( '<a href="?page=ovens_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
		];

		return sprintf( '<strong>%s</strong> %s', $item['naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kolom beschikbaarheid
	 *
	 * @param array $item   row (key, value array).
	 * @return string
	 */
	public function column_beschikbaarheid( array $item ) : string {
		$beschikbaarheid = json_decode( $item['beschikbaarheid'], true );
		return implode( ', ', $beschikbaarheid );
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() : array {
		return [
			'naam'            => 'Naam',
			'kosten_laag'     => 'Laag tarief',
			'kosten_midden'   => 'Midden tarief',
			'kosten_hoog'     => 'Hoog tarief',
			'beschikbaarheid' => 'Beschikbaarheid',
			'id'              => 'Id',
		];
	}

	/**
	 * Definieer de sorteerbare kolommen
	 *
	 * @return array
	 */
	public function get_sortable_columns() : array {
		return [
			'naam' => [ 'naam', true ],
		];
	}

	/**
	 * Per pagina specifieke functie voor het ophalen van de items.
	 *
	 * @param string $search   Zoekterm.
	 * @param string $order    Sorteer volgorde.
	 * @param string $orderby Element waarop gesorteerd moet worden.
	 *
	 * @return array
	 */
	protected function geef_items( string $search, string $order, string $orderby ) : array {
		global $wpdb;
		$where = ! empty( $search ) ? "WHERE naam='$search'" : '';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_ovens %s ORDER BY %s %s", $where, $orderby, $order ), ARRAY_A );
	}

}
