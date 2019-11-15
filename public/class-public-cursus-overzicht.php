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
			if ( array_key_exists( $cursus_id, $cursist_inschrijvingen ) && ! $cursist_inschrijvingen[ $cursus_id ]->geannuleerd ) {
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
				'id'    => $data['id'],
				'naam'  => $cursus->naam,
				'code'  => $cursus->code,
				'loopt' => $cursus->start_datum < strtotime( 'today' ),
			];
			$data['cursisten'] = [];
			foreach ( $this->inschrijvingen( $data['id'] ) as $cursist_id => $inschrijving ) {
				if ( $cursus->vol && ! $inschrijving->ingedeeld ) {
					continue; // Het heeft geen zin om wachtende inschrijvingen te tonen als de cursus geen plaats meer heeft.
				}
				$cursist             = get_userdata( $cursist_id );
				$data['cursisten'][] = [
					'id'             => $cursist_id,
					'naam'           => $cursist->display_name . ( 1 < $inschrijving->aantal ? ' (' . $inschrijving->aantal . ')' : '' ),
					'telnr'          => $cursist->telnr,
					'email'          => $cursist->user_email,
					'i_betaald'      => $inschrijving->i_betaald,
					'c_betaald'      => $inschrijving->c_betaald,
					'restant_email'  => $inschrijving->restant_email,
					'herinner_email' => $inschrijving->herinner_email,
					'technieken'     => implode( ', ', $inschrijving->technieken ),
					'wacht'          => ( ! $inschrijving->ingedeeld && $inschrijving->datum > $cursus->start_datum && ! \Kleistad\Order::zoek_order( $inschrijving->code ) ),
				];
			}
		} elseif ( 'indelen' === $data['actie'] ) {
			list( $cursist_id, $cursus_id ) = array_map( 'intval', explode( '-', $data['id'] ) );
			$cursus                         = new \Kleistad\Cursus( $cursus_id );
			$inschrijving                   = new \Kleistad\Inschrijving( $cursist_id, $cursus_id );
			$cursist                        = get_userdata( $cursist_id );
			$lopend                         = $cursus->lopend( $inschrijving->datum );
			$data['cursus']                 = [
				'id'          => $cursus_id,
				'lessen'      => $lopend['lessen'],
				'lessen_rest' => $lopend['lessen_rest'],
				'bedrag'      => $lopend['bedrag'],
				'max'         => $cursus->inschrijfkosten + $cursus->cursuskosten,
			];
			$data['cursist']                = [
				'id'     => $cursist_id,
				'naam'   => $cursist->display_name . ( 1 < $inschrijving->aantal ? ' (' . $inschrijving->aantal . ')' : '' ),
				'datum'  => $inschrijving->datum,
				'aantal' => $inschrijving->aantal,
			];

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
						'start_datum'    => strftime( '%d-%m-%Y', $cursus->start_datum ),
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
				'cursist_id' => FILTER_SANITIZE_NUMBER_INT,
				'cursus_id'  => FILTER_SANITIZE_NUMBER_INT,
				'bedrag'     => FILTER_SANITIZE_NUMBER_FLOAT,
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
		if ( 'indelen' === $data['form_actie'] ) {
			$inschrijving                 = new \Kleistad\Inschrijving( $data['input']['cursist_id'], $data['input']['cursus_id'] );
			$inschrijving->lopende_cursus = (float) $data['input']['bedrag'];
			$inschrijving->save();
			$inschrijving->email( 'inschrijving', $inschrijving->bestel_order() );
			return [
				'status'  => $this->status( 'De order is aangemaakt en een email met factuur is naar de cursist verstuurd' ),
				'content' => $this->display(),
			];
		} else {
			$aantal_verzonden_email = 0;
			foreach ( $this->inschrijvingen( $data['input']['cursus_id'] ) as $inschrijving ) {
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
						$inschrijving->email( 'herinner_email' );
						$inschrijving->save();
				}
			}
			return [
				'status'  => $this->status( ( $aantal_verzonden_email > 0 ) ? "Emails zijn verstuurd naar $aantal_verzonden_email cursisten" : 'Er zijn geen nieuwe emails verzonden' ),
				'content' => $this->display(),
			];
		}
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
