<?php
/**
 * De shortcode debiteuren (overzicht en formulier).
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad betalingen class.
 */
class Public_Debiteuren extends ShortcodeForm {

	/**
	 * Maak de lijst van openstaande betalingen.
	 *
	 * @return array De info.
	 */
	private function debiteuren() {
		$debiteuren = [];
		$orders     = \Kleistad\Order::all();
		foreach ( $orders as $order ) {
			$debiteuren[] = [
				'id'         => $order->id,
				'naam'       => $order->klant['naam'],
				'betreft'    => \Kleistad\Artikel::get_artikel_naam( $order->referentie ),
				'referentie' => $order->referentie,
				'openstaand' => $order->bruto() - $order->betaald,
				'sinds'      => $order->datum,
			];
		}
		return $debiteuren;
	}

	/**
	 * Toon de informatie van één debiteur.
	 *
	 * @param int $id Het order id.
	 * @return array De informatie.
	 */
	private function debiteur( $id ) {
		$order = new \Kleistad\Order( $id );
		return [
			'id'         => $order->id,
			'naam'       => $order->klant['naam'],
			'betreft'    => \Kleistad\Artikel::get_artikel_naam( $order->referentie ),
			'referentie' => $order->referentie,
			'factuur'    => $order->factuurnr(),
			'betaald'    => $order->betaald,
			'openstaand' => $order->bruto() - $order->betaald,
			'sinds'      => $order->datum,
			'historie'   => $order->historie,
			'ontvangst'  => 0.0,
			'korting'    => 0.0,
			'restant'    => 0.0,
		];
	}

	/**
	 * Prepareer 'debiteuren' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   6.1.0
	 */
	protected function prepare( &$data ) {
		if ( 'debiteur' === $data['actie'] ) {
			$data['debiteur'] = $this->debiteur( $data['id'] );
		} else {
			$data['debiteuren'] = $this->debiteuren();
		}
		return true;
	}

	/**
	 *
	 * Valideer/sanitize 'debiteuren' form
	 *
	 * @param array $data gevalideerde data.
	 * @return bool
	 *
	 * @since   6.1.0
	 */
	protected function validate( &$data ) {
		$error         = new \WP_Error();
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'id'             => FILTER_SANITIZE_NUMBER_INT,
				'ontvangst'      => FILTER_SANITIZE_NUMBER_FLOAT,
				'korting'        => FILTER_SANITIZE_NUMBER_FLOAT,
				'restant'        => FILTER_SANITIZE_NUMBER_FLOAT,
				'debiteur_actie' => FILTER_SANITIZE_STRING,
			]
		);
		$order         = new \Kleistad\Order( $data['input']['id'] );
		if ( 'korting' === $data['input']['debiteur_actie'] ) {
			if ( $order->bruto() < $data['input']['korting'] ) {
				$error->add( 'fout', 'De korting kan niet groter zijn dan het totale bedrag' );
			}
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Bewaar 'debiteuren' form gegevens
	 *
	 * @param array $data te bewaren data.
	 * @return array
	 * @since   6.1.0
	 */
	protected function save( $data ) {
		$order   = new \Kleistad\Order( $data['input']['id'] );
		$artikel = \Kleistad\Artikel::get_artikel( $order->referentie );
		$status  = '';

		switch ( $data['input']['debiteur_actie'] ) {
			case 'bankbetaling':
				$artikel->ontvang_order( $data['input']['id'], (float) $data['input']['ontvangst'] );
				$status = 'De betaling is verwerkt';
				break;
			case 'annulering':
				$artikel->email( 'annulering', $artikel->annuleer_order( $data['input']['id'], (float) $data['input']['restant'] ) );
				$status = 'De annulering is verwerkt en een bevestiging is verstuurd';
				break;
			case 'korting':
				$artikel->email( 'correctie', $artikel->korting_order( $data['input']['id'], (float) $data['input']['korting'] ) );
				$status = 'De korting is verwerkt en een correctie is verstuurd';
				break;
		}
		if ( ! empty( $status ) ) {
			return [
				'status'  => $this->status( $status ),
				'content' => $this->display(),
			];
		}
		return [ 'status' => new \WP_Error( 'fout', 'Er is iets fout gegaan' ) ];
	}
}
