<?php
/**
 * De definitie van de order class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Order class.
 *
 * @property int    id
 * @property float  betaald
 * @property int    datum
 * @property int    credit_id
 * @property int    origineel_id
 * @property bool   gesloten
 * @property bool   credit
 * @property array  historie
 * @property array  klant
 * @property int    klant_id
 * @property int    mutatie_datum
 * @property int    verval_datum
 * @property string referentie
 * @property string regels
 * @property string opmerking
 * @property int    factuurnr
 * @property string transactie_id
 *
 * @since 6.1.0
 */
class Order {

	/**
	 * De orderdata
	 *
	 * @var array $data De ruwe data.
	 */
	private array $data;

	/**
	 * De order regels
	 *
	 * @var Orderregels $regels De orderregel objecten.
	 */
	public Orderregels $orderregels;

	/**
	 * Maak het object aan.
	 *
	 * @param int|string $arg  Het order id of de referentie.
	 * @param array|null $load (optioneel) data waarmee het object geladen kan worden (ivm performance).
	 */
	public function __construct( int|string $arg, ?array $load = null ) {
		global $wpdb;
		$this->data = [
			'id'            => 0,
			'betaald'       => 0.0,
			'datum'         => date( 'Y-m-d H:i:s' ),
			'credit_id'     => 0,
			'origineel_id'  => 0,
			'gesloten'      => false,
			'credit'        => false,
			'historie'      => wp_json_encode( [] ),
			'klant'         => wp_json_encode(
				[
					'naam'  => '',
					'adres' => '',
					'email' => '',
				]
			),
			'klant_id'      => 0,
			'mutatie_datum' => null,
			'verval_datum'  => date( 'Y-m-d 00:00:0', strtotime( '+14 days 00:00' ) ),
			'referentie'    => '',
			'regels'        => wp_json_encode( [] ),
			'opmerking'     => '',
			'factuurnr'     => 0,
			'transactie_id' => '',
		];
		$resultaat  = null;
		if ( ! is_null( $load ) ) {
			$resultaat = $load;
		} elseif ( is_numeric( $arg ) ) {
			$resultaat = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_orders WHERE id = %d", intval( $arg ) ), ARRAY_A );
		} elseif ( $arg ) {
			$this->referentie = $arg;
			$resultaat        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_orders WHERE referentie = %s OR transactie_id = %s ORDER BY id DESC LIMIT 1", $arg, $arg ), ARRAY_A ) ?? 0;
		}
		if ( ! empty( $resultaat ) ) {
			$this->data = $resultaat;
		}
		$this->orderregels = new Orderregels( $this->data['regels'] );
	}

