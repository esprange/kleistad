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
	 * Bepaal de actieve cursisten in een cursus.
	 *
	 * @param  int $cursus_id Het id van de cursus.
	 * @return array De inschrijving van cursisten voor de cursus. Cursist_id is de index.
	 */
	private function inschrijvingen( $cursus_id ) {
		$cursist_inschrijving = [];
		$inschrijvingen       = \Kleistad\Inschrijving::all();
		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			if ( array_key_exists( $cursus_id, $cursist_inschrijvingen ) && $cursist_inschrijvingen[ $cursus_id ]->ingedeeld && ! $cursist_inschrijvingen[ $cursus_id ]->geannuleerd ) {
				$cursist_inschrijving[ $cursist_id ] = $cursist_inschrijvingen[ $cursus_id ];
			}
		}
		return $cursist_inschrijving;
	}

	/**
	 *
	 * Prepareer 'cursus_overzicht' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.5.4
	 */
	protected function prepare( &$data ) {
		if ( 'cursisten' === $data['actie'] ) {
			$cursus            = new \Kleistad\Cursus( $data['id'] );
			$data['cursus']    = [
				'naam'      => $cursus->naam,
				'code'      => $cursus->code,
				'cursus_id' => $data['id'],
			];
			$data['cursisten'] = [];
			foreach ( $this->inschrijvingen( $data['id'] ) as $cursist_id => $inschrijving ) {
				$cursist             = get_userdata( $cursist_id );
				$data['cursisten'][] = [
					'naam'           => $cursist->display_name . ( $inschrijving->aantal ? ' (' . $inschrijving->aantal . ')' : '' ),
					'telnr'          => $cursist->telnr,
					'email'          => $cursist->user_email,
					'i_betaald'      => $inschrijving->i_betaald,
					'c_betaald'      => $inschrijving->c_betaald,
					'restant_email'  => $inschrijving->restant_email,
					'herinner_email' => $inschrijving->herinner_email,
					'technieken'     => implode( ', ', $inschrijving->technieken ),
				];
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
		$aantal_verzonden_email = 0;
		foreach ( $this->inschrijvingen( $cursus_id ) as $inschrijving ) {
			if ( $inschrijving->c_betaald ) {
				/**
				 * Als de cursist al betaald heeft, geen actie.
				 */
				continue;
			}
			if ( 'herinner_email' === $data['form_actie'] &&
				$inschrijving->restant_email &&
				! $inschrijving->herinner_email ) {
					/**
					 * Stuur herinnerings emails als de cursist eerder een restant email heeft gehad en nog niet de cursus volledig betaald heeft.
					 */
					$aantal_verzonden_email++;
					$inschrijving->herinner_email = true;
			} elseif ( 'restant_email' === $data ['form_actie'] &&
				! $inschrijving->restant_email ) {
					/**
					 * Stuur restant emails als de cursist nog niet de cursus volledig betaald heeft.
					 */
					$aantal_verzonden_email++;
					$inschrijving->restant_email = true;
			} else {
				continue;
			}
			$inschrijving->email( $data['form_actie'] );
			$inschrijving->save();
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
		$cursus_id        = filter_input( INPUT_GET, 'cursus_id', FILTER_SANITIZE_NUMBER_INT );
		$cursisten_fields = [
			'Achternaam',
			'Voornaam',
			'Telefoonnummer',
			'Email',
			'Aantal',
			'Technieken',
			'Opmerking',
			'Datum',
			'Betaald',
		];
		fputcsv( $this->file_handle, $cursisten_fields, ';', '"' );
		foreach ( $this->inschrijvingen( $cursus_id ) as $cursist_id => $inschrijving ) {
			$cursist          = get_userdata( $cursist_id );
			$cursist_gegevens = [
				$cursist->first_name,
				$cursist->last_name,
				$cursist->telnr,
				$cursist->user_email,
				$inschrijving->aantal,
				implode( ' ', $inschrijving->technieken ),
				$inschrijving->opmerking,
				date( 'd-m-Y', $inschrijving->datum ),
				$inschrijving->c_betaald ? 'Ja' : 'Nee',
			];
			fputcsv( $this->file_handle, $cursist_gegevens, ';', '"' );
		}
	}
}
