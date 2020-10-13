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
	protected function prepare( &$data ) {
		if ( ! isset( $data['input'] ) ) {
			$data['input'] = [
				'tree'          => [],
				'email_content' => '',
				'onderwerp'     => '',
				'aanhef'        => 'Beste Kleistad gebruiker',
				'namens'        => wp_get_current_user()->display_name,
			];
		}

		$gebruiker       = wp_get_current_user();
		$bestuur_rechten = in_array( 'bestuur', (array) $gebruiker->roles, true );
		if ( $bestuur_rechten ) {
			$bestuur = get_users( [ 'role' => 'bestuur' ] );
			foreach ( $bestuur as $bestuurslid ) {
				$data['input']['tree'][-1]['naam']                      = 'Bestuur';
				$data['input']['tree'][-1]['leden'][ $bestuurslid->ID ] = $bestuurslid->display_name;
			}

			$docenten = get_users( [ 'role' => 'docenten' ] );
			foreach ( $docenten as $docent ) {
				$data['input']['tree'][-2]['naam']                 = 'Docenten';
				$data['input']['tree'][-2]['leden'][ $docent->ID ] = $docent->display_name;
			}

			$abonnementen = \Kleistad\Abonnement::all();
			foreach ( $abonnementen as $abonnee_id => $abonnement ) {
				if ( ! $abonnement->geannuleerd() ) {
					$abonnee                          = get_userdata( $abonnee_id );
					$data['input']['tree'][0]['naam'] = 'Abonnees';
					$data['input']['tree'][0]['leden'][ $abonnee->ID ] = $abonnee->display_name;
				}
			}
		}

		$cursus_criterium = strtotime( '-6 months' ); // Cursussen die langer dan een half jaar gelden zijn geëindigd worden niet getoond.
		$inschrijvingen   = \Kleistad\Inschrijving::all();
		$cursussen        = \Kleistad\Cursus::all();
		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			$cursist = get_userdata( $cursist_id );
			foreach ( $cursist_inschrijvingen as $cursus_id => $inschrijving ) {
				if ( ! $bestuur_rechten && intval( $cursussen[ $cursus_id ]->docent ) !== $gebruiker->ID ) {
					continue;
				}
				if ( $inschrijving->ingedeeld && ! $inschrijving->geannuleerd && $cursus_criterium < $cursussen[ $cursus_id ]->eind_datum ) {
					$data['input']['tree'][ $cursus_id ]['naam']                  = $cursussen[ $cursus_id ]->code . ' - ' . $cursussen[ $cursus_id ]->naam;
					$data['input']['tree'][ $cursus_id ]['leden'][ $cursist->ID ] = $cursist->display_name;
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
				'gebruikerids'  => FILTER_SANITIZE_STRING,
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
		if ( 'verzenden' === $data['form_actie'] && empty( $data['input']['gebruikerids'] ) ) {
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
		$gebruiker       = wp_get_current_user();
		$bestuur_rechten = in_array( 'bestuur', (array) $gebruiker->roles, true );
		$gebruikerids    = array_unique( explode( ',', $data['input']['gebruikerids'] ) );
		$query           = new \WP_User_Query(
			[
				'include' => array_map( 'intval', $gebruikerids ),
				'fields'  => [ 'user_email' ],
			]
		);
		$emailadressen   = array_column( (array) $query->get_results(), 'user_email' );
		$emailadressen[] = "{$gebruiker->display_name} <{$gebruiker->user_email}>";
		$emailer         = new \Kleistad\Email();
		$emailer->send(
			array_merge(
				$this->mail_parameters( $data ),
				[
					'to'       => 'Kleistad gebruiker <' . \Kleistad\Email::info() . \Kleistad\Email::domein() . '>',
					'bcc'      => $emailadressen,
					'from'     => \Kleistad\Email::info() . \Kleistad\Email::verzend_domein(),
					'reply-to' => $bestuur_rechten ? ( \Kleistad\Email::info() . \Kleistad\Email::domein() ) : $gebruiker->user_email,
					'subject'  => $data['input']['onderwerp'],
				]
			)
		);
		return [
			'status'  => $this->status( 'De email is naar ' . count( $emailadressen ) . ' personen verzonden' ),
			'content' => $this->goto_home(),
		];
	}

	/**
	 * Verzend een testemail
	 *
	 * @param array $data data te verzenden.
	 * @return array
	 */
	protected function test( $data ) {
		$gebruiker = wp_get_current_user();
		$emailer   = new \Kleistad\Email();
		$emailer->send(
			array_merge(
				$this->mail_parameters( $data ),
				[
					'to'      => "{$gebruiker->display_name} <{$gebruiker->user_email}>",
					'subject' => "TEST: {$data['input']['onderwerp']}",
				]
			)
		);
		return [
			'status' => $this->status( 'De test email is verzonden' ),
		];
	}

	/**
	 * Vul de generieke mail parameters in (welke zowel voor een testbericht als echt bericht identiek zijn).
	 *
	 * @param array $data Date te verzenden.
	 * @return array
	 */
	private function mail_parameters( $data ) {
		return [
			'from_name' => "{$data['input']['namens']} namens Kleistad",
			'content'   => "<p>{$data['input']['aanhef']},</p>{$data['input']['email_content']}<br/>",
			'sign'      => "{$data['input']['namens']},<br/>Kleistad",
			'auto'      => false,
		];
	}
}
