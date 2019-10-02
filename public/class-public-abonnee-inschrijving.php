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
	protected function prepare( &$data = null ) {
		if ( ! isset( $data['input'] ) ) {
			$data['input'] = [
				'gebruiker_id'     => 0,
				'EMAIL'            => '',
				'email_controle'   => '',
				'FNAME'            => '',
				'LNAME'            => '',
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
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => [ 'nicename' ],
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
	 * @return \WP_Error|bool
	 *
	 * @since   4.0.87
	 */
	protected function validate( &$data ) {
		$error         = new \WP_Error();
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'gebruiker_id'     => FILTER_SANITIZE_NUMBER_INT,
				'EMAIL'            => FILTER_SANITIZE_EMAIL,
				'email_controle'   => FILTER_SANITIZE_EMAIL,
				'FNAME'            => FILTER_SANITIZE_STRING,
				'LNAME'            => FILTER_SANITIZE_STRING,
				'straat'           => FILTER_SANITIZE_STRING,
				'huisnr'           => FILTER_SANITIZE_STRING,
				'pcode'            => FILTER_SANITIZE_STRING,
				'plaats'           => FILTER_SANITIZE_STRING,
				'telnr'            => FILTER_SANITIZE_STRING,
				'abonnement_keuze' => FILTER_SANITIZE_STRING,
				'extras'           => [
					'filter'  => FILTER_SANITIZE_STRING,
					'flags'   => FILTER_REQUIRE_ARRAY,
					'options' => [ 'default' => [] ],
				],
				'dag'              => FILTER_SANITIZE_STRING,
				'start_datum'      => FILTER_SANITIZE_STRING,
				'opmerking'        => FILTER_SANITIZE_STRING,
				'betaal'           => FILTER_SANITIZE_STRING,
				'mc4wp-subscribe'  => FILTER_SANITIZE_STRING,
			]
		);
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
	 * @return \WP_Error|array
	 *
	 * @since   4.0.87
	 */
	protected function save( $data ) {
		if ( $data['input']['gebruiker_id'] ) {
			$gebruiker_id = $data['input']['gebruiker_id'];
		} else {
			$gebruiker_id = email_exists( $data['input']['EMAIL'] );
			if ( false !== $gebruiker_id && \Kleistad\Roles::reserveer( $gebruiker_id ) ) {
				return new \WP_Error( 'niet toegestaan', 'Het is niet mogelijk om een bestaand abonnement via dit formulier te wijzigen' );
			};
			$gebruiker_id = Public_Main::upsert_user(
				[
					'ID'         => ( false !== $gebruiker_id ) ? $gebruiker_id : null,
					'first_name' => $data['input']['FNAME'],
					'last_name'  => $data['input']['LNAME'],
					'telnr'      => $data['input']['telnr'],
					'user_email' => $data['input']['EMAIL'],
					'straat'     => $data['input']['straat'],
					'huisnr'     => $data['input']['huisnr'],
					'pcode'      => $data['input']['pcode'],
					'plaats'     => $data['input']['plaats'],
				]
			);
		}
		$abonnement              = new \Kleistad\Abonnement( $gebruiker_id );
		$abonnement->soort       = $data['input']['abonnement_keuze'];
		$abonnement->opmerking   = $data['input']['opmerking'];
		$abonnement->start_datum = strtotime( $data['input']['start_datum'] );
		$abonnement->geannuleerd = false;
		$abonnement->gepauzeerd  = false;
		$abonnement->dag         = $data['input']['dag'];
		$abonnement->extras      = $data['input']['extras'];
		$abonnement->save();

		$status = $abonnement->start( $abonnement->start_datum, $data['input']['betaal'] );
		if ( is_string( $status ) ) { // Er is gekozen voor een ideal betaling, dus redirect uitvoeren.
			return [
				'redirect_uri' => $status,
			];
		}
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'De inschrijving van het abonnement is verwerkt en er wordt een email verzonden met bevestiging' ),
		];
	}

}