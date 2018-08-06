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
				'id'         => $gebruiker->ID,
				'voornaam'   => $gebruiker->first_name,
				'achternaam' => $gebruiker->last_name,
				'straat'     => $gebruiker->straat,
				'huisnr'     => $gebruiker->huisnr,
				'pcode'      => $gebruiker->pcode,
				'plaats'     => $gebruiker->plaats,
				'telnr'      => $gebruiker->telnr,
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

		$input = filter_input_array(
			INPUT_POST, [
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

		$input['pcode'] = strtoupper( $input['pcode'] );
		if ( ! $input['voornaam'] ) {
			$error->add( 'verplicht', 'Een voornaam is verplicht' );
		}
		if ( ! $input['achternaam'] ) {
			$error->add( 'verplicht', 'Een achternaam is verplicht' );
		}
		$err = $error->get_error_codes();
		if ( ! empty( $err ) ) {
			return $error;
		}

		$data = [
			'input' => $input,
		];
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
			$gebruiker_id = Kleistad_Public::upsert_user( [
				'ID'         => $data['input']['gebruiker_id'],
				'first_name' => $data['input']['voornaam'],
				'last_name'  => $data['input']['achternaam'],
				'telnr'      => $data['input']['telnr'],
				'straat'     => $data['input']['straat'],
				'huisnr'     => $data['input']['huisnr'],
				'pcode'      => $data['input']['pcode'],
				'plaats'     => $data['input']['plaats'],
			]);

			if ( false !== $gebruiker_id ) {
				return 'Gegevens zijn opgeslagen';
			} else {
				$error->add( 'security', 'De wijzigingen konden niet worden verwerkt' );
				return $error;
			}
		}
	}
}
