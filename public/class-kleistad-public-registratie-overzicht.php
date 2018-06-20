<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_Registratie_Overzicht extends Kleistad_Shortcode {

	/**
	 *
	 * Prepareer 'registratie_overzicht' form
	 *
	 * @param array $data data to be prepared.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$cursussen    = Kleistad_Cursus::all();
		$registraties = [];

		$inschrijvingen = Kleistad_Inschrijving::all();
		$gebruikers     = Kleistad_Gebruiker::all();
		$abonnementen   = Kleistad_Abonnement::all();
		foreach ( $gebruikers as $gebruiker_id => $gebruiker ) {
			$cursuslijst       = '';
			$inschrijvinglijst = [];
			$is_lid            = false;
			if ( array_key_exists( $gebruiker_id, $abonnementen ) ) {
				$is_lid       = true;
				$abonnee_info = [
					'code'        => $abonnementen[ $gebruiker_id ]->code,
					'start_datum' => date( 'd-m-Y', $abonnementen[ $gebruiker_id ]->start_datum ),
					'dag'         => ( 'beperkt' === $abonnementen[ $gebruiker_id ]->soort ) ? $abonnementen[ $gebruiker_id ]->dag : '',
					'soort'       => $abonnementen[ $gebruiker_id ]->soort,
					'geannuleerd' => $abonnementen[ $gebruiker_id ]->geannuleerd,
					'opmerking'   => $abonnementen[ $gebruiker_id ]->opmerking,
				];
			} else {
				$abonnee_info = [];
			}
			if ( array_key_exists( $gebruiker_id, $inschrijvingen ) ) {
				foreach ( $inschrijvingen[ $gebruiker_id ] as $cursus_id => $inschrijving ) {
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
				'naam'   => $gebruiker->voornaam . ' ' . $gebruiker->achternaam,
				'straat' => $gebruiker->straat,
				'huisnr' => $gebruiker->huisnr,
				'pcode'  => $gebruiker->pcode,
				'plaats' => $gebruiker->plaats,
				'telnr'  => $gebruiker->telnr,
				'email'  => $gebruiker->email,
			];

			$registraties[] = [
				'is_lid'         => $is_lid,
				'cursuslijst'    => $cursuslijst,
				'deelnemer_info' => $deelnemer_info,
				'abonnee_info'   => $abonnee_info,
				'inschrijvingen' => $inschrijvinglijst,
				'achternaam'     => $gebruiker->achternaam,
				'voornaam'       => $gebruiker->voornaam,
				'email'          => $gebruiker->email,
				'telnr'          => $gebruiker->telnr,
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
	 * @param array $data Returned data.
	 * @return array
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
	 * @param array $data data to save.
	 * @return string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		if ( ! Kleistad_Roles::override() ) {
			return true;
		}
		$csv   = tempnam( sys_get_temp_dir(), $data['download'] );
		$f_csv = fopen( $csv, 'w' );
		fwrite( $f_csv, "\xEF\xBB\xBF" );

		switch ( $data['download'] ) {
			case 'cursisten':
				$cursussen      = Kleistad_Cursus::all();
				$gebruikers     = Kleistad_Gebruiker::all();
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
				foreach ( $gebruikers as $gebruiker_id => $gebruiker ) {
					$is_lid = ( ! empty( $gebruiker->rol ) || ( is_array( $gebruiker->rol ) && ( count( $gebruiker->rol ) > 0 ) ) );

					$gebruiker_gegevens = [
						$gebruiker->achternaam,
						$gebruiker->voornaam,
						$gebruiker->email,
						$gebruiker->straat,
						$gebruiker->huisnr,
						$gebruiker->pcode,
						$gebruiker->plaats,
						$gebruiker->telnr,
						$is_lid ? 'Ja' : 'Nee',
					];

					if ( array_key_exists( $gebruiker_id, $inschrijvingen ) ) {
						foreach ( $inschrijvingen[ $gebruiker_id ] as $cursus_id => $inschrijving ) {
							$gebruiker_cursus_gegevens = array_merge(
								$gebruiker_gegevens, [
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
							fputcsv( $f_csv, $gebruiker_cursus_gegevens, ';', '"' );
						}
					}
				}
				break;
			case 'abonnees':
				$gebruikers     = Kleistad_Gebruiker::all();
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
				foreach ( $gebruikers as $gebruiker_id => $gebruiker ) {
					$is_lid = ( ! empty( $gebruiker->rol ) || ( is_array( $gebruiker->rol ) && ( count( $gebruiker->rol ) > 0 ) ) );

					$gebruiker_gegevens = [
						$gebruiker->achternaam,
						$gebruiker->voornaam,
						$gebruiker->email,
						$gebruiker->straat,
						$gebruiker->huisnr,
						$gebruiker->pcode,
						$gebruiker->plaats,
						$gebruiker->telnr,
						$is_lid ? 'Ja' : 'Nee',
					];

					if ( array_key_exists( $gebruiker_id, $abonnementen ) ) {
						$gebruiker_abonnee_gegevens = array_merge(
							$gebruiker_gegevens, [
								date( 'd-m-Y', $abonnementen[ $gebruiker_id ]->datum ),
								date( 'd-m-Y', $abonnementen[ $gebruiker_id ]->start_datum ),
								$abonnementen[ $gebruiker_id ]->code,
								$abonnementen[ $gebruiker_id ]->soort,
								( 'beperkt' === $abonnementen[ $gebruiker_id ]->soort ) ? $abonnementen[ $gebruiker_id ]->dag : '',
								$abonnementen[ $gebruiker_id ]->opmerking,
							]
						);
						fputcsv( $f_csv, $gebruiker_abonnee_gegevens, ';', '"' );
					}
				}
				break;
			default:
				unlink( $csv );
				return true;
		}
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
