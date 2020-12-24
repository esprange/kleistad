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
	 *
	 * Prepareer 'stookbestand' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data ) {
		return isset( $data );
	}

	/**
	 * Schrijf abonnees informatie naar het bestand.
	 */
	protected function stook() {
		$vanaf_datum = strtotime( filter_input( INPUT_GET, 'vanaf_datum', FILTER_SANITIZE_STRING ) );
		$tot_datum   = strtotime( filter_input( INPUT_GET, 'tot_datum', FILTER_SANITIZE_STRING ) );
		$ovens       = new Ovens();
		$stoken      = [];
		foreach ( $ovens as $oven ) {
			$stoken[ $oven->id ] = new Stoken( $oven->id, $vanaf_datum, $tot_datum );
			foreach ( $stoken[ $oven->id ] as $stook ) {
				foreach ( $stook->stookdelen as $stookdeel ) {
					$medestokers[ "$stookdeel->medestoker" ] = get_userdata( $stookdeel->medestoker )->display_name;
				}
			}
		}
		asort( $medestokers );

		$fields                 = [ 'Stoker', 'Datum', 'Oven', 'Soort Stook', 'Temperatuur', 'Programma' ];
		$fields_lege_percentage = [];
		$fields_lege_prijs      = [];
		for ( $i = 1; $i <= 2; $i ++ ) {
			foreach ( $medestokers as $id => $medestoker ) {
				$fields[]                      = $medestoker;
				$fields_lege_percentage[ $id ] = '';
				$fields_lege_prijs[ $id ]      = '';
			}
		}
		fputcsv( $this->file_handle, $fields, ';', '"' );
		$records = [];
		foreach ( $ovens as $oven ) {
			foreach ( $stoken[ $oven->id ] as $stook ) {
				$stook_values             = [
					get_userdata( $stook->hoofdstoker )->display_name,
					date( 'd-m-Y', $stook->datum ),
					$oven->naam,
					$stook->soort,
					$stook->temperatuur,
					$stook->programma,
				];
				$stoker_percentage_values = $fields_lege_percentage;
				$stoker_prijs_values      = $fields_lege_prijs;
				foreach ( $stook->stookdelen as $stookdeel ) {
					$stoker_percentage_values[ "$stookdeel->medestoker" ] = "$stookdeel->percentage %";
					$stoker_prijs_values[ "$stookdeel->medestoker" ]      = '€ ' . number_format_i18n( $stookdeel->prijs, 2 );
				}
				$records[ "$stook->datum $oven->naam" ] = array_merge( $stook_values, $stoker_percentage_values, $stoker_prijs_values );
			}
		}
		ksort( $records );
		foreach ( $records as $record ) {
			fputcsv( $this->file_handle, $record, ';', '"' );
		}
	}

}
