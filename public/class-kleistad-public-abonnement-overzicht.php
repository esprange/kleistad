<?php
/**
 * Shortcode abonnement overzicht.
 *
 * @link       https://www.kleistad.nl
 * @since      4.5.6
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * De kleistad abonnement overzicht class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Abonnement_Overzicht extends Kleistad_ShortcodeForm {

	/**
	 *
	 * Prepareer 'cursus_overzicht' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.5.4
	 */
	public function prepare( &$data = null ) {
		$abonnementen = Kleistad_Abonnement::all();
		$abonnee_info = [];
		$email_lijst  = '';
		foreach ( $abonnementen as $abonnee_id => $abonnement ) {
			if ( ! $abonnement->geannuleerd ) {
				$abonnee        = get_userdata( $abonnee_id );
				$abonnee_info[] = [
					'naam'   => $abonnee->display_name,
					'telnr'  => $abonnee->telnr,
					'email'  => $abonnee->user_email,
					'soort'  => $abonnement->soort . ( 'beperkt' === $abonnement->soort ? ' (' . $abonnement->dag . ')' : '' ),
					'status' => $abonnement->start_datum > strtotime( 'today' ) ? 'nieuw' : ( $abonnement->gepauzeerd ? 'pauze' : 'actief' ),
					'extras' => implode( ',<br/> ', $abonnement->extras ),
				];
				$email_lijst   .= $abonnee->user_email . ';';
			}
		}
		$data = [
			'abonnee_info' => $abonnee_info,
			'email_lijst'  => $email_lijst,
		];
		return true;
	}

	/**
	 * Valideer/sanitize 'registratie' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return bool
	 *
	 * @since   4.5.6
	 */
	public function validate( &$data ) {
		return true;
	}

	/**
	 *
	 * Bewaar 'abonnement_overzicht' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return string
	 *
	 * @since   4.5.6
	 */
	public function save( $data ) {
		if ( ! Kleistad_Roles::override() ) {
			return '';
		}
		$csv   = tempnam( sys_get_temp_dir(), 'abonnees' );
		$f_csv = fopen( $csv, 'w' );
		fwrite( $f_csv, "\xEF\xBB\xBF" );

		$betalen         = new Kleistad_Betalen();
		$abonnementen    = Kleistad_Abonnement::all();
		$abonnees_fields = [
			'Code',
			'Achternaam',
			'Voornaam',
			'Telefoonnummer',
			'Email',
			'Soort',
		];
		foreach ( $this->options['extra'] as $extra ) {
			$abonnees_fields[] = ucfirst( $extra['naam'] );
		}
		$abonnees_fields = array_merge( $abonnees_fields,
			[
				'Status',
				'Start',
				'Pauze',
				'Herstart',
				'Incasso',
			]
		);
		fputcsv( $f_csv, $abonnees_fields, ';', '"' );

		foreach ( $abonnementen as $abonnee_id => $abonnement ) {
			if ( ! $abonnement->geannuleerd ) {
				$abonnee          = get_userdata( $abonnee_id );
				$abonnee_gegevens = [
					'A' . $abonnee_id,
					$abonnee->first_name,
					$abonnee->last_name,
					$abonnee->telnr,
					$abonnee->user_email,
					$abonnement->soort . ( 'beperkt' === $abonnement->soort ? ' (' . $abonnement->dag . ')' : '' ),
				];
				foreach ( $this->options['extra']  as $extra ) {
					$abonnee_gegevens[] = array_search( $extra['naam'], $abonnement->extras, true ) ? 'ja' : '';
				}
				$abonnee_gegevens = array_merge( $abonnee_gegevens,
					[
						$abonnement->start_datum > strtotime( 'today' ) ? 'nieuw' : ( $abonnement->gepauzeerd ? 'pauze' : 'actief' ),
						$abonnement->start_datum ? strftime( '%d-%m-%y', $abonnement->start_datum ) : '',
						$abonnement->pauze_datum ? strftime( '%d-%m-%y', $abonnement->pauze_datum ) : '',
						$abonnement->herstart_datum ? strftime( '%d-%m-%y', $abonnement->herstart_datum ) : '',
						$betalen->heeft_mandaat( $abonnee_id ) ? 'ja' : 'nee',
					]
				);
				fputcsv( $f_csv, $abonnee_gegevens, ';', '"' );
			}
		}
		fclose( $f_csv );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename=abonnementen.csv' );
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
