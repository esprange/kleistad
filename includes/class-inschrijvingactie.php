<?php
/**
 * Definieer de inschrijving actie class
 *
 * @link       https://www.kleistad.nl
 * @since      6.14.7
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad InschrijvingActie class.
 *
 * @since 6.14.7
 */
class InschrijvingActie {

	/**
	 * Het inschrijving object
	 *
	 * @var Inschrijving $inschrijving
	 */
	private Inschrijving $inschrijving;

	/**
	 * Constructor
	 *
	 * @param Inschrijving $inschrijving De inschrijving.
	 */
	public function __construct( Inschrijving $inschrijving ) {
		$this->inschrijving = $inschrijving;
	}

	/**
	 * Zeg de gemaakte afspraak voor de cursus af.
	 */
	public function afzeggen() {
		if ( ! $this->inschrijving->geannuleerd ) {
			$this->inschrijving->geannuleerd = true;
			$this->inschrijving->wacht_datum = 0;
			$this->inschrijving->save();
			foreach ( $this->inschrijving->extra_cursisten as $extra_cursist_id ) {
				$extra_inschrijving              = new Inschrijving( $this->inschrijving->cursus->id, $extra_cursist_id );
				$extra_inschrijving->geannuleerd = true;
				$extra_inschrijving->save();
			}
		}
	}

	/**
	 * Stuur de herinnerings email.
	 *
	 * @return int Aantal emails verstuurd.
	 */
	public function herinnering() : int {
		if ( 0 === $this->inschrijving->aantal || $this->inschrijving->geannuleerd || ! $this->inschrijving->ingedeeld ) {
			return 0;
		}
		$order            = new Order( $this->inschrijving->get_referentie() );
		$regeling_betaald = $order->betaald > ( $this->inschrijving->aantal * $this->inschrijving->cursus->inschrijfkosten + 1 );
		if ( $order->gesloten || $regeling_betaald || $this->inschrijving->herinner_email ) {
			/**
			 * Als de cursist al betaald heeft of via deelbetaling de kosten voldoet en een eerste deel betaald heeft, geen actie.
			 * En uiteraard sturen maar éénmaal de standaard herinnering.
			 */
			return 0;
		}
		$this->inschrijving->artikel_type   = 'cursus';
		$this->inschrijving->herinner_email = true;
		$this->inschrijving->save();
		$this->inschrijving->verzend_email( '_herinnering' );
		return 1;
	}

	/**
	 * Verstuur de melding dat het restant betaald moet worden als dat nog niet betaald is
	 */
	public function restant_betaling() {
		$order = new Order( $this->inschrijving->get_referentie() );
		if ( $order->id && ! $order->gesloten ) {
			$this->inschrijving->artikel_type  = 'cursus';
			$this->inschrijving->restant_email = true;
			$this->inschrijving->save();
			$this->inschrijving->verzend_email( '_restant' );
		}
	}

	/**
	 * Geef de cursist aan dat er ruimte beschikbaar is gekomen
	 */
	public function plaatsbeschikbaar() {
		$this->inschrijving->wacht_datum = $this->inschrijving->cursus->ruimte_datum;
		$this->inschrijving->save();
		$this->inschrijving->verzend_email( '_ruimte' );
	}

	/**
	 * Corrigeer de inschrijving naar nieuwe cursus.
	 *
	 * @param int $cursus_id nieuw cursus_id.
	 * @param int $aantal    aantal.
	 * @return bool
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	public function correctie( int $cursus_id, int $aantal ) : bool {
		$order = new Order( $this->inschrijving->get_referentie() );
		if ( $cursus_id === $this->inschrijving->cursus->id ) {
			if ( $aantal === $this->inschrijving->aantal ) {
				return false; // Geen wijzigingen.
			}
			$this->inschrijving->aantal = $aantal;
			$this->inschrijving->save();
		} else {
			$nieuwe_cursus              = new Cursus( $cursus_id );
			$oude_cursus_id             = $this->inschrijving->cursus->id;
			$this->inschrijving->code   = "C$cursus_id-{$this->inschrijving->klant_id}";
			$this->inschrijving->aantal = $aantal;
			$this->inschrijving->cursus = $nieuwe_cursus;
			$this->inschrijving->save();
			foreach ( $this->inschrijving->extra_cursisten as $extra_cursist_id ) {
				$extra_inschrijving = new Inschrijving( $this->inschrijving->cursus->id, $extra_cursist_id );
				$extra_inschrijving->save();
			}
			$oude_inschrijving = new Inschrijving( $oude_cursus_id, $this->inschrijving->klant_id );
			$oude_inschrijving->actie->afzeggen();
		}
		$factuur = $order->wijzig( $this->inschrijving->get_referentie() );
		if ( empty( $factuur ) ) {
			return false; // Er is niets gewijzigd.
		}
		$this->inschrijving->verzend_email( '_wijziging', $factuur );
		return true;
	}

	/**
	 * Verwerk de aanvraag tot inschrijving
	 *
	 * @param string $betaalwijze Bank of ideal.
	 * @param int    $aantal      Aantal in te schrijven cursisten.
	 * @param array  $technieken  Gekozen technieken.
	 * @param string $opmerking   Opmerking gemaakt door klant.
	 * @return bool|string De url ingeval van ideal of het resultaat.
	 */
	public function aanvraag( string $betaalwijze, int $aantal, array $technieken, string $opmerking ): bool|string {
		if ( $this->inschrijving->geannuleerd ) { // Blijkbaar eerder geannuleerd, eerst resetten.
			$this->inschrijving->ingedeeld    = false;
			$this->inschrijving->geannuleerd  = false;
			$this->inschrijving->ingeschreven = false;
		}
		$this->inschrijving->aantal       = $aantal;
		$this->inschrijving->technieken   = $technieken;
		$this->inschrijving->opmerking    = $opmerking;
		$this->inschrijving->wacht_datum  = $this->inschrijving->cursus->vol ? time() : 0;
		$this->inschrijving->artikel_type = 'inschrijving';
		$this->inschrijving->save();
		if ( $this->inschrijving->cursus->vol ) {
			$this->inschrijving->verzend_email( '_wachtlijst' );
			return true;
		}
		if ( $this->inschrijving->cursus->is_lopend() ) {
			$this->inschrijving->verzend_email( '_lopend' );
			return true;
		}
		if ( 'ideal' === $betaalwijze ) {
			return $this->inschrijving->betaling->doe_ideal( 'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging', $this->inschrijving->aantal * $this->inschrijving->cursus->get_bedrag(), $this->inschrijving->get_referentie() );
		}
		$order = new Order( $this->inschrijving->get_referentie() );
		$this->inschrijving->verzend_email( 'inschrijving', $order->bestel( 0.0, $this->inschrijving->cursus->start_datum, $this->inschrijving->get_restant_melding() ) );
		return true;
	}

