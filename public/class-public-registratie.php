<?php
/**
 * Shortcode registratie (wijzig persoonlijke registratie gegevens).
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_Error;

/**
 * De kleistad registratie class.
 */
class Public_Registratie extends ShortcodeForm {

	/**
	 * Prepareer 'registratie' form
	 *
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare() {
		$gebruiker = wp_get_current_user();

		if ( ! isset( $this->data['input'] ) ) {
			$this->data['input'] = [
				'gebruiker_id' => $gebruiker->ID,
				'first_name'   => $gebruiker->first_name,
				'last_name'    => $gebruiker->last_name,
				'straat'       => $gebruiker->straat,
				'huisnr'       => $gebruiker->huisnr,
				'pcode'        => $gebruiker->pcode,
				'plaats'       => $gebruiker->plaats,
				'telnr'        => $gebruiker->telnr,
				'user_email'   => $gebruiker->user_email,
			];
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'registratie' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return WP_ERROR|bool
	 *
	 * @since   4.0.87
	 */
	protected function validate( array &$data ) {
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'first_name' => FILTER_SANITIZE_STRING,
				'last_name'  => FILTER_SANITIZE_STRING,
				'straat'     => FILTER_SANITIZE_STRING,
				'huisnr'     => FILTER_SANITIZE_STRING,
				'pcode'      => FILTER_SANITIZE_STRING,
				'plaats'     => FILTER_SANITIZE_STRING,
				'telnr'      => FILTER_SANITIZE_STRING,
				'user_email' => FILTER_SANITIZE_EMAIL,
			]
		);
		if ( is_array( $data['input'] ) ) {
			$data['gebruiker_id'] = get_current_user_id();
			if ( ! $data['gebruiker_id'] ) {
				return new WP_Error( 'security', 'Er is een security fout geconstateerd' );
			}
			$error = $this->validator->gebruiker( $data['input'] );
			if ( is_wp_error( $error ) ) {
				return $error;
			}
			$gebruiker_id = email_exists( $data['input']['user_email'] );
			if ( false !== $gebruiker_id && $gebruiker_id !== $data['gebruiker_id'] ) {
				return new WP_Error( 'onjuist', 'Dit email adres is al in gebruik' );
			}
			return true;
		}
		return new WP_Error( 'intern', 'Er is iets fout gegaan, probeer het opnieuw' );
	}

	/**
	 *
	 * Bewaar 'registratie' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return WP_ERROR|array
	 *
	 * @since   4.0.87
	 */
	protected function save( array $data ) : array {
		$result = wp_update_user(
			(object) [
				'ID'         => $data['gebruiker_id'],
				'first_name' => $data['input']['first_name'],
				'last_name'  => $data['input']['last_name'],
				'telnr'      => $data['input']['telnr'],
				'straat'     => $data['input']['straat'],
				'huisnr'     => $data['input']['huisnr'],
				'pcode'      => $data['input']['pcode'],
				'plaats'     => $data['input']['plaats'],
				'user_email' => $data['input']['user_email'],
			]
		);
		if ( ! is_wp_error( $result ) ) {
			return [
				'content' => $this->goto_home(),
				'status'  => $this->status( 'Gegevens zijn opgeslagen' ),
			];
		}
		return [
			'status' => $this->status( new WP_Error( 'intern', 'Er is iets fout gegaan, probeer het a.u.b. opnieuw' ) ),
		];
	}
}
