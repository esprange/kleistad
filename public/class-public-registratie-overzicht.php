<?php
/**
 * Shortcode registratie overzicht.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad registratie overzicht class.
 */
class Public_Registratie_Overzicht extends Shortcode {

	/**
	 * Haal de registratie data op
	 *
	 * @return array de registraties.
	 */
	private function registraties() : array {
		$registraties = [];
		foreach ( get_users( [ 'orderby' => 'display_name' ] ) as $gebruiker ) {
			$registraties[ $gebruiker->ID ] = [
				'is_abonnee'         => false,
				'is_dagdelenkaart'   => false,
				'is_cursist'         => '',
				'deelnemer_info'     => [
					'naam'   => $gebruiker->display_name,
					'straat' => $gebruiker->straat,
					'huisnr' => $gebruiker->huisnr,
					'pcode'  => $gebruiker->pcode,
					'plaats' => $gebruiker->plaats,
					'telnr'  => $gebruiker->telnr,
					'email'  => $gebruiker->user_email,
				],
				'abonnee_info'       => [],
				'dagdelenkaart_info' => [],
				'inschrijving_info'  => [],
				'voornaam'           => $gebruiker->first_name,
				'achternaam'         => $gebruiker->last_name,
				'telnr'              => $gebruiker->telnr,
				'email'              => $gebruiker->user_email,
			];
		}
		foreach ( new Abonnementen() as $abonnement ) {
			$registraties[ $abonnement->klant_id ]['is_abonnee']   = ! $abonnement->is_geannuleerd();
			$registraties[ $abonnement->klant_id ]['abonnee_info'] = [
				'code'           => $abonnement->code,
				'start_datum'    => date( 'd-m-Y', $abonnement->start_datum ),
				'pauze_datum'    => $abonnement->pauze_datum ? date( 'd-m-Y', $abonnement->pauze_datum ) : '',
				'herstart_datum' => $abonnement->herstart_datum ? date( 'd-m-Y', $abonnement->herstart_datum ) : '',
				'eind_datum'     => $abonnement->eind_datum ? date( 'd-m-Y', $abonnement->eind_datum ) : '',
				'dag'            => ( 'beperkt' === $abonnement->soort ) ? $abonnement->dag : '',
				'soort'          => ucfirst( $abonnement->soort ),
				'extras'         => implode( ' ', $abonnement->extras ),
				'geannuleerd'    => $abonnement->is_geannuleerd(),
				'opmerking'      => $abonnement->opmerking,
			];
		}
		foreach ( new Dagdelenkaarten() as $dagdelenkaart ) {
			$registraties[ $dagdelenkaart->klant_id ]['is_dagdelenkaart']   = $dagdelenkaart->eind_datum >= strtotime( 'today' );
			$registraties[ $dagdelenkaart->klant_id ]['dagdelenkaart_info'] = [
				'code'        => $dagdelenkaart->code,
				'start_datum' => date( 'd-m-Y', $dagdelenkaart->start_datum ),
			];
		}
		foreach ( new Cursisten() as $cursist ) {
			foreach ( $cursist->inschrijvingen as $inschrijving ) {
				$registraties[ $cursist->ID ]['is_cursist']         .= "C{$inschrijving->cursus->id};";
				$registraties[ $cursist->ID ]['inschrijving_info'][] = [
					'ingedeeld'   => $inschrijving->ingedeeld,
					'geannuleerd' => $inschrijving->geannuleerd,
					'code'        => $inschrijving->code,
					'aantal'      => $inschrijving->aantal,
					'naam'        => $inschrijving->cursus->naam,
					'technieken'  => $inschrijving->technieken,
				];
			}
		}
		return $registraties;
	}

