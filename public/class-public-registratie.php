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

/**
 * De kleistad registratie class.
 */
class Public_Registratie extends ShortcodeForm {

	/**
	 * Prepareer 'registratie' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data ) {
		$gebruiker = wp_get_current_user();

		if ( ! isset( $data['input'] ) ) {
			$data['input'] = [
				'gebruiker_id' => $gebruiker->ID,
				'voornaam'     => $gebruiker->first_name,
				'achternaam'   => $gebruiker->last_name,
				'straat'       => $gebruiker->straat,
				'huisnr'       => $gebruiker->huisnr,
				'pcode'        => $gebruiker->pcode,
				'plaats'       => $gebruiker->plaats,
				'telnr'        => $gebruiker->telnr,
				'email'        => $gebruiker->user_email,
			];
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'registratie' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return \WP_ERROR|bool
	 *
	 * @since   4.0.87
	 */
	protected function validate( &$data ) {
		$error                = new \WP_Error();
		$data['input']        = filter_input_array(
			INPUT_POST,
			[
				'voornaam'            => FILTER_SANITIZE_STRING,
				'achternaam'          => FILTER_SANITIZE_STRING,
				'straat'              => FILTER_SANITIZE_STRING,
				'huisnr'              => FILTER_SANITIZE_STRING,
				'pcode'               => FILTER_SANITIZE_STRING,
				'plaats'              => FILTER_SANITIZE_STRING,
				'telnr'               => FILTER_SANITIZE_STRING,
				'email'               => FILTER_SANITIZE_EMAIL,
				'huidig_wachtwoord'   => FILTER_SANITIZE_STRING,
				'nieuw_wachtwoord'    => FILTER_SANITIZE_STRING,
				'bevestig_wachtwoord' => FILTER_SANITIZE_STRING,
			]
		);
		$gebruiker            = wp_get_current_user();
		$data['gebruiker_id'] = $gebruiker->ID;
		if ( ! $data['gebruiker_id'] ) {
			$error->add( 'security', 'Er is een security fout geconstateerd' );
		} elseif ( 'wachtwoord' === $data['form_actie'] ) {
			if ( empty( $data['input']['huidig_wachtwoord'] ) || empty( $data['input']['nieuw_wachtwoord'] ) || empty( $data['input']['bevestig_wachtwoord'] ) ) {
				$error->add( 'onjuist', 'Alle velden moeten gevuld zijn' );
			}
			if ( ! wp_check_password( $data['input']['huidig_wachtwoord'], $gebruiker->data->user_pass, $gebruiker->ID ) ) {
				$error->add( 'onjuist', 'Het huidige wachtwoord is onjuist' );
			}
			if ( $data['input']['nieuw_wachtwoord'] !== $data['input']['bevestig_wachtwoord'] ) {
				$error->add( 'onjuist', 'Het nieuw ingevulde wachtwoord is niet gelijk aan het kopie wachtwoord' );
			}
			if ( 9 > strlen( $data['input']['nieuw_wachtwoord'] ) ) {
				$error->add( 'onjuist', 'Het nieuw ingevulde wachtwoord is te kort en moet minimaal 9 karakters lang zijn' );
			}
		} else {
			if ( ! empty( $data['input']['telnr'] ) && ! $this->validate_telnr( $data['input']['telnr'] ) ) {
				$error->add( 'onjuist', 'Het ingevoerde telefoonnummer lijkt niet correct' );
			}
			if ( ! empty( $data['input']['pcode'] ) && ! $this->validate_pcode( $data['input']['pcode'] ) ) {
				$error->add( 'onjuist', 'De ingevoerde postcode lijkt niet correct. Alleen Nederlandse postcodes kunnen worden doorgegeven' );
			}
			if ( ! $this->validate_naam( $data['input']['voornaam'] ) ) {
				$error->add( 'verplicht', 'Een voornaam (een of meer alfabetische karakters) is verplicht' );
				$data['input']['voornaam'] = '';
			}
			if ( ! $this->validate_naam( $data['input']['achternaam'] ) ) {
				$error->add( 'verplicht', 'Een achternaam (een of meer alfabetische karakters) is verplicht' );
				$data['input']['achternaam'] = '';
			}
			if ( ! $this->validate_email( $data['input']['email'] ) ) {
				$error->add( 'onjuist', 'Het email adres lijkt niet correct.' );
			} elseif ( 0 !== strcasecmp( $data['input']['email'], $gebruiker->user_email ) && email_exists( $data['input']['email'] ) ) {
				$error->add( 'onjuist', 'Dit email adres is al in gebruik' );
			}
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 *
	 * Bewaar 'registratie' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return \WP_ERROR|array
	 *
	 * @since   4.0.87
	 */
	protected function save( $data ) {
		if ( 'wachtwoord' === $data['form_actie'] ) {
			wp_update_user(
				[
					'ID'        => intval( $data['gebruiker_id'] ),
					'user_pass' => $data['input']['nieuw_wachtwoord'],
				]
			); // Bij gebruik update_user wordt de email notificatie verzonden, bij set_password niet.
			wp_set_auth_cookie( $data['gebruiker_id'], true );
			return [
				'content' => $this->goto_home(),
				'status'  => $this->status( 'Het wachtwoord is gewijzigd' ),
			];
		} else {
			$result = Public_Main::upsert_user(
				[
					'ID'         => $data['gebruiker_id'],
					'first_name' => $data['input']['voornaam'],
					'last_name'  => $data['input']['achternaam'],
					'telnr'      => $data['input']['telnr'],
					'straat'     => $data['input']['straat'],
					'huisnr'     => $data['input']['huisnr'],
					'pcode'      => $data['input']['pcode'],
					'plaats'     => $data['input']['plaats'],
					'user_email' => $data['input']['email'],
				]
			);
			if ( ! is_wp_error( $result ) ) {
				return [
					'content' => $this->goto_home(),
					'status'  => $this->status( 'Gegevens zijn opgeslagen' ),
				];
			} else {
				return [
					'status' => $this->status( new \WP_Error( 'intern', 'Er is iets fout gegaan, probeer het a.u.b. opnieuw' ) ),
				];
			}
		}
	}
}
