<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.3.0
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
class Kleistad_Public_Betaling extends Kleistad_Shortcode {

	/**
	 *
	 * Prepareer 'saldo' form
	 *
	 * @param array $data the prepared data.
	 * @return array
	 *
	 * @since   4.3.0
	 */
	public function prepare( &$data = null ) {
		$error      = new WP_Error();
		$cursist_id = filter_input( INPUT_GET, 'gid', FILTER_SANITIZE_NUMBER_INT );
		$cursus_id  = filter_input( INPUT_GET, 'crss', FILTER_SANITIZE_NUMBER_INT );
		$hash       = filter_input( INPUT_GET, 'hsh', FILTER_SANITIZE_STRING );

		$data['leeg'] = ( '' === $cursist_id || '' === $cursus_id );

		if ( $data['leeg'] ) {
			return true; // Waarschijnlijk bezoek na succesvolle betaling. Pagina blijft leeg, behalve eventuele boodschap.
		}

		$cursist      = get_userdata( $cursist_id );
		$cursus       = new Kleistad_Cursus( $cursus_id );
		$inschrijving = new Kleistad_Inschrijving( $cursist_id, $cursus_id );

		if ( $hash === $inschrijving->controle() ) {
			if ( $inschrijving->c_betaald ) {
				$error->add( 'betaald', 'Volgens onze informatie is er reeds betaald voor deze cursus. Neem eventueel contact op met Kleistad' );
			} else {
				$data = [
					'cursist'      => $cursist,
					'cursus'       => $cursus,
					'inschrijving' => $inschrijving,
				];
			}
		} else {
			$error->add( 'Security', 'Je hebt geklikt op een ongeldige link of deze is nu niet geldig meer.' );
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'betaling' form
	 *
	 * @param array $data Returned data.
	 * @return array
	 *
	 * @since   4.3.0
	 */
	public function validate( &$data ) {
		$error = new WP_Error();

		$input        = filter_input_array(
			INPUT_POST, [
				'cursist_id' => FILTER_SANITIZE_NUMBER_INT,
				'cursus_id'  => FILTER_SANITIZE_NUMBER_INT,
				'betaal'     => FILTER_SANITIZE_STRING,
			]
		);
		$inschrijving = new Kleistad_Inschrijving( $input['cursist_id'], $input['cursus_id'] );

		if ( $inschrijving->c_betaald ) {
			$error->add( 'betaald', 'Volgens onze informatie is er reeds betaald voor deze cursus. Neem eventueel contact op met Kleistad' );
		}
		$data['input'] = $input;

		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Bewaar 'betaling' form gegevens
	 *
	 * @param array $data the data to be saved.
	 *
	 * @since   4.3.0
	 */
	public function save( $data ) {
		$inschrijving = new Kleistad_Inschrijving( $data['input']['cursist_id'], $data['input']['cursus_id'] );

		if ( 'ideal' === $data['input']['betaal'] ) {
			$inschrijving->betalen(
				'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging',
				false
			);
		} else {
			$inschrijving->email( 'betaling_bank' );
		}
	}
}
