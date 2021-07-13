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
	private function geef_inschrijvingen( int $cursus_id ) : array {
		$inschrijvingen = [];
		foreach ( new Inschrijvingen( $cursus_id, true ) as $inschrijving ) {
			if ( ! current_user_can( BESTUUR ) && ! $inschrijving->ingedeeld ) {
				continue;
			}
			$inschrijvingen[ $inschrijving->klant_id ] = $inschrijving;
		}
		return $inschrijvingen;
	}

	/**
	 * Geef de cursus info mee, alleen actieve cursussen.
	 *
	 * @return array De cursus informatie.
	 */
	private function geef_cursussen() : array {
		$cursus_info = [];
		$docent_id   = current_user_can( BESTUUR ) ? 0 : get_current_user_id();
		foreach ( new Cursussen( true ) as $cursus ) {
			if ( ! $cursus->vervallen && ( 0 === $docent_id || intval( $cursus->docent ) === $docent_id ) ) {
				$cursus_info[ $cursus->id ] = [
					'start_dt'             => $cursus->start_datum,
					'code'                 => "C{$cursus->id}",
					'naam'                 => $cursus->naam,
					'docent'               => $cursus->docent_naam(),
					'start_datum'          => strftime( '%d-%m-%Y', $cursus->start_datum ),
					'heeft_inschrijvingen' => ! empty( $this->geef_inschrijvingen( $cursus->id ) ),
				];
			}
		}
		return $cursus_info;
	}

	/**
	 * Overzicht cursisten op cursus
	 *
	 * @param Cursus $cursus     De cursus.
	 * @return array De cursisten.
	 */
	private function cursistenlijst( Cursus $cursus ) : array {
		$cursisten = [];
		foreach ( new Inschrijvingen( $cursus->id, true ) as $inschrijving ) {
			$cursist      = get_userdata( $inschrijving->klant_id );
			$cursist_info = [
				'code'       => $inschrijving->code,
				'naam'       => $cursist->display_name . $inschrijving->toon_aantal(),
				'telnr'      => $cursist->telnr,
				'email'      => $cursist->user_email,
				'extra'      => true,
				'technieken' => implode( ', ', $inschrijving->technieken ),
			];
			if ( ! $inschrijving->hoofd_cursist_id ) {
				$order        = new Order( $inschrijving->geef_referentie() );
				$cursist_info = array_merge(
					$cursist_info,
					[
						'extra'          => false,
						'ingedeeld'      => $inschrijving->ingedeeld,
						'betaald'        => $order->gesloten,
						'restant_email'  => $inschrijving->restant_email,
						'herinner_email' => $inschrijving->herinner_email,
						'wacht'          => ! $inschrijving->ingedeeld && $inschrijving->datum > $cursus->start_datum && ! $order->id,
						'wachtlijst'     => ! $inschrijving->ingedeeld && 0 < $inschrijving->wacht_datum,
					]
				);
			}
			$cursisten[] = $cursist_info;
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
	protected function prepare( array &$data ) {
		$data['bestuur_rechten'] = current_user_can( BESTUUR );
		if ( 'cursisten' === $data['actie'] ) {
			$cursus            = new Cursus( $data['id'] );
			$data['cursus']    = [
				'id'    => $cursus->id,
				'naam'  => $cursus->naam,
				'code'  => $cursus->code,
				'loopt' => $cursus->start_datum < strtotime( 'today' ),
			];
			$data['cursisten'] = $this->cursistenlijst( $cursus );
			return true;
		}
		if ( 'indelen' === $data['actie'] || 'uitschrijven' === $data['actie'] ) {
			list( $cursus_id, $cursist_id ) = sscanf( $data['id'], 'C%d-%d' );
			$cursus                         = new Cursus( $cursus_id );
			$inschrijving                   = new Inschrijving( $cursus_id, $cursist_id );
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
			return true;
		}
		$data['cursus_info'] = $this->geef_cursussen();
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
	protected function validate( array &$data ) {
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
			'Geannuleerd',
		];
		fputcsv( $this->file_handle, $cursisten_fields, ';' );
		foreach ( new Inschrijvingen( $cursus_id ) as $inschrijving ) {
			$cursist          = get_userdata( $inschrijving->klant_id );
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
				$inschrijving->geannuleerd ? 'Ja' : 'Nee',
			];
			fputcsv( $this->file_handle, $cursist_gegevens, ';' );
		}
	}

	/**
	 * Maak een presentielijst aan.
	 *
	 * @return string Pad naar de presentielijst.
	 */
	protected function presentielijst() : string {
		$cursus_id = filter_input( INPUT_GET, 'cursus_id', FILTER_SANITIZE_NUMBER_INT );
		$cursus    = new Cursus( $cursus_id );
		$cursisten = [];
		foreach ( new Inschrijvingen( $cursus_id, true ) as $inschrijving ) {
			if ( $inschrijving->ingedeeld ) {
				$cursisten[] = get_user_by( 'id', $inschrijving->klant_id )->display_name . $inschrijving->toon_aantal();
			}
		}
		$presentielijst = new Presentielijst( 'L' );
		return $presentielijst->run( $cursus, $cursisten );
	}

	/**
	 * Deel een cursist in
	 *
	 * @param array $data data te bewaren.
	 *
	 * @return array
	 */
	protected function indelen( array $data ) : array {
		$inschrijving = new Inschrijving( $data['input']['cursus_id'], $data['input']['cursist_id'] );
		$inschrijving->actie->indelen_lopend( (float) $data['input']['kosten'] );

		return [
			'status'  => $this->status( 'De order is aangemaakt en een email met factuur is naar de cursist verstuurd' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Schrijf een cursist uit
	 *
	 * @param array $data data te bewaren.
	 *
	 * @return array
	 */
	protected function uitschrijven( array $data ) : array {
		$inschrijving = new Inschrijving( $data['input']['cursus_id'], $data['input']['cursist_id'] );
		$inschrijving->actie->uitschrijven_wachtlijst();
		return [
			'status'  => $this->status( 'De inschrijving is geannuleerd' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Stuur een herinner email
	 *
	 * @param array $data data te bewaren.
	 *
	 * @return array
	 */
	protected function herinner_email( array $data ) : array {
		$aantal_email = 0;
		// Alleen voor de cursisten die ingedeeld zijn en niet geannuleerd.
		foreach ( new Inschrijvingen( $data['input']['cursus_id'], true ) as $inschrijving ) {
			/**
			 * Stuur herinnerings emails als de cursist nog niet de cursus volledig betaald heeft.
			 */
			$aantal_email += $inschrijving->actie->herinnering();
		}
		return [
			'status'  => $this->status( ( $aantal_email > 0 ) ? "Emails zijn verstuurd naar $aantal_email cursisten" : 'Er zijn geen nieuwe emails verzonden' ),
			'content' => $this->display(),
		];
	}

}
