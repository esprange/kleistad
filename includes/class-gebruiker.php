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
	 * Registreer de gebruiker op basis van input
	 *
	 * @param array $data De input.
	 * @return int
	 */
	public static function registreren( array $data ) : int {
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
				return (int) wp_insert_user( (object) $userdata );
			}
			return (int) wp_update_user( (object) $userdata );
		}
		return $gebruiker_id;
	}
}
