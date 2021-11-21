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
	 * @since   4.0.87
	 *
	 * @return string
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	protected function prepare() : string {
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
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'registratie' form
	 *
	 * @since   4.0.87
	 *
	 * @return array
	 */
	protected function process() : array {
		$this->data['input'] = filter_input_array(
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
		if ( is_array( $this->data['input'] ) ) {
			$this->data['gebruiker_id'] = get_current_user_id();
			if ( ! $this->data['gebruiker_id'] ) {
				return $this->melding( new WP_Error( 'security', 'Er is een security fout geconstateerd' ) );
			}
			$error = $this->validator->gebruiker( $this->data['input'] );
			if ( ! is_bool( $error ) ) {
				return $this->melding( $error );
			}
			$gebruiker_id = email_exists( $this->data['input']['user_email'] );
			if ( false !== $gebruiker_id && $gebruiker_id !== $this->data['gebruiker_id'] ) {
				return $this->melding( new WP_Error( 'onjuist', 'Dit email adres is al in gebruik' ) );
			}
			return $this->save();
		}
		return $this->melding( new WP_Error( 'intern', 'Er is iets fout gegaan, probeer het opnieuw' ) );
	}

	/**
	 *
	 * Bewaar 'registratie' form gegevens
	 *
	 * @return WP_ERROR|array
	 *
	 * @since   4.0.87
	 */
	protected function save() : array {
		$result = wp_update_user(
			(object) [
				'ID'         => $this->data['gebruiker_id'],
				'first_name' => $this->data['input']['first_name'],
				'last_name'  => $this->data['input']['last_name'],
				'telnr'      => $this->data['input']['telnr'],
				'straat'     => $this->data['input']['straat'],
				'huisnr'     => $this->data['input']['huisnr'],
				'pcode'      => $this->data['input']['pcode'],
				'plaats'     => $this->data['input']['plaats'],
				'user_email' => $this->data['input']['user_email'],
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
