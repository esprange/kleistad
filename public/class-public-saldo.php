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
	 *
	 * Prepareer 'saldo' form
	 *
	 * @param array $data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data ) {
		$gebruiker_id = get_current_user_id();
		$saldo        = new Saldo( $gebruiker_id );
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
	 * @return \WP_Error|bool
	 *
	 * @since   4.0.87
	 */
	protected function validate( &$data ) {

		$data['input'] = filter_input_array(
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
		if ( ! intval( $data['input']['bedrag'] ) ) {
			$data['input']['bedrag'] = $data['input']['ander'];
		}
		if ( 15 > floatval( $data['input']['bedrag'] ) || 100 < floatval( $data['input']['bedrag'] ) ) {
			return new WP_Error( 'onjuist', 'Het bedrag moet tussen 15 en 100 euro liggen' );
		}
		return true;
	}

	/**
	 * Bewaar 'saldo' form gegevens
	 *
	 * @param array $data te bewaren data.
	 * @return WP_ERROR|array
	 *
	 * @since   4.0.87
	 */
	protected function save( $data ) {
		$saldo = new Saldo( intval( $data['input']['gebruiker_id'] ) );
		$saldo->nieuw( floatval( $data['input']['bedrag'] ) );

		if ( 'ideal' === $data['input']['betaal'] ) {
			$ideal_uri = $saldo->doe_idealbetaling( 'Bedankt voor de betaling! Het saldo wordt aangepast en er wordt een email verzonden met bevestiging' );
			if ( ! empty( $ideal_uri ) ) {
				return [ 'redirect_uri' => $ideal_uri ];
			}
			return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
		}
		if ( $saldo->verzend_email( '_bank', $saldo->bestel_order( 0.0, strtotime( '+7 days 0:00' ) ) ) ) {
			return [
				'content' => $this->goto_home(),
				'status'  => $this->status( 'Er is een email verzonden met nadere informatie over de betaling' ),
			];
		}
		return [
			'status' => $this->status( new WP_Error( '', 'Een bevestigings email kon niet worden verzonden. Neem s.v.p. contact op met Kleistad.' ) ),
		];
	}
}
