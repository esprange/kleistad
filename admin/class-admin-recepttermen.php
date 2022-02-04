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

/**
 * Receptermen list table
 */
class Admin_Recepttermen extends Admin_List_Table {

	/**
	 * De hoofdterm waarvoor de tabel getoond moet worden.
	 *
	 * @var int $hoofdterm_id Het id van de hoofd term
	 */
	private int $hoofdterm_id;

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
	 * Toon de kolom naam en de acties
	 *
	 * @param array $item row (key, value).
	 * @return string
	 */
	public function column_naam( array $item ) : string {
		$actions = [
			'edit'   => sprintf( '<a href="?page=recepttermen_form&id=%s&hoofdterm_id=%s">%s</a>', $item['id'], $this->hoofdterm_id, 'Wijzigen' ),
			'delete' => sprintf(
				'<a class="submitdelete" href="?page=%s&action=delete&id=%s&hoofdterm_id=%s&nonce=%s">%s</a>',
				filter_input( INPUT_GET, 'page' ),
				$item['id'],
				$this->hoofdterm_id,
				wp_create_nonce( 'kleistad_receptterm' ),
				'Verwijderen'
			),
		];
		return sprintf( '<strong>%s</strong> %s', $item['naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() : array {
		return [
			'naam'   => 'Naam',
			'aantal' => 'Aantal recepten gepubliceerd',
		];
	}

	/**
	 * Geef de kolom titels die verborgen worden.
	 *
	 * @return array
	 */
	public function get_hidden() : array {
		return [
			'id' => 'Id',
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
		$termen = [];
		foreach ( get_terms(
			[
				'taxonomy'   => Recept::CATEGORY,
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
		return $termen;
	}

}
