<?php
/**
 * Definieer de abonnement betaling class
 *
 * @link       https://www.kleistad.nl
 * @since      6.14.7
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad AbonnementBetaling class.
 *
 * @since 6.14.7
 */
class AbonnementBetaling extends ArtikelBetaling {

	/**
	 * Het abonnement object
	 *
	 * @var Abonnement $abonnement Het abonnement.
	 */
	private Abonnement $abonnement;

	/**
	 * Het betaal object
	 *
	 * @var Betalen $betalen Het betaal object.
	 */
	private Betalen $betalen;

	/**
	 * Constructor
	 *
	 * @param Abonnement $abonnement Het abonnement.
	 */
	public function __construct( Abonnement $abonnement ) {
		$this->abonnement = $abonnement;
		$this->betalen    = new Betalen();
	}

	/**
	 * Betaal het abonnement met iDeal.
	 *
	 * @param  string $bericht Het bericht bij succesvolle betaling.
	 * @param  float  $bedrag  Het te betalen bedrag.
	 * @return string|bool De redirect url ingeval van een ideal betaling of leeg als het niet lukt.
	 */
	public function doe_ideal( string $bericht, float $bedrag ) {
		switch ( $this->abonnement->artikel_type ) {
			case 'start':
				$vermelding = sprintf(
					' vanaf %s tot %s',
					strftime( '%d-%m-%Y', $this->abonnement->start_datum ),
					strftime( '%d-%m-%Y', $this->abonnement->start_eind_datum )
				);
				$mandaat    = false;
				break;
			case 'overbrugging':
				$vermelding = sprintf(
					' vanaf %s tot %s',
					strftime( '%d-%m-%Y', $this->abonnement->start_eind_datum + DAY_IN_SECONDS ),
					strftime( '%d-%m-%Y', $this->abonnement->reguliere_datum - DAY_IN_SECONDS )
				);
				$mandaat    = true;
				break;
			case 'mandaat':
				$vermelding = ' machtiging tot sepa-incasso';
				$mandaat    = true;
				break;
			default: // Regulier of pauze, echter dan is artikel type YYMM.
				$vermelding = '';
				$mandaat    = false;
		}
		return $this->betalen->order(
			$this->abonnement->klant_id,
			$this->abonnement->geef_referentie(),
			$bedrag,
			"Kleistad abonnement {$this->abonnement->code}$vermelding",
			$bericht,
			$mandaat
		);
	}

	/**
	 * Maak de sepa incasso betalingen.
	 *
	 * @return string
	 */
	public function doe_sepa_incasso() : string {
		$bedrag = $this->geef_bedrag( "#{$this->abonnement->artikel_type}" );
		if ( 0.0 < $bedrag ) {
			return $this->betalen->eenmalig(
				$this->abonnement->klant_id,
				$this->abonnement->geef_referentie(),
				$bedrag,
				"Kleistad abonnement {$this->abonnement->code} " . strftime( '%B %Y', strtotime( 'today' ) ),
			);
		}
		return '';
	}

