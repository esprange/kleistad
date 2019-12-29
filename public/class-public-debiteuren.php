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
	 * @param string $zoek De eventuele zoek term.
	 * @return array De info.
	 */
	private function debiteuren( $zoek = '' ) {
		$debiteuren = [];
		$orders     = \Kleistad\Order::all( $zoek );
		foreach ( $orders as $order ) {
			$artikel      = \Kleistad\Artikel::get_artikel( $order->referentie );
			$debiteuren[] = [
				'id'         => $order->id,
				'naam'       => $order->klant['naam'],
				'betreft'    => $artikel->artikel_naam(),
				'referentie' => $order->referentie,
				'openstaand' => $order->te_betalen(),
				'credit'     => boolval( $order->origineel_id ),
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
		$order   = new \Kleistad\Order( $id );
		$artikel = \Kleistad\Artikel::get_artikel( $order->referentie );
		return [
			'id'         => $order->id,
			'naam'       => $order->klant['naam'],
			'betreft'    => $artikel->artikel_naam(),
			'referentie' => $order->referentie,
			'factuur'    => $order->factuurnr(),
			'betaald'    => $order->betaald,
			'openstaand' => $order->te_betalen(),
			'sinds'      => $order->datum,
			'historie'   => $order->historie,
			'ontvangst'  => 0.0,
			'korting'    => 0.0,
			'restant'    => 0.0,
			'credit'     => boolval( $order->origineel_id ),
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
		$atts = shortcode_atts(
			[ 'actie' => '' ],
			$this->atts,
			'kleistad_debiteuren'
		);
		if ( 'debiteur' === $data['actie'] ) {
			$data['debiteur'] = $this->debiteur( $data['id'] );
			$data['bewerken'] = true;
		} elseif ( 'toon_debiteur' === $data['actie'] ) {
			$data['actie']    = 'debiteur';
			$data['debiteur'] = $this->debiteur( $data['id'] );
			$data['bewerken'] = false;
		} elseif ( 'zoek' === $atts['actie'] ) {
			$data['actie']      = 'zoek';
			$data['debiteuren'] = ! empty( $data['id'] ) ? $this->debiteuren( $data['id'] ) : [];
		} else {
			$data['actie']      = 'openstaand';
			$data['debiteuren'] = $this->debiteuren();
		}
		return true;
	}

	/**
	 *
	 * Valideer/sanitize 'debiteuren' form
	 *
	 * @param array $data gevalideerde data.
	 * @return bool|\WP_Error
	 *
	 * @since   6.1.0
	 */
	protected function validate( &$data ) {
		$error         = new \WP_Error();
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'id'             => FILTER_SANITIZE_NUMBER_INT,
				'ontvangst'      => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'korting'        => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'restant'        => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
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
		$emailer = new \Kleistad\Email();
		$artikel = \Kleistad\Artikel::get_artikel( $order->referentie );
		$status  = '';
		switch ( $data['input']['debiteur_actie'] ) {
			case 'bankbetaling':
				if ( $order->origineel_id ) {
					$artikel->ontvang_order( $data['input']['id'], - (float) $data['input']['ontvangst'] );
				} else {
					$artikel->ontvang_order( $data['input']['id'], (float) $data['input']['ontvangst'] );
				}
				$status = 'De betaling is verwerkt';
				break;
			case 'annulering':
				$emailer->send(
					[
						'to'          => $artikel->naw_klant()['email'],
						'slug'        => 'order_annulering',
						'subject'     => 'Order geannuleerd',
						'attachments' => $artikel->annuleer_order( $data['input']['id'], (float) $data['input']['restant'] ),
						'parameters'  => [
							'naam'       => $artikel->naw_klant()['naam'],
							'artikel'    => $artikel->artikel_naam(),
							'referentie' => $order->referentie,
						],
					]
				);
				$status = 'De annulering is verwerkt en een bevestiging is verstuurd';
				break;
			case 'korting':
				$emailer->send(
					[
						'to'          => $artikel->naw_klant()['email'],
						'slug'        => 'order_correctie',
						'subject'     => 'Order gecorrigeerd',
						'attachments' => $artikel->korting_order( $data['input']['id'], (float) $data['input']['korting'] ),
						'parameters'  => [
							'naam'       => $artikel->naw_klant()['naam'],
							'artikel'    => $artikel->artikel_naam(),
							'referentie' => $order->referentie,
						],
					]
				);
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
