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
	private function debiteuren( string $zoek = '' ) : array {
		$data            = [
			'openstaand'   => 0,
			'terugstorten' => false,
			'debiteuren'   => [],
		];
		$artikelregister = new Artikelregister();
		$orders          = new Orders();
		foreach ( $orders as $order ) {
			if ( (
				! empty( $zoek ) && false === stripos( $order->klant['naam'] . ' ' . $order->referentie, $zoek )
			) ||
				( empty( $zoek ) && $order->gesloten )
				) {
				continue;
			}
			$openstaand           = $order->te_betalen();
			$data['debiteuren'][] = [
				'id'           => $order->id,
				'naam'         => $order->klant['naam'],
				'betreft'      => $artikelregister->geef_naam( $order->referentie ),
				'referentie'   => $order->referentie,
				'openstaand'   => $openstaand,
				'credit'       => boolval( $order->origineel_id ),
				'sinds'        => $order->datum,
				'gesloten'     => $order->gesloten,
				'verval_datum' => $order->verval_datum,
			];
			$data['openstaand']  += $openstaand;
			$data['terugstorten'] = $data['terugstorten'] || 0 > $openstaand;
		}
		return $data;
	}

	/**
	 * Toon de informatie van één debiteur.
	 *
	 * @param int $order_id Het order id.
	 * @return array De informatie.
	 */
	private function debiteur( int $order_id ) : array {
		$order           = new Order( $order_id );
		$artikelregister = new Artikelregister();
		return [
			'id'            => $order->id,
			'naam'          => $order->klant['naam'],
			'betreft'       => $artikelregister->geef_naam( $order->referentie ),
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
	protected function prepare( array &$data ) {
		$blokkade                 = new Blokkade();
		$data['huidige_blokkade'] = $blokkade->get();
		if ( 'blokkade' === $data['actie'] ) {
			$data['wijzigbaar'] = $blokkade->wijzigbaar();
			return true;
		}
		if ( 'debiteur' === $data['actie'] ) {
			$data['debiteur'] = $this->debiteur( $data['id'] );
			return true;
		}
		if ( 'zoek' === $data['actie'] ) {
			$zoek = $data['id'] ?? random_bytes( 15 ); // Als er nog geen zoek string is, zoek dan naar iets wat niet gevonden kan worden.
			$data = array_merge( $data, $this->debiteuren( $zoek ) );
			return true;
		}
		$data = array_merge( $data, [ 'actie' => 'openstaand' ], $this->debiteuren() );
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
	protected function validate( array &$data ) {
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'id'                   => FILTER_SANITIZE_NUMBER_INT,
				'bedrag_betaald'       => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'bedrag_gestort'       => [
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
			]
		);
		if ( 'blokkade' === $data['form_actie'] ) {
			return true;
		}
		$data['order'] = new Order( $data['input']['id'] );
		if ( 'korting' === $data['form_actie'] ) {
			if ( $data['order']->orderregels->bruto() < $data['input']['korting'] ) {
				return new WP_Error( 'fout', 'De korting kan niet groter zijn dan het totale bedrag' );
			}
		}
		return true;
	}

	/**
	 * Voer een bankbetaling uit
	 *
	 * @param array $data te bewaren data.
	 * @return array
	 */
	protected function bankbetaling( array $data ) : array {
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->geef_object( $data['order']->referentie );
		$artikel->betaling->verwerk( $data['order'], floatval( $data['input']['bedrag_betaald'] ) ?: - floatval( $data['input']['bedrag_gestort'] ), true, 'bank' );
		return [
			'status'  => $this->status( 'De betaling is verwerkt' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Annuleer een order.
	 *
	 * @param array $data te bewaren data.
	 * @return array
	 */
	protected function annulering( array $data ) : array {
		$emailer         = new Email();
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->geef_object( $data['order']->referentie );
		$melding         = '';
		if ( $data['order']->betaald - floatval( $data['input']['restant'] ) > 0 ) {
			$melding = $data['order']->transactie_id ? 'Er wordt een stornering gedaan' : 'Het teveel betaalde moet per bank teruggestort worden';
		}
		$credit_factuur = $artikel->annuleer_order( $data['order'], floatval( $data['input']['restant'] ), $data['input']['opmerking_annulering'] );
		if ( false === $credit_factuur ) {
			return [
				'status' => $this->status( new WP_Error( 'fout', 'Er bestaat al een creditering dus mogelijk een interne fout' ) ),
			];
		}
		$emailer->send(
			[
				'to'          => $data['order']->klant['email'],
				'slug'        => 'order_annulering',
				'subject'     => 'Order geannuleerd',
				'attachments' => $credit_factuur,
				'parameters'  => [
					'naam'        => $data['order']->klant['naam'],
					'artikel'     => $artikel->geef_artikelnaam(),
					'referentie'  => $data['order']->referentie,
					'betaal_link' => $artikel->betaal_link,
				],
			]
		);
		return [
			'status'  => $this->status( 'De annulering is verwerkt en een bevestiging is verstuurd. ' . $melding ),
			'content' => $this->display(),
		];
	}

	/**
	 * Geef een korting
	 *
	 * @param array $data te bewaren data.
	 * @return array
	 */
	protected function korting( array $data ) : array {
		$emailer         = new Email();
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->geef_object( $data['order']->referentie );
		$factuur         = $artikel->korting_order( $data['order'], floatval( $data['input']['korting'] ), $data['input']['opmerking_korting'] );
		$emailer->send(
			[
				'to'          => $data['order']->klant['email'],
				'slug'        => 'order_correctie',
				'subject'     => 'Order gecorrigeerd',
				'attachments' => $factuur,
				'parameters'  => [
					'naam'        => $data['order']->klant['naam'],
					'artikel'     => $artikel->geef_artikelnaam(),
					'referentie'  => $data['order']->referentie,
					'betaal_link' => $artikel->betaal_link,
				],
			]
		);
		return [
			'status'  => $this->status( 'De korting is verwerkt en een correctie is verstuurd' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Herzend de factuur
	 *
	 * @param array $data de input data.
	 * @return array
	 */
	protected function factuur( array $data ) : array {
		$emailer         = new Email();
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->geef_object( $data['order']->referentie );
		$factuur         = $artikel->herzenden( $data['order'] );
		$emailer->send(
			[
				'to'          => $data['order']->klant['email'],
				'slug'        => 'herzend_factuur',
				'subject'     => 'Herzending factuur',
				'attachments' => $factuur,
				'parameters'  => [
					'naam'        => $data['order']->klant['naam'],
					'referentie'  => $data['order']->referentie,
					'betaal_link' => $artikel->betaal_link,
				],
			]
		);
		return [
			'status'  => $this->status( 'Een email met factuur is opnieuw verzonden' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Boek een order af (dubieuze debiteur)
	 *
	 * @param array $data de input data.
	 * @return array
	 */
	protected function afboeken( array $data ) : array {
		$data['order']->afboeken();
		return [
			'status'  => $this->status( 'De order is afgeboekt' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Voer een blokkade op
	 *
	 * @return array
	 */
	protected function blokkade() : array {
		$blokkade = new Blokkade();
		return [
			'status'  => $blokkade->set() ? 'De blokkade datum is gewijzigd' : new WP_Error( 'intern', 'De blokkade datum kon niet gewijzigd worden' ),
			'content' => $this->goto_home(),
		];
	}

}
