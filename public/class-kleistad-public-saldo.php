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
class Kleistad_Public_Saldo extends Kleistad_Shortcode {

	/**
	 *
	 * Prepareer 'saldo' form
	 *
	 * @param array $data the prepared data.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$gebruiker_id = get_current_user_id();
		$saldo = new Kleistad_Saldo( $gebruiker_id );

		$data = [
			'gebruiker_id' => $gebruiker_id,
			'saldo'        => number_format( $saldo->bedrag, 2, ',', '' ),
		];
		return true;
	}

	/**
	 * Valideer/sanitize 'saldo' form
	 *
	 * @param array $data Returned data.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function validate( &$data ) {

		$input = filter_input_array(
			INPUT_POST, [
				'gebruiker_id' => FILTER_SANITIZE_NUMBER_INT,
				'bedrag'       => FILTER_SANITIZE_NUMBER_FLOAT,
				'bank'         => FILTER_SANITIZE_STRING,
				'betaal'       => FILTER_SANITIZE_STRING,
			]
		);
		$data['input'] = $input;
		return true;
	}

	/**
	 * Bewaar 'saldo' form gegevens
	 *
	 * @param array $data the data to be saved.
	 * @return string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		$error = new WP_Error();

		$saldo = new Kleistad_Saldo( $data['input']['gebruiker_id'] );

		if ( 'ideal' === $data['input']['betaal'] ) {
			$saldo->betalen(
				$data['input']['bedrag'],
				$data['input']['bank'],
				'Bedankt voor de betaling! Het saldo wordt aangepast en er wordt een email verzonden met bevestiging'
			);
		} else {
			if ( $saldo->email( $data['input']['betaal'], $data['input']['bedrag'] ) ) {
				return 'Er is een email verzonden met nadere informatie over de betaling';
			} else {
				$error->add( '', 'Een bevestigings email kon niet worden verzonden. Neem s.v.p. contact op met Kleistad.' );
				return $error;
			}
		}
	}
}
