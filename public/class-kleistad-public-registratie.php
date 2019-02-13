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
	public function prepare( &$data = null ) {
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
	public function validate( &$data ) {
		$error = new WP_Error();

		$data['input']          = filter_input_array(
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
		$data['input']['pcode'] = strtoupper( $data['input']['pcode'] );
		if ( ! $data['input']['voornaam'] ) {
			$error->add( 'verplicht', 'Een voornaam is verplicht' );
		}
		if ( ! $data['input']['achternaam'] ) {
			$error->add( 'verplicht', 'Een achternaam is verplicht' );
		}
		if ( ! empty( $data['input']['telnr'] ) ) {
			$telnr = str_replace( [ ' ', '-' ], [ '', '' ], $data['input']['telnr'] );
			if ( ! ( preg_match( '/^(((0)[1-9]{2}[0-9][-]?[1-9][0-9]{5})|((\\+31|0|0031)[1-9][0-9][-]?[1-9][0-9]{6}))$/', $telnr ) ||
				preg_match( '/^(((\\+31|0|0031)6){1}[1-9]{1}[0-9]{7})$/i', $telnr ) ) ) {
				$error->add( 'onjuist', 'Het ingevoerde telefoonnummer lijkt niet correct. Alleen Nederlandse telefoonnummers kunnen worden doorgegeven' );
			}
		}
		$data['input']['pcode'] = strtoupper( str_replace( ' ', '', $data['input']['pcode'] ) );

		$err = $error->get_error_codes();
		if ( ! empty( $err ) ) {
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
	public function save( $data ) {
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
				return 'Gegevens zijn opgeslagen';
			} else {
				$error->add( '', $gebruiker_id->get_error_message() );
				return $error;
			}
		}
	}
}
