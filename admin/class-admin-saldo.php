<?php
/**
 * De admin-specifieke functies voor beheer van de saldo.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * Beheer saldo van leden
 */
class Admin_Saldo extends Admin_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'saldo',
				'plural'   => 'stooksaldi',
			]
		);
	}

	/**
	 * Toon de kolom naam inclusief acties
	 *
	 * @param array $item - row (key, value array).
	 * @return string
	 */
	public function column_naam( array $item ) : string {
		$actions = [
			'edit' => sprintf( '<a href="?page=saldo_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
		];

		return sprintf( '<strong>%s</strong> %s', $item['naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kolom saldo
	 *
	 * @param array $item - row (key, value array).
	 * @return string
	 */
	public function column_saldo( array $item ) : string {
		return sprintf( '%.2f', $item['saldo'] );
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() : array {
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
	public function get_sortable_columns() : array {
		return [
			'naam'  => [ 'naam', true ],
			'saldo' => [ 'saldo', true ],
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
	protected function get_items( string $search, string $order, string $orderby ) : array {
		$stokers = [];
		foreach ( new Stokers() as $stoker ) {
			if ( ! empty( $search ) && false === stripos( $stoker->display_name . $stoker->user_email, $search ) ) {
				continue;
			}
			$stokers[] = [
				'id'    => $stoker->ID,
				'naam'  => $stoker->display_name,
				'saldo' => $stoker->saldo->bedrag,
			];
		}
		usort(
			$stokers,
			function( $links, $rechts ) use ( $orderby, $order ) {
				if ( is_float( $links[ $orderby ] ) ) {
					return ( 'asc' === $order ) ? $links[ $orderby ] <=> $rechts[ $orderby ] : $rechts[ $orderby ] <=> $links[ $orderby ];
				}
				return ( 'asc' === $order ) ? strcasecmp( $links[ $orderby ], $rechts[ $orderby ] ) : strcasecmp( $rechts[ $orderby ], $links[ $orderby ] );
			}
		);
		return $stokers;
	}

}