	/**
	 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
	 *
	 * @since        4.2.0
	 *
	 * @param Order  $order         De order als deze bestaat.
	 * @param float  $bedrag        Het betaalde bedrag, wordt hier niet gebruikt.
	 * @param bool   $betaald       Of er werkelijk betaald is.
	 * @param string $type          Type betaling, ideal , directdebit of bank.
	 * @param string $transactie_id De betaling id.
	 */
	public function verwerk( Order $order, float $bedrag, bool $betaald, string $type, string $transactie_id = '' ) {
		if ( $betaald ) {
			if ( $order->id ) {
				/**
				 * Er bestaat blijkbaar al een order voor deze referentie. Het komt dan vanaf een email betaal link of incasso of betaling per bank.
				 */
				if ( 0 < $bedrag ) {
					if ( 'ideal' === $type ) {
						$this->abonnement->ontvang_order( $order, $bedrag, $transactie_id );
						$this->abonnement->verzend_email( '_ideal_betaald' );
						return;
					}
					if ( 'directdebit' === $type ) { // Als het een incasso is dan wordt er ook een factuur aangemaakt.
						$this->abonnement->verzend_email( '_regulier_incasso', $this->abonnement->ontvang_order( $order, $bedrag, $transactie_id, true ) );
						return;
					}
					// Anders is het een bank betaling en daarvoor wordt geen bedank email verzonden.
					$this->abonnement->ontvang_order( $order, $bedrag, $transactie_id );
					return;
				}
				// Anders is het een terugstorting.
				$this->abonnement->ontvang_order( $order, $bedrag, $transactie_id );
				return;
			}
			if ( 'mandaat' === $this->abonnement->artikel_type ) {
				/**
				 * Bij een mandaat ( 1 eurocent ) hoeven we geen factuur te sturen en is er dus geen order aangemaakt.
				 */
				$this->abonnement->bericht = 'Je hebt Kleistad toestemming gegeven voor een maandelijkse incasso van het abonnement';
				$this->abonnement->verzend_email( '_gewijzigd' );
				return;
			}
			if ( 'start' === $this->abonnement->artikel_type ) {
				/**
				 * Bij een start en nog niet bestaande order moet dit wel afkomstig zijn van het invullen van
				 * een inschrijving formulier.
				 */
				$this->abonnement->factuur_maand = (int) date( 'Ym' );
				$this->abonnement->save();
				$this->abonnement->verzend_email( '_start_ideal', $this->abonnement->bestel_order( $bedrag, $this->abonnement->start_datum, '', $transactie_id ) );
				return;
			}
		} elseif ( 'directdebit' === $type && $order->id ) {
			/**
			 * Als het een incasso betreft die gefaald is dan is het bedrag 0 en moet de factuur alsnog aangemaakt worden.
			 */
			$this->abonnement->verzend_email( '_regulier_mislukt', $this->abonnement->ontvang_order( $order, 0, $transactie_id, true ) );
			return;
		} elseif ( 'ideal' === $type && ! $order->id ) {
			$this->abonnement->erase();
		}
	}

	/**
	 * Bereken de prijs van een extra.
	 *
	 * @param string $extra het extra element.
	 * @return float Het maandbedrag van de extra.
	 */
	public function geef_bedrag_extra( string $extra ) : float {
		foreach ( opties()['extra'] as $extra_optie ) {
			if ( $extra === $extra_optie['naam'] ) {
				return (float) $extra_optie['prijs'];
			}
		}
		return 0.0;
	}

	/**
	 * Bereken de maandelijkse kosten, de overbrugging, of het startbedrag.
	 *
	 * @param  string $type Welk bedrag gevraagd wordt, standaard het maandbedrag.
	 * @return float Het maandbedrag.
	 */
	public function geef_bedrag( string $type = '' ) : float {
		$basis_bedrag  = (float) opties()[ "{$this->abonnement->soort}_abonnement" ];
		$extras_bedrag = 0.0;
		foreach ( $this->abonnement->extras as $extra ) {
			$extras_bedrag += $this->geef_bedrag_extra( $extra );
		}
		switch ( $type ) {
			case '#mandaat':
				return 0.01;
			case '#start':
				return 3 * $basis_bedrag;
			case '#overbrugging':
				return $this->abonnement->geef_overbrugging_fractie() * $basis_bedrag;
			case '#regulier':
				return $basis_bedrag + $extras_bedrag;
			case '#pauze':
				return $this->abonnement->geef_pauze_fractie() * ( $basis_bedrag + $extras_bedrag );
			default:
				return $basis_bedrag;
		}
	}

		/**
		 * Bepaalt of er automatisch betaald wordt.
		 *
		 * @return bool
		 */
	public function incasso_actief() : bool {
		return $this->betalen->heeft_mandaat( $this->abonnement->klant_id );
	}

}
