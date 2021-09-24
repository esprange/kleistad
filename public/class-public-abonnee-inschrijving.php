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
	 * @param array $data data voor het formulier.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( array &$data ) {
		if ( ! isset( $data['input'] ) ) {
			$data['input'] = [
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
		$data['gebruikers'] = get_users(
			[
				'fields'       => [ 'ID', 'display_name' ],
				'orderby'      => 'display_name',
				'role__not_in' => [ LID ],
			]
		);
		return true;
	}

	/**
	 * Valideer/sanitize 'abonnee_inschrijving' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return WP_Error|bool
	 *
	 * @since   4.0.87
	 */
	protected function validate( array &$data ) {
		$data['input'] = filter_input_array(
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
		if ( is_array( $data['input'] ) ) {
			if ( '' === $data['input']['abonnement_keuze'] ) {
				return new WP_Error( 'verplicht', 'Er is nog geen type abonnement gekozen' );
			}
			if ( '' === $data['input']['start_datum'] ) {
				return new WP_Error( 'verplicht', 'Er is nog niet aangegeven wanneer het abonnement moet ingaan' );
			}
			if ( 0 === intval( $data['input']['gebruiker_id'] ) ) {
				$error = $this->validator->gebruiker( $data['input'] );
				if ( is_wp_error( $error ) ) {
					return $error;
				}
			}
			return true;
		}
		return new WP_Error( 'input', 'geen juiste data ontvangen' );
	}

	/**
	 * Bewaar 'abonnee_inschrijving' form gegevens
	 *
	 * @param array $data te bewaren data.
	 * @return WP_Error|array
	 *
	 * @since   4.0.87
	 * @suppressWarnings(PHPMD.StaticAccess)
	 */
	protected function save( array $data ) : array {
		$gebruiker_id = Gebruiker::registreren( $data['input'] );
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
			strtotime( $data['input']['start_datum'] ),
			$data['input']['abonnement_keuze'],
			$data['input']['dag'],
			$data['input']['opmerking'] ?? '',
			$data['input']['betaal']
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
