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
		$blokkade                 = new Blokkade();
		$this->data['wijzigbaar'] = $blokkade->wijzigbaar();
		return $this->content();
	}

	/**
	 * Prepareer 'debiteuren' debiteur form
	 *
	 * @return string
	 */
	protected function prepare_debiteur() : string {
		$this->data['order'] = new Order( $this->data['id'] );
		return $this->content();
	}

	/**
	 * Prepareer 'debiteuren' zoek form
	 *
	 * @return string
	 */
	protected function prepare_zoek() : string {
		$zoek       = ( $this->data['id'] ?? '' ) ?: wp_generate_uuid4(); // Als er nog geen zoek string is, zoek dan naar iets wat niet gevonden kan worden.
		$this->data = array_merge( $this->data, $this->get_debiteuren( [ 'zoek' => $zoek ] ) );
		return $this->content();
	}

	/**
	 * Prepareer 'debiteuren' overzicht form
	 *
	 * @return string
	 */
	protected function prepare_overzicht() : string {
		$this->data = array_merge( $this->data, $this->get_debiteuren( [ 'open' => true ] ) );
		return $this->content();
	}

	/**
	 * Prepareer 'aan te manen debiteuren' overzicht form
	 *
	 * @return string
	 */
	protected function prepare_aanmanen() : string {
		$this->data = array_merge( $this->data, $this->get_debiteuren( [ 'aanmanen' => true ] ) );
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
				'aanmaan'              => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_FORCE_ARRAY,
				],
			]
		);
		if ( str_contains( 'blokkade aanmanen', $this->form_actie ) ) {
			return $this->save();
		}
		$this->data['order'] = new Order( $this->data['input']['id'] );
		if ( 'korting' === $this->form_actie ) {
			if ( $this->data['order']->orderregels->get_bruto() < $this->data['input']['korting'] ) {
				return $this->melding( new WP_Error( 'fout', 'De korting kan niet groter zijn dan het totale bedrag' ) );
			}
		}
		return $this->save();
	}

	/**
	 * Verzend de aanmaningen
	 *
	 * @return array
	 */
	protected function aanmanen() : array {
		$emailer = new Email();
		foreach ( $this->data['input']['aanmaan'] as $order_id ) {
			$order                = new Order( $order_id );
			$artikelregister      = new Artikelregister();
			$artikel              = $artikelregister->get_object( $order->referentie );
			$order->aanmaan_datum = strtotime( 'now' );
			$order->save();
			$emailer->send(
				[
					'to'          => $order->klant['email'],
					'slug'        => 'order_aanmaning',
					'subject'     => 'Herinnering betaling',
					'attachments' => $order->get_factuur(),
					'parameters'  => [
						'naam'        => $order->klant['naam'],
						'referentie'  => $order->referentie,
						'openstaand'  => number_format_i18n( $order->get_te_betalen(), 2 ),
						'betaal_link' => $artikel->get_betaal_link(),
						'artikel'     => $artikel->get_artikelnaam(),
					],
				]
			);
		}
		return [
			'status'  => $this->status(
				count( $this->data['input']['aanmaan'] ) ?
					sprintf( 'Er zijn %d herinneringen per email verzonden', count( $this->data['input']['aanmaan'] ) ) :
					'Er zijn geen emails verzonden'
			),
			'content' => $this->display(),
		];
	}

	/**
	 * Voer een bankbetaling uit
	 *
	 * @return array
	 */
	protected function bankbetaling() : array {
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->get_object( $this->data['order']->referentie );
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
		$artikel         = $artikelregister->get_object( $this->data['order']->referentie );
		$melding         = '';
		if ( $this->data['order']->betaald - floatval( $this->data['input']['restant'] ) > 0 ) {
			$melding = $this->data['order']->transactie_id ? 'Er wordt een stornering gedaan' : 'Het teveel betaalde moet per bank teruggestort worden';
		}
		$credit_factuur = $this->data['order']->annuleer( floatval( $this->data['input']['restant'] ), $this->data['input']['opmerking_annulering'] );
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
					'artikel'     => $artikel->get_artikelnaam(),
					'referentie'  => $this->data['order']->referentie,
					'betaal_link' => $artikel->get_betaal_link(),
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
		$artikel         = $artikelregister->get_object( $this->data['order']->referentie );
		$factuur         = $this->data['order']->korting( floatval( $this->data['input']['korting'] ), $this->data['input']['opmerking_korting'] );
		$emailer->send(
			[
				'to'          => $this->data['order']->klant['email'],
				'slug'        => 'order_correctie',
				'subject'     => 'Order gecorrigeerd',
				'attachments' => $factuur,
				'parameters'  => [
					'naam'        => $this->data['order']->klant['naam'],
					'artikel'     => $artikel->get_artikelnaam(),
					'referentie'  => $this->data['order']->referentie,
					'betaal_link' => $artikel->get_betaal_link(),
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
	protected function zend_factuur() : array {
		$emailer         = new Email();
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->get_object( $this->data['order']->referentie );
		$emailer->send(
			[
				'to'          => $this->data['order']->klant['email'],
				'slug'        => 'herzend_factuur',
				'subject'     => 'Herzending factuur',
				'attachments' => $this->data['order']->get_factuur(),
				'parameters'  => [
					'naam'        => $this->data['order']->klant['naam'],
					'referentie'  => $this->data['order']->referentie,
					'betaal_link' => $artikel->get_betaal_link(),
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
		$this->data['order']->afboeken();
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
	 * Maak de lijst van openstaande betalingen.
	 *
	 * @param array $filter Het eventuele filter.
	 * @return array De info.
	 */
	private function get_debiteuren( array $filter ) : array {
		$data   = [
			'openstaand'   => 0,
			'terugstorten' => false,
			'orders'       => [],
		];
		$filter = array_merge(
			[
				'zoek'     => '',
				'open'     => false,
				'aanmanen' => false,
			],
			$filter
		);
		$orders = new Orders( [ 'latest' ] );
		foreach ( $orders as $order ) {
			if (
				( $filter['zoek'] && false === stripos( "{$order->klant['naam']} $order->referentie", $filter['zoek'] ) ) ||
				( $filter['open'] && $order->gesloten ) ||
				( $filter['aanmanen'] && ( $order->verval_datum > strtotime( 'today' ) || $order->gesloten || $order->credit ) )
			) {
				continue;
			}
			$openstaand           = $order->get_te_betalen();
			$data['orders'][]     = $order;
			$data['openstaand']  += $openstaand;
			$data['terugstorten'] = $data['terugstorten'] || ( 0 > $openstaand && ! $order->transactie_id ); // Alleen bankterugstorting.
		}
		return $data;
	}

}
