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

use WP_Error;

/**
 * De class Betaling.
 */
class Public_Betaling extends ShortcodeForm {

	/**
	 *
	 * Prepareer 'betaling' form
	 *
	 * @param array $data formulier data.
	 * @return WP_Error|bool
	 *
	 * @since   4.2.0
	 */
	protected function prepare( &$data ) {
		$param = filter_input_array(
			INPUT_GET,
			[
				'order' => FILTER_SANITIZE_NUMBER_INT,
				'hsh'   => FILTER_SANITIZE_STRING,
				'art'   => FILTER_SANITIZE_STRING,
			]
		);
		if ( is_null( $param ) || is_null( $param['order'] ) ) {
			$data['actie'] = '';
			return true; // Waarschijnlijk bezoek na succesvolle betaling. Pagina blijft leeg, behalve eventuele boodschap.
		}
		$order = new Order( intval( $param['order'] ) );
		if ( $order->gesloten ) {
			return new WP_Error( 'Betaald', 'Volgens onze informatie is er reeds betaald. Neem eventueel contact op met Kleistad' );
		}
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->geef_object( $order->referentie );
		if ( is_null( $artikel ) || $param['hsh'] !== $artikel->controle() ) {
			return new WP_Error( 'Security', 'Je hebt geklikt op een ongeldige link of deze is nu niet geldig meer.' );
		}
		$data = [
			'order_id'      => $param['order'],
			'actie'         => 'betalen',
			'klant'         => $order->klant['naam'],
			'openstaand'    => $order->te_betalen(),
			'reeds_betaald' => $order->betaald,
			'orderregels'   => $order->orderregels,
			'betreft'       => $artikel->geef_artikelnaam(),
			'artikel_type'  => $param['art'],
		];
		return true;
	}

	/**
	 * Valideer/sanitize 'betaling' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return WP_ERROR|bool
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
		$data['order'] = new Order( $data['input']['order_id'] );
		if ( $data['order']->gesloten ) {
			return new WP_Error( 'Betaald', 'Volgens onze informatie is er reeds betaald. Neem eventueel contact op met Kleistad' );
		}
		$artikelregister = new Artikelregister();
		$data['artikel'] = $artikelregister->geef_object( $data['order']->referentie );
		if ( is_object( $data['artikel'] ) ) {
			$controle = $data['artikel']->beschikbaarcontrole();
			if ( empty( $controle ) ) {
				return true;
			}
			return new WP_Error( 'Beschikbaar', $controle );
		}
		return new WP_Error( 'Beschikbaar', 'Interne fout' );
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
		if ( 'ideal' === $data['input']['betaal'] ) {
			$data['artikel']->artikel_type = $data['input']['artikel_type'];
			$ideal_uri                     = $data['artikel']->doe_idealbetaling( 'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging', $data['order']->referentie, $data['order']->te_betalen() );
			if ( false === $ideal_uri ) {
				return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
			}
			return [ 'redirect_uri' => $ideal_uri ];
		}
		return [
			'status'  => 'Er heeft geen betaling plaatsgevonden',
			'content' => $this->goto_home(),
		];
	}
}
