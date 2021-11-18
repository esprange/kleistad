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

use WP_Error;

/**
 * De kleistad saldo class.
 */
class Public_Saldo extends ShortcodeForm {

	/**
	 * Prepareer 'saldo' form
	 */
	protected function prepare() {
		$gebruiker_id = get_current_user_id();
		$saldo        = new Saldo( $gebruiker_id );
		$this->data   = [
			'gebruiker_id' => $gebruiker_id,
			'saldo'        => number_format_i18n( $saldo->bedrag, 2 ),
		];
		return true;
	}

	/**
	 * Valideer/sanitize 'saldo' form
	 *
	 * @return WP_Error|bool
	 *
	 * @since   4.0.87
	 */
	protected function validate() {

		$this->data['input'] = filter_input_array(
			INPUT_POST,
			[
				'gebruiker_id' => FILTER_SANITIZE_NUMBER_INT,
				'bedrag'       => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'ander'        => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'betaal'       => FILTER_SANITIZE_STRING,
			]
		);
		if ( ! intval( $this->data['input']['bedrag'] ) ) {
			$this->data['input']['bedrag'] = $this->data['input']['ander'];
		}
		if ( 15 > floatval( $this->data['input']['bedrag'] ) || 100 < floatval( $this->data['input']['bedrag'] ) ) {
			return new WP_Error( 'onjuist', 'Het bedrag moet tussen 15 en 100 euro liggen' );
		}
		return true;
	}

	/**
	 * Bewaar 'saldo' form gegevens
	 *
	 * @return array
	 *
	 * @since   4.0.87
	 */
	protected function save() : array {
		$saldo  = new Saldo( intval( $this->data['input']['gebruiker_id'] ) );
		$result = $saldo->actie->nieuw( floatval( $this->data['input']['bedrag'] ), $this->data['input']['betaal'] );
		if ( false === $result ) {
			return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
		}
		if ( is_string( $result ) ) {
			return [ 'redirect_uri' => $result ];
		}
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'Er is een email verzonden met nadere informatie over de betaling' ),
		];
	}
}
