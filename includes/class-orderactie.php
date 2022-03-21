<?php
/**
 * De definitie van de order actie class
 *
 * @link       https://www.kleistad.nl
 * @since      7.2.3
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad OrderActie class.
 *
 * @since 7.2.3
 */
final class OrderActie {

	/**
	 * De order waarop de acties moeten worden uitgevoerd.
	 *
	 * @var Order De order.
	 */
	private Order $order;

	/**
	 * Constructor
	 *
	 * @param Order $order De order.
	 */
	public function __construct( Order $order ) {
		$this->order = $order;
	}

	/**
	 * Een bestelling aanmaken.
	 *
	 * @param float  $bedrag        Het betaalde bedrag.
	 * @param int    $verval_datum  De datum waarop de factuur vervalt.
	 * @param string $opmerking     De optionele opmerking in de factuur.
	 * @param string $transactie_id De betalings id.
	 * @param bool   $factuur       Of er een factuur aangemaakt moet worden.
	 *
	 * @return string De url van de factuur.
	 * @suppressWarnings(PHPMD.BooleanArgumentFlag)
	 */
	public function bestel( float $bedrag, int $verval_datum, string $opmerking = '', string $transactie_id = '', bool $factuur = true ): string {
		if ( $this->order->id && $this->order->is_credit() ) {
			return ''; // Credit orders worden niet hergebruikt.
		}
		$artikelregister            = new Artikelregister();
		$artikel                    = $artikelregister->geef_object( $this->order->referentie );
		$this->order->betaald      += $bedrag; // Als er al eerder op de order betaald is, het bedrag toevoegen.
		$this->order->klant_id      = $artikel->klant_id;
		$this->order->klant         = $artikel->naw_klant();
		$this->order->opmerking     = $opmerking;
		$this->order->transactie_id = $transactie_id ?? $this->order->transactie_id; // Overschrijf het transactie_id alleen als er een ideal betaling is gedaan.
		$this->order->verval_datum  = $verval_datum;
		$this->order->orderregels   = $artikel->geef_factuurregels();
		$this->order->save( $factuur ? sprintf( 'Order en factuur aangemaakt, nieuwe status betaald is € %01.2f', $bedrag ) : 'Order aangemaakt' );
		do_action( 'kleistad_betaalinfo_update', $artikel->klant_id );
		return $factuur ? $this->maak_factuur( '' ) : '';
	}

	/**
	 * Een bestelling annuleren.
	 *
	 * @param float  $restant   Het te betalen bedrag bij annulering.
	 * @param string $opmerking De opmerkingstekst in de factuur.
	 *
	 * @return bool|string De url van de creditfactuur of false indien annulering niet mogelijk.
	 */
	public function annuleer( float $restant, string $opmerking = '' ): bool|string {
		if ( $this->order->credit_id || $this->order->origineel_id ) {
			return false;  // De relatie id's zijn ingevuld dus er is al een credit factuur of dit is een creditering.
		}
		$artikelregister            = new Artikelregister();
		$artikel                    = $artikelregister->geef_object( $this->order->referentie );
		$credit_order               = clone $this->order;
		$credit_order->origineel_id = $this->order->id;
		$credit_order->verval_datum = strtotime( 'tomorrow' );
		$credit_order->opmerking    = $opmerking;
		$credit_order->orderregels  = new Orderregels();

		foreach ( $this->order->orderregels as $orderregel ) {
			$credit_order->orderregels->toevoegen( new Orderregel( "annulering $orderregel->artikel", - $orderregel->aantal, $orderregel->prijs, $orderregel->btw ) );
		}
		if ( 0.0 < $restant ) {
			$credit_order->orderregels->toevoegen( new Orderregel( 'kosten i.v.m. annulering', 1, $restant ) );
		}
		$this->order->credit_id = $credit_order->save( 'Order en credit factuur aangemaakt' );
		$this->order->betaald   = 0;
		$this->order->save( sprintf( 'Geannuleerd, credit factuur %s aangemaakt', $credit_order->factuurnummer() ) );

		if ( property_exists( $artikel, 'actie' ) && method_exists( $artikel->actie, 'afzeggen' ) ) {
			$artikel->actie->afzeggen();
		}
		do_action( 'kleistad_betaalinfo_update', $artikel->klant_id );
		return $credit_order->actie->maak_factuur( 'credit' );
	}

