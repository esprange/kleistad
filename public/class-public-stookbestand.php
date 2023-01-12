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
	 * Rapportage start datum
	 *
	 * @var int $vanaf_datum De datum vanaf.
	 */
	private int $vanaf_datum;

	/**
	 * Rapportage eind datum
	 *
	 * @var int $tot_datum De datum tot.
	 */
	private int $tot_datum;

	/**
	 * De ovens.
	 *
	 * @var Ovens $ovens De ovens.
	 */
	private Ovens $ovens;

	/**
	 * De stoken.
	 *
	 * @var array $stoken De stoken in de periode.
	 */
	private array $stoken;

	/**
	 * Prepareer 'stookbestand' form
	 *
	 * @return string
	 */
	protected function prepare() : string {
		return $this->content();
	}

	/**
	 * Schrijf stook informatie naar het bestand.
	 */
	protected function stook() {
		$this->vanaf_datum = strtotime( filter_input( INPUT_GET, 'vanaf_datum', FILTER_SANITIZE_STRING ) ?? '' );
		$this->tot_datum   = strtotime( filter_input( INPUT_GET, 'tot_datum', FILTER_SANITIZE_STRING ) ?? '' );
		if ( ! $this->vanaf_datum || ! $this->tot_datum ) {
			return;
		}
		$this->ovens       = new Ovens();
		$medestokers       = $this->bepaal_medestokers();
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
		fputcsv( $this->filehandle, $fields, ';' );

		$records = [];
		foreach ( $this->ovens as $oven ) {
			foreach ( $this->stoken[ $oven->id ] as $stook ) {
				$stook_values        = [
					get_userdata( $stook->hoofdstoker_id )->display_name,
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
			fputcsv( $this->filehandle, $record, ';' );
		}
	}

	/**
	 * Bepaal de stokers en meteen ook de stoken.
	 *
	 * @return array De medestokers.
	 */
	private function bepaal_medestokers() : array {
		$medestokers = [];
		foreach ( $this->ovens as $oven ) {
			$this->stoken[ $oven->id ] = new Stoken( $oven, $this->vanaf_datum, $this->tot_datum );
			foreach ( $this->stoken[ $oven->id ] as $stook ) {
				foreach ( $stook->stookdelen as $stookdeel ) {
					$medestokers[ "$stookdeel->medestoker" ] = get_userdata( $stookdeel->medestoker )->display_name;
				}
			}
		}
		asort( $medestokers );
		return $medestokers;
	}

}
