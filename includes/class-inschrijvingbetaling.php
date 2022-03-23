<?php
/**
 * Definieer de inschrijving betaling class
 *
 * @link       https://www.kleistad.nl
 * @since      6.14.7
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad InschrijvingBetaling class.
 *
 * @since 6.14.7
 */
class InschrijvingBetaling extends ArtikelBetaling {

	/**
	 * Het inschrijving object
	 *
	 * @var Inschrijving $inschrijving Het object
	 */
	private Inschrijving $inschrijving;

	/**
	 * Het betaal object
	 *
	 * @var Betalen $betalen Het betaal object
	 */
	private Betalen $betalen;

	/**
	 * Constructor
	 *
	 * @param Inschrijving $inschrijving De inschrijving.
	 */
	public function __construct( Inschrijving $inschrijving ) {
		$this->inschrijving = $inschrijving;
		$this->betalen      = new Betalen();
	}

	/**
	 * Betaal het abonnement met iDeal.
	 *
	 * @param  string $bericht    Het bericht bij succesvolle betaling.
	 * @param  float  $bedrag     Het te betalen bedrag.
	 * @param  string $referentie De referentie.
	 * @return bool|string De redirect url ingeval van een ideal betaling of leeg als het niet lukt.
	 */
	public function doe_ideal( string $bericht, float $bedrag, string $referentie ): bool|string {
		return $this->betalen->order(
			$this->inschrijving->klant_id,
			$referentie,
			$bedrag,
			sprintf(
				'Kleistad cursus %s %skosten voor %s',
				$this->inschrijving->code,
				$this->inschrijving->heeft_restant() ? 'inschrijf' : 'cursus',
				1 === $this->inschrijving->aantal ? '1 cursist' : $this->inschrijving->aantal . ' cursisten'
			),
			$bericht,
			false
		);
	}

	/**
	 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
	 *
	 * @since        4.2.0
	 *
	 * @param Order  $order         De order, als deze bestaat.
	 * @param float  $bedrag        Het betaalde bedrag.
	 * @param bool   $betaald       Of er werkelijk betaald is.
	 * @param string $type          Type betaling, ideal , directdebit of bank.
	 * @param string $transactie_id De betaling id.
	 */
	public function verwerk( Order $order, float $bedrag, bool $betaald, string $type, string $transactie_id = '' ) {
		if ( $betaald ) {
			$this->verwerk_betaald( $order, $bedrag, $type, $transactie_id );
			return;
		}
		$this->verwerk_mislukt( $order, $type );
	}

	/**
	 * Verwerk de betaling.
	 *
	 * @param Order  $order         De order als deze bestaat.
	 * @param float  $bedrag        Het betaalde bedrag.
	 * @param string $type          Type betaling, ideal , directdebit of bank.
	 * @param string $transactie_id De betaling id.
	 *
	 * @return void
	 */
	private function verwerk_betaald( Order $order, float $bedrag, string $type, string $transactie_id ) {
		if ( ! $this->inschrijving->ingedeeld && 0 < $bedrag ) {
			if ( $this->indelen() ) {
				if ( ! $order->id ) {
					/**
					 * Er is nog geen order, dan betreft dit inschrijving vanuit het formulier.
					 */
					$order = new Order( $this->inschrijving->get_referentie() );
					$this->inschrijving->verzend_email( 'indeling', $order->actie->bestel( $bedrag, $this->inschrijving->cursus->start_datum, $this->inschrijving->heeft_restant(), $transactie_id ) );
					return;
				}
				if ( $order->is_credit() ) {
					/**
					 * Er is een credit order en het bedrag is positief, dan betreft dit inschrijving vanuit het formulier.
					 */
					$this->inschrijving->verzend_email( 'indeling', $order->actie->bestel( $bedrag, $this->inschrijving->cursus->start_datum, $this->inschrijving->heeft_restant(), $transactie_id ) );
					return;
				}
				/**
				 * Er is al een order, dus er is betaling vanuit een mail link of er is al inschrijfgeld betaald.
				 */
				$order->actie->ontvang( $bedrag, $transactie_id );
				$this->inschrijving->verzend_email( 'indeling' );
				return;
			}
			/**
			 * Indelen was niet meer mogelijk, annuleer de order en zet de cursist op de wachtlijst.
			 */
			$this->inschrijving->actie->naar_wachtlijst();
			return;
		}
		/**
		 * Als de cursist al ingedeeld is volstaat een bedankje ingeval van een betaling per ideal, bank hoeft niet.
		 */
		$order->actie->ontvang( $bedrag, $transactie_id );
		if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting, dan geen email nodig.
			$this->inschrijving->verzend_email( '_ideal_betaald' );
		}
	}

	/**
	 * Verwerk de betaling als deze mislukt is. Alleen bij ideal betalingen interessant.
	 *
	 * @param Order  $order         De order als deze bestaat.
	 * @param string $type          Type betaling, ideal , directdebit of bank.
	 *
	 * @return void
	 */
	private function verwerk_mislukt( Order $order, string $type ) {
		if ( 'ideal' === $type && ! $order->id ) {
			/**
			 * De betaling is fout gegaan, dus als er nog niet ingedeeld is, dan de inschrijving laten vervallen.
			 */
			$this->inschrijving->erase();
		}
	}

	/**
	 * Deel de cursist in.
	 */
	private function indelen() : bool {
		$ruimte = $this->inschrijving->cursus->get_ruimte();
		if ( $ruimte < $this->inschrijving->aantal ) {
			return false;
		}
		$this->inschrijving->ingedeeld   = true;
		$this->inschrijving->wacht_datum = 0;
		$this->inschrijving->save();
		if ( 0 === $ruimte - $this->inschrijving->aantal ) {
			$this->inschrijving->cursus->registreer_vol();
		}
		return true;
	}

}
