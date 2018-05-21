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
class Kleistad_Public_Dagdelenkaart extends Kleistad_Shortcode {

	/**
	 *
	 * Prepareer 'dagdelenkaart' form
	 *
	 * @param array $data the prepared data.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$gebruiker_id = get_current_user_id();
		$data = [
			'gebruiker_id' => $gebruiker_id,
		];
		return true;
	}

	/**
	 * Valideer/sanitize 'dagdelenkaart' form
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
			]
		);
		$data['input'] = $input;
		return true;
	}

	/**
	 * Bewaar 'dagdelenkaart' form gegevens
	 *
	 * @param array $data the data to be saved.
	 * @return string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		$error = new WP_Error();

		$dagdelenkaart = new Kleistad_Dagdelenkaart( $data['input']['gebruiker_id'] );

		if ( 'ideal' === $data['input']['betaal'] ) {
			$dagdelenkaart->betalen(
				$data['input']['bedrag'],
				'Bedankt voor de betaling! Een dagdelenkaart is aangemaakt en kan bij Kleistad opgehaald worden'
			);
		} else {
			if ( $dagdelenkaart->email( '', $data['input']['bedrag'] ) ) {
				return 'Er is een email verzonden met nadere informatie over de betaling';
			} else {
				$error->add( '', 'Een bevestigings email kon niet worden verzonden. Neem s.v.p. contact op met Kleistad.' );
				return $error;
			}
		}
	}
}
