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

use WP_Error;
use WP_Query;
use WP_User_Query;

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

		$data['input']['tree'] = $this->cursisten();
		if ( current_user_can( BESTUUR ) ) {
			$data['input']['tree'] = array_merge(
				$data['input']['tree'],
				$this->abonnees(),
				$this->dagdelengebruikers(),
				$this->docenten(),
				$this->bestuur(),
			);
		}

		krsort( $data['input']['tree'] );
		return true;
	}

	/**
	 * Haal de abonnees gegevens op
	 *
	 * @return array
	 */
	private function abonnees() : array {
		$data = [];
		foreach ( new Abonnees() as $abonnee ) {
			if ( ! $abonnee->abonnement->is_geannuleerd() ) {
				$data[0]['naam']                  = 'Abonnees';
				$data[0]['leden'][ $abonnee->ID ] = $abonnee->display_name;
			}
		}
		return $data;
	}

	/**
	 * Haal de dagdelengebruikers gegevens op
	 *
	 * @return array
	 */
	private function dagdelengebruikers() : array {
		$data = [];
		foreach ( new Dagdelengebruikers() as $dagdelengebruiker ) {
			if ( $dagdelengebruiker->is_actief() ) {
				$data[-1]['naam']                            = 'Dagdelenkaarten';
				$data[-1]['leden'][ $dagdelengebruiker->ID ] = $dagdelengebruiker->display_name;
			}
		}
		return $data;
	}

	/**
	 * Haal de docenten gegevens op
	 *
	 * @return array
	 */
	private function docenten() : array {
		$data = [];
		foreach ( get_users( [ 'role' => DOCENT ] ) as $docent ) {
			$data[-2]['naam']                 = 'Docenten';
			$data[-2]['leden'][ $docent->ID ] = $docent->display_name;
		}
		return $data;
	}

	/**
	 * Haal de bestuursleden gegevens op
	 *
	 * @return array
	 */
	private function bestuur() : array {
		$data = [];
		foreach ( get_users( [ 'role' => BESTUUR ] ) as $bestuurslid ) {
			$data[-3]['naam']                      = 'Bestuur';
			$data[-3]['leden'][ $bestuurslid->ID ] = $bestuurslid->display_name;
		}
		return $data;
	}

	/**
	 * Haal de cursist gegevens op
	 *
	 * @return array
	 */
	private function cursisten() : array {
		$data             = [];
		$cursus_criterium = strtotime( '-6 months' ); // Cursussen die langer dan een half jaar gelden zijn geëindigd worden niet getoond.
		foreach ( new Cursisten() as $cursist ) {
			foreach ( $cursist->inschrijvingen as $inschrijving ) {
				if ( ! current_user_can( BESTUUR ) && intval( $inschrijving->cursus->docent ) !== get_current_user_id() ) {
					continue;
				}
				if ( $inschrijving->ingedeeld && ! $inschrijving->geannuleerd && $cursus_criterium < $inschrijving->cursus->eind_datum ) {
					$data[ $inschrijving->cursus->id ]['naam']                  = "{$inschrijving->cursus->code} - {$inschrijving->cursus->naam}";
					$data[ $inschrijving->cursus->id ]['leden'][ $cursist->ID ] = $cursist->display_name;
				}
			}
		}
		return $data;
	}

	/**
	 * Valideer/sanitize email form
	 *
	 * @param array $data Gevalideerde data.
	 * @return WP_ERROR|bool
	 *
	 * @since   5.5.0
	 */
	protected function validate( &$data ) {
		$error                          = new WP_Error();
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
	 * @return WP_ERROR|array
	 *
	 * @since   5.5.0
	 */
	protected function save( $data ) {
		$gebruiker = wp_get_current_user();
		$emailer   = new Email();
		if ( 'test_email' === $data['form_actie'] ) {
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
		$gebruikerids    = array_unique( explode( ',', $data['input']['gebruikerids'] ) );
		$query           = new WP_User_Query(
			[
				'include' => array_map( 'intval', $gebruikerids ),
				'fields'  => [ 'user_email' ],
			]
		);
		$emailadressen   = array_column( (array) $query->get_results(), 'user_email' );
		$emailadressen[] = "{$gebruiker->display_name} <{$gebruiker->user_email}>";
		$from            = 'production' === wp_get_environment_type() ? "{$emailer->info}{$emailer->domein}" : get_bloginfo( 'admin_email' );
		$emailer->send(
			array_merge(
				$this->mail_parameters( $data ),
				[
					'to'       => "Kleistad gebruiker <$from>",
					'bcc'      => $emailadressen,
					'from'     => $from,
					'reply-to' => current_user_can( BESTUUR ) ? $from : $gebruiker->user_email,
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
