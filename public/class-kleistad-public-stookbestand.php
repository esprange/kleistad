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
	 * File handle voor download bestanden
	 *
	 * @var resource $file_handle De file pointer
	 */
	private $file_handle;

	/**
	 *
	 * Prepareer 'stookbestand' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$gebruiker_id = get_current_user_id();
		$data         = [
			'gebruiker_id' => $gebruiker_id,
		];
		return true;
	}

	/**
	 *
	 * Valideer/sanitize 'stookbestand' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	public function validate( &$data ) {
		$vanaf_datum  = strtotime( filter_input( INPUT_POST, 'vanaf_datum', FILTER_SANITIZE_STRING ) );
		$tot_datum    = strtotime( filter_input( INPUT_POST, 'tot_datum', FILTER_SANITIZE_STRING ) );
		$gebruiker_id = filter_input( INPUT_POST, 'gebruiker_id', FILTER_SANITIZE_NUMBER_INT );
		$data         = [
			'vanaf_datum'  => $vanaf_datum,
			'tot_datum'    => $tot_datum,
			'gebruiker_id' => $gebruiker_id,
		];
		return true;
	}

	/**
	 * Schrijf abonnees informatie naar het bestand.
	 *
	 * @param int $vanaf_datum timestamp begin stoken.
	 * @param int $tot_datum   timestamp eind stoken.
	 */
	private function stook( $vanaf_datum, $tot_datum ) {
		fwrite( $this->file_handle, "\xEF\xBB\xBF" );

		$ovens          = Kleistad_Oven::all();
		$reserveringen  = Kleistad_Reservering::all();
		$regeling_store = new Kleistad_Regelingen();

		$medestokers = [];
		foreach ( $reserveringen as $reservering ) {
			if ( ( $reservering->datum < $vanaf_datum ) || ( $reservering->datum > $tot_datum ) ) {
				continue;
			}
			foreach ( $reservering->verdeling as $verdeling ) {
				$medestoker_id = $verdeling['id'];
				if ( $medestoker_id > 0 && ! array_key_exists( $medestoker_id, $medestokers ) ) {
					$medestoker = get_userdata( $medestoker_id );
					if ( false !== $medestoker ) {
						$medestokers[ $medestoker_id ] = $medestoker->display_name;
					}
				}
			}
		}

		asort( $medestokers );
		$fields = [ 'Stoker', 'Datum', 'Oven', 'Kosten', 'Soort Stook', 'Temperatuur', 'Programma' ];
		for ( $i = 1; $i <= 2; $i ++ ) {
			foreach ( $medestokers as $medestoker ) {
				$fields[] = $medestoker;
			}
		}
		$fields[] = 'Totaal';
		fputcsv( $this->file_handle, $fields, ';', '"' );

		foreach ( $reserveringen as $reservering ) {
			if ( ( $reservering->datum < $vanaf_datum ) || ( $reservering->datum > $tot_datum ) ) {
				continue;
			}
			$stoker      = get_userdata( $reservering->gebruiker_id );
			$stoker_naam = ( ! $stoker ) ? 'onbekend' : $stoker->display_name;
			$values      = [
				$stoker_naam,
				$reservering->dag . '-' . $reservering->maand . '-' . $reservering->jaar,
				$ovens[ $reservering->oven_id ]->naam,
				number_format_i18n( $ovens[ $reservering->oven_id ]->kosten, 2 ),
				$reservering->soortstook,
				$reservering->temperatuur,
				$reservering->programma,
			];

			foreach ( $medestokers as $id => $medestoker ) {
				$percentage = 0;
				foreach ( $reservering->verdeling as $stookdeel ) {
					if ( $stookdeel['id'] == $id ) { // phpcs:ignore
						$percentage += $stookdeel['perc'];
					}
				}
				$values [] = ( 0 === $percentage ) ? '' : $percentage;
			}

			$totaal = 0;
			foreach ( $medestokers as $id => $medestoker ) {
				$kosten       = 0;
				$kosten_tonen = false;
				foreach ( $reservering->verdeling as $stookdeel ) {
					if ( $stookdeel['id'] == $id ) { // phpcs:ignore
						if ( isset( $stookdeel['prijs'] ) ) { // Berekening als vastgelegd in transactie.
							$kosten += $stookdeel['prijs'];
						} else { // Voorlopige berekening.
							$regeling = $regeling_store->get( $id, $reservering->oven_id );
							$kosten  += round( $stookdeel['perc'] / 100 * ( ( is_null( $regeling ) ) ? $ovens[ $reservering->oven_id ]->kosten : $regeling ), 2 );
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
		fclose( $this->file_handle );
	}

	/**
	 *
	 * Bewaar 'stookbestand' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return string|WP_Error
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		$error = new WP_Error();

		if ( ! Kleistad_Roles::override() ) {
			$error->add( 'security', 'Geen toegang tot deze functie.' );
			return $error;
		}
		$csv    = tempnam( sys_get_temp_dir(), 'stookbestand' );
		$result = fopen( $csv, 'w' );
		if ( false === $result ) {
			$error->add( 'fout', 'Er kan geen bestand worden aangemaakt' );
			return $error;
		} else {
			$this->file_handle = $result;
		}
		$this->stook( $data['vanaf_datum'], $data['tot_datum'] );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename=stookbestand_' . strftime( '%Y%m%d' ) . '.csv' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $csv ) );
		ob_clean();
		flush();
		readfile( $csv ); // phpcs:ignore
		unlink( $csv );
		exit;
	}
}
