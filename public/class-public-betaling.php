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
	 * @since   4.2.0
	 *
	 * @return string
	 */
	protected function prepare() : string {
		$param = filter_input_array(
			INPUT_GET,
			[
				'order' => FILTER_SANITIZE_NUMBER_INT,
				'hsh'   => FILTER_SANITIZE_STRING,
			]
		);
		if ( is_null( $param ) || is_null( $param['order'] ) ) {
			return ''; // Waarschijnlijk bezoek na succesvolle betaling. Pagina blijft leeg, behalve eventuele boodschap.
		}
		$order           = new Order( intval( $param['order'] ) );
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->get_object( $order->referentie );
		if ( is_null( $artikel ) || $param['hsh'] !== $artikel->get_controle() ) {
			return $this->status( new WP_Error( 'Security', 'Je hebt geklikt op een ongeldige link of deze is nu niet geldig meer.' ) );
		}
		if ( $order->gesloten ) {
			return $this->status( new WP_Error( 'Betaald', 'Volgens onze informatie is er reeds betaald. Neem eventueel contact op met Kleistad' ) );
		}
		$this->data = [
			'order_id'      => $order->id,
			'klant'         => $order->klant['naam'],
			'openstaand'    => $order->get_te_betalen(),
			'reeds_betaald' => $order->betaald,
			'orderregels'   => $order->orderregels,
			'betreft'       => $artikelregister->get_naam( $order->referentie ),
			'factuur'       => $order->get_factuurnummer(),
			'annuleerbaar'  => $artikel::DEFINITIE['annuleerbaar'], // Annuleerbaar door klant.
		];
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'betaling' form
	 *
	 * @since   4.2.0
	 *
	 * @return array
	 */
	public function process() : array {
		$this->data['input'] = filter_input_array(
			INPUT_POST,
			[
				'order_id' => FILTER_SANITIZE_NUMBER_INT,
				'betaal'   => FILTER_SANITIZE_STRING,
			]
		);
		$this->data['order'] = new Order( $this->data['input']['order_id'] );
		if ( $this->data['order']->gesloten ) {
			return $this->melding( new WP_Error( 'Betaald', 'Volgens onze informatie is er reeds betaald. Neem eventueel contact op met Kleistad' ) );
		}
		$artikelregister       = new Artikelregister();
		$this->data['artikel'] = $artikelregister->get_object( $this->data['order']->referentie );
		if ( is_object( $this->data['artikel'] ) ) {
			$beschikbaar = '';
			if ( property_exists( $this->data['artikel'], 'actie' ) ) {
				if ( method_exists( $this->data['artikel']->actie, 'get_beschikbaarheid' ) ) {
					$beschikbaar = $this->data['artikel']->actie->get_beschikbaarheid();
				}
			}
			if ( empty( $beschikbaar ) ) {
				return $this->save();
			}
			return $this->melding( new WP_Error( 'Beschikbaar', $beschikbaar ) );
		}
		return $this->melding( new WP_Error( 'Beschikbaar', 'Interne fout' ) );
	}

	/**
	 * Bewaar 'betaling' form gegevens
	 *
	 * @return array
	 *
	 * @since   4.2.0
	 */
	protected function betalen() : array {
		if ( 'ideal' === $this->data['input']['betaal'] ) {
			$ideal_uri = $this->data['artikel']->betaling->doe_ideal( 'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging', $this->data['order']->get_te_betalen(), $this->data['order']->referentie );
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

	/**
	 * Annulering door klant
	 *
	 * @return array
	 */
	protected function annuleren() : array {
		$order = new Order( $this->data['artikel']->get_referentie() );
		if ( $order->is_annuleerbaar() && $order->actie->annuleer( 0.0, 'Geannuleerd door klant' ) ) {
			return [
				'status'  => 'De order is geannuleerd en een bevestiging is verstuurd',
				'content' => $this->goto_home(),
			];
		}
		return [ 'status' => $this->status( new WP_Error( 'annuleren', 'Annulering blijkt niet mogelijk. Neem eventueel contact op met Kleistad' ) ) ];
	}

	/**
	 * Schrijf geef de url van het bestand.
	 *
	 * @return string De url.
	 */
	protected function url_factuur() : string {
		$order_id = intval( filter_input( INPUT_GET, 'order_id', FILTER_SANITIZE_NUMBER_INT ) ?? 0 );
		if ( $order_id ) {
			$order   = new Order( $order_id );
			$factuur = new Factuur();
			return $factuur->overzicht( $order->get_factuurnummer() )[0];
		}
		return '';
	}
}
