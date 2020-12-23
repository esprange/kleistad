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

use WP_Error;

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
		$orders     = new Orders();
		foreach ( $orders as $order ) {
			if ( ! empty( $zoek ) && false === stripos( $order->klant['naam'] . ' ' . $order->referentie, $zoek ) ) {
				continue;
			}
			$betreft      = '@' !== $order->referentie[0] ? get_artikel( $order->referentie )->geef_artikelnaam() : 'afboeking';
			$debiteuren[] = [
				'id'           => $order->id,
				'naam'         => $order->klant['naam'],
				'betreft'      => $betreft,
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
	 * @param int $order_id Het order id.
	 * @return array De informatie.
	 */
	private function debiteur( $order_id ) {
		$order   = new Order( $order_id );
		$betreft = '@' !== $order->referentie[0] ? get_artikel( $order->referentie )->geef_artikelnaam() : 'afboeking';
		return [
			'id'            => $order->id,
			'naam'          => $order->klant['naam'],
			'betreft'       => $betreft,
			'referentie'    => $order->referentie,
			'factuur'       => $order->factuurnummer(),
			'betaald'       => $order->betaald,
			'openstaand'    => $order->te_betalen(),
			'sinds'         => $order->datum,
			'historie'      => $order->historie,
			'gesloten'      => $order->gesloten,
			'ontvangst'     => 0.0,
			'korting'       => 0.0,
			'restant'       => 0.0,
			'geblokkeerd'   => $order->is_geblokkeerd(),
			'annuleerbaar'  => $order->is_annuleerbaar(),
			'terugstorting' => $order->is_terugstorting_actief(),
			'credit'        => $order->is_credit(),
			'afboekbaar'    => $order->is_afboekbaar(),
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
			$data['openstaand'] = 0;
			foreach ( $data['debiteuren'] as $debiteur ) {
				$data['openstaand'] += $debiteur['openstaand'];
			}
		} elseif ( 'blokkade' === $atts['actie'] ) {
			$data['actie']            = 'blokkade';
			$data['huidige_blokkade'] = get_blokkade();
			$data['nieuwe_blokkade']  = strtotime( '+3 month', $data['huidige_blokkade'] );
		} else {
			$data['actie']      = 'openstaand';
			$data['debiteuren'] = $this->debiteuren();
			$data['openstaand'] = 0;
			foreach ( $data['debiteuren'] as $debiteur ) {
				$data['openstaand'] += $debiteur['openstaand'];
			}
		}
		return true;
	}

	/**
	 *
	 * Valideer/sanitize 'debiteuren' form
	 *
	 * @param array $data gevalideerde data.
	 * @return bool|WP_Error
	 *
	 * @since   6.1.0
	 */
	protected function validate( &$data ) {
		$error         = new WP_Error();
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'id'                   => FILTER_SANITIZE_NUMBER_INT,
				'ontvangst'            => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'terugstorting'        => [
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
			$order = new Order( $data['input']['id'] );
			if ( 'korting' === $data['input']['debiteur_actie'] ) {
				if ( $order->orderregels->bruto() < $data['input']['korting'] ) {
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
			zet_blokkade( strtotime( '+3 month', get_blokkade() ) );
			return [
				'status'  => 'De blokkade datum is gewijzigd',
				'content' => $this->goto_home(),
			];
		}
		$order   = new Order( $data['input']['id'] );
		$emailer = new Email();
		$artikel = get_artikel( $order->referentie );
		$status  = '';
		switch ( $data['input']['debiteur_actie'] ) {
			case 'bankbetaling':
				if ( $data['input']['ontvangst'] ) {
					$artikel->verwerk_betaling( $data['input']['id'], (float) $data['input']['ontvangst'], true, 'bank' );
				}
				if ( $data['input']['terugstorting'] ) {
					$artikel->verwerk_betaling( $data['input']['id'], - (float) $data['input']['terugstorting'], true, 'bank' );
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
							'artikel'     => $artikel->geef_artikelnaam(),
							'referentie'  => $order->referentie,
							'betaal_link' => $artikel->betaal_link,
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
							'artikel'     => $artikel->geef_artikelnaam(),
							'referentie'  => $order->referentie,
							'betaal_link' => $artikel->betaal_link,
						],
					]
				);
				$status = 'De korting is verwerkt en een correctie is verstuurd';
				break;
			case 'afboeken':
				$artikel->afzeggen();
				$order->afboeken();
				$status = 'De order is afgeboekt';
		}
		if ( ! empty( $status ) ) {
			return [
				'status'  => $this->status( $status ),
				'content' => $this->display(),
			];
		}
		return [ 'status' => new WP_Error( 'fout', 'Er is iets fout gegaan' ) ];
	}
}
