<?php
/**
 * Shortcode betaling van willekeurig welk artikel.
 *
 * @link       https://www.kleistad.nl
 * @since      4.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De class Betaling.
 */
class Public_Betaling extends ShortcodeForm {

	/**
	 *
	 * Prepareer 'betaling' form
	 *
	 * @param array $data formulier data.
	 * @return \WP_Error|bool
	 *
	 * @since   4.2.0
	 */
	protected function prepare( &$data ) {
		$error = new \WP_Error();
		$param = filter_input_array(
			INPUT_GET,
			[
				'order' => FILTER_SANITIZE_NUMBER_INT,
				'hsh'   => FILTER_SANITIZE_STRING,
				'art'   => FILTER_SANITIZE_STRING,
			]
		);
		if ( is_null( $param ) ) {
			$data['actie'] = '';
			return true; // Waarschijnlijk bezoek na succesvolle betaling. Pagina blijft leeg, behalve eventuele boodschap.
		}
		if ( ! is_null( $param['order'] ) ) {
			$order = new \Kleistad\Order( intval( $param['order'] ) );
			if ( $order->gesloten ) {
				$error->add( 'Betaald', 'Volgens onze informatie is er reeds betaald. Neem eventueel contact op met Kleistad' );
			} else {
				$artikel = \Kleistad\Artikel::get_artikel( $order->referentie );
				if ( ! is_null( $artikel ) && $param['hsh'] === $artikel->controle() ) {
					$data = [
						'order_id'      => $param['order'],
						'actie'         => 'betalen',
						'klant'         => $order->klant['naam'],
						'openstaand'    => $order->te_betalen(),
						'reeds_betaald' => $order->betaald,
						'regels'        => $order->regels,
						'betreft'       => $artikel->artikel_naam(),
						'artikel_type'  => $param['art'],
					];
				} else {
					$error->add( 'Security', 'Je hebt geklikt op een ongeldige link of deze is nu niet geldig meer.' );
				}
			}
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'betaling' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return \WP_ERROR|bool
	 *
	 * @since   4.2.0
	 */
	protected function validate( &$data ) {
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'order_id'     => FILTER_SANITIZE_NUMBER_INT,
				'betaal'       => FILTER_SANITIZE_STRING,
				'artikel_type' => FILTER_SANITIZE_STRING,
			]
		);
		$order         = new \Kleistad\Order( $data['input']['order_id'] );
		if ( $order->gesloten ) {
			return new \WP_Error( 'Betaald', 'Volgens onze informatie is er reeds betaald. Neem eventueel contact op met Kleistad' );
		}
		$data['artikel'] = \Kleistad\Artikel::get_artikel( $order->referentie );
		$controle        = $data['artikel']->beschikbaarcontrole();
		if ( empty( $controle ) ) {
			return true;
		} else {
			return new \WP_Error( 'Beschikbaar', $controle );
		}
	}

	/**
	 * Bewaar 'betaling' form gegevens
	 *
	 * @param array $data te bewaren data.
	 * @return array
	 *
	 * @since   4.2.0
	 */
	protected function save( $data ) {
		$ideal_uri = '';
		if ( 'ideal' === $data['input']['betaal'] ) {
			$data['artikel']->artikel_type = $data['input']['artikel_type'];
			$ideal_uri                     = $data['artikel']->ideal( 'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging', $data['order']->referentie, $data['order']->te_betalen() );
		}
		if ( ! empty( $ideal_uri ) ) {
			return [ 'redirect_uri' => $ideal_uri ];
		}
		return [ 'status' => $this->status( new \WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
	}
}
