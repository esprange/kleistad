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

/**
 * De kleistad stookbestand class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_Stookbestand extends Kleistad_ShortcodeForm {

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
	 * Array dat alle regelingen bevat
	 *
	 * @var Kleistad_Regelingen $regeling De regelingen met o.a. hun kosten.
	 */
	private $regelingen;

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
	protected function prepare( &$data = null ) {
		return true;
	}

	/**
	 * Array walk functie, bepaal de medestokers van alle reserveringen in de tijdrange.
	 *
	 * @param Kleistad_Reservering $reservering Het reservering object.
	 */
	private function bepaal_medestokers( $reservering ) {
		if ( $reservering->datum >= $this->vanaf_datum && $reservering->datum <= $this->tot_datum ) {
			foreach ( $reservering->verdeling as $verdeling ) {
				$medestoker_id = $verdeling['id'];
				if ( $medestoker_id > 0 && ! array_key_exists( $medestoker_id, $this->medestokers ) ) {
					$medestoker = get_userdata( $medestoker_id );
					if ( false !== $medestoker ) {
						$this->medestokers[ $medestoker_id ] = $medestoker->display_name;
					}
				}
			}
		}
	}

	/**
	 * Array walk functie, bepaal de percentages en kosten van alle reserveringen in de tijdrange.
	 *
	 * @param Kleistad_Reservering $reservering Het reservering object.
	 */
	private function bepaal_stookgegevens( $reservering ) {
		if ( $reservering->datum >= $this->vanaf_datum && $reservering->datum <= $this->tot_datum ) {
			$stoker      = get_userdata( $reservering->gebruiker_id );
			$stoker_naam = ( ! $stoker ) ? 'onbekend' : $stoker->display_name;
			$values      = [
				$stoker_naam,
				$reservering->dag . '-' . $reservering->maand . '-' . $reservering->jaar,
				$this->ovens[ $reservering->oven_id ]->naam,
				number_format_i18n( $this->ovens[ $reservering->oven_id ]->kosten, 2 ),
				$reservering->soortstook,
				$reservering->temperatuur,
				$reservering->programma,
			];

			foreach ( $this->medestokers as $id => $medestoker ) {
				$percentage = 0;
				foreach ( $reservering->verdeling as $stookdeel ) {
					if ( $stookdeel['id'] == $id ) { // phpcs:ignore
						$percentage += $stookdeel['perc'];
					}
				}
				$values [] = ( 0 === $percentage ) ? '' : $percentage;
			}

			$totaal = 0.0;
			foreach ( $this->medestokers as $id => $medestoker ) {
				$kosten       = 0.0;
				$kosten_tonen = false;
				foreach ( $reservering->verdeling as $stookdeel ) {
					if ( $stookdeel['id'] == $id ) { // phpcs:ignore
						if ( isset( $stookdeel['prijs'] ) ) { // Berekening als vastgelegd in transactie.
							$kosten += $stookdeel['prijs'];
						} else { // Voorlopige berekening.
							$regeling = $this->regelingen->get( $id, $reservering->oven_id );
							$kosten  += round( $stookdeel['perc'] / 100 * ( ( is_null( $regeling ) ) ? $this->ovens[ $reservering->oven_id ]->kosten : $regeling ), 2 );
						}
						$totaal      += $kosten;
						$kosten_tonen = true;
					}
				}
				$values [] = ( $kosten_tonen ) ? number_format_i18n( $kosten, 2 ) : '';
			}
			$values [] = number_format_i18n( $totaal, 2 );
			fputcsv( $this->file_handle, $values, ';', '"' );
		}
	}

	/**
	 * Schrijf abonnees informatie naar het bestand.
	 */
	protected function stook() {
		$this->vanaf_datum = strtotime( filter_input( INPUT_POST, 'vanaf_datum', FILTER_SANITIZE_STRING ) );
		$this->tot_datum   = strtotime( filter_input( INPUT_POST, 'tot_datum', FILTER_SANITIZE_STRING ) );
		$this->ovens       = Kleistad_Oven::all();
		$this->regelingen  = new Kleistad_Regelingen();
		$reserveringen     = Kleistad_Reservering::all();
		array_walk( $reserveringen, [ $this, 'bepaal_medestokers' ] );
		asort( $this->medestokers );

		$fields = [ 'Stoker', 'Datum', 'Oven', 'Kosten', 'Soort Stook', 'Temperatuur', 'Programma' ];
		for ( $i = 1; $i <= 2; $i ++ ) {
			foreach ( $this->medestokers as $medestoker ) {
				$fields[] = $medestoker;
			}
		}
		$fields[] = 'Totaal';
		fputcsv( $this->file_handle, $fields, ';', '"' );

		array_walk( $reserveringen, [ $this, 'bepaal_stookgegevens' ] );
	}

	/**
	 * Valideer/sanitize form (dummy)
	 *
	 * @param array $data Gevalideerde data.
	 * @return bool
	 *
	 * @since   5.5.2
	 */
	protected function validate( &$data ) {
		return true;
	}

	/**
	 * Bewaar form gegevens (dummy)
	 *
	 * @param array $data te bewaren data.
	 * @return array
	 *
	 * @since   5.5.2
	 */
	protected function save( $data ) {
		return [];
	}
}
