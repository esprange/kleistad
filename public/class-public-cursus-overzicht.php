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

namespace Kleistad;

/**
 * De kleistad cursus overzicht class.
 */
class Public_Cursus_Overzicht extends ShortcodeForm {

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
		if ( 'cursisten' === $data['actie'] ) {
			$cursus            = new \Kleistad\Cursus( $data['id'] );
			$data['cursus']    = [
				'naam'      => $cursus->naam,
				'code'      => $cursus->code,
				'cursus_id' => $cursus->id,
			];
			$data['cursisten'] = [];
			$inschrijvingen    = \Kleistad\Inschrijving::all();
			foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
				if ( array_key_exists( $data['id'], $cursist_inschrijvingen ) && $cursist_inschrijvingen[ $data['id'] ]->ingedeeld && ! $cursist_inschrijvingen[ $data['id'] ]->geannuleerd ) {
					$cursist             = get_userdata( $cursist_id );
					$data['cursisten'][] = [
						'naam'          => $cursist->display_name . ( 1 < $cursist_inschrijvingen[ $data['id'] ]->aantal ? ' (' . $cursist_inschrijvingen[ $data['id'] ]->aantal . ')' : '' ),
						'telnr'         => $cursist->telnr,
						'email'         => $cursist->user_email,
						'i_betaald'     => $cursist_inschrijvingen[ $data['id'] ]->i_betaald,
						'c_betaald'     => $cursist_inschrijvingen[ $data['id'] ]->c_betaald,
						'restant_email' => $cursist_inschrijvingen[ $data['id'] ]->restant_email,
						'technieken'    => implode( ', ', $cursist_inschrijvingen[ $data['id'] ]->technieken ),
					];
				}
			}
		} else {
			$cursussen           = \Kleistad\Cursus::all();
			$data['cursus_info'] = [];

			foreach ( $cursussen as $cursus_id => $cursus ) {
				if ( ! $cursus->vervallen ) {
					$data['cursus_info'][ $cursus_id ] = [
						'start_dt'       => $cursus->start_datum,
						'code'           => "C$cursus_id",
						'naam'           => $cursus->naam,
						'docent'         => $cursus->docent,
						'start_datum'    => strftime( '%d-%m-%y', $cursus->start_datum ),
						'inschrijvingen' => $cursus->ruimte() !== $cursus->maximum,
					];
				}
			}
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'cursus_overzicht' form
	 *
	 * @param array $data gevalideerde data.
	 * @return bool
	 *
	 * @since   5.4.0
	 */
	protected function validate( &$data ) {
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'cursus_id' => FILTER_SANITIZE_NUMBER_INT,
			]
		);
		return true;
	}

	/**
	 * Bewaar 'cursus_overzicht' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return array
	 *
	 * @since   5.4.0
	 */
	protected function save( $data ) {
		$cursus_id              = $data['input']['cursus_id'];
		$inschrijvingen         = \Kleistad\Inschrijving::all();
		$aantal_verzonden_email = 0;
		foreach ( $inschrijvingen as $inschrijving ) {
			if ( array_key_exists( $cursus_id, $inschrijving ) ) {
				if (
					( $inschrijving[ $cursus_id ]->geannuleerd ) ||
					( $inschrijving[ $cursus_id ]->c_betaald ) ||
					( $inschrijving[ $cursus_id ]->restant_email )
				) {
					continue;
				}
				if ( $inschrijving[ $cursus_id ]->ingedeeld ) {
					$aantal_verzonden_email++;
					$inschrijving[ $cursus_id ]->email( 'betaling' );
					$inschrijving[ $cursus_id ]->restant_email = true;
					$inschrijving[ $cursus_id ]->save();
				}
			}
		}
		return [
			'status'  => $this->status( ( $aantal_verzonden_email > 0 ) ? "Emails zijn verstuurd naar $aantal_verzonden_email cursisten" : 'Er zijn geen nieuwe emails verzonden' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Schrijf cursisten informatie naar het bestand.
	 */
	protected function cursisten() {
		$cursus_id        = filter_input( INPUT_POST, 'cursus_id', FILTER_SANITIZE_STRING );
		$inschrijvingen   = \Kleistad\Inschrijving::all();
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
