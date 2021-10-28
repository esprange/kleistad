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
	 * @return string|bool De redirect url ingeval van een ideal betaling of leeg als het niet lukt.
	 */
	public function doe_ideal( string $bericht, float $bedrag, string $referentie ) {
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
	 * @param float  $bedrag        Het betaalde bedrag, wordt hier niet gebruikt.
	 * @param bool   $betaald       Of er werkelijk betaald is.
	 * @param string $type          Type betaling, ideal , directdebit of bank.
	 * @param string $transactie_id De betaling id.
	 */
	public function verwerk( Order $order, float $bedrag, bool $betaald, string $type, string $transactie_id = '' ) {
		if ( $betaald ) {
			if ( ! $order->id ) {
				/**
				 * Er is nog geen order, dus dit betreft inschrijving vanuit het formulier.
				 */
				$this->indelen();
				$this->inschrijving->verzend_email( 'indeling', $this->inschrijving->bestel_order( $bedrag, $this->inschrijving->cursus->start_datum, $this->inschrijving->heeft_restant(), $transactie_id ) );
				return;
			}
			/**
			 * Er is al een order, dus er is betaling vanuit een mail link of er is al inschrijfgeld betaald.
			 */
			$this->inschrijving->ontvang_order( $order, $bedrag, $transactie_id );
			if ( ! $this->inschrijving->ingedeeld ) { // Voorafgaand de betaling was de cursist nog niet ingedeeld.
				/**
				 * De cursist krijgt de melding dat deze nu ingedeeld is als er nog ruimte is.
				 */
				if ( $this->indelen() ) {
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
			if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting, dan geen email nodig.
				$this->inschrijving->verzend_email( '_ideal_betaald' );
			}
		} elseif ( 'ideal' === $type && ! $order->id ) {
			$this->inschrijving->erase();
		}
	}

	/**
	 * Deel de cursist in.
	 */
	private function indelen() : bool {
		$ruimte = $this->inschrijving->cursus->ruimte();
		if ( $ruimte < $this->inschrijving->aantal ) {
			return false;
		}
		$this->inschrijving->ingedeeld = true;
		$this->inschrijving->save();
		if ( 0 === $ruimte - $this->inschrijving->aantal ) {
			$this->inschrijving->cursus->registreer_vol();
		}
		return true;
	}

}
