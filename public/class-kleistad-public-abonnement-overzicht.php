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
	protected function prepare( &$data = null ) {
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
					'status' => $abonnement->status(),
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
	 * Schrijf abonnementen naar het bestand.
	 */
	protected function abonnementen() {
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
		$abonnees_fields = array_merge(
			$abonnees_fields,
			[
				'Status',
				'Start',
				'Pauze',
				'Herstart',
				'Incasso',
			]
		);
		fputcsv( $this->file_handle, $abonnees_fields, ';', '"' );

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
				$abonnee_gegevens = array_merge(
					$abonnee_gegevens,
					[
						$abonnement->status(),
						$abonnement->start_datum ? strftime( '%d-%m-%y', $abonnement->start_datum ) : '',
						$abonnement->pauze_datum ? strftime( '%d-%m-%y', $abonnement->pauze_datum ) : '',
						$abonnement->herstart_datum ? strftime( '%d-%m-%y', $abonnement->herstart_datum ) : '',
						$betalen->heeft_mandaat( $abonnee_id ) ? 'ja' : 'nee',
					]
				);
				fputcsv( $this->file_handle, $abonnee_gegevens, ';', '"' );
			}
		}
	}

}
