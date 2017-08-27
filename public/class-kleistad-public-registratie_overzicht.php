<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
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
class Kleistad_Public_RegistratieOverzicht extends Kleistad_Public_Shortcode {

	/**
	 *
	 * prepareer 'registratie_overzicht' form
	 *
	 * @return array
	 *
	 * @since   4.0.0
	 */
	public function prepare( $data = null ) {
		$cursusStore = new Kleistad_Cursussen();
		$cursussen = $cursusStore->get();
		$registraties = [];

		$inschrijvingStore = new Kleistad_Inschrijvingen();
		$inschrijvingen = $inschrijvingStore->get();
		$gebruikerStore = new Kleistad_Gebruikers;
		$gebruikers = $gebruikerStore->get();
		foreach ( $gebruikers as $gebruiker_id => $gebruiker ) {
			$cursuslijst = '';
			$inschrijvinglijst = [];
			$is_lid = ( ! empty( $gebruiker->rol ) or ( is_array( $gebruiker->rol ) and ( count( $gebruiker->rol ) > 0 ) ) );
			if ( $is_lid ) {
				$abonnement = new Kleistad_Abonnement( $gebruiker_id );
				$abonnee_info = [
					'code' => $abonnement->code,
					'start_datum' => date( 'd-m-Y', $abonnement->start_datum ),
					'dag' => $abonnement->beperkt ? $abonnement->dag : '',
					'beperkt' => $abonnement->beperkt ? 'beperkt' : 'onbeperkt',
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
		return compact( 'registraties', 'cursussen' );
	}

	/**
	 *
	 * valideer/sanitize 'registratie_overzicht' form
	 *
	 * @return array
	 *
	 * @since   4.0.0
	 */
	public function validate() {
		// nothing to validate
		return true;
	}

	/**
	 *
	 * bewaar 'registratie_overzicht' form gegevens
	 *
	 * @return string
	 *
	 * @since   4.0.0
	 */
	public function save( $data ) {
		if ( ! Kleistad_Roles::override() ) {
			return '';
		}
		$error = new WP_Error();

		$cursusStore = new Kleistad_Cursussen();
		$cursussen = $cursusStore->get();

		$gebruikerStore = new Kleistad_Gebruikers();
		$gebruikers = $gebruikerStore->get();

		$inschrijvingStore = new Kleistad_Inschrijvingen();
		$inschrijvingen = $inschrijvingStore->get();

		$upload_dir = wp_upload_dir();
		$bijlage = $upload_dir['basedir'] . '/registratiebestand_' . date( 'Y_m_d' ) . '.csv';
		$f = fopen( $bijlage, 'w' );

		$fields = [ 'Achternaam', 'Voornaam', 'Email', 'Straat', 'Huisnr', 'Postcode', 'Plaats', 'Telefoon', 'Lid', 'Cursus', 'Cursus code', 'Inschrijf datum', 'Inschrijf status', 'Technieken', 'Opmerking' ];
		fputcsv( $f, $fields, ';', '"' );

		foreach ( $gebruikers as $gebruiker_id => $gebruiker ) {
			$is_lid = ( ! empty( $gebruiker->rol ) or ( is_array( $gebruiker->rol ) and ( count( $gebruiker->rol ) > 0 ) ) );

			$values = [
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
					$values = array_merge(
						$values, [
							$cursussen[ $cursus_id ]->naam,
							$inschrijving->code,
							date( 'm-d-Y', $inschrijving->datum ),
							$inschrijving->ingedeeld ? 'ingedeeld' : $inschrijving->i_betaald ? 'wachtlijst' : 'wacht op betaling',
							implode( ' ', $inschrijving->technieken ),
							$inschrijving->opmerking,
						]
					);
					fputcsv( $f, $values, ';', '"' );
				}
			} else {
				fputcsv( $f, $values, ';', '"' );
			}
		}
		fclose( $f );

		$gebruiker = wp_get_current_user();
		$to = "$gebruiker->user_firstname $gebruiker->user_lastname <$gebruiker->user_email>";
		$message = '<p>Bijgaand het bestand in .CSV formaat met alle registraties.</p>';
		$attachments = [ $bijlage ];
		if ( ! self::compose_email( $to, 'Kleistad registratiebestand', $message, [], $attachments ) ) {
			$error->add( 'fout', 'Er is een fout opgetreden' );
			return $error;
		}
		return 'Het bestand is per email verzonden.';
	}

}
