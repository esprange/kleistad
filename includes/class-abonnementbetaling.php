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

use Exception;
use Mollie\Api\Exceptions\ApiException;

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
	 * @param string $bericht    Het bericht bij succesvolle betaling.
	 * @param float  $bedrag     Het te betalen bedrag.
	 * @param string $referentie De referentie van de order.
	 * @return bool|string De redirect url ingeval van een ideal betaling of leeg als het niet lukt.
	 */
	public function doe_ideal( string $bericht, float $bedrag, string $referentie ): bool|string {
		$artikel_type = explode( '-', $referentie )[1];
		$vermelding   = '';
		$mandaat      = false;
		switch ( $artikel_type ) {
			case 'start':
				$vermelding = sprintf(
					' vanaf %s tot %s',
					strftime( '%d-%m-%Y', $this->abonnement->start_datum ),
					strftime( '%d-%m-%Y', $this->abonnement->start_eind_datum )
				);
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
			case 'regulier':
			case 'pauze':
		}
		return $this->betalen->order(
			$this->abonnement->klant_id,
			$referentie,
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
		$bedrag = $this->get_bedrag( "#{$this->abonnement->artikel_type}" );
		if ( 0.0 < $bedrag ) {
			try {
				return $this->betalen->eenmalig(
					$this->abonnement->klant_id,
					$this->abonnement->get_referentie(),
					$bedrag,
					"Kleistad abonnement {$this->abonnement->code} " . strftime( '%B %Y', strtotime( 'today' ) ),
				);
			} catch ( Exception ) { // phpcs:ignore
				// geen verdere actie.
			}
		}
		return '';
	}

	/**
	 * Bereken de prijs van een extra.
	 *
	 * @param string $extra het extra element.
	 * @return float Het maandbedrag van de extra.
	 */
	public function get_bedrag_extra( string $extra ) : float {
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
	public function get_bedrag( string $type = '' ) : float {
		$basis_bedrag  = (float) opties()[ "{$this->abonnement->soort}_abonnement" ];
		$extras_bedrag = 0.0;
		foreach ( $this->abonnement->extras as $extra ) {
			$extras_bedrag += $this->get_bedrag_extra( $extra );
		}

		return match ( $type ) {
			'#mandaat'      => 0.01,
			'#start'        => opties()['start_maanden'] * $basis_bedrag,
			'#overbrugging' => $this->abonnement->get_overbrugging_fractie() * $basis_bedrag,
			'#regulier'     => $basis_bedrag + $extras_bedrag,
			'#pauze'        => $this->abonnement->get_pauze_fractie() * ( $basis_bedrag + $extras_bedrag ),
			default         => $basis_bedrag,
		};
	}

	/**
	 * Bepaalt of er automatisch betaald wordt.
	 *
	 * @return bool
	 * @throws ApiException Op hoger nivo af te handelen.
	 */
	public function incasso_actief() : bool {
		return $this->betalen->heeft_mandaat( $this->abonnement->klant_id );
	}

	/**
	 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
	 *
	 * @since        4.2.0
	 *
	 * @param Order  $order         De order als deze bestaat.
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
		if ( $order->id ) {
			/**
			 * Er bestaat blijkbaar al een order voor deze referentie. Het komt dan vanaf een email betaal link of incasso of betaling per bank.
			 */
			if ( 0 < $bedrag ) {
				if ( 'ideal' === $type ) {
					$order->ontvang( $bedrag, $transactie_id );
					$this->abonnement->verzend_email( '_ideal_betaald' );
					return;
				}
				if ( 'directdebit' === $type ) { // Als het een incasso is dan wordt er ook een factuur aangemaakt.
					$this->abonnement->verzend_email( '_regulier_incasso', $order->ontvang( $bedrag, $transactie_id, true ) );
					return;
				}
				// Anders is het een bank betaling en daarvoor wordt geen bedank email verzonden.
				$order->ontvang( $bedrag, $transactie_id );
				return;
			}
			// Anders is het een terugstorting.
			$order->ontvang( $bedrag, $transactie_id );
			return;
		}
		/**
		 * Geen order.
		 */
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
			$order = new Order( $this->abonnement->get_referentie() );
			$this->abonnement->verzend_email( '_start_ideal', $order->bestel( $bedrag, $this->abonnement->start_datum, '', $transactie_id ) );
			return;
		}
		/**
		 * Blijkbaar een ander artikel type. Dat zou niet mogen.
		 */
		fout( __CLASS__, "betaling niet verwerkt voor artikel type {$this->abonnement->artikel_type}, betaaltype: $type, transactie: $transactie_id" );
	}

	/**
	 * Verwerk de betaling als deze mislukt is.
	 *
	 * @param Order  $order         De order als deze bestaat.
	 * @param string $type          Type betaling, ideal , directdebit of bank.
	 *
	 * @return void
	 */
	private function verwerk_mislukt( Order $order, string $type ) {
		if ( 'directdebit' === $type && $order->id ) {
				/**
				 * Als het een incasso betreft die gefaald is dan is het bedrag 0 en moet de factuur alsnog aangemaakt worden.
				 */
			$this->abonnement->verzend_email( '_regulier_mislukt', $order->ontvang( 0, '', true ) );
			return;
		}
		if ( 'ideal' === $type ) {
			/**
			 * Een inschrijving die niet voltooid wordt.
			 */
			if ( ! $order->id ) {
				$this->abonnement->erase();
			}
			return;
		}
		/**
		 * Blijkbaar iets anders. Dat zou niet mogen.
		 */
		fout( __CLASS__, "betaling mislukt niet verwerkt voor artikel type {$this->abonnement->artikel_type}, betaaltype: $type" );
	}

}
