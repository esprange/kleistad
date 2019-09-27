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

namespace Kleistad;

/**
 * De kleistad rapport email.
 */
class Public_Email extends ShortcodeForm {

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
				'aanhef'        => 'Beste Kleistad gebruiker',
				'namens'        => wp_get_current_user()->display_name,
			];
		}

		$bestuur = get_users( [ 'role' => 'bestuur' ] );
		foreach ( $bestuur as $bestuurslid ) {
			$data['input']['tree'][-1]['naam']                              = 'Bestuur';
			$data['input']['tree'][-1]['leden'][ $bestuurslid->user_email ] = $bestuurslid->display_name;
		}

		$abonnementen = \Kleistad\Abonnement::all();
		foreach ( $abonnementen as $abonnee_id => $abonnement ) {
			if ( ! $abonnement->geannuleerd ) {
				$abonnee                          = get_userdata( $abonnee_id );
				$data['input']['tree'][0]['naam'] = 'Abonnees';
				$data['input']['tree'][0]['leden'][ $abonnee->user_email ] = $abonnee->display_name;
			}
		}
		$cursus_criterium = strtotime( '-6 months' ); // Cursussen die langer dan een half jaar gelden zijn geëindigd worden niet getoond.
		$inschrijvingen   = \Kleistad\Inschrijving::all();
		$cursussen        = \Kleistad\Cursus::all();
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
		$error                          = new \WP_Error();
		$data['input']                  = filter_input_array(
			INPUT_POST,
			[
				'adressen'      => FILTER_SANITIZE_STRING,
				'onderwerp'     => FILTER_SANITIZE_STRING,
				'email_content' => FILTER_DEFAULT,
				'aanhef'        => FILTER_SANITIZE_STRING,
				'namens'        => FILTER_SANITIZE_STRING,
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
		if ( empty( $data['input']['aanhef'] ) ) {
			$error->add( 'email', 'Er is niet aangegeven aan wie de email gericht is' );
		}
		if ( empty( $data['input']['namens'] ) ) {
			$error->add( 'email', 'Er is niet aangegeven wie de email verstuurt' );
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
	 * @return \WP_ERROR|array
	 *
	 * @since   5.5.0
	 */
	protected function save( $data ) {
		$adressen = array_unique( explode( ',', $data['input']['adressen'] ) );
		$emailer  = new \Kleistad\Email();
		foreach ( array_chunk( $adressen, 5 ) as $adressen_deel ) { // Maximaal 5 adressen per keer.
			$emailer->send(
				[
					'to'        => 'Kleistad gebruiker <info@' . \Kleistad\Email::domein() . '>',
					'bcc'       => $adressen_deel,
					'from_name' => "{$data['input']['namens']} namens Kleistad",
					'from'      => 'info@' . \Kleistad\Email::verzend_domein(),
					'reply-to'  => 'info@' . \Kleistad\Email::domein(),
					'subject'   => $data['input']['onderwerp'],
					'content'   => "<p>{$data['input']['aanhef']},</p>{$data['input']['email_content']}<br/>",
					'sign'      => "{$data['input']['namens']},<br/>Kleistad",
					'auto'      => false,
				]
			);
		}
		return [
			'status'  => 'De email is naar ' . count( $adressen ) . ' personen verzonden',
			'vervolg' => 'reset',
		];
	}

	/**
	 * Verzend een testemail
	 *
	 * @param array $data data te verzenden.
	 * @return array
	 */
	protected function test( $data ) {
		$huidige_gebruiker = wp_get_current_user();
		$emailer           = new \Kleistad\Email();
		$emailer->send(
			[
				'to'        => "{$huidige_gebruiker->display_name} <{$huidige_gebruiker->user_email}>",
				'from_name' => "{$data['input']['namens']} namens Kleistad",
				'subject'   => "TEST: {$data['input']['onderwerp']}",
				'content'   => "<p>{$data['input']['aanhef']},</p>{$data['input']['email_content']}<br/>",
				'sign'      => "{$huidige_gebruiker->display_name},<br/>Kleistad",
				'auto'      => false,
			]
		);
		return [
			'status'  => 'De test email is verzonden',
			'vervolg' => 'none',
		];
	}
}
