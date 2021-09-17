<?php
/**
 * Shortcode email (email versturen naar GROEPen).
 *
 * @link       https://www.kleistad.nl
 * @since      5.5.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_Error;
use WP_User_Query;

/**
 * De kleistad rapport email.
 */
class Public_Email extends ShortcodeForm {

	private const GROEP = [
		'abonnees'           => 0,
		'dagdelengebruikers' => -1,
		'docenten'           => -2,
		'bestuur'            => -3,
		'wachters'           => -4,
	];

	/**
	 *
	 * Prepareer 'email' form inhoud
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   5.5.0
	 */
	protected function prepare( array &$data ) {
		if ( ! isset( $data['input'] ) ) {
			$data['input'] = [
				'tree'          => [],
				'email_content' => '',
				'onderwerp'     => '',
				'aanhef'        => 'Beste Kleistad gebruiker',
				'namens'        => wp_get_current_user()->display_name,
			];
		}

		if ( current_user_can( BESTUUR ) ) {
			$data['input']['tree'] = array_merge(
				$this->cursisten(),
				$this->wachtlijst(),
				$this->abonnees(),
				$this->dagdelengebruikers(),
				$this->docenten(),
				$this->bestuur()
			);
			return true;
		}

		$data['input']['tree'] = $this->cursisten();
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
				$data[ self::GROEP['abonnees'] ]['naam']                  = 'Abonnees';
				$data[ self::GROEP['abonnees'] ]['leden'][ $abonnee->ID ] = $abonnee->display_name;
			}
		}
		return $this->sorteren( $data );
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
				$data[ self::GROEP['dagdelengebruikers'] ]['naam']                            = 'Dagdelenkaarten';
				$data[ self::GROEP['dagdelengebruikers'] ]['leden'][ $dagdelengebruiker->ID ] = $dagdelengebruiker->display_name;
			}
		}
		return $this->sorteren( $data );
	}

	/**
	 * Haal de docenten gegevens op
	 *
	 * @return array
	 */
	private function docenten() : array {
		$data = [];
		foreach ( get_users( [ 'role' => DOCENT ] ) as $docent ) {
			$data[ self::GROEP['docenten'] ]['naam']                 = 'Docenten';
			$data[ self::GROEP['docenten'] ]['leden'][ $docent->ID ] = $docent->display_name;
		}
		return $this->sorteren( $data );
	}

	/**
	 * Haal de bestuursleden gegevens op
	 *
	 * @return array
	 */
	private function bestuur() : array {
		$data = [];
		foreach ( get_users( [ 'role' => BESTUUR ] ) as $bestuurslid ) {
			$data[ self::GROEP['bestuur'] ]['naam']                      = 'Bestuur';
			$data[ self::GROEP['bestuur'] ]['leden'][ $bestuurslid->ID ] = $bestuurslid->display_name;
		}
		return $this->sorteren( $data );
	}

	/**
	 * Haal de cursist gegevens op
	 *
	 * @return array
	 */
	private function cursisten() : array {
		$is_bestuur       = current_user_can( BESTUUR );
		$data             = [];
		$cursus_criterium = strtotime( '-6 months' ); // Cursussen die langer dan een half jaar gelden zijn geëindigd worden niet getoond.
		foreach ( new Cursisten() as $cursist ) {
			foreach ( $cursist->inschrijvingen as $inschrijving ) {
				if ( ! $is_bestuur && intval( $inschrijving->cursus->docent ) !== get_current_user_id() ) {
					continue;
				}
				if ( ! $inschrijving->geannuleerd && $inschrijving->ingedeeld && $cursus_criterium < $inschrijving->cursus->eind_datum ) {
					$data[ $inschrijving->cursus->id ]['naam']                  = "{$inschrijving->cursus->code} - {$inschrijving->cursus->naam}";
					$data[ $inschrijving->cursus->id ]['leden'][ $cursist->ID ] = $cursist->display_name;
				}
			}
		}
		return $this->sorteren( $data );
	}

	/**
	 * Sorteer op naam van de groep en de leden.
	 *
	 * @param array $data De groep en leden.
	 *
	 * @return array De gesorteerde lijst.
	 */
	private function sorteren( $data ) : array {
		usort(
			$data,
			function ( $links, $rechts ) {
				$retval = $links <=> $rechts;
				if ( 0 === $retval ) {
					$retval = strtoupper( $links['leden'] ) <=> strtoupper( $rechts['leden'] );
				}
				return $retval;
			}
		);
		return $data;
	}

	/**
	 * Haal de wachtlijst cursist gegevens op
	 *
	 * @return array
	 */
	private function wachtlijst() : array {
		$data                 = [];
		$wachtlijst_criterium = strtotime( 'today' );
		foreach ( new Cursisten() as $cursist ) {
			foreach ( $cursist->inschrijvingen as $inschrijving ) {
				if ( ! $inschrijving->geannuleerd && ! $inschrijving->ingedeeld && $wachtlijst_criterium < $inschrijving->cursus->eind_datum ) {
					$data[ self::GROEP['wachters'] ]['naam']                  = 'Cursisten op wachtlijst';
					$data[ self::GROEP['wachters'] ]['leden'][ $cursist->ID ] = $cursist->display_name;
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
	protected function validate( array &$data ) {
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
	 * Verzend test_email
	 *
	 * @param array $data data te verzenden.
	 * @return array
	 */
	protected function test_email( array $data ) : array {
		$gebruiker = wp_get_current_user();
		$emailer   = new Email();
		$emailer->send(
			array_merge(
				$this->mail_parameters( $data ),
				[
					'to'      => "$gebruiker->display_name <$gebruiker->user_email>",
					'subject' => "TEST: {$data['input']['onderwerp']}",
				]
			)
		);

		return [
			'status' => $this->status( 'De test email is verzonden' ),
		];
	}

	/**
	 * Verzend de email naar de geselecteerde ontvanger
	 *
	 * @param array $data data te verzenden.
	 * @return array
	 */
	protected function verzenden( array $data ) : array {
		$gebruiker       = wp_get_current_user();
		$emailer         = new Email();
		$gebruikerids    = array_unique( explode( ',', $data['input']['gebruikerids'] ) );
		$query           = new WP_User_Query(
			[
				'include' => array_map( 'intval', $gebruikerids ),
				'fields'  => [ 'user_email' ],
			]
		);
		$emailadressen   = array_column( $query->get_results(), 'user_email' );
		$emailadressen[] = "$gebruiker->display_name <$gebruiker->user_email>";
		$from            = 'production' === wp_get_environment_type() ? "$emailer->info@$emailer->domein" : get_bloginfo( 'admin_email' );
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
	private function mail_parameters( array $data ) : array {
		return [
			'from_name' => "{$data['input']['namens']} namens Kleistad",
			'content'   => "<p>{$data['input']['aanhef']},</p>{$data['input']['email_content']}<br/>",
			'sign'      => "{$data['input']['namens']},<br/>Kleistad",
			'auto'      => false,
		];
	}
}
