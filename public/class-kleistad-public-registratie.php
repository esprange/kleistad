<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_Registratie extends Kleistad_Shortcode {

	/**
	 * Prepareer 'registratie' form
	 *
	 * @param array $data data to be prepared.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$gebruiker_id = get_current_user_id();
		$gebruiker = new Kleistad_Gebruiker( $gebruiker_id );

		if ( is_null( $data ) ) {
			$data['input'] = [
				'voornaam' => $gebruiker->voornaam,
				'achternaam' => $gebruiker->achternaam,
				'straat' => $gebruiker->straat,
				'huisnr' => $gebruiker->huisnr,
				'pcode' => $gebruiker->pcode,
				'plaats' => $gebruiker->plaats,
				'telnr' => $gebruiker->telnr,
			];
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'registratie' form
	 *
	 * @param array $data Returned data.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function validate( &$data ) {
		$error = new WP_Error();

		$input = filter_input_array(
			INPUT_POST, [
				'gebruiker_id' => FILTER_SANITIZE_NUMBER_INT,
				'voornaam' => FILTER_SANITIZE_STRING,
				'achternaam' => FILTER_SANITIZE_STRING,
				'straat' => FILTER_SANITIZE_STRING,
				'huisnr' => FILTER_SANITIZE_STRING,
				'pcode' => FILTER_SANITIZE_STRING,
				'plaats' => FILTER_SANITIZE_STRING,
				'telnr' => FILTER_SANITIZE_STRING,
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
	 * @param array $data data to be saved.
	 * @return string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		$error = new WP_Error();

		if ( ! is_user_logged_in() ) {
			$error->add( 'security', 'Dit formulier mag alleen ingevuld worden door ingelogde gebruikers' );
			return $error;
		} else {
			$gebruiker = new Kleistad_Gebruiker( $data['input']['gebruiker_id'] );
			$gebruiker->voornaam = $data['input']['voornaam'];
			$gebruiker->achternaam = $data['input']['achternaam'];
			$gebruiker->straat = $data['input']['straat'];
			$gebruiker->huisnr = $data['input']['huisnr'];
			$gebruiker->pcode = $data['input']['pcode'];
			$gebruiker->plaats = $data['input']['plaats'];
			$gebruiker->telnr = $data['input']['telnr'];
			$result = $gebruiker->save();
			if ( false !== $result ) {
				return 'Gegevens zijn opgeslagen';
			} else {
				$error->add( 'security', 'De wijzigingen konden niet worden verwerkt' );
				return $error;
			}
		}
	}
}
