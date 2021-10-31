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
	 *
	 * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
	 */
	protected function prepare( array &$data ) {
		return isset( $data );
	}

	/**
	 * Schrijf stook informatie naar het bestand.
	 *
	 * @param array $data De argumenten.
	 */
	protected function stook( array $data ) {
		$vanaf_datum = strtotime( filter_input( INPUT_GET, 'vanaf_datum', FILTER_SANITIZE_STRING ) ?? '' );
		$tot_datum   = strtotime( filter_input( INPUT_GET, 'tot_datum', FILTER_SANITIZE_STRING ) ?? '' );
		if ( ! $vanaf_datum || ! $tot_datum ) {
			return;
		}
		$ovens  = new Ovens();
		$stoken = [];
		foreach ( $ovens as $oven ) {
			$stoken[ $oven->id ] = new Stoken( $oven->id, $vanaf_datum, $tot_datum );
			foreach ( $stoken[ $oven->id ] as $stook ) {
				foreach ( $stook->stookdelen as $stookdeel ) {
					$medestokers[ "$stookdeel->medestoker" ] = get_userdata( $stookdeel->medestoker )->display_name;
				}
			}
		}
		asort( $medestokers );

		$fields            = [ 'Stoker', 'Datum', 'Oven', 'Soort Stook', 'Temperatuur', 'Programma' ];
		$fields_lege_perc  = [];
		$fields_lege_prijs = [];
		for ( $i = 1; $i <= 2; $i ++ ) {
			foreach ( $medestokers as $id => $medestoker ) {
				$fields[]                 = $medestoker;
				$fields_lege_perc[ $id ]  = '';
				$fields_lege_prijs[ $id ] = '';
			}
		}
		fputcsv( $data['filehandle'], $fields, ';' );
		$records = [];
		foreach ( $ovens as $oven ) {
			foreach ( $stoken[ $oven->id ] as $stook ) {
				$stook_values        = [
					get_userdata( $stook->hoofdstoker )->display_name,
					date( 'd-m-Y', $stook->datum ),
					$oven->naam,
					$stook->soort,
					$stook->temperatuur,
					$stook->programma,
				];
				$stoker_perc_values  = $fields_lege_perc;
				$stoker_prijs_values = $fields_lege_prijs;
				foreach ( $stook->stookdelen as $stookdeel ) {
					$stoker_perc_values[ "$stookdeel->medestoker" ]  = "$stookdeel->percentage %";
					$stoker_prijs_values[ "$stookdeel->medestoker" ] = '€ ' . number_format_i18n( $stookdeel->prijs, 2 );
				}
				$records[ "$stook->datum $oven->naam" ] = array_merge( $stook_values, $stoker_perc_values, $stoker_prijs_values );
			}
		}
		ksort( $records );
		foreach ( $records as $record ) {
			fputcsv( $data['filehandle'], $record, ';' );
		}
	}

}
