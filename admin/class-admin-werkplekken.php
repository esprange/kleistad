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

/**
 * Beheer stooksaldo van leden
 */
class Admin_Werkplekken extends Admin_List_Table {

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
		$this->orderby_default = 'start_datum';
	}

	/**
	 * Toon de kolom start_datum inclusief acties
	 *
	 * @param array $item - row (key, value array).
	 * @return string
	 */
	public function column_start_datum( array $item ) : string {
		$actions = [
			'edit'   => sprintf( '<a href="?page=werkplekken_form&start_datum=%s&eind_datum=%s">%s</a>', $item['start_datum'], $item['eind_datum'], 'Wijzigen' ),
			'copy'   => sprintf( '<a href="?page=werkplekken_form&action=copy&start_datum=%s&eind_datum=%s">%s</a>', $item['start_datum'], $item['eind_datum'], 'Kopiëren' ),
			'delete' => sprintf(
				'<a class="submitdelete" href="?page=%s&action=delete&start_datum=%s&eind_datum=%s&nonce=%s" >%s</a>',
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
	 * @param array $item - row (key, value array).
	 * @return string
	 */
	public function column_eind_datum( array $item ) : string {
		return $item['eind_datum'] ? date( 'd-m-Y', $item['eind_datum'] ) : 'heden';
	}

	/**
	 * Geef de kolom titels
	 *
	 * @return array
	 */
	public function get_columns() : array {
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
	public function get_sortable_columns() : array {
		return [
			'start_datum' => [ 'start_datum', true ],
			'eind_datum'  => [ 'eind_datum', true ],
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
		usort(
			$werkplekconfigs,
			function( $links, $rechts ) use ( $orderby, $order ) {
				return ( 'asc' === $order ) ? strcmp( $links[ $orderby ], $rechts[ $orderby ] ) : strcmp( $rechts[ $orderby ], $links[ $orderby ] );
			}
		);
		return $werkplekconfigs;
	}

}
