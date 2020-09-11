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
				'extras'           => [
					'filter'  => FILTER_SANITIZE_STRING,
					'flags'   => FILTER_REQUIRE_ARRAY,
					'options' => [ 'default' => [] ],
				],
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
			$gebruiker_id = email_exists( $data['input']['user_email'] );
			if ( false !== $gebruiker_id && \Kleistad\Roles::reserveer( $gebruiker_id ) ) {
				return [
					'status' => $this->status( new \WP_Error( 'niet toegestaan', 'Het is niet mogelijk om een bestaand abonnement via dit formulier te wijzigen' ) ),
				];
			};
			$gebruiker_id = upsert_user(
				[
					'ID'         => ( false !== $gebruiker_id ) ? $gebruiker_id : null,
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
		}
		$abonnement                   = new \Kleistad\Abonnement( $gebruiker_id );
		$abonnement->soort            = $data['input']['abonnement_keuze'];
		$abonnement->opmerking        = $data['input']['opmerking'];
		$abonnement->start_datum      = strtotime( $data['input']['start_datum'] );
		$abonnement->start_eind_datum = strtotime( '+3 month', $abonnement->start_datum );
		$abonnement->reguliere_datum  = strtotime( 'first day of +4 month ', $abonnement->start_datum );
		$abonnement->dag              = $data['input']['dag'];
		$abonnement->extras           = $data['input']['extras'];
		$abonnement->artikel_type     = 'start';
		$abonnement->save();

		if ( 'ideal' === $data['input']['betaal'] ) {
			$ideal_uri = $abonnement->ideal( 'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging', $abonnement->referentie() );
			if ( is_string( $ideal_uri ) ) { // Er is gekozen voor een ideal betaling, dus redirect uitvoeren.
				if ( ! empty( $ideal_uri ) ) {
					return [ 'redirect_uri' => $ideal_uri ];
				}
				return [ 'status' => $this->status( new \WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
			}
		} else {
			$abonnement->email( '_start_bank', $abonnement->bestel_order( 0.0, $abonnement->start_datum ) );
			return [
				'content' => $this->goto_home(),
				'status'  => $this->status( 'De inschrijving van het abonnement is verwerkt en er wordt een email verzonden met bevestiging' ),
			];
		}
	}

}