	/**
	 * Een bestelling wijzigen ivm korting.
	 *
	 * @param float  $korting   De te geven korting.
	 * @param string $opmerking De opmerking in de factuur.
	 *
	 * @return bool|string De url van de factuur of fout.
	 */
	public function korting( float $korting, string $opmerking = '' ): bool|string {
		if ( $this->order->is_geblokkeerd() ) {
			return false;
		}
		$this->order->orderregels->toevoegen( new Orderregel( Orderregel::KORTING, 1, - $korting ) );
		$this->order->gesloten  = false;
		$this->order->opmerking = $opmerking;
		$this->order->save( sprintf( 'Correctie factuur i.v.m. korting € %01.2f', $korting ) );
		do_action( 'kleistad_betaalinfo_update', $this->order->klant_id );
		return $this->maak_factuur( 'correctie' );
	}

	/**
	 * Een bestelling wijzigen.
	 *
	 * @param string $referentie De verwijzing naar het gewijzigde artikel.
	 * @param string $opmerking  De optionele opmerking in de factuur.
	 *
	 * @return bool|string De url van de factuur of false.
	 * @noinspection PhpNonStrictObjectEqualityInspection
	 */
	public function wijzig( string $referentie, string $opmerking = '' ): bool|string {
		$artikelregister          = new Artikelregister();
		$artikel                  = $artikelregister->geef_object( $referentie );
		$originele_order          = clone $this->order;
		$this->order->orderregels = $artikel->geef_factuurregels();
		$this->order->referentie  = $referentie;
		$this->order->opmerking   = $opmerking;
		if ( $originele_order == $this->order ) { // phpcs:ignore
			return '';
		}
		$this->order->gesloten = false;
		if ( $this->order->is_geblokkeerd() && $this->order->te_betalen() !== $originele_order->te_betalen() ) {
			return false;
		}
		$this->order->save( 'Order gewijzigd' );
		do_action( 'kleistad_betaalinfo_update', $this->order->klant_id );
		return $this->maak_factuur( 'correctie' );
	}

	/**
	 * Een bestelling betalen.
	 *
	 * @param float  $bedrag        Het betaalde bedrag.
	 * @param string $transactie_id De betalings id.
	 * @param bool   $factuur       Of er wel / niet een factuur aangemaakt moet worden.
	 * @return string Pad naar de factuur of leeg.
	 * @suppressWarnings(PHPMD.BooleanArgumentFlag)
	 */
	public function ontvang( float $bedrag, string $transactie_id, bool $factuur = false ): string {
		$this->order->betaald      += $bedrag;
		$this->order->transactie_id = $transactie_id;
		$this->order->save( sprintf( '%s bedrag € %01.2f nieuwe status betaald is € %01.2f', 0 <= $bedrag ? 'Betaling' : 'Stornering', abs( $bedrag ), $this->order->betaald ) );
		do_action( 'kleistad_betaalinfo_update', $this->order->klant_id );
		return ( $factuur ) ? $this->maak_factuur( '' ) : '';
	}

	/**
	 * Afboeken van een order.
	 */
	public function afboeken() {
		$te_betalen        = $this->order->te_betalen();
		$dd_order          = new Order( '@-' . $this->order->referentie );
		$dd_order->betaald = $te_betalen;
		$dd_order->klant   = $this->order->klant;
		$dd_order->orderregels->toevoegen( new Orderregel( 'Afboeking', 1, $te_betalen ) );
		$dd_order->save( sprintf( 'Afboeking order door %s', wp_get_current_user()->display_name ) );
		$this->order->betaald += $te_betalen;
		$this->order->save( 'Afboeking' );
	}

	/**
	 * Maak een factuur aan.
	 *
	 * @param string $type  Het type factuur.
	 * @return string Het pad naar de factuur.
	 */
	protected function maak_factuur( string $type ): string {
		$factuur = new Factuur();
		return $factuur->run( $this->order, $type );
	}

	/**
	 * Maak opnieuw de factuur aan
	 *
	 * @return string
	 */
	public function herzenden() : string {
		return $this->maak_factuur( $this->order->is_credit() ? 'credit' : '' );
	}

}
