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

use Exception;

/**
 * Abonnees list table
 */
class Admin_Abonnees extends Admin_List_Table {

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
	 * Toon de kolom soort en actie
	 *
	 * @param array $item row (key, value).
	 * @return string
	 */
	public function column_naam( array $item ) : string {
		$actions = [
			'edit'     => sprintf( '<a href="?page=abonnees_form&id=%s&actie=status">%s</a>', $item['id'], 'Wijzigen' ),
			'historie' => sprintf( '<a href="?page=abonnees_form&id=%s&actie=historie">%s</a>', $item['id'], 'Historie inzien' ),
		];
		return sprintf( '<strong>%s</strong> %s', $item['naam'], $this->row_actions( $actions ) );
	}

	/**
	 * Toon de kolom mollie en actie
	 *
	 * @param array $item row (key, value).
	 * @return string
	 */
	public function column_mollie( array $item ) : string {
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
	public function get_columns() : array {
		return [
			'naam'   => 'Naam',
			'code'   => 'Code',
			'status' => 'Status',
			'soort'  => 'Soort abonnement',
			'extras' => 'Extras',
			'mollie' => 'Incasso',
		];
	}

	/**
	 * Definieer de sorteerbare kolommen
	 *
	 * @return array
	 */
	public function get_sortable_columns() : array {
		return [
			'naam'   => [ 'naam', true ],
			'status' => [ 'status', true ],
			'soort'  => [ 'soort', true ],
			'extras' => [ 'extras', false ],
			'code'   => [ 'code', true ],
			'mollie' => [ 'mollie', true ],
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
		$abonnees = [];
		$betalen  = new Betalen();
		foreach ( new Abonnees() as $abonnee ) {
			if ( ! empty( $search ) && false === stripos( $abonnee->display_name . $abonnee->user_email, $search ) ) {
				continue;
			}
			try {
				$mandaat = $betalen->heeft_mandaat( $abonnee->ID );
			} catch ( Exception ) {
				$mandaat = false;
			}
			$abonnees[] = [
				'id'     => $abonnee->ID,
				'naam'   => $abonnee->display_name,
				'status' => $abonnee->abonnement->get_statustekst( false ),
				'soort'  => $abonnee->abonnement->soort,
				'extras' => implode( ', ', $abonnee->abonnement->extras ),
				'code'   => $abonnee->abonnement->code,
				'mollie' => ! $abonnee->abonnement->is_geannuleerd() && $mandaat,
			];
		}
		usort(
			$abonnees,
			function( $links, $rechts ) use ( $orderby, $order ) {
				return ( 'asc' === $order ) ? strcasecmp( $links[ $orderby ], $rechts[ $orderby ] ) : strcasecmp( $rechts[ $orderby ], $links[ $orderby ] );
			}
		);
		return $abonnees;
	}
}
