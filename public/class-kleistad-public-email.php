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

		$data['input']['tree'][0]['naam'] = 'Abonnees';
		$abonnementen                     = Kleistad_Abonnement::all();
		foreach ( $abonnementen as $abonnee_id => $abonnement ) {
			if ( ! $abonnement->geannuleerd ) {
				$data['input']['tree'][0]['leden'][ $abonnee_id ] = get_userdata( $abonnee_id )->display_name;
			}
		}
		$inschrijvingen = Kleistad_Inschrijving::all();
		$cursussen      = Kleistad_Cursus::all();
		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			foreach ( $cursist_inschrijvingen as $cursus_id => $inschrijving ) {
				if ( $inschrijving->ingedeeld && ! $inschrijving->geannuleerd && strtotime( '-6 months' ) < $cursussen[ $cursus_id ]->eind_datum ) {
					$data['input']['tree'][ $cursus_id ]['naam']                 = $cursussen[ $cursus_id ]->code . ' - ' . $cursussen[ $cursus_id ]->naam;
					$data['input']['tree'][ $cursus_id ]['leden'][ $cursist_id ] = get_userdata( $cursist_id )->display_name;
				}
			}
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
		$data['input']                  = filter_input_array(
			INPUT_POST,
			[
				'gebruikers'    => FILTER_SANITIZE_STRING,
				'onderwerp'     => FILTER_SANITIZE_STRING,
				'email_content' => FILTER_DEFAULT,
			]
		);
		$data['input']['email_content'] = wp_kses_post( $data['input']['email_content'] );

		if ( empty( $data['input']['email_content'] ) ) {
			$error->add( 'email', 'Er is geen email content' );
		}
		if ( empty( $data['input']['gebruikers'] ) ) {
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
		$error             = new WP_Error();
		$huidige_gebruiker = wp_get_current_user();
		$gebruiker_ids     = array_unique( explode( ',', $data['input']['gebruikers'] ) );
		$adressen          = [];
		foreach ( $gebruiker_ids as $gebruiker_id ) {
			$adressen[] = get_userdata( $gebruiker_id )->email_address;
		}
		Kleistad_Email::create(
			$adressen,
			$huidige_gebruiker->display_name,
			$data['input']['onderwerp'],
			'<p>Beste Kleistad gebruiker,</p>' . $data['input']['email_content'] . '<br/>'
		);
		return 'De email is naar ' . count( $adressen ) . ' personen verzonden';
	}
}
