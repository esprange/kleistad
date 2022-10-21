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

/**
 * Cursisten list table
 */
class Admin_Cursisten extends Admin_List_Table {

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
	 * Toon de kolom naam en acties
	 *
	 * @param array $item row (key, value).
	 * @return string
	 */
	public function column_naam( array $item ) : string {
		if ( $item['geannuleerd'] || $item['hoofd_cursist'] ) {
			return sprintf( '<strong>%s</strong>', $item['naam'] );
		}
		$actions = [
			'edit' => sprintf( '<a href="?page=cursisten_form&id=%s">%s</a>', $item['id'], 'Wijzigen' ),
		];
		return sprintf( '<strong>%s</strong> %s', $item['naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kolom naam
	 *
	 * @param array $item row(key, value).
	 *
	 * @return string
	 */
	public function column_medecursist( array $item ) : string {
		return $item['hoofd_cursist'] ? 'X' : '';
	}

	/**
	 * Toon de kolom geannuleerd
	 *
	 * @param array $item row (key, value).
	 * @return string
	 */
	public function column_geannuleerd( array $item ) : string {
		return $item['geannuleerd'] ? 'X' : '';
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() : array {
		return [
			'naam'        => 'Naam',
			'id'          => 'Code',
			'cursus'      => 'Cursus',
			'medecursist' => 'Medecursist',
			'geannuleerd' => 'Geannuleerd',
		];
	}

	/**
	 * Definieer de sorteerbare kolommen
	 *
	 * @return array
	 */
	public function get_sortable_columns() : array {
		return [
			'naam'        => [ 'naam', true ],
			'cursus'      => [ 'cursus', true ],
			'id'          => [ 'id', true ],
			'medecursist' => [ 'medecursist', true ],
			'geannuleerd' => [ 'geannuleerd', true ],
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
		$cursisten = [];
		$vandaag   = strtotime( 'today' );
		foreach ( new Cursisten() as $cursist ) {
			if ( ! empty( $search ) && false === stripos( $cursist->display_name, $search ) ) {
				continue;
			}
			foreach ( $cursist->inschrijvingen as $inschrijving ) {
				if ( $vandaag > $inschrijving->cursus->eind_datum ) {
					continue;
				}
				$cursisten[] = [
					'id'              => $inschrijving->code,
					'naam'            => $cursist->display_name . ( 1 < $inschrijving->aantal ? ' (' . $inschrijving->aantal . ')' : '' ),
					'cursus'          => $inschrijving->cursus->naam,
					'geannuleerd'     => $inschrijving->geannuleerd,
					'hoofd_cursist'   => $inschrijving->hoofd_cursist_id,
					'extra_cursisten' => $inschrijving->extra_cursisten,
				];
			}
		}
		usort(
			$cursisten,
			function( $links, $rechts ) use ( $orderby, $order ) {
				return ( 'asc' === $order ) ? strcasecmp( $links[ $orderby ], $rechts[ $orderby ] ) : strcasecmp( $rechts[ $orderby ], $links[ $orderby ] );
			}
		);
		return $cursisten;
	}

}
