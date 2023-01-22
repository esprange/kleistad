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

use WP_Error;
use Exception;

/**
 * De kleistad abonnement overzicht class.
 */
class Public_Abonnement_Overzicht extends ShortcodeForm {

	/**
	 *
	 * Prepareer 'abonnement_overzicht'
	 *
	 * @since   4.5.4
	 *
	 * return string
	 */
	protected function prepare_overzicht() : string {
		$this->data['abonnee_info'] = [];
		$abonnees                   = new Abonnees();
		$betalen                    = new Betalen();
		foreach ( $abonnees as $abonnee ) {
			try {
				$mandaat = $betalen->heeft_mandaat( $abonnee->ID );
			} catch ( Exception ) {
				$mandaat = false;
			}
			if ( ! $abonnee->abonnement->is_geannuleerd() ) {
				$this->data['abonnee_info'][] = [
					'id'      => $abonnee->ID,
					'code'    => $abonnee->abonnement->code,
					'naam'    => $abonnee->display_name,
					'email'   => $abonnee->user_email,
					'soort'   => $abonnee->abonnement->soort,
					'status'  => $abonnee->abonnement->get_statustekst( false ),
					'extras'  => implode( ',<br/> ', $abonnee->abonnement->extras ),
					'mandaat' => ! $abonnee->abonnement->is_geannuleerd() && $mandaat,
				];
			}
		}
		return $this->content();
	}

	/**
	 * Toon de details van het abonnement
	 *
	 * @return string
	 */
	protected function prepare_wijzigen(): string {
		$betalen = new Betalen();
		try {
			$mandaat = $betalen->heeft_mandaat( $this->data['id'] );
		} catch ( Exception ) {
			$mandaat = false;
		}
		$this->data['abonnee'] = new Abonnee( $this->data['id'] );
		$this->data['mandaat'] = ! $this->data['abonnee']->abonnement->is_geannuleerd() && $mandaat;
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'cursus_overzicht' form
	 *
	 * @since   9.10.1
	 *
	 * @return array
	 */
	public function process() : array {
		$this->data['input'] = filter_input_array(
			INPUT_POST,
			[
				'id'               => FILTER_SANITIZE_NUMBER_INT,
				'soort'            => FILTER_SANITIZE_STRING,
				'start_datum'      => FILTER_SANITIZE_STRING,
				'start_eind_datum' => FILTER_SANITIZE_STRING,
				'pauze_datum'      => FILTER_SANITIZE_STRING,
				'herstart_datum'   => FILTER_SANITIZE_STRING,
				'eind_datum'       => FILTER_SANITIZE_STRING,
				'extras'           => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
			]
		);
		if ( 'wijzigen' === $this->form_actie ) {
			if ( ! is_array( $this->data['input']['extras'] ) ) {
				$this->data['input']['extras'] = [];
			}
			if ( strtotime( $this->data['input']['start_eind_datum'] ) < strtotime( $this->data['input']['start_datum'] ) ) {
				return $this->melding( new WP_Error( 'datums', 'De eind datum van de startperiode kan niet voor de start datum liggen' ) );
			}
			if ( empty( $this->data['input']['pauze_datum'] ) !== empty( $this->data['input']['herstart_datum'] ) ) {
				return $this->melding( new WP_Error( 'datums', 'Ingeval van pauze moet de pauze datum èn de herstart datum ingevoerd worden' ) );
			}
			if (
				! empty( $this->data['input']['pauze_datum'] ) &&
				( strtotime( $this->data['input']['start_datum'] ) >= strtotime( $this->data['input']['pauze_datum'] ) ) ||
				( strtotime( $this->data['input']['herstart_datum'] ) < strtotime( $this->data['input']['pauze_datum'] ) )
			) {
				return $this->melding( new WP_Error( 'datums', 'De start datum, pauze datum en herstart datum moeten in logische volgorde zijn' ) );
			}
		}
		return $this->save();
	}

	/**
	 * Wijzig het abonnement.
	 *
	 * @return array
	 */
	protected function wijzigen(): array {
		$abonnement = new Abonnement( $this->data['input']['id'] );
		$abonnement->actie->correctie( $this->data['input'] );
		return [
			'status'  => $this->status( 'Het abonnement is aangepast' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Stop het mollie Mandaat van de abonee.
	 *
	 * @return array
	 */
	protected function stop_mandaat(): array {
		$abonnement = new Abonnement( $this->data['input']['id'] );
		$abonnement->actie->stop_incasso();
		return [
			'status'  => $this->status( 'Het mandaat is verwijderd' ),
			'content' => $this->display(),
		];
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
			'Opmerking',
		];
		foreach ( opties()['extra'] as $extra ) {
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
		fputcsv( $this->filehandle, $abonnees_fields, ';' );
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
			$abonnee->abonnement->soort,
			$abonnee->abonnement->opmerking,
		];
		foreach ( opties()['extra']  as $extra ) {
			$abonnee_gegevens[] = array_search( $extra['naam'], $abonnee->abonnement->extras, true ) ? 'ja' : '';
		}
		$abonnee_gegevens = array_merge(
			$abonnee_gegevens,
			[
				$abonnee->abonnement->get_statustekst( false ),
				$abonnee->abonnement->start_datum ? wp_date( 'd-m-Y', $abonnee->abonnement->start_datum ) : '',
				$abonnee->abonnement->pauze_datum ? wp_date( 'd-m-Y', $abonnee->abonnement->pauze_datum ) : '',
				$abonnee->abonnement->herstart_datum ? wp_date( 'd-m-Y', $abonnee->abonnement->herstart_datum ) : '',
				$abonnee->abonnement->betaling->incasso_actief() ? 'ja' : 'nee',
				$abonnee->abonnement->overbrugging_email ? 'ja' : 'nee',
			]
		);
		fputcsv( $this->filehandle, $abonnee_gegevens, ';' );
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