	/**
	 * Deep clone
	 */
	public function __clone() {
		$this->origineel_id = $this->id;
		$this->id           = 0;
		$this->datum        = time();
		$this->orderregels  = clone $this->orderregels;
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @since 6.1.0
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( string $attribuut ) {
		if ( in_array( $attribuut, [ 'mutatie_datum', 'verval_datum', 'datum' ], true ) ) {
			return strtotime( $this->data[ $attribuut ] );
		}
		if ( in_array( $attribuut, [ 'credit_id', 'origineel_id', 'klant_id', 'id' ], true ) ) {
			return intval( $this->data[ $attribuut ] );
		}
		if ( in_array( $attribuut, [ 'klant', 'historie' ], true ) ) {
			return json_decode( $this->data[ $attribuut ], true );
		}
		if ( in_array( $attribuut, [ 'gesloten', 'credit' ], true ) ) {
			return boolval( $this->data[ $attribuut ] );
		}
		if ( 'betaald' === $attribuut ) {
			return floatval( $this->data[ $attribuut ] );
		}
		return is_string( $this->data[ $attribuut ] ) ? htmlspecialchars_decode( $this->data[ $attribuut ] ) : $this->data[ $attribuut ];
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @since 6.1.0
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde    Attribuut waarde.
	 */
	public function __set( string $attribuut, mixed $waarde ) : void {
		switch ( $attribuut ) {
			case 'klant':
				$this->data[ $attribuut ] = wp_json_encode( $waarde );
				break;
			case 'historie':
				break;
			case 'datum':
			case 'mutatie_datum':
			case 'verval_datum':
				$this->data[ $attribuut ] = date( 'Y-m-d H:i:s', $waarde );
				break;
			case 'gesloten':
			case 'credit':
				$this->data[ $attribuut ] = (int) $waarde;
				break;
			default:
				$this->data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Erase de order
	 */
	public function erase() {
		global $wpdb;
		$wpdb->delete( "{$wpdb->prefix}kleistad_orders", [ 'id' => $this->id ] );
	}

	/**
	 * Bepaal of de order nog annuleerbaar is. Order mag niet al gecrediteerd zijn.
	 */
	public function is_annuleerbaar() : bool {
		return ! boolval( $this->credit_id ) && '@' !== $this->referentie[0];
	}

	/**
	 * Bepaal of de order afboekbaar is, na de Wettelijke betaaltermijn 30 dagen.
	 */
	public function is_afboekbaar() : bool {
		return 0 < $this->get_te_betalen() && strtotime( 'today' ) > strtotime( '+30 days', $this->verval_datum );
	}

	/**
	 * Controleer of er een terugstorting actief is. In dat geval moeten er geen bankbetalingen gedaan worden.
	 */
	public function is_terugstorting_actief() : bool {
		if ( $this->transactie_id ) {
			$betalen = new Betalen();
			return $betalen->terugstorting_actief( $this->transactie_id );
		}
		return false;
	}

	/**
	 * Maak het factuurnr van de order als het nog niet bestaat.
	 *
	 * @return string Het factuur nummer.
	 */
	public function get_factuurnummer() : string {
		return sprintf( '%s-%06d', date( 'Y', $this->datum ), $this->factuurnr );
	}

	/**
	 * Te betalen bedrag, kan eventueel aangepast worden zoals bijvoorbeeld voor de inschrijfkosten van de cursus.
	 *
	 * @return float Het te betalen bedrag.
	 */
	public function get_te_betalen() : float {
		if ( $this->gesloten ) {
			return 0;
		}
		if ( $this->credit ) {
			$origineel_order = new Order( $this->origineel_id );
			return round( $origineel_order->orderregels->get_bruto() + $this->orderregels->get_bruto() - $this->betaald, 2 );
		}
		return round( $this->orderregels->get_bruto() - $this->betaald, 2 );
	}

	/**
	 * Bewaar de order in de database.
	 *
	 * @param string $reden De mutatie reden.
	 *
	 * @return int De order id.
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @global object $wpdb WordPress database.
	 */
	public function save( string $reden = '' ) : int {
		global $wpdb;
		if ( ! empty( $reden ) ) {
			$historie               = $this->historie;
			$historie[]             = sprintf( '%s %s', strftime( '%x %H:%M' ), $reden );
			$this->data['historie'] = wp_json_encode( $historie );
		}
		$this->gesloten = $this->credit_id || ( 0.01 >= abs( $this->get_te_betalen() ) );
		$this->regels   = $this->orderregels->get_json_export();
		$wpdb->query( 'START TRANSACTION READ WRITE' );
		if ( $this->id ) {
			$this->mutatie_datum = time();
			$wpdb->update( "{$wpdb->prefix}kleistad_orders", $this->data, [ 'id' => $this->id ] );
		} else {
			$this->factuurnr = 1 + intval( $wpdb->get_var( "SELECT MAX(factuurnr) FROM {$wpdb->prefix}kleistad_orders" ) );
			$wpdb->insert( "{$wpdb->prefix}kleistad_orders", $this->data );
			$this->id = $wpdb->insert_id;
		}
		$wpdb->query( 'COMMIT' );
		return $this->id;
	}

	/**
	 * Een bestelling aanmaken.
	 *
	 * @param float  $bedrag        Het betaalde bedrag.
	 * @param string $opmerking     De optionele opmerking in de factuur.
	 * @param string $transactie_id De betalings id.
	 *
	 * @return string De url van de factuur.
	 */
	public function bestel( float $bedrag = 0.0, string $opmerking = '', string $transactie_id = '' ): string {
		if ( $this->id ) {
			fout( __CLASS__, "Bestelling met bestaand id $this->id" );
			return '';
		}
		$artikelregister     = new Artikelregister();
		$artikel             = $artikelregister->get_object( $this->referentie );
		$orderregels         = $artikel->get_factuurregels();
		$this->betaald      += $bedrag;
		$this->klant_id      = $artikel->klant_id;
		$this->klant         = $artikel->get_naw_klant();
		$this->opmerking     = $opmerking;
		$this->transactie_id = $transactie_id;
		$this->verval_datum  = $orderregels->verval_datum;
		foreach ( $orderregels as $orderregel ) {
			$this->orderregels->toevoegen( $orderregel );
		}
		$this->save( sprintf( 'Order en factuur aangemaakt, nieuwe status betaald is € %01.2f', $this->betaald ) );
		do_action( 'kleistad_betaalinfo_update', $this->klant_id );
		$factuur = new Factuur();
		return $factuur->run( $this );
	}

	/**
	 * Een bestelling annuleren. Bij een annulering wordt altijd een credit nota gestuurd.
	 *
	 * @param float  $restant   Het te betalen bedrag bij annulering.
	 * @param string $opmerking De opmerkingstekst in de factuur.
	 *
	 * @return bool|string De url van de creditfactuur of false indien annulering niet mogelijk.
	 */
	public function annuleer( float $restant, string $opmerking = '' ): bool|string {
		if ( $this->credit ) {
			return false; // Een credit order is niet te crediteren.
		}
		$credit_order    = $this->crediteer( $opmerking, false, $restant );
		$this->credit_id = $credit_order->id;
		$this->save( 'Geannuleerd, credit factuur aangemaakt' );
		do_action( 'kleistad_order_annulering', $this->referentie );
		do_action( 'kleistad_order_stornering', $credit_order );
		do_action( 'kleistad_betaalinfo_update', $this->klant_id );
		$factuur = new Factuur();
		return $factuur->run( $credit_order );
	}

	/**
	 * Voer een korting uit op de order.
	 *
	 * @param float  $korting   De korting in euro.
	 * @param string $opmerking De opmerking die op de factuur moet komen.
	 *
	 * @return string
	 */
	public function korting( float $korting = 0.0, string $opmerking = '' ) : string {
		$nieuwe_order           = clone $this;
		$nieuwe_order->gesloten = false;
		// Als er een nieuwe korting gegeven wordt, deze toevoegen aan de set.
		$nieuwe_order->orderregels->toevoegen( new Orderregel( Orderregel::KORTING, 1, - $korting ) );
		// Crediteer nu de volledige order, omdat die gesloten wordt bevat deze geen stornering.
		$credit_order    = $this->crediteer( 'creditering ivm wijziging order', true );
		$this->credit_id = $credit_order->id;
		// En sluit de huidige order. De betaling is overgeboekt naar de nieuwe order.
		$this->betaald  = 0.0;
		$this->gesloten = true; // En de order wordt gesloten omdat deze vervangen wordt.
		$this->save( 'gecrediteerd i.v.m. wijziging' );
		$factuur = new Factuur();
		$factuur->run( $credit_order ); // Wordt niet standaard verstuurd.
		$nieuwe_order->save( sprintf( "Deze factuur vervangt %s\n%s", $this->get_factuurnummer(), $opmerking ) );
		do_action( 'kleistad_order_stornering', $nieuwe_order ); // Er zou stornering nodig kunnen zijn.
		do_action( 'kleistad_betaalinfo_update', $this->klant_id );
		$factuur = new Factuur();
		return $factuur->run( $nieuwe_order );
	}

	/**
	 * Een bestelling wijzigen. In dit geval wordt de complete factuur gecrediteerd en wordt een nieuwe factuur verstuurd, het eerder betaalde bedrag wordt op de
	 * nieuwe order geboekt. Als het te veel is dan wordt dit automatisch gestorneerd.
	 * Als er al wel betaald is, dan wordt dit overgenomen op de nieuwe factuur.
	 *
	 * @param string $referentie De (nieuwe) verwijzing naar het gewijzigde artikel.
	 * @param string $opmerking  De optionele opmerking in de factuur.
	 *
	 * @return string De url van de factuur of leeg als er geen verschil is.
	 */
	public function wijzig( string $referentie, string $opmerking = '' ) : string {
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->get_object( $referentie );
		if (
			$artikel->get_naw_klant() !== $this->klant ||
			$artikel->get_referentie() !== $this->referentie ||
			$artikel->get_factuurregels()->get_bruto() !== $this->orderregels->get_bruto()
		) {
			// Een nieuwe order wordt aangemaakt, eventuele bestaande kortingen en de betaald status worden overgenomen.
			$nieuwe_order             = clone $this;
			$nieuwe_order->gesloten   = false;
			$nieuwe_order->referentie = $artikel->get_referentie();
			$nieuwe_order->orderregels->reset();
			// Crediteer nu de volledige order, omdat die gesloten wordt bevat deze geen stornering.
			$credit_order    = $this->crediteer( 'creditering ivm wijziging order', true );
			$this->credit_id = $credit_order->id;
			// En sluit de huidige order. De betaling is overgeboekt naar de nieuwe order.
			$this->betaald  = 0.0;
			$this->gesloten = true; // En de order wordt gesloten omdat deze vervangen wordt.
			$this->save( 'gecrediteerd i.v.m. wijziging' );
			do_action( 'kleistad_order_stornering', $nieuwe_order ); // Het zou kunnen zijn dat er a.g.v. de wijziging stornering nodig is.
			do_action( 'kleistad_betaalinfo_update', $this->klant_id );
			$factuur = new Factuur();
			$factuur->run( $credit_order ); // Wordt niet standaard verstuurd.
			return $nieuwe_order->bestel( 0.0, sprintf( "Deze factuur vervangt %s\n%s", $this->get_factuurnummer(), $opmerking ), $this->transactie_id );
		}
		return '';
	}

	/**
	 * Een bestelling betalen.
	 *
	 * @param float  $bedrag        Het betaalde bedrag.
	 * @param string $transactie_id De betalings id.
	 * @return string Pad naar de factuur.
	 */
	public function ontvang( float $bedrag, string $transactie_id ): string {
		$this->betaald      += $bedrag;
		$this->transactie_id = $transactie_id;
		$this->save( sprintf( '%s bedrag € %01.2f nieuwe status betaald is € %01.2f', 0 <= $bedrag ? 'Betaling' : 'Stornering', abs( $bedrag ), $this->betaald ) );
		do_action( 'kleistad_betaalinfo_update', $this->klant_id );
		return $this->get_factuur();
	}

	/**
	 * Afboeken van een order.
	 */
	public function afboeken() {
		$te_betalen            = $this->get_te_betalen();
		$afboek_order          = new Order( '@-' . $this->referentie );
		$afboek_order->betaald = $te_betalen;
		$afboek_order->klant   = $this->klant;
		$afboek_order->orderregels->toevoegen( new Orderregel( 'Afboeking', 1, $te_betalen ) );
		$afboek_order->save( sprintf( 'Afboeking order door %s', wp_get_current_user()->display_name ) );
		$this->betaald += $te_betalen;
		$this->save( 'Afboeking' );
	}

	/**
	 * Maak opnieuw de factuur aan
	 *
	 * @param bool $url Of de url dan wel het pad moet worden teruggegeven.
	 * @return string
	 */
	public function get_factuur( bool $url = false ) : string {
		$factuur = new Factuur();
		if ( $url ) {
			return $factuur->get( $this )['url'];
		}
		return $factuur->get( $this )['locatie'];
	}

	/**
	 * Hulp functie om de huidige order ter crediteren.
	 *
	 * @param string $reden    De reden.
	 * @param bool   $gesloten Of de order meteen gesloten moet worden. Dat betekent ook dat er geen betaling meer plaatsvindt.
	 * @param float  $restant  Eventuele kosten.
	 *
	 * @return Order
	 */
	private function crediteer( string $reden, bool $gesloten, float $restant = 0.0 ) : Order {
		$credit_order               = clone $this;
		$credit_order->verval_datum = strtotime( 'tomorrow' );
		$credit_order->opmerking    = 'creditering vanwege vervanging factuur';
		$credit_order->orderregels  = new Orderregels();
		$credit_order->credit       = true;
		$credit_order->gesloten     = $gesloten;
		if ( $gesloten ) {
			$credit_order->betaald = 0.0;
		}
		foreach ( $this->orderregels as $orderregel ) {
			$credit_order->orderregels->toevoegen( new Orderregel( "annulering $orderregel->artikel", - $orderregel->aantal, $orderregel->prijs, $orderregel->btw ) );
		}
		if ( 0.0 < $restant ) {
			$credit_order->orderregels->toevoegen( new Orderregel( 'kosten i.v.m. annulering', 1, $restant ) );
		}
		$credit_order->save( $reden );
		return $credit_order;
	}

}
