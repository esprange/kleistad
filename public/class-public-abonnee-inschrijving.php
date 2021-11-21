<?php
/**
 * Shortcode abonnee inschrijvingen.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_Error;

/**
 * De class Abonnee Inschrijving.
 */
class Public_Abonnee_Inschrijving extends ShortcodeForm {

	/**
	 * Prepareer 'abonnee_inschrijving' form
	 *
	 * @since   4.0.87
	 *
	 * return string
	 */
	protected function prepare() : string {
		if ( ! isset( $this->data['input'] ) ) {
			$this->data['input'] = [
				'gebruiker_id'     => 0,
				'user_email'       => '',
				'email_controle'   => '',
				'first_name'       => '',
				'last_name'        => '',
				'straat'           => '',
				'huisnr'           => '',
				'pcode'            => '',
				'plaats'           => '',
				'telnr'            => '',
				'abonnement_keuze' => '',
				'extras'           => [],
				'dag'              => '',
				'start_datum'      => '',
				'opmerking'        => '',
				'betaal'           => 'ideal',
				'mc4wp-subscribe'  => '0',
			];
		}
		$this->data['gebruikers'] = get_users(
			[
				'fields'       => [ 'ID', 'display_name' ],
				'orderby'      => 'display_name',
				'role__not_in' => [ LID ],
			]
		);
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'abonnee_inschrijving' form
	 *
	 * @return array
	 *
	 * @since   4.0.87
	 */
	protected function process() : array {
		$this->data['input'] = filter_input_array(
			INPUT_POST,
			[
				'gebruiker_id'     => FILTER_SANITIZE_NUMBER_INT,
				'user_email'       => FILTER_SANITIZE_EMAIL,
				'email_controle'   => FILTER_SANITIZE_EMAIL,
				'first_name'       => FILTER_SANITIZE_STRING,
				'last_name'        => FILTER_SANITIZE_STRING,
				'straat'           => FILTER_SANITIZE_STRING,
				'huisnr'           => FILTER_SANITIZE_STRING,
				'pcode'            => FILTER_SANITIZE_STRING,
				'plaats'           => FILTER_SANITIZE_STRING,
				'telnr'            => FILTER_SANITIZE_STRING,
				'abonnement_keuze' => FILTER_SANITIZE_STRING,
				'dag'              => FILTER_SANITIZE_STRING,
				'start_datum'      => FILTER_SANITIZE_STRING,
				'opmerking'        => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_FLAG_STRIP_LOW,
				],
				'betaal'           => FILTER_SANITIZE_STRING,
				'mc4wp-subscribe'  => FILTER_SANITIZE_STRING,
			]
		);
		if ( is_array( $this->data['input'] ) ) {
			if ( '' === $this->data['input']['abonnement_keuze'] ) {
				return $this->melding( new WP_Error( 'verplicht', 'Er is nog geen type abonnement gekozen' ) );
			}
			if ( '' === $this->data['input']['start_datum'] ) {
				return $this->melding( new WP_Error( 'verplicht', 'Er is nog niet aangegeven wanneer het abonnement moet ingaan' ) );
			}
			if ( 0 === intval( $this->data['input']['gebruiker_id'] ) ) {
				$error = $this->validator->gebruiker( $this->data['input'] );
				if ( ! is_bool( $error ) ) {
					return $this->melding( $error );
				}
			}
			return $this->save();
		}
		return $this->melding( new WP_Error( 'input', 'geen juiste data ontvangen' ) );
	}

	/**
	 * Bewaar 'abonnee_inschrijving' form gegevens
	 *
	 * @return array
	 *
	 * @since   4.0.87
	 * @suppressWarnings(PHPMD.StaticAccess)
	 */
	protected function save() : array {
		$gebruiker_id = Gebruiker::registreren( $this->data['input'] );
		if ( ! is_int( $gebruiker_id ) ) {
			return [ 'status' => $this->status( new WP_Error( 'intern', 'Er is iets fout gegaan, probeer het later opnieuw' ) ) ];
		}
		$abonnement = new Abonnement( $gebruiker_id );
		if ( $abonnement->factuur_maand && ! $abonnement->eind_datum ) {
			return [
				'status' => $this->status( new WP_Error( 'niet toegestaan', 'Het is niet mogelijk om een bestaand abonnement via dit formulier te wijzigen' ) ),
			];
		}
		$result = $abonnement->actie->starten(
			strtotime( $this->data['input']['start_datum'] ),
			$this->data['input']['abonnement_keuze'],
			$this->data['input']['dag'],
			$this->data['input']['opmerking'] ?? '',
			$this->data['input']['betaal']
		);
		if ( false === $result ) {
			return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
		}
		if ( is_string( $result ) ) {
			return [ 'redirect_uri' => $result ];
		}
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'De inschrijving van het abonnement is verwerkt en er wordt een email verzonden met bevestiging' ),
		];
	}

}
