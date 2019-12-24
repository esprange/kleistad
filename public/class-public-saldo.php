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
	protected function prepare( &$data ) {
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
				'bedrag'       => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
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
		$saldo = new \Kleistad\Saldo( $data['input']['gebruiker_id'] );
		$saldo->nieuw( $data['input']['bedrag'] );

		if ( 'ideal' === $data['input']['betaal'] ) {
			$ideal_uri = $saldo->betalen( 'Bedankt voor de betaling! Het saldo wordt aangepast en er wordt een email verzonden met bevestiging' );
			if ( ! empty( $ideal_uri ) ) {
				return [ 'redirect_uri' => $ideal_uri ];
			}
			return [ 'status' => $this->status( new \WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
		} else {
			if ( $saldo->email( '_bank', $saldo->bestel_order( 0.0, 'saldo' ) ) ) {
				return [
					'content' => $this->goto_home(),
					'status'  => $this->status( 'Er is een email verzonden met nadere informatie over de betaling' ),
				];
			} else {
				return [
					'status' => $this->status( new \WP_Error( '', 'Een bevestigings email kon niet worden verzonden. Neem s.v.p. contact op met Kleistad.' ) ),
				];
			}
		}
	}
}
