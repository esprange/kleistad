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
	 * Prepareer 'debiteuren' blokkade form
	 *
	 * @return string
	 */
	protected function prepare_blokkade() : string {
		$blokkade                       = new Blokkade();
		$this->data['huidige_blokkade'] = $blokkade->get();
		$this->data['wijzigbaar']       = $blokkade->wijzigbaar();
		return $this->content();
	}

	/**
	 * Prepareer 'debiteuren' debiteur form
	 *
	 * @return string
	 */
	protected function prepare_debiteur() : string {
		$blokkade                       = new Blokkade();
		$this->data['huidige_blokkade'] = $blokkade->get();
		$this->data['debiteur']         = $this->debiteur( $this->data['id'] );
		return $this->content();
	}

	/**
	 * Prepareer 'debiteuren' zoek form
	 *
	 * @return string
	 */
	protected function prepare_zoek() : string {
		$zoek       = ( $this->data['id'] ?? '' ) ?: wp_generate_uuid4(); // Als er nog geen zoek string is, zoek dan naar iets wat niet gevonden kan worden.
		$this->data = array_merge( $this->data, $this->debiteuren( $zoek ) );
		return $this->content();
	}

	/**
	 * Prepareer 'debiteuren' overzicht form
	 *
	 * @return string
	 */
	protected function prepare_overzicht() : string {
		$this->data = array_merge( $this->data, $this->debiteuren() );
		return $this->content();
	}

	/**
	 *
	 * Valideer/sanitize 'debiteuren' form
	 *
	 * @since   6.1.0
	 *
	 * @return array
	 */
	public function process() : array {
		$this->data['input'] = filter_input_array(
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
		if ( 'blokkade' === $this->form_actie ) {
			return $this->save();
		}
		$this->data['order'] = new Order( $this->data['input']['id'] );
		if ( 'korting' === $this->form_actie ) {
			if ( $this->data['order']->orderregels->bruto() < $this->data['input']['korting'] ) {
				return $this->melding( new WP_Error( 'fout', 'De korting kan niet groter zijn dan het totale bedrag' ) );
			}
		}
		return $this->save();
	}

	/**
	 * Voer een bankbetaling uit
	 *
	 * @return array
	 */
	protected function bankbetaling() : array {
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->geef_object( $this->data['order']->referentie );
		$artikel->betaling->verwerk( $this->data['order'], floatval( $this->data['input']['bedrag_betaald'] ) ?: - floatval( $this->data['input']['bedrag_gestort'] ), true, 'bank' );
		return [
			'status'  => $this->status( 'De betaling is verwerkt' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Annuleer een order.
	 *
	 * @return array
	 */
	protected function annulering() : array {
		$emailer         = new Email();
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->geef_object( $this->data['order']->referentie );
		$melding         = '';
		if ( $this->data['order']->betaald - floatval( $this->data['input']['restant'] ) > 0 ) {
			$melding = $this->data['order']->transactie_id ? 'Er wordt een stornering gedaan' : 'Het teveel betaalde moet per bank teruggestort worden';
		}
		$credit_factuur = $this->data['order']->actie->annuleer( floatval( $this->data['input']['restant'] ), $this->data['input']['opmerking_annulering'] );
		if ( false === $credit_factuur ) {
			return [
				'status' => $this->status( new WP_Error( 'fout', 'Er bestaat al een creditering dus mogelijk een interne fout' ) ),
			];
		}
		$emailer->send(
			[
				'to'          => $this->data['order']->klant['email'],
				'slug'        => 'order_annulering',
				'subject'     => 'Order geannuleerd',
				'attachments' => $credit_factuur,
				'parameters'  => [
					'naam'        => $this->data['order']->klant['naam'],
					'artikel'     => $artikel->geef_artikelnaam(),
					'referentie'  => $this->data['order']->referentie,
					'betaal_link' => $artikel->maak_betaal_link(),
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
	 * @return array
	 */
	protected function korting() : array {
		$emailer         = new Email();
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->geef_object( $this->data['order']->referentie );
		$factuur         = $this->data['order']->actie->korting( floatval( $this->data['input']['korting'] ), $this->data['input']['opmerking_korting'] );
		$emailer->send(
			[
				'to'          => $this->data['order']->klant['email'],
				'slug'        => 'order_correctie',
				'subject'     => 'Order gecorrigeerd',
				'attachments' => $factuur,
				'parameters'  => [
					'naam'        => $this->data['order']->klant['naam'],
					'artikel'     => $artikel->geef_artikelnaam(),
					'referentie'  => $this->data['order']->referentie,
					'betaal_link' => $artikel->maak_betaal_link(),
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
	 * @return array
	 */
	protected function factuur() : array {
		$emailer         = new Email();
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->geef_object( $this->data['order']->referentie );
		$factuur         = $this->data['order']->actie->herzenden( $this->data['order'] );
		$emailer->send(
			[
				'to'          => $this->data['order']->klant['email'],
				'slug'        => 'herzend_factuur',
				'subject'     => 'Herzending factuur',
				'attachments' => $factuur,
				'parameters'  => [
					'naam'        => $this->data['order']->klant['naam'],
					'referentie'  => $this->data['order']->referentie,
					'betaal_link' => $artikel->maak_betaal_link(),
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
	 * @return array
	 */
	protected function afboeken() : array {
		$this->data['order']->actie->afboeken();
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
			if (
				( ! empty( $zoek ) && false === stripos( $order->klant['naam'] . ' ' . $order->referentie, $zoek ) ) ||
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
			$data['terugstorten'] = $data['terugstorten'] || ( 0 > $openstaand && ! $order->transactie_id ); // Alleen bankterugstorting.
		}
		return $data;
	}

}
