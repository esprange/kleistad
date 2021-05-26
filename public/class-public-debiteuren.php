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
		$debiteuren      = [];
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
			$debiteuren[] = [
				'id'           => $order->id,
				'naam'         => $order->klant['naam'],
				'betreft'      => $artikelregister->geef_naam( $order->referentie ),
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
		$atts = shortcode_atts(
			[ 'actie' => '' ],
			$this->atts,
			'kleistad_debiteuren'
		);
		if ( 'debiteur' === $data['actie'] ) {
			$data['debiteur'] = $this->debiteur( $data['id'] );
			return true;
		}
		if ( 'zoek' === $atts['actie'] ) {
			$data['actie']      = 'zoek';
			$data['debiteuren'] = ! empty( $data['id'] ) ? $this->debiteuren( $data['id'] ) : [];
			$data['openstaand'] = 0;
			foreach ( $data['debiteuren'] as $debiteur ) {
				$data['openstaand'] += $debiteur['openstaand'];
			}
			return true;
		}
		if ( 'blokkade' === $atts['actie'] ) {
			$data['actie']            = 'blokkade';
			$data['huidige_blokkade'] = get_blokkade();
			$data['nieuwe_blokkade']  = strtotime( '+3 month', $data['huidige_blokkade'] );
			return true;
		}
		$data['actie']      = 'openstaand';
		$data['debiteuren'] = $this->debiteuren();
		$data['openstaand'] = 0;
		foreach ( $data['debiteuren'] as $debiteur ) {
			$data['openstaand'] += $debiteur['openstaand'];
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
	protected function validate( array &$data ) {
		$error         = new WP_Error();
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'id'                   => FILTER_SANITIZE_NUMBER_INT,
				'bedrag'               => [
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
			$data['order'] = new Order( $data['input']['id'] );
			if ( 'korting' === $data['input']['debiteur_actie'] ) {
				if ( $data['order']->orderregels->bruto() < $data['input']['korting'] ) {
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
	protected function save( array $data ) : array {
		if ( 'blokkade' === $data['form_actie'] ) {
			return $this->blokkade();
		}
		if ( 'bankbetaling' === $data['input']['debiteur_actie'] ) {
			return $this->bankbetaling( $data['order'], (float) $data['input']['bedrag'] );
		}
		if ( 'bankstorting' === $data['input']['debiteur_actie'] ) {
			return $this->bankbetaling( $data['order'], - (float) $data['input']['bedrag'] );
		}
		if ( 'annulering' === $data['input']['debiteur_actie'] ) {
			return $this->annulering( $data['order'], (float) $data['input']['restant'], $data['input']['opmerking_annulering'] );
		}
		if ( 'korting' === $data['input']['debiteur_actie'] ) {
			return $this->korting( $data['order'], (float) $data['input']['korting'], $data['input']['opmerking_korting'] );
		}
		if ( 'afboeken' === $data['input']['debiteur_actie'] ) {
			return $this->afboeken( $data['order'] );
		}
		return [
			'status'  => 'Er heeft geen actie plaatsgevonden',
			'content' => $this->goto_home(),
		];
	}

	/**
	 * Voer een bankbetaling uit
	 *
	 * @param Order $order  De order.
	 * @param float $bedrag Het bedrag dat betaalt of ontvangen is.
	 * @return array
	 */
	private function bankbetaling( Order $order, float $bedrag ) : array {
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->geef_object( $order->referentie );
		$artikel->betaling->verwerk( $order, $bedrag, true, 'bank' );
		return [
			'status'  => $this->status( 'De betaling is verwerkt' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Annuleer een order.
	 *
	 * @param Order  $order     De order.
	 * @param float  $restant   Het restant nog te betalen.
	 * @param string $opmerking Een opmerking te plaatsen op de factuur.
	 * @return array
	 */
	private function annulering( Order $order, float $restant, string $opmerking ) : array {
		$emailer         = new Email();
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->geef_object( $order->referentie );
		$melding         = '';
		if ( $order->betaald - $restant > 0 ) {
			$melding = $order->transactie_id ? 'Er wordt een stornering gedaan' : 'Het teveel betaalde moet per bank teruggestort worden';
		}
		$emailer->send(
			[
				'to'          => $order->klant['email'],
				'slug'        => 'order_annulering',
				'subject'     => 'Order geannuleerd',
				'attachments' => $artikel->annuleer_order( $order, $restant, $opmerking ),
				'parameters'  => [
					'naam'        => $order->klant['naam'],
					'artikel'     => $artikel->geef_artikelnaam(),
					'referentie'  => $order->referentie,
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
	 * @param Order  $order     De order.
	 * @param float  $korting   De korting.
	 * @param string $opmerking Een opmerking die op de factuur geplaatst wordt.
	 * @return array
	 */
	private function korting( Order $order, float $korting, string $opmerking ) : array {
		$emailer         = new Email();
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->geef_object( $order->referentie );
		$factuur         = $artikel->korting_order( $order, $korting, $opmerking );
		$emailer->send(
			[
				'to'          => $order->klant['email'],
				'slug'        => 'order_correctie',
				'subject'     => 'Order gecorrigeerd',
				'attachments' => $factuur,
				'parameters'  => [
					'naam'        => $order->klant['naam'],
					'artikel'     => $artikel->geef_artikelnaam(),
					'referentie'  => $order->referentie,
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
	 * Boek een order af (dubieuze debiteur)
	 *
	 * @param Order $order De order.
	 * @return array
	 */
	private function afboeken( Order $order ) : array {
		$order->afboeken();
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
	private function blokkade() : array {
		zet_blokkade( strtotime( '+3 month', get_blokkade() ) );
		return [
			'status'  => 'De blokkade datum is gewijzigd',
			'content' => $this->goto_home(),
		];
	}
}
