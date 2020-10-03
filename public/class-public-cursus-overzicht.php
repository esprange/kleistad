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
	 * @param  int  $cursus_id Het id van de cursus.
	 * @param  bool $compleet  Of het overzicht ook niet ingedeelde cursisten moet tonen.
	 * @return array De inschrijving van cursisten voor de cursus. Cursist_id is de index.
	 */
	private function inschrijvingen( $cursus_id, $compleet = false ) {
		$cursist_inschrijving = [];
		$inschrijvingen       = \Kleistad\Inschrijving::all();
		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			if ( array_key_exists( $cursus_id, $cursist_inschrijvingen ) && ! $cursist_inschrijvingen[ $cursus_id ]->geannuleerd ) {
				if ( ! $compleet && ! $cursist_inschrijvingen[ $cursus_id ]->ingedeeld ) {
					continue;
				}
				$cursist_inschrijving[ $cursist_id ] = $cursist_inschrijvingen[ $cursus_id ];
			}
		}
		return $cursist_inschrijving;
	}

	/**
	 * Geef de cursus info mee.
	 *
	 * @param int $docent_id Nul (ingeval van bestuur) of het id van de docent.
	 * @return array De cursus informatie.
	 */
	private function cursussen( $docent_id = 0 ) {
		$cursussen      = \Kleistad\Cursus::all();
		$inschrijvingen = \Kleistad\Inschrijving::all();
		$cursus_info    = [];
		foreach ( $cursussen as $cursus_id => $cursus ) {
			if ( ! $cursus->vervallen && ( 0 === $docent_id || intval( $cursus->docent ) === $docent_id ) ) {
				$heeft_inschrijvingen = false;
				foreach ( $inschrijvingen as $cursist_inschrijvingen ) {
					if ( array_key_exists( $cursus_id, $cursist_inschrijvingen ) && ! $cursist_inschrijvingen[ $cursus_id ]->geannuleerd ) {
						$heeft_inschrijvingen = true;
						break;
					}
				}
				$cursus_info[ $cursus_id ] = [
					'start_dt'       => $cursus->start_datum,
					'code'           => "C$cursus_id",
					'naam'           => $cursus->naam,
					'docent'         => $cursus->docent_naam(),
					'start_datum'    => strftime( '%d-%m-%Y', $cursus->start_datum ),
					'inschrijvingen' => $heeft_inschrijvingen,
				];
			}
		}
		return $cursus_info;
	}

	/**
	 * Overzicht cursisten op cursus
	 *
	 * @param \Kleistad\Cursus $cursus     De cursus.
	 * @param bool             $is_bestuur Toon alle cursussen of alleen die van de huidige gebruiker (docent).
	 * @return array De cursisten.
	 */
	private function cursistenlijst( $cursus, $is_bestuur ) {
		$cursisten = [];
		foreach ( $this->inschrijvingen( $cursus->id, $is_bestuur ) as $cursist_id => $inschrijving ) {
			$cursist = get_userdata( $cursist_id );
			if ( $inschrijving->hoofd_cursist_id ) {
				$cursisten[] = [
					'id'         => $cursist_id,
					'naam'       => $cursist->display_name,
					'telnr'      => $cursist->telnr,
					'email'      => $cursist->user_email,
					'extra'      => true,
					'technieken' => implode( ', ', $inschrijving->technieken ),
				];
			} else {
				$order       = new \Kleistad\Order( $inschrijving->referentie() );
				$cursisten[] = [
					'id'             => $cursist_id,
					'naam'           => $cursist->display_name . $inschrijving->toon_aantal(),
					'telnr'          => $cursist->telnr,
					'email'          => $cursist->user_email,
					'extra'          => false,
					'i_betaald'      => $inschrijving->inschrijving_betaald( $order->betaald ),
					'c_betaald'      => $order->gesloten,
					'restant_email'  => $inschrijving->restant_email,
					'herinner_email' => $inschrijving->herinner_email,
					'technieken'     => implode( ', ', $inschrijving->technieken ),
					'wacht'          => ( ! $inschrijving->ingedeeld && $inschrijving->datum > $cursus->start_datum && ! \Kleistad\Order::zoek_order( $inschrijving->code ) ),
					'wachtlijst'     => ! $inschrijving->ingedeeld && $inschrijving->wacht_datum,
				];
			}
		}
		return $cursisten;
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
		$user                    = wp_get_current_user();
		$is_bestuur              = in_array( 'bestuur', (array) $user->roles, true );
		$data['bestuur_rechten'] = $is_bestuur;
		if ( 'cursisten' === $data['actie'] ) {
			$cursus            = new \Kleistad\Cursus( $data['id'] );
			$data['cursus']    = [
				'id'    => $cursus->id,
				'naam'  => $cursus->naam,
				'code'  => $cursus->code,
				'loopt' => $cursus->start_datum < strtotime( 'today' ),
			];
			$data['cursisten'] = $this->cursistenlijst( $cursus, $is_bestuur );
		} elseif ( 'indelen' === $data['actie'] ) {
			list( $cursist_id, $cursus_id ) = array_map( 'intval', explode( '-', $data['id'] ) );
			$cursus                         = new \Kleistad\Cursus( $cursus_id );
			$inschrijving                   = new \Kleistad\Inschrijving( $cursus_id, $cursist_id );
			$cursist                        = get_userdata( $cursist_id );
			$lopend                         = $cursus->lopend( $inschrijving->datum );
			$data['cursus']                 = [
				'id'          => $cursus_id,
				'lessen'      => $lopend['lessen'],
				'lessen_rest' => $lopend['lessen_rest'],
				'kosten'      => $lopend['kosten'],
				'max'         => round( $cursus->inschrijfkosten, 1 ) + $cursus->cursuskosten,
			];
			$data['cursist']                = [
				'id'     => $cursist_id,
				'naam'   => $cursist->display_name . ( 1 < $inschrijving->aantal ? ' (' . $inschrijving->aantal . ')' : '' ),
				'datum'  => $inschrijving->datum,
				'aantal' => $inschrijving->aantal,
			];

		} else {
			$user = wp_get_current_user();
			if ( $is_bestuur ) {
				$data['cursus_info'] = $this->cursussen();
			} else {
				$data['cursus_info'] = $this->cursussen( $user->ID );
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
				'kosten'     => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
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
			$inschrijving                 = new \Kleistad\Inschrijving( $data['input']['cursus_id'], $data['input']['cursist_id'] );
			$inschrijving->lopende_cursus = (float) $data['input']['kosten'];
			$inschrijving->ingedeeld      = true;
			$inschrijving->restant_email  = true; // We willen geen restant email naar deze cursist.
			$inschrijving->artikel_type   = 'inschrijving';
			$inschrijving->save();
			$inschrijving->email( '_lopend_betalen', $inschrijving->bestel_order( 0.0, strtotime( '+7 days 0:00' ) ) );
			return [
				'status'  => $this->status( 'De order is aangemaakt en een email met factuur is naar de cursist verstuurd' ),
				'content' => $this->display(),
			];
		} elseif ( 'herinner_email' === $data['form_actie'] ) {
			$aantal_verzonden_email = 0;
			// Alleen voor de cursisten die ingedeeld zijn en niet geannuleerd.
			foreach ( $this->inschrijvingen( $data['input']['cursus_id'], false ) as $inschrijving ) {
				/**
				 * Stuur herinnerings emails als de cursist nog niet de cursus volledig betaald heeft.
				 */
				$aantal_verzonden_email += $inschrijving->herinnering();
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
			'Voornaam',
			'Achternaam',
			'Telefoonnummer',
			'Email',
			'Aantal',
			'Technieken',
			'Opmerking',
			'Datum',
			'Ingedeeld',
		];
		fputcsv( $this->file_handle, $cursisten_fields, ';', '"' );
		foreach ( $this->inschrijvingen( $cursus_id, true ) as $cursist_id => $inschrijving ) {
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
				$inschrijving->ingedeeld ? 'Ja' : 'Nee',
			];
			fputcsv( $this->file_handle, $cursist_gegevens, ';', '"' );
		}
	}

	/**
	 * Maak een presentielijst aan.
	 */
	protected function presentielijst() {
		$cursus_id = filter_input( INPUT_GET, 'cursus_id', FILTER_SANITIZE_NUMBER_INT );
		$cursus    = new \Kleistad\Cursus( $cursus_id );
		$cursisten = [];
		foreach ( $this->inschrijvingen( $cursus_id, false ) as $cursist_id => $inschrijving ) {
			$cursisten[] = get_user_by( 'id', $cursist_id )->display_name . $inschrijving->toon_aantal();
		}
		$presentielijst = new \Kleistad\Presentielijst( 'L' );
		return $presentielijst->run( $cursus, $cursisten );
	}
}
