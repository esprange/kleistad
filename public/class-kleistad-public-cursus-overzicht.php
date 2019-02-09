<?php
/**
 * Shortcode cursus overzicht.
 *
 * @link       https://www.kleistad.nl
 * @since      4.5.4
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * De kleistad cursus overzicht class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Cursus_Overzicht extends Kleistad_ShortcodeForm {

	/**
	 *
	 * Prepareer 'cursus_overzicht' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.5.4
	 */
	public function prepare( &$data = null ) {
		$cursussen      = Kleistad_Cursus::all();
		$inschrijvingen = Kleistad_Inschrijving::all();
		$cursus_info    = [];

		foreach ( $cursussen as $cursus_id => $cursus ) {
			$cursus_info[ $cursus_id ] = [
				'start_dt'    => $cursus->start_datum,
				'code'        => 'C' . $cursus_id,
				'naam'        => $cursus->naam,
				'docent'      => $cursus->docent,
				'start_datum' => strftime( '%d-%m-%y', $cursus->start_datum ),
				'lijst'       => [],
			];
		}
		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			$cursist = get_userdata( $cursist_id );
			foreach ( $cursist_inschrijvingen as $cursus_id => $inschrijving ) {
				if ( $inschrijving->ingedeeld && ! $inschrijving->geannuleerd ) {
					$cursus_info[ $cursus_id ]['lijst'][] = [
						'aantal'     => $inschrijving->aantal,
						'naam'       => $cursist->display_name,
						'telnr'      => $cursist->telnr,
						'email'      => $cursist->user_email,
						'technieken' => implode( ', ', $inschrijving->technieken ),
					];

				}
			}
		}
		$data = [
			'cursus_info' => $cursus_info,
		];
		return true;
	}

	/**
	 * Valideer/sanitize 'registratie' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return bool
	 *
	 * @since   4.5.4
	 */
	public function validate( &$data ) {
		$data['id'] = filter_input( INPUT_POST, 'cursus_id', FILTER_SANITIZE_STRING );
		return true;
	}

	/**
	 *
	 * Bewaar 'registratie_overzicht' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return string|WP_Error
	 *
	 * @since   4.5.4
	 */
	public function save( $data ) {
		$error = new WP_Error();

		if ( ! Kleistad_Roles::override() ) {
			$error->add( 'security', 'Geen toegang tot deze functie.' );
			return $error;
		}
		$csv   = tempnam( sys_get_temp_dir(), 'cursisten_C' . $data['id'] );
		$f_csv = fopen( $csv, 'w' );
		if ( false === $f_csv ) {
			$error->add( 'security', 'Bestand kon niet aangemaakt worden.' );
			return $error;
		}
		fwrite( $f_csv, "\xEF\xBB\xBF" );

		$inschrijvingen   = Kleistad_Inschrijving::all();
		$cursisten_fields = [
			'Achternaam',
			'Voornaam',
			'Telefoonnummer',
			'Email',
			'Aantal',
			'Technieken',
			'Opmerking',
		];
		fputcsv( $f_csv, $cursisten_fields, ';', '"' );

		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			if ( array_key_exists( $data['id'], $cursist_inschrijvingen ) ) {
				if ( $cursist_inschrijvingen[ $data['id'] ]->ingedeeld && ! $cursist_inschrijvingen[ $data['id'] ]->geannuleerd ) {
					$cursist          = get_userdata( $cursist_id );
					$cursist_gegevens = [
						$cursist->first_name,
						$cursist->last_name,
						$cursist->telnr,
						$cursist->user_email,
						$cursist_inschrijvingen[ $data['id'] ]->aantal,
						implode( ' ', $cursist_inschrijvingen[ $data['id'] ]->technieken ),
					];
					fputcsv( $f_csv, $cursist_gegevens, ';', '"' );
				}
			}
		}
		fclose( $f_csv );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename=cursisten_C' . $data['id'] . '.csv' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $csv ) );
		ob_clean();
		flush();
		readfile( $csv ); // phpcs:ignore
		unlink( $csv );
	}
}