	/**
	 * Deel de cursist in op een lopende cursus
	 *
	 * @param float $prijs De prijs van de cursus.
	 * @return void
	 */
	public function indelen_lopend( float $prijs ) {
		$this->inschrijving->maatwerkkosten = $prijs;
		$this->inschrijving->ingedeeld      = true;
		$this->inschrijving->wacht_datum    = 0;
		$this->inschrijving->restant_email  = true; // We willen geen restant email naar deze cursist.
		$this->inschrijving->artikel_type   = 'inschrijving';
		$this->inschrijving->save();
		$order = new Order( $this->inschrijving->get_referentie() );
		$this->inschrijving->verzend_email( '_lopend_betalen', $order->bestel( 0.0, strtotime( '+7 days 0:00' ) ) );
	}

	/**
	 * Deel de cursist in nadat deze op de wachtlijst gestaan heeft.
	 *
	 * @return bool|string De betaal URI of het resultaat.
	 */
	public function indelen_na_wachten() : bool|string {
		$this->inschrijving->artikel_type = 'inschrijving';
		$this->inschrijving->save();
		return $this->inschrijving->betaling->doe_ideal( 'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging', $this->inschrijving->cursus->get_bedrag(), $this->inschrijving->get_referentie() );
	}

	/**
	 * Uitschrijven van wachtlijst
	 *
	 * @return void
	 */
	public function uitschrijven_wachtlijst() {
		$this->inschrijving->geannuleerd = true;
		$this->inschrijving->save();
	}

	/**
	 * Cursist is ingeschreven maar heeft nog niet betaald. Omdat de cursus vol zit, gaat de cursist naar de wachtlijst.
	 *
	 * @return void
	 */
	public function naar_wachtlijst() {
		if ( $this->inschrijving->wacht_datum || $this->inschrijving->ingedeeld || $this->inschrijving->geannuleerd || $this->inschrijving->cursus->is_lopend() ) {
			return; // Niets doen als de inschrijving al op de wachtlijst staat of is ingedeeld of geannuleerd of de cursus al gestart is.
		}
		$order = new Order( $this->inschrijving->get_referentie() );
		$this->inschrijving->verzend_email(
			'_naar_wachtlijst',
			$order->annuleer( 0.0, 'i.v.m. volle cursus verplaatst naar wachtlijst' ) ?: ''
		); // De cursist is naar de wachtlijst verplaatst, de order is geannuleerd en de email kan verzonden worden.
		$this->inschrijving->wacht_datum = time();
		$this->inschrijving->geannuleerd = false;
		$this->inschrijving->save();
	}

	/**
	 * Bepaal of er nog wel ingeschreven kan worden voor de cursus. Deze functie wordt vanuit Artikel betaal proces aangeroepen.
	 *
	 * @since 6.6.1
	 *
	 * @return string Lege string als inschrijving mogelijk is, anders de foutboodschap.
	 */
	public function get_beschikbaarheid() : string {
		if ( ! $this->inschrijving->ingedeeld && $this->inschrijving->cursus->vol ) {
			$this->inschrijving->wacht_datum = $this->inschrijving->wacht_datum ?: time();
			$this->inschrijving->save();
			return 'Helaas is de cursus nu vol. Mocht er een plek vrijkomen dan ontvang je een email';
		}
		return '';
	}

}
