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

namespace Kleistad;

/**
 * De kleistad abonnement overzicht class.
 */
class Public_Abonnement_Overzicht extends Shortcode {

	/**
	 *
	 * Prepareer 'abonnement_overzicht'
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.5.4
	 */
	protected function prepare( &$data ) {
		$data['abonnee_info'] = [];
		$abonnees             = new Abonnees();
		foreach ( $abonnees as $abonnee ) {
			if ( ! $abonnee->abonnement->is_geannuleerd() ) {
				$data['abonnee_info'][] = [
					'naam'   => $abonnee->display_name,
					'email'  => $abonnee->user_email,
					'soort'  => $abonnee->abonnement->soort . ( 'beperkt' === $abonnee->abonnement->soort ? ' (' . $abonnee->abonnement->dag . ')' : '' ),
					'status' => $abonnee->abonnement->geef_statustekst( false ),
					'extras' => implode( ',<br/> ', $abonnee->abonnement->extras ),
				];
			}
		}
		return true;
	}

	/**
	 * Schrijf de eerste regel naar het download bestand.
	 */
	private function schrijf_labels() {
		$abonnees_fields = [
			'Code',
			'Achternaam',
			'Voornaam',
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
				'Overbrugging',
			]
		);
		fputcsv( $this->file_handle, $abonnees_fields, ';', '"' );
	}

	/**
	 * Schrijf één regel naar het bestand
	 *
	 * @param Abonnee $abonnee De abonnee.
	 */
	private function schrijf_record( Abonnee $abonnee ) {
		$abonnee_gegevens = [
			'A' . $abonnee->ID,
			$abonnee->first_name,
			$abonnee->last_name,
			$abonnee->user_email,
			$abonnee->abonnement->soort . ( 'beperkt' === $abonnee->abonnement->soort ? ' (' . $abonnee->abonnement->dag . ')' : '' ),
		];
		foreach ( $this->options['extra']  as $extra ) {
			$abonnee_gegevens[] = array_search( $extra['naam'], $abonnee->abonnement->extras, true ) ? 'ja' : '';
		}
		$abonnee_gegevens = array_merge(
			$abonnee_gegevens,
			[
				$abonnee->abonnement->geef_statustekst( false ),
				$abonnee->abonnement->start_datum ? strftime( '%d-%m-%Y', $abonnee->abonnement->start_datum ) : '',
				$abonnee->abonnement->pauze_datum ? strftime( '%d-%m-%Y', $abonnee->abonnement->pauze_datum ) : '',
				$abonnee->abonnement->herstart_datum ? strftime( '%d-%m-%Y', $abonnee->abonnement->herstart_datum ) : '',
				$abonnee->abonnement->betaling->incasso_actief() ? 'ja' : 'nee',
				$abonnee->abonnement->overbrugging_email ? 'ja' : 'nee',
			]
		);
		fputcsv( $this->file_handle, $abonnee_gegevens, ';', '"' );
	}

	/**
	 * Schrijf abonnementen naar het bestand.
	 */
	protected function abonnementen() {
		$this->schrijf_labels();
		foreach ( new Abonnees() as $abonnee ) {
			if ( ! $abonnee->abonnement->is_geannuleerd() ) {
				$this->schrijf_record( $abonnee );
			}
		}
	}
}
