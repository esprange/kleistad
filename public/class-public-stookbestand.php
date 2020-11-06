<?php
/**
 * Shortcode stookbestand (voor bestuur).
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad stookbestand class.
 */
class Public_Stookbestand extends Shortcode {

	/**
	 * Array dat alle medestokers weergeeft in periode
	 *
	 * @var array $medestokers De stokers inclusief hun naam.
	 */
	private $medestokers = [];

	/**
	 * Array dat alle ovens bevat
	 *
	 * @var array $ovens De ovens met o.a. hun kosten.
	 */
	private $ovens = [];

	/**
	 * De vanaf datum van het stookbestand
	 *
	 * @var int $vanaf_datum de begindatum van het stookbestand.
	 */
	private $vanaf_datum;

	/**
	 * De tot datum van het stookbestand
	 *
	 * @var int $tot_datum de einddatum van het stookbestand.
	 */
	private $tot_datum;

	/**
	 *
	 * Prepareer 'stookbestand' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data ) {
		return true;
	}

	/**
	 * Array walk functie, bepaal de medestokers van alle reserveringen in de tijdrange.
	 *
	 * @param  \Kleistad\Reservering $reservering Het reservering object.
	 */
	private function bepaal_medestokers( $reservering ) {
		if ( $reservering->datum < $this->vanaf_datum || $reservering->datum > $this->tot_datum ) {
			return;
		}
		foreach ( $reservering->verdeling as $verdeling ) {
			$medestoker_id = $verdeling['id'];
			if ( $medestoker_id > 0 && ! array_key_exists( $medestoker_id, $this->medestokers ) ) {
				$medestoker                          = get_userdata( $medestoker_id );
				$this->medestokers[ $medestoker_id ] = ( ! $medestoker ) ? 'onbekend' : $medestoker->display_name;
			}
		}
	}

	/**
	 * Array walk functie, bepaal de percentages en kosten van alle reserveringen in de tijdrange.
	 *
	 * @param  \Kleistad\Reservering $reservering Het reservering object.
	 */
	private function bepaal_stookgegevens( $reservering ) {
		if ( $reservering->datum < $this->vanaf_datum || $reservering->datum > $this->tot_datum ) {
			return;
		}
		$stoker      = get_userdata( $reservering->gebruiker_id );
		$stoker_naam = ( ! $stoker ) ? 'onbekend' : $stoker->display_name;
		$totaal      = 0.0;
		$perc_values = [];
		$kost_values = [];
		$values      = [
			$stoker_naam,
			$reservering->dag . '-' . $reservering->maand . '-' . $reservering->jaar,
			$this->ovens[ $reservering->oven_id ]->naam,
			$reservering->soortstook,
			$reservering->temperatuur,
			$reservering->programma,
		];
		foreach ( $this->medestokers as $id => $medestoker ) {
			$percentage = 0;
			$kosten     = 0.0;
			foreach ( $reservering->verdeling as $stookdeel ) {
				if ( $stookdeel['id'] == $id ) { // phpcs:ignore
					$percentage += $stookdeel['perc'];
					$kosten     += $stookdeel['prijs'] ?? $this->ovens[ $reservering->oven_id ]->stookkosten( $id, $stookdeel['perc'], $reservering->temperatuur );
					$totaal     += $kosten;
				}
			}
			$perc_values [] = $percentage ?: '';
			$kost_values [] = $kosten ? number_format_i18n( $kosten, 2 ) : '';
		}
		fputcsv( $this->file_handle, array_merge( $values, $perc_values, $kost_values, [ number_format_i18n( $totaal, 2 ) ] ), ';', '"' );
	}

	/**
	 * Schrijf abonnees informatie naar het bestand.
	 */
	protected function stook() {
		$this->vanaf_datum = strtotime( filter_input( INPUT_GET, 'vanaf_datum', FILTER_SANITIZE_STRING ) );
		$this->tot_datum   = strtotime( filter_input( INPUT_GET, 'tot_datum', FILTER_SANITIZE_STRING ) );
		$this->ovens       = \Kleistad\Oven::all();
		$reserveringen     = \Kleistad\Reservering::all();
		array_walk( $reserveringen, [ $this, 'bepaal_medestokers' ] );
		asort( $this->medestokers );

		$fields = [ 'Stoker', 'Datum', 'Oven', 'Soort Stook', 'Temperatuur', 'Programma' ];
		for ( $i = 1; $i <= 2; $i ++ ) {
			foreach ( $this->medestokers as $medestoker ) {
				$fields[] = $medestoker;
			}
		}
		$fields[] = 'Totaal';
		fputcsv( $this->file_handle, $fields, ';', '"' );

		array_walk( $reserveringen, [ $this, 'bepaal_stookgegevens' ] );
	}

}
