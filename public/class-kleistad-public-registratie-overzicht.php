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

/**
 * De kleistad registratie overzicht class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Registratie_Overzicht extends Kleistad_ShortcodeForm {

	/**
	 * File handle voor download bestanden
	 *
	 * @var resource $file_handle De file pointer
	 */
	private $file_handle;

	/**
	 *
	 * Prepareer 'registratie_overzicht' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$cursussen    = Kleistad_Cursus::all();
		$registraties = [];

		$gebruikers     = get_users( [ 'orderby' => 'nicename' ] );
		$inschrijvingen = Kleistad_Inschrijving::all();
		$abonnementen   = Kleistad_Abonnement::all();
		foreach ( $gebruikers as $gebruiker ) {
			$cursuslijst       = '';
			$inschrijvinglijst = [];
			$is_lid            = false;
			if ( array_key_exists( $gebruiker->ID, $abonnementen ) ) {
				$is_lid       = true;
				$abonnee_info = [
					'code'           => $abonnementen[ $gebruiker->ID ]->code,
					'start_datum'    => date( 'd-m-Y', $abonnementen[ $gebruiker->ID ]->start_datum ),
					'pauze_datum'    => $abonnementen[ $gebruiker->ID ]->pauze_datum ? date( 'd-m-Y', $abonnementen[ $gebruiker->ID ]->pauze_datum ) : '',
					'herstart_datum' => $abonnementen[ $gebruiker->ID ]->herstart_datum ? date( 'd-m-Y', $abonnementen[ $gebruiker->ID ]->herstart_datum ) : '',
					'eind_datum'     => $abonnementen[ $gebruiker->ID ]->eind_datum ? date( 'd-m-Y', $abonnementen[ $gebruiker->ID ]->eind_datum ) : '',
					'dag'            => ( 'beperkt' === $abonnementen[ $gebruiker->ID ]->soort ) ? $abonnementen[ $gebruiker->ID ]->dag : '',
					'soort'          => ucfirst( $abonnementen[ $gebruiker->ID ]->soort ),
					'extras'         => implode( ' ', $abonnementen[ $gebruiker->ID ]->extras ),
					'geannuleerd'    => $abonnementen[ $gebruiker->ID ]->geannuleerd,
					'opmerking'      => $abonnementen[ $gebruiker->ID ]->opmerking,
				];
			} else {
				$abonnee_info = [];
			}
			if ( array_key_exists( $gebruiker->ID, $inschrijvingen ) ) {
				foreach ( $inschrijvingen[ $gebruiker->ID ] as $cursus_id => $inschrijving ) {
					$cursuslijst        .= 'C' . $cursus_id . ';';
					$inschrijvinglijst[] = [
						'ingedeeld'   => $inschrijving->ingedeeld,
						'i_betaald'   => $inschrijving->i_betaald,
						'c_betaald'   => $inschrijving->c_betaald,
						'geannuleerd' => $inschrijving->geannuleerd,
						'code'        => $inschrijving->code,
						'aantal'      => $inschrijving->aantal,
						'naam'        => $cursussen[ $cursus_id ]->naam,
						'technieken'  => $inschrijving->technieken,
					];
				}
			}
			$deelnemer_info = [
				'naam'   => $gebruiker->display_name,
				'straat' => $gebruiker->straat,
				'huisnr' => $gebruiker->huisnr,
				'pcode'  => $gebruiker->pcode,
				'plaats' => $gebruiker->plaats,
				'telnr'  => $gebruiker->telnr,
				'email'  => $gebruiker->user_email,
			];

			$registraties[] = [
				'is_lid'         => $is_lid,
				'cursuslijst'    => $cursuslijst,
				'deelnemer_info' => $deelnemer_info,
				'abonnee_info'   => $abonnee_info,
				'inschrijvingen' => $inschrijvinglijst,
				'voornaam'       => $gebruiker->first_name,
				'achternaam'     => $gebruiker->last_name,
				'telnr'          => $gebruiker->telnr,
				'email'          => $gebruiker->user_email,
			];
		}
		$data = [
			'registraties' => $registraties,
			'cursussen'    => $cursussen,
		];
		return true;
	}

	/**
	 * Valideer/sanitize 'registratie' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return bool
	 *
	 * @since   4.3.8
	 */
	public function validate( &$data ) {
		$data['download'] = filter_input( INPUT_POST, 'download', FILTER_SANITIZE_STRING );
		return true;
	}

	/**
	 * Schrijf cursisten informatie naar het bestand.
	 */
	private function cursisten() {
		$cursisten      = get_users( [ 'orderby' => 'nicename' ] );
		$cursussen      = Kleistad_Cursus::all();
		$inschrijvingen = Kleistad_Inschrijving::all();
		$cursus_fields  = [
			'Achternaam',
			'Voornaam',
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
			'Inschrijfgeld',
			'Cursusgeld',
			'Opmerking',
		];
		fwrite( $this->file_handle, "\xEF\xBB\xBF" );
		fputcsv( $this->file_handle, $cursus_fields, ';', '"' );
		foreach ( $cursisten as $cursist ) {
			$is_lid = ( ! empty( $cursist->role ) || ( is_array( $cursist->role ) && ( count( $cursist->role ) > 0 ) ) );

			$cursist_gegevens = [
				$cursist->first_name,
				$cursist->last_name,
				$cursist->user_email,
				$cursist->straat,
				$cursist->huisnr,
				$cursist->pcode,
				$cursist->plaats,
				$cursist->telnr,
				$is_lid ? 'Ja' : 'Nee',
			];

			if ( array_key_exists( $cursist->ID, $inschrijvingen ) ) {
				foreach ( $inschrijvingen[ $cursist->ID ] as $cursus_id => $inschrijving ) {
					$cursist_cursus_gegevens = array_merge(
						$cursist_gegevens,
						[
							'C' . $cursus_id . '-' . $cursussen[ $cursus_id ]->naam,
							$inschrijving->code,
							date( 'd-m-Y', $inschrijving->datum ),
							$inschrijving->geannuleerd ? 'geannuleerd' : ( $inschrijving->ingedeeld ? 'ingedeeld' : 'wacht op betaling' ),
							$inschrijving->aantal,
							implode( ' ', $inschrijving->technieken ),
							$inschrijving->i_betaald ? 'Ja' : 'Nee',
							$inschrijving->c_betaald ? 'Ja' : 'Nee',
							$inschrijving->opmerking,
						]
					);
					fputcsv( $this->file_handle, $cursist_cursus_gegevens, ';', '"' );
				}
			}
		}
		fclose( $this->file_handle );
	}

	/**
	 * Schrijf abonnees informatie naar het bestand.
	 */
	private function abonnees() {
		$abonnees       = get_users( [ 'orderby' => 'nicename' ] );
		$abonnementen   = Kleistad_Abonnement::all();
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
		fwrite( $this->file_handle, "\xEF\xBB\xBF" );
		fputcsv( $this->file_handle, $abonnee_fields, ';', '"' );
		foreach ( $abonnees as $abonnee ) {
			$abonnee_gegevens = [
				$abonnee->last_name,
				$abonnee->first_name,
				$abonnee->user_email,
				$abonnee->straat,
				$abonnee->huisnr,
				$abonnee->pcode,
				$abonnee->plaats,
				$abonnee->telnr,
			];

			if ( array_key_exists( $abonnee->ID, $abonnementen ) ) {
				$abonnee_abonnement_gegevens = array_merge(
					$abonnee_gegevens,
					[
						$abonnementen[ $abonnee->ID ]->status(),
						date( 'd-m-Y', $abonnementen[ $abonnee->ID ]->datum ),
						date( 'd-m-Y', $abonnementen[ $abonnee->ID ]->start_datum ),
						$abonnementen[ $abonnee->ID ]->pauze_datum ? date( 'd-m-Y', $abonnementen[ $abonnee->ID ]->pauze_datum ) : '',
						$abonnementen[ $abonnee->ID ]->eind_datum ? date( 'd-m-Y', $abonnementen[ $abonnee->ID ]->eind_datum ) : '',
						$abonnementen[ $abonnee->ID ]->code,
						$abonnementen[ $abonnee->ID ]->soort,
						( 'beperkt' === $abonnementen[ $abonnee->ID ]->soort ) ? $abonnementen[ $abonnee->ID ]->dag : '',
						implode( ', ', $abonnementen[ $abonnee->ID ]->extras ),
						$abonnementen[ $abonnee->ID ]->opmerking,
					]
				);
				fputcsv( $this->file_handle, $abonnee_abonnement_gegevens, ';', '"' );
			}
		}
		fclose( $this->file_handle );
	}

	/**
	 *
	 * Bewaar 'registratie_overzicht' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return \WP_Error|string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		$error = new WP_Error();

		if ( ! Kleistad_Roles::override() ) {
			$error->add( 'security', 'Dit formulier mag alleen ingevuld worden door ingelogde gebruikers' );
			return $error;
		}
		$csv    = tempnam( sys_get_temp_dir(), $data['download'] );
		$result = fopen( $csv, 'w' );
		if ( false === $result ) {
			$error->add( 'fout', 'Er kan geen bestand worden aangemaakt' );
			return $error;
		} else {
			$this->file_handle = $result;
		}
		call_user_func( [ $this, $data['download'] ] );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename=' . $data['download'] . '_' . strftime( '%Y%m%d' ) . '.csv' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $csv ) );
		ob_clean();
		flush();
		readfile( $csv ); // phpcs:ignore
		unlink( $csv );
		die();
	}
}
