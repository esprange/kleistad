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
		$cursus_store = new Kleistad_Cursussen();
		$cursussen = $cursus_store->get();
		$registraties = [];

		$inschrijving_store = new Kleistad_Inschrijvingen();
		$inschrijvingen = $inschrijving_store->get();
		$gebruiker_store = new Kleistad_Gebruikers();
		$gebruikers = $gebruiker_store->get();
		foreach ( $gebruikers as $gebruiker_id => $gebruiker ) {
			$cursuslijst = '';
			$inschrijvinglijst = [];
			$is_lid = ( ! empty( $gebruiker->rol ) || ( is_array( $gebruiker->rol ) && ( count( $gebruiker->rol ) > 0 ) ) );
			if ( $is_lid ) {
				$abonnement = new Kleistad_Abonnement( $gebruiker_id );
				$abonnee_info = [
					'code' => $abonnement->code,
					'start_datum' => date( 'd-m-Y', $abonnement->start_datum ),
					'dag' => ( 'beperkt' === $abonnement->soort ) ? $abonnement->dag : '',
					'soort' => $abonnement->soort,
					'geannuleerd' => $abonnement->geannuleerd,
					'opmerking' => $abonnement->opmerking,
				];
			} else {
				$abonnee_info = [];
			}
			if ( array_key_exists( $gebruiker_id, $inschrijvingen ) ) {
				foreach ( $inschrijvingen[ $gebruiker_id ] as $cursus_id => $inschrijving ) {
					$cursuslijst .= 'C' . $cursus_id . ';';
					$inschrijvinglijst[] = [
						'ingedeeld' => $inschrijving->ingedeeld,
						'i_betaald' => $inschrijving->i_betaald,
						'c_betaald' => $inschrijving->c_betaald,
						'geannuleerd' => $inschrijving->geannuleerd,
						'code' => $inschrijving->code,
						'naam' => $cursussen[ $cursus_id ]->naam,
						'technieken' => $inschrijving->technieken,
					];
				}
			}
			$deelnemer_info = [
				'naam' => $gebruiker->voornaam . ' ' . $gebruiker->achternaam,
				'straat' => $gebruiker->straat,
				'huisnr' => $gebruiker->huisnr,
				'pcode' => $gebruiker->pcode,
				'plaats' => $gebruiker->plaats,
				'telnr' => $gebruiker->telnr,
				'email' => $gebruiker->email,
			];

			$registraties[] = [
				'is_lid' => $is_lid,
				'cursuslijst' => $cursuslijst,
				'deelnemer_info' => $deelnemer_info,
				'abonnee_info' => $abonnee_info,
				'inschrijvingen' => $inschrijvinglijst,
				'achternaam' => $gebruiker->achternaam,
				'voornaam' => $gebruiker->voornaam,
				'email' => $gebruiker->email,
				'telnr' => $gebruiker->telnr,
			];
		}
		$data = [
			'registraties' => $registraties,
			'cursussen' => $cursussen,
		];
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
			return '';
		}
		$error = new WP_Error();

		$cursus_store = new Kleistad_Cursussen();
		$cursussen = $cursus_store->get();

		$gebruiker_store = new Kleistad_Gebruikers();
		$gebruikers = $gebruiker_store->get();

		$inschrijving_store = new Kleistad_Inschrijvingen();
		$inschrijvingen = $inschrijving_store->get();

		$abonnementen_store = new Kleistad_Abonnementen();
		$abonnementen = $abonnementen_store->get();

		$upload_dir = wp_upload_dir();
		$bijlage_cursus = $upload_dir['basedir'] . '/cursusregistratiebestand_' . date( 'Y_m_d' ) . '.csv';
		$bijlage_abonnee = $upload_dir['basedir'] . '/abonneeregistratiebestand_' . date( 'Y_m_d' ) . '.csv';
		$f_cursus = fopen( $bijlage_cursus, 'w' );
		$f_abonnee = fopen( $bijlage_abonnee, 'w' );

		$cursus_fields = [
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
		fputcsv( $f_cursus, $cursus_fields, ';', '"' );
		$abonnee_fields = [
			'Achternaam',
			'Voornaam',
			'Email',
			'Straat',
			'Huisnr',
			'Postcode',
			'Plaats',
			'Telefoon',
			'Inschrijf datum',
			'Start_datum',
			'Abonnee code',
			'Abonnement_soort',
			'Dag',
			'Opmerking',
		];
		fputcsv( $f_abonnee, $abonnee_fields, ';', '"' );

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
			];

			if ( array_key_exists( $gebruiker_id, $inschrijvingen ) ) {
				foreach ( $inschrijvingen[ $gebruiker_id ] as $cursus_id => $inschrijving ) {
					$gebruiker_cursus_gegevens = array_merge(
						$gebruiker_gegevens, [
							$is_lid ? 'Ja' : 'Nee',
							$cursussen[ $cursus_id ]->naam,
							$inschrijving->code,
							date( 'm-d-Y', $inschrijving->datum ),
							$inschrijving->geannuleerd ? 'geannuleerd' : ( $inschrijving->ingedeeld ? 'ingedeeld' : ( $inschrijving->i_betaald ? 'wachtlijst' : 'wacht op betaling' ) ),
							implode( ' ', $inschrijving->technieken ),
							$inschrijving->i_betaald ? 'Ja' : 'Nee',
							$inschrijving->c_betaald ? 'Ja' : 'Nee',
							$inschrijving->opmerking,
						]
					);
					fputcsv( $f_cursus, $gebruiker_cursus_gegevens, ';', '"' );
				}
			}
			if ( array_key_exists( $gebruiker_id, $abonnementen ) ) {
				$gebruiker_abonnee_gegevens = array_merge(
					$gebruiker_gegevens, [
						date( 'm-d-Y', $abonnementen[ $gebruiker_id ]->datum ),
						date( 'm-d-Y', $abonnementen[ $gebruiker_id ]->start_datum ),
						$abonnementen[ $gebruiker_id ]->code,
						$abonnementen[ $gebruiker_id ]->soort,
						( 'beperkt' === $abonnementen[ $gebruiker_id ]->soort ) ? $abonnementen[ $gebruiker_id ]->dag : '',
						$abonnementen[ $gebruiker_id ]->opmerking,
					]
				);
				fputcsv( $f_abonnee, $gebruiker_abonnee_gegevens, ';', '"' );
			}
		}
		fclose( $f_cursus );
		fclose( $f_abonnee );

		$gebruiker = wp_get_current_user();
		$to = "$gebruiker->user_firstname $gebruiker->user_lastname <$gebruiker->user_email>";
		$message = '<p>Bijgaand de bestanden in .CSV formaat met alle registraties voor cursussen en abonnementen.</p>';
		$attachments = [ $bijlage_cursus, $bijlage_abonnee ];
		if ( ! self::compose_email( $to, 'Kleistad registratiebestanden', $message, [], $attachments ) ) {
			$error->add( 'fout', 'Er is een fout opgetreden' );
			return $error;
		}
		return 'De registratiebestanden zijn per email verzonden.';
	}

}
