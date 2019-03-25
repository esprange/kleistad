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
	protected function prepare( &$data = null ) {
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
	 * Schrijf cursisten informatie naar het bestand.
	 */
	protected function cursisten() {
		$cursus_id        = filter_input( INPUT_POST, 'cursus_id', FILTER_SANITIZE_STRING );
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
		fputcsv( $this->file_handle, $cursisten_fields, ';', '"' );

		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			if ( array_key_exists( $cursus_id, $cursist_inschrijvingen ) ) {
				if ( $cursist_inschrijvingen[ $cursus_id ]->ingedeeld && ! $cursist_inschrijvingen[ $cursus_id ]->geannuleerd ) {
					$cursist          = get_userdata( $cursist_id );
					$cursist_gegevens = [
						$cursist->first_name,
						$cursist->last_name,
						$cursist->telnr,
						$cursist->user_email,
						$cursist_inschrijvingen[ $cursus_id ]->aantal,
						implode( ' ', $cursist_inschrijvingen[ $cursus_id ]->technieken ),
					];
					fputcsv( $this->file_handle, $cursist_gegevens, ';', '"' );
				}
			}
		}
	}
}
