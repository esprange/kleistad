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
	protected function prepare( &$data ) {
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
		$atts               = shortcode_atts(
			[ 'verklaring' => '' ],
			$this->atts,
			'kleistad_abonnee_inschrijving'
		);
		$gebruikers         = get_users(
			[
				'fields'       => [ 'ID', 'display_name' ],
				'orderby'      => 'display_name',
				'role__not_in' => [ LID ],
			]
		);
		$data['gebruikers'] = $gebruikers;
		$data['verklaring'] = htmlspecialchars_decode( $atts['verklaring'] );

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
	protected function validate( &$data ) {
		$error         = new WP_Error();
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
		) ?? [];
		if ( '' === $data['input']['abonnement_keuze'] ) {
			$error->add( 'verplicht', 'Er is nog geen type abonnement gekozen' );
		}
		if ( '' === $data['input']['start_datum'] ) {
			$error->add( 'verplicht', 'Er is nog niet aangegeven wanneer het abonnement moet ingaan' );
		}
		if ( 0 === intval( $data['input']['gebruiker_id'] ) ) {
			$this->validate_gebruiker( $error, $data['input'] );
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Bewaar 'abonnee_inschrijving' form gegevens
	 *
	 * @param array $data te bewaren data.
	 * @return WP_Error|array
	 *
	 * @since   4.0.87
	 */
	protected function save( $data ) {
		$gebruiker_id = $data['input']['gebruiker_id'] ?: email_exists( $data['input']['user_email'] );
		if ( false !== $gebruiker_id && user_can( $gebruiker_id, LID ) ) {
			return [
				'status' => $this->status( new WP_Error( 'niet toegestaan', 'Het is niet mogelijk om een bestaand abonnement via dit formulier te wijzigen' ) ),
			];
		};
		$gebruiker_id = upsert_user(
			[
				'ID'         => $gebruiker_id ?: null,
				'first_name' => $data['input']['first_name'],
				'last_name'  => $data['input']['last_name'],
				'telnr'      => $data['input']['telnr'],
				'user_email' => $data['input']['user_email'],
				'straat'     => $data['input']['straat'],
				'huisnr'     => $data['input']['huisnr'],
				'pcode'      => $data['input']['pcode'],
				'plaats'     => $data['input']['plaats'],
			]
		);
		if ( ! is_int( $gebruiker_id ) ) {
			return [ 'status' => $this->status( new WP_Error( 'intern', 'interne fout' ) ) ];
		}
		$abonnement = new Abonnement( $gebruiker_id );
		$abonnement->starten(
			strtotime( $data['input']['start_datum'] ),
			$data['input']['abonnement_keuze'],
			$data['input']['dag'],
			$data['input']['opmerking']
		);

		if ( 'ideal' === $data['input']['betaal'] ) {
			$ideal_uri = $abonnement->doe_idealbetaling( 'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging', $abonnement->geef_referentie() );
			if ( false === $ideal_uri ) {
				return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
			}
			return [ 'redirect_uri' => $ideal_uri ];
		}
		$abonnement->verzend_email( '_start_bank', $abonnement->bestel_order( 0.0, $abonnement->start_datum ) );
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'De inschrijving van het abonnement is verwerkt en er wordt een email verzonden met bevestiging' ),
		];
	}

}
