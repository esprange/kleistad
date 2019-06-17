<?php
/**
 * Shortcode email (email versturen naar groepen).
 *
 * @link       https://www.kleistad.nl
 * @since      5.5.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * De kleistad rapport email.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Email extends Kleistad_ShortcodeForm {

	/**
	 *
	 * Prepareer 'email' form inhoud
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   5.5.0
	 */
	protected function prepare( &$data = null ) {
		if ( is_null( $data ) ) {
			$data          = [];
			$data['input'] = [
				'groepen'       => [],
				'email_content' => '',
				'onderwerp'     => '',
			];
		}

		$data['groepen'][0] = 'Abonnees';
		$cursussen          = Kleistad_Cursus::all();
		foreach ( $cursussen as $cursus_id => $cursus ) {
			if ( $cursus->ruimte() === $cursus->maximum ) {
				continue;
			}
			$data['groepen'][ $cursus_id ] = 'Cursus ' . $cursus->code . ' ' . $cursus->naam;
		}

		return true;
	}

	/**
	 * Valideer/sanitize email form
	 *
	 * @param array $data Gevalideerde data.
	 * @return \WP_ERROR|bool
	 *
	 * @since   5.5.0
	 */
	protected function validate( &$data ) {
		$error                          = new WP_Error();
		$data['input']['groepen']       = filter_input(
			INPUT_POST,
			'groepen',
			FILTER_SANITIZE_NUMBER_INT,
			[
				'flags'   => FILTER_REQUIRE_ARRAY,
				'options' => [ 'default' => [] ],
			]
		);
		$data['input']['onderwerp']     = filter_input( INPUT_POST, 'onderwerp', FILTER_SANITIZE_STRING );
		$data['input']['email_content'] = wp_kses_post( filter_input( INPUT_POST, 'email_content' ) );

		if ( empty( $data['input']['email_content'] ) ) {
			$error->add( 'email', 'Er is geen email content' );
		}
		if ( empty( $data['input']['groepen'] ) ) {
			$error->add( 'email', 'Er is geen enkele groep geselecteerd' );
		}
		if ( empty( $data['input']['onderwerp'] ) ) {
			$error->add( 'email', 'Er is geen onderwerp opgegeven' );
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 *
	 * Verzend emails
	 *
	 * @param array $data data te verzenden.
	 * @return \WP_ERROR|string
	 *
	 * @since   5.5.0
	 */
	protected function save( $data ) {
		$error             = new WP_Error();
		$huidige_gebruiker = wp_get_current_user();
		$verzonden         = [];
		$geadresseerde     = [];
		$adressen          = [];
		$inschrijvingen    = Kleistad_Inschrijving::all();
		$abonnementen      = Kleistad_Abonnement::all();
		if ( ! is_user_logged_in() ) {
			$error->add( 'security', 'Dit formulier mag alleen ingevuld worden door ingelogde gebruikers' );
			return $error;
		}
		foreach ( $data['input']['groepen'] as $groep_id ) {
			$groep_id = intval( $groep_id );
			if ( 0 === $groep_id ) {
				$geadresseerde[0][''] = 'abonnee';
				foreach ( $abonnementen as $abonnee_id => $abonnement ) {
					if ( ! in_array( $abonnee_id, $verzonden, true ) && ! $abonnement->geannuleerd ) {
						$adressen[]  = get_userdata( $abonnee_id )->user_email;
						$verzonden[] = $abonnee_id;
					}
				}
			} else {
				$geadresseerde[1] = 'cursist';
				foreach ( $inschrijvingen as $cursist_id => $inschrijving ) {
					if ( ! in_array( $cursist_id, $verzonden, true ) && array_key_exists( $groep_id, $inschrijving ) ) {
						if ( $inschrijving[ $groep_id ]->ingedeeld && ! $inschrijving[ $groep_id ]->geannuleerd ) {
							$adressen[]  = get_userdata( $cursist_id )->user_email;
							$verzonden[] = $cursist_id;
						}
					}
				}
			}
		}
		Kleistad_Email::create(
			$adressen,
			$huidige_gebruiker->display_name,
			$data['input']['onderwerp'],
			'<p>Beste ' . implode( ' / ', $geadresseerde ) . ',</p>' . $data['input']['email_content'] . '<br/>'
		);
		return 'De email is naar ' . count( $verzonden ) . ' personen verzonden';
	}
}
