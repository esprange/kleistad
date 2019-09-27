<?php
/**
 * Shortcode saldo (aanvullen saldo door lid).
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad saldo class.
 */
class Public_Saldo extends ShortcodeForm {

	/**
	 *
	 * Prepareer 'saldo' form
	 *
	 * @param array $data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data = null ) {
		$gebruiker_id = get_current_user_id();
		$saldo        = new \Kleistad\Saldo( $gebruiker_id );
		$data         = [
			'gebruiker_id' => $gebruiker_id,
			'saldo'        => number_format_i18n( $saldo->bedrag, 2 ),
		];
		return true;
	}

	/**
	 * Valideer/sanitize 'saldo' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function validate( &$data ) {

		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'gebruiker_id' => FILTER_SANITIZE_NUMBER_INT,
				'bedrag'       => FILTER_SANITIZE_NUMBER_FLOAT,
				'betaal'       => FILTER_SANITIZE_STRING,
			]
		);
		return true;
	}

	/**
	 * Bewaar 'saldo' form gegevens
	 *
	 * @param array $data te bewaren data.
	 * @return \WP_ERROR|array
	 *
	 * @since   4.0.87
	 */
	protected function save( $data ) {
		$error = new \WP_Error();
		$saldo = new \Kleistad\Saldo( $data['input']['gebruiker_id'] );

		if ( 'ideal' === $data['input']['betaal'] ) {
			$saldo->betalen(
				'Bedankt voor de betaling! Het saldo wordt aangepast en er wordt een email verzonden met bevestiging',
				$data['input']['bedrag']
			);
		} else {
			if ( $saldo->email( '_bank', $data['input']['bedrag'] ) ) {
				return [
					'status'  => 'Er is een email verzonden met nadere informatie over de betaling',
					'vervolg' => 'home',
				];
			} else {
				$error->add( '', 'Een bevestigings email kon niet worden verzonden. Neem s.v.p. contact op met Kleistad.' );
				return $error;
			}
		}
	}
}
