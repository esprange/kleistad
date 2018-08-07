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
					'soort'          => $abonnementen[ $gebruiker->ID ]->soort,
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
				'naam'   => $gebruiker->first_name . ' ' . $gebruiker->last_name,
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
	 *
	 * Bewaar 'registratie_overzicht' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		if ( ! Kleistad_Roles::override() ) {
			return '';
		}
		$csv   = tempnam( sys_get_temp_dir(), $data['download'] );
		$f_csv = fopen( $csv, 'w' );
		fwrite( $f_csv, "\xEF\xBB\xBF" );

		switch ( $data['download'] ) {
			case 'cursisten':
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
					'Technieken',
					'Inschrijfgeld',
					'Cursusgeld',
					'Opmerking',
				];
				fputcsv( $f_csv, $cursus_fields, ';', '"' );
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
								$cursist_gegevens, [
									'C' . $cursus_id . '-' . $cursussen[ $cursus_id ]->naam,
									$inschrijving->code,
									date( 'd-m-Y', $inschrijving->datum ),
									$inschrijving->geannuleerd ? 'geannuleerd' : ( $inschrijving->ingedeeld ? 'ingedeeld' : ( $inschrijving->i_betaald ? 'wachtlijst' : 'wacht op betaling' ) ),
									implode( ' ', $inschrijving->technieken ),
									$inschrijving->i_betaald ? 'Ja' : 'Nee',
									$inschrijving->c_betaald ? 'Ja' : 'Nee',
									$inschrijving->opmerking,
								]
							);
							fputcsv( $f_csv, $cursist_cursus_gegevens, ';', '"' );
						}
					}
				}
				break;
			case 'abonnees':
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
					'Lid',
					'Inschrijf datum',
					'Start_datum',
					'Abonnee code',
					'Abonnement_soort',
					'Dag',
					'Opmerking',
				];
				fputcsv( $f_csv, $abonnee_fields, ';', '"' );
				foreach ( $abonnees as $abonnee ) {
					$is_lid = ( ! empty( $abonnee->role ) || ( is_array( $abonnee->role ) && ( count( $abonnee->role ) > 0 ) ) );

					$abonnee_gegevens = [
						$abonnee->last_name,
						$abonnee->first_name,
						$abonnee->user_email,
						$abonnee->straat,
						$abonnee->huisnr,
						$abonnee->pcode,
						$abonnee->plaats,
						$abonnee->telnr,
						$is_lid ? 'Ja' : 'Nee',
					];

					if ( array_key_exists( $abonnee->ID, $abonnementen ) ) {
						$abonnee_abonnement_gegevens = array_merge(
							$abonnee_gegevens, [
								date( 'd-m-Y', $abonnementen[ $abonnee->ID ]->datum ),
								date( 'd-m-Y', $abonnementen[ $abonnee->ID ]->start_datum ),
								$abonnementen[ $abonnee->ID ]->code,
								$abonnementen[ $abonnee->ID ]->soort,
								( 'beperkt' === $abonnementen[ $abonnee->ID ]->soort ) ? $abonnementen[ $abonnee->ID ]->dag : '',
								$abonnementen[ $abonnee->ID ]->opmerking,
							]
						);
						fputcsv( $f_csv, $abonnee_abonnement_gegevens, ';', '"' );
					}
				}
				break;
			default:
				unlink( $csv );
				return '';
		}
		fclose( $f_csv );
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
		exit;
	}
}
