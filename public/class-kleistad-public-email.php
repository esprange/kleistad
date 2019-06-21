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
				'tree'          => [],
				'email_content' => '',
				'onderwerp'     => '',
			];
		}

		$bestuur = get_users( [ 'role' => 'bestuur' ] );
		foreach ( $bestuur as $bestuurslid ) {
			$data['input']['tree'][-1]['naam']                              = 'Bestuur';
			$data['input']['tree'][-1]['leden'][ $bestuurslid->user_email ] = $bestuurslid->display_name;
		}

		$abonnementen = Kleistad_Abonnement::all();
		foreach ( $abonnementen as $abonnee_id => $abonnement ) {
			if ( ! $abonnement->geannuleerd ) {
				$abonnee                          = get_userdata( $abonnee_id );
				$data['input']['tree'][0]['naam'] = 'Abonnees';
				$data['input']['tree'][0]['leden'][ $abonnee->user_email ] = $abonnee->display_name;
			}
		}
		$cursus_criterium = strtotime( '-6 months' ); // Cursussen die langer dan een half jaar gelden zijn geëindigd worden niet getoond.
		$inschrijvingen   = Kleistad_Inschrijving::all();
		$cursussen        = Kleistad_Cursus::all();
		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			$cursist = get_userdata( $cursist_id );
			foreach ( $cursist_inschrijvingen as $cursus_id => $inschrijving ) {
				if ( $inschrijving->ingedeeld && ! $inschrijving->geannuleerd && $cursus_criterium < $cursussen[ $cursus_id ]->eind_datum ) {
					$data['input']['tree'][ $cursus_id ]['naam']                          = $cursussen[ $cursus_id ]->code . ' - ' . $cursussen[ $cursus_id ]->naam;
					$data['input']['tree'][ $cursus_id ]['leden'][ $cursist->user_email ] = $cursist->display_name;
				}
			}
		}
		krsort( $data['input']['tree'] );
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
		$data['input']                  = filter_input_array(
			INPUT_POST,
			[
				'adressen'      => FILTER_SANITIZE_STRING,
				'onderwerp'     => FILTER_SANITIZE_STRING,
				'email_content' => FILTER_DEFAULT,
			]
		);
		$data['input']['email_content'] = wp_kses_post( $data['input']['email_content'] );

		if ( empty( $data['input']['email_content'] ) ) {
			$error->add( 'email', 'Er is geen email content' );
		}
		if ( 'verzenden' === $data['form_actie'] && empty( $data['input']['adressen'] ) ) {
			$error->add( 'email', 'Er is geen enkele ontvanger geselecteerd' );
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
		$huidige_gebruiker = wp_get_current_user();
		$adressen          = array_unique( explode( ',', $data['input']['adressen'] ) );
		Kleistad_Email::create(
			$adressen,
			$huidige_gebruiker->display_name,
			$data['input']['onderwerp'],
			'<p>Beste Kleistad gebruiker,</p>' . $data['input']['email_content'] . '<br/>'
		);
		return 'De email is naar ' . count( $adressen ) . ' personen verzonden';
	}

	/**
	 * Verzend een testemail
	 *
	 * @param array $data data te verzenden.
	 * @return string
	 */
	protected function email( $data ) {
		$huidige_gebruiker = wp_get_current_user();
		Kleistad_Email::create(
			$huidige_gebruiker->user_email,
			$huidige_gebruiker->display_name,
			$data['input']['onderwerp'],
			'<p>Beste Kleistad gebruiker,</p>' . $data['input']['email_content'] . '<br/>'
		);
		return 'De test email is verzonden';
	}
}
