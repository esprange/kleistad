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
				'id'           => $order->id,
				'naam'         => $order->klant['naam'],
				'betreft'      => $artikel->artikel_naam(),
				'referentie'   => $order->referentie,
				'openstaand'   => $order->te_betalen(),
				'credit'       => boolval( $order->origineel_id ),
				'sinds'        => $order->datum,
				'gesloten'     => $order->gesloten,
				'verval_datum' => $order->verval_datum,
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
			'id'            => $order->id,
			'naam'          => $order->klant['naam'],
			'betreft'       => $artikel->artikel_naam(),
			'referentie'    => $order->referentie,
			'factuur'       => $order->factuurnr(),
			'betaald'       => $order->betaald,
			'openstaand'    => $order->te_betalen(),
			'sinds'         => $order->datum,
			'historie'      => $order->historie,
			'gesloten'      => $order->gesloten,
			'ontvangst'     => 0.0,
			'korting'       => 0.0,
			'restant'       => 0.0,
			'geblokkeerd'   => $order->geblokkeerd(),
			'terugstorting' => $order->terugstorting_actief(),
			'credit'        => boolval( $order->origineel_id ),
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
		} elseif ( 'zoek' === $atts['actie'] ) {
			$data['actie']      = 'zoek';
			$data['debiteuren'] = ! empty( $data['id'] ) ? $this->debiteuren( $data['id'] ) : [];
		} elseif ( 'blokkade' === $atts['actie'] ) {
			$data['actie']            = 'blokkade';
			$data['huidige_blokkade'] = \Kleistad\Order::get_blokkade();
			$data['nieuwe_blokkade']  = strtotime( '+3 month', $data['huidige_blokkade'] );
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
				'id'                   => FILTER_SANITIZE_NUMBER_INT,
				'ontvangst'            => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'korting'              => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'restant'              => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'opmerking_korting'    => FILTER_SANITIZE_STRING,
				'opmerking_annulering' => FILTER_SANITIZE_STRING,
				'debiteur_actie'       => FILTER_SANITIZE_STRING,
			]
		);
		if ( 'blokkade' !== $data['form_actie'] ) {
			$order = new \Kleistad\Order( $data['input']['id'] );
			if ( 'korting' === $data['input']['debiteur_actie'] ) {
				if ( $order->bruto() < $data['input']['korting'] ) {
					$error->add( 'fout', 'De korting kan niet groter zijn dan het totale bedrag' );
				}
			}
			if ( ! empty( $error->get_error_codes() ) ) {
				return $error;
			}
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
		if ( 'blokkade' === $data['form_actie'] ) {
			\Kleistad\Order::zet_blokkade( strtotime( '+3 month', \Kleistad\Order::get_blokkade() ) );
			return [
				'status'  => 'De blokkade datum is gewijzigd',
				'content' => $this->goto_home(),
			];
		}
		$order   = new \Kleistad\Order( $data['input']['id'] );
		$emailer = new \Kleistad\Email();
		$artikel = \Kleistad\Artikel::get_artikel( $order->referentie );
		$status  = '';
		switch ( $data['input']['debiteur_actie'] ) {
			case 'bankbetaling':
				if ( $order->origineel_id ) {
					$artikel->verwerk_betaling( $data['input']['id'], - (float) $data['input']['ontvangst'], true, 'bank' );
				} else {
					$artikel->verwerk_betaling( $data['input']['id'], (float) $data['input']['ontvangst'], true, 'bank' );
				}
				$status = 'De betaling is verwerkt';
				break;
			case 'annulering':
				$emailer->send(
					[
						'to'          => $order->klant['email'],
						'slug'        => 'order_annulering',
						'subject'     => 'Order geannuleerd',
						'attachments' => $artikel->annuleer_order( $data['input']['id'], (float) $data['input']['restant'], $data['input']['opmerking_annulering'] ),
						'parameters'  => [
							'naam'        => $order->klant['naam'],
							'artikel'     => $artikel->artikel_naam(),
							'referentie'  => $order->referentie,
							'betaal_link' => $artikel->betaal_link(),
						],
					]
				);
				$status = 'De annulering is verwerkt en een bevestiging is verstuurd';
				break;
			case 'korting':
				$emailer->send(
					[
						'to'          => $order->klant['email'],
						'slug'        => 'order_correctie',
						'subject'     => 'Order gecorrigeerd',
						'attachments' => $artikel->korting_order( $data['input']['id'], (float) $data['input']['korting'], $data['input']['opmerking_korting'] ),
						'parameters'  => [
							'naam'        => $order->klant['naam'],
							'artikel'     => $artikel->artikel_naam(),
							'referentie'  => $order->referentie,
							'betaal_link' => $artikel->betaal_link(),
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