	/**
	 *
	 * Prepareer 'registratie_overzicht' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data ) {
		$data = [
			'registraties' => $this->registraties(),
			'cursussen'    => new Cursussen(),
		];
		return true;
	}

	/**
	 * Schrijf cursisten informatie naar het bestand.
	 */
	protected function cursisten() {
		$cursus_fields = [
			'Voornaam',
			'Achternaam',
			'Email',
			'Straat',
			'Huisnr',
			'Postcode',
			'Plaats',
			'Telefoon',
			'Lid',
			'Cursus',
			'Cursus code',
			'Inschrijf datum',
			'Inschrijf status',
			'Aantal',
			'Technieken',
			'Opmerking',
		];
		fputcsv( $this->file_handle, $cursus_fields, ';', '"' );
		foreach ( new Cursisten() as $cursist ) {
			$inschrijvingen = [];
			foreach ( $cursist->inschrijvingen as $inschrijving ) {
				$inschrijvingen[] = [
					'inschrijfdatum' => $inschrijving->datum,
					'data'           => array_merge(
						[
							'C' . $inschrijving->cursus->id . '-' . $inschrijving->cursus->naam,
							$inschrijving->code,
							date( 'd-m-Y', $inschrijving->datum ),
							$inschrijving->geannuleerd ? 'geannuleerd' : ( $inschrijving->ingedeeld ? 'ingedeeld' : 'wacht op betaling' ),
							$inschrijving->aantal,
							implode( ' ', $inschrijving->technieken ),
							$inschrijving->opmerking,
						]
					),
				];
			}
			foreach ( $inschrijvingen as $inschrijving ) {
				fputcsv(
					$this->file_handle,
					array_merge(
						[
							$cursist->first_name,
							$cursist->last_name,
							$cursist->user_email,
							$cursist->straat,
							$cursist->huisnr,
							$cursist->pcode,
							$cursist->plaats,
							$cursist->telnr,
							user_can( $cursist->ID, LID ) ? 'Ja' : 'Nee',
						],
						$inschrijving['data']
					),
					';',
					'"'
				);
			}
		}
	}

	/**
	 * Schrijf abonnees informatie naar het bestand.
	 */
	protected function abonnees() {
		$abonnee_fields = [
			'Achternaam',
			'Voornaam',
			'Email',
			'Straat',
			'Huisnr',
			'Postcode',
			'Plaats',
			'Telefoon',
			'Status',
			'Inschrijf datum',
			'Start_datum',
			'Pauze_datum',
			'Eind_datum',
			'Abonnee code',
			'Abonnement_soort',
			'Dag',
			'Abonnement_extras',
			'Opmerking',
		];
		fputcsv( $this->file_handle, $abonnee_fields, ';', '"' );
		foreach ( new Abonnees() as $abonnee ) {
			$abonnee_gegevens = [
				$abonnee->last_name,
				$abonnee->first_name,
				$abonnee->user_email,
				$abonnee->straat,
				$abonnee->huisnr,
				$abonnee->pcode,
				$abonnee->plaats,
				$abonnee->telnr,
				$abonnee->abonnement->geef_statustekst( false ),
				date( 'd-m-Y', $abonnee->abonnement->datum ),
				date( 'd-m-Y', $abonnee->abonnement->start_datum ),
				$abonnee->abonnement->pauze_datum ? date( 'd-m-Y', $abonnee->abonnement->pauze_datum ) : '',
				$abonnee->abonnement->eind_datum ? date( 'd-m-Y', $abonnee->abonnement->eind_datum ) : '',
				$abonnee->abonnement->code,
				$abonnee->abonnement->soort,
				'beperkt' === $abonnee->abonnement->soort ? $abonnee->abonnement->dag : '',
				implode( ', ', $abonnee->abonnement->extras ),
				$abonnee->abonnement->opmerking,
			];
			fputcsv( $this->file_handle, $abonnee_gegevens, ';', '"' );
		}
	}

	/**
	 * Schrijf dagdelenkaart informatie naar het bestand.
	 */
	protected function dagdelenkaarten() {
		$dagdelenkaart_fields = [
			'Achternaam',
			'Voornaam',
			'Email',
			'Straat',
			'Huisnr',
			'Postcode',
			'Plaats',
			'Telefoon',
			'Dagdelenkaart code',
			'Start_datum',
			'Eind_datum',
			'Opmerking',
		];
		fputcsv( $this->file_handle, $dagdelenkaart_fields, ';', '"' );
		foreach ( new Dagdelengebruikers() as $dagdelengebruiker ) {
			fputcsv(
				$this->file_handle,
				[
					$dagdelengebruiker->last_name,
					$dagdelengebruiker->first_name,
					$dagdelengebruiker->user_email,
					$dagdelengebruiker->straat,
					$dagdelengebruiker->huisnr,
					$dagdelengebruiker->pcode,
					$dagdelengebruiker->plaats,
					$dagdelengebruiker->telnr,
					$dagdelengebruiker->dagdelenkaart->code,
					date( 'd-m-Y', $dagdelengebruiker->dagdelenkaart->start_datum ),
					date( 'd-m-Y', $dagdelengebruiker->dagdelenkaart->eind_datum ),
					$dagdelengebruiker->dagdelenkaart->opmerking,
				],
				';',
				'"'
			);
		}
	}

}
