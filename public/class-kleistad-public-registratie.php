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

/**
 * De kleistad registratie class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Registratie extends Kleistad_ShortcodeForm {

	/**
	 * Prepareer 'registratie' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data = null ) {
		$gebruiker = wp_get_current_user();

		if ( is_null( $data ) ) {
			$data          = [];
			$data['input'] = [
				'gebruiker_id' => $gebruiker->ID,
				'voornaam'     => $gebruiker->first_name,
				'achternaam'   => $gebruiker->last_name,
				'straat'       => $gebruiker->straat,
				'huisnr'       => $gebruiker->huisnr,
				'pcode'        => $gebruiker->pcode,
				'plaats'       => $gebruiker->plaats,
				'telnr'        => $gebruiker->telnr,
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
		$error         = new WP_Error();
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'gebruiker_id' => FILTER_SANITIZE_NUMBER_INT,
				'voornaam'     => FILTER_SANITIZE_STRING,
				'achternaam'   => FILTER_SANITIZE_STRING,
				'straat'       => FILTER_SANITIZE_STRING,
				'huisnr'       => FILTER_SANITIZE_STRING,
				'pcode'        => FILTER_SANITIZE_STRING,
				'plaats'       => FILTER_SANITIZE_STRING,
				'telnr'        => FILTER_SANITIZE_STRING,
			]
		);
		if ( ! empty( $data['input']['telnr'] ) && ! $this->validate_telnr( $data['input']['telnr'] ) ) {
			$error->add( 'onjuist', 'Het ingevoerde telefoonnummer lijkt niet correct. Alleen Nederlandse telefoonnummers kunnen worden doorgegeven' );
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
	 * @return \WP_ERROR|string
	 *
	 * @since   4.0.87
	 */
	protected function save( $data ) {
		$error = new WP_Error();

		if ( ! is_user_logged_in() ) {
			$error->add( 'security', 'Dit formulier mag alleen ingevuld worden door ingelogde gebruikers' );
			return $error;
		} else {
			$gebruiker_id = Kleistad_Public::upsert_user(
				[
					'ID'         => $data['input']['gebruiker_id'],
					'first_name' => $data['input']['voornaam'],
					'last_name'  => $data['input']['achternaam'],
					'telnr'      => $data['input']['telnr'],
					'straat'     => $data['input']['straat'],
					'huisnr'     => $data['input']['huisnr'],
					'pcode'      => $data['input']['pcode'],
					'plaats'     => $data['input']['plaats'],
				]
			);

			if ( ! is_wp_error( $gebruiker_id ) ) {
				return [
					'status' => 'Gegevens zijn opgeslagen',
					'actie'  => 'home',
				];
			} else {
				$error->add( '', $gebruiker_id->get_error_message() );
				return $error;
			}
		}
	}
}
