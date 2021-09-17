<?php
/**
 * De definitie van de gebruiker class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use WP_User;
use WP_Error;

if ( ! function_exists( 'wp_delete_user' ) ) {
	require_once ABSPATH . 'wp-admin/includes/user.php';
}

/**
 * Kleistad Gebruiker class.
 *
 * @since 6.11.0
 *
 * @property string straat
 * @property string huisnr
 * @property string pcode
 * @property string plaats
 * @property string telnr
 */
class Gebruiker extends WP_User {

	/**
	 * Bepaal of de gebruiker nu actief is.
	 *
	 * @return bool True als actief.
	 */
	public function is_actief() : bool {
		return true;
	}

	/**
	 * Anonimiseer de gebruiker
	 *
	 * @return bool
	 */
	public function anonimiseer() : bool {
		if ( ! $this->ID ) {
			return false;
		}
		$emailer = new Email();
		add_filter( // Voorkom dat email over dummy emailadres verzonden wordt.
			'email_change_email',
			function() use ( $emailer ) {
				return [
					'to'      => "$emailer->info@$emailer->domein",
					'subject' => 'Anonimiseer gebruiker',
					'message' => "Gebruiker $this->display_name wordt geanonimiseerd",
					'headers' => [],
				];
			}
		);
		$stub = "verwijderd$this->ID";
		wp_update_user(
			(object) [
				'ID'            => $this->ID,
				'user_nicename' => $stub,
				'role'          => '',
				'display_name'  => "- $stub -",
				'user_email'    => "$stub@$emailer->domein",
				'nickname'      => '',
				'first_name'    => '',
				'last_name'     => $stub,
				'description'   => '',
				'telnr'         => '******',
				'straat'        => '******',
				'huisnr'        => '******',
				'pcode'         => '******',
				'plaats'        => '******',
			]
		);
		// Uiteindelijk moet ook de login naam geanonimiseerd worden.
		global $wpdb;
		$wpdb->update( $wpdb->users, [ 'user_login' => $stub ], [ 'ID' => $this->ID ] );
		return true;
	}

	/**
	 * Registreer de gebruiker op basis van input
	 *
	 * @param array $data De input.
	 * @return int|WP_Error
	 */
	public static function registreren( array $data ) {
		$gebruiker_id = intval( $data['gebruiker_id'] ?? 0 );
		if ( ! $gebruiker_id ) {
			$userdata = [
				'ID'         => email_exists( $data['user_email'] ) ?: null,
				'first_name' => $data['first_name'],
				'last_name'  => $data['last_name'],
				'telnr'      => $data['telnr'] ?? '',
				'user_email' => $data['user_email'],
				'straat'     => $data['straat'] ?? '',
				'huisnr'     => $data['huisnr'] ?? '',
				'pcode'      => $data['pcode'] ?? '',
				'plaats'     => $data['plaats'] ?? '',
			];
			if ( is_null( $userdata['ID'] ) ) {
				$userdata['role']          = '';
				$userdata['user_login']    = $userdata['user_email'];
				$userdata['user_pass']     = wp_generate_password( 12, true );
				$userdata['user_nicename'] = strtolower( $userdata['first_name'] . '-' . $userdata['last_name'] );
				return wp_insert_user( (object) $userdata );
			}
			return wp_update_user( (object) $userdata );
		}
		return $gebruiker_id;
	}

	/**
	 * Dagelijkse job
	 */
	public static function doe_dagelijks() {
		$gebruikers = get_users(
			[
				'role__not_in' => [ BESTUUR, DOCENT, BOEKHOUD, LID, INTERN ],
			]
		);
		foreach ( $gebruikers as $gebruiker ) {
			$cursist = new Cursist( $gebruiker->ID );
			if ( count( $cursist->inschrijvingen ) ) {
				continue;
			}
			$abonnee = new Abonnee( $gebruiker->ID );
			if ( $abonnee->abonnement->start_datum ) {
				continue;
			}
			$dagdelengebruiker = new Dagdelengebruiker( $gebruiker->ID );
			if ( $dagdelengebruiker->dagdelenkaart->start_datum ) {
				continue;
			}
			$stoker = new Stoker( $gebruiker->ID );
			if ( ! empty( $stoker->saldo->storting ) ) {
				continue;
			}
			error_log( 'opruimen van ' . $gebruiker->display_name ); // phpcs:ignore
			// phpcs:ignore wp_delete_user( $gebruiker->ID );
		}

	}
}
