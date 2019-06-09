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
			if ( $cursus->ruimte === $cursus->maximum ) {
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
		$data['input']['email_content'] = sanitize_textarea_field( filter_input( INPUT_POST, 'email_content' ) );

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
		$geadresseerden    = '';
		if ( ! is_user_logged_in() ) {
			$error->add( 'security', 'Dit formulier mag alleen ingevuld worden door ingelogde gebruikers' );
			return $error;
		}
		$cursussen      = Kleistad_Cursus::all();
		$inschrijvingen = Kleistad_Inschrijving::all();
		foreach ( $cursussen as $cursus_id => $cursus ) {
			if ( in_array( $cursus_id, $data['input']['groepen'], true ) ) {
				foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
					foreach ( $cursist_inschrijvingen as $cursist_cursus_id => $cursist_inschrijving ) {
						if ( $cursist_cursus_id !== $cursus_id ) {
							continue;
						}
						if ( ! $cursist_inschrijving->ingedeeld || $cursist_inschrijving->geannuleerd || in_array( $cursist_id, $verzonden, true ) ) {
							continue;
						}
						$cursist = get_userdata( $cursist_id );
						Kleistad_Email::create(
							$cursist->user_email,
							$huidige_gebruiker->display_name,
							$data['input']['onderwerp'],
							'<p>Beste ' . $cursist->display_name . ',</p>' . $data['input']['email_content']
						);
						$verzonden[]    = $cursist_id;
						$geadresseerden = 'cursisten';
					}
				}
			}
		}

		if ( in_array( 0, $data['input']['groepen'], true ) ) {
			$geadresseerden .= empty( $geadresseerden ) ? 'abonnees' : ' en abonnees';
			$abonnementen    = Kleistad_Abonnement::all();
			foreach ( $abonnementen as $abonnee_id => $abonnement ) {
				if ( ! $abonnement->geannuleerd ) {
					$abonnee = get_userdata( $abonnee_id );
					Kleistad_Email::create(
						$abonnee->user_email,
						$huidige_gebruiker->display_name,
						$data['input']['onderwerp'],
						'<p>Beste ' . $abonnee->display_name . ',</p>' . $data['input']['email_content']
					);
					$verzonden[] = $abonnee_id;
				}
			}
		}
		$aantal = count( $verzonden );
		return "De email is naar $aantal $geadresseerden verzonden";
	}
}
