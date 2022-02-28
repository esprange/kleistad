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

namespace Kleistad;

use WP_User_Query;
/**
 * List table voor regelingen.
 */
class Admin_Regelingen extends Admin_List_Table {

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
		$this->orderby_default = 'display_name';
	}

	/**
	 * Toon de gebruiker_naam kolom met acties
	 *
	 * @param array $item - row (key, value).
	 * @return string
	 */
	public function column_gebruiker_naam( array $item ) : string {
		$actions = [
			'edit'   => sprintf( '<a href="?page=regelingen_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
			'delete' => sprintf(
				'<a class="submitdelete" href="?page=%s&action=delete&id=%s&nonce=%s">%s</a>',
				filter_input( INPUT_GET, 'page' ),
				$item['id'],
				wp_create_nonce( 'kleistad_regeling' ),
				'Verwijderen'
			),
		];

		return sprintf( '<strong>%s</strong> %s', $item['gebruiker_naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kosten kolom
	 *
	 * @param array $item - row (key, value).
	 * @return string
	 */
	public function column_kosten( array $item ) : string {
		return sprintf( '%.2f', $item['kosten'] );
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() : array {
		return [
			'gebruiker_naam' => 'Naam gebruiker',
			'oven_naam'      => 'Oven',
			'kosten'         => 'Regeling',
		];
	}

	/**
	 * Geef de sorteerbare kolommen
	 *
	 * @return array
	 */
	public function get_sortable_columns() : array {
		return [
			'gebruiker_naam' => [ 'gebruiker_naam', true ],
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
		$gebruiker_query = new WP_User_Query(
			[
				'fields'   => [ 'ID', 'display_name' ],
				'orderby'  => $orderby,
				'order'    => $order,
				'search'   => $search,
				'meta_key' => Oven::REGELING,
			]
		);
		$regelingen      = [];

		foreach ( $gebruiker_query->get_results() as $gebruiker ) {
			$gebruiker_regelingen = get_user_meta( $gebruiker->ID, Oven::REGELING, true );
			foreach ( $gebruiker_regelingen as $oven_id => $kosten_oven ) {
				$oven         = new Oven( $oven_id );
				$regelingen[] = [
					'id'             => $gebruiker->ID . '-' . $oven_id,
					'gebruiker_naam' => $gebruiker->display_name,
					'oven_naam'      => $oven->naam,
					'kosten'         => $kosten_oven,
				];
			}
		}
		return $regelingen;
	}

}
