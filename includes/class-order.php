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
 * @since 6.1.0
 */
class Order {

	/**
	 * Order id
	 *
	 * @var int $id Id van de order.
	 */
	public int $id = 0;

	/**
	 * Betaald bedrag
	 *
	 * @var float $betaald Bedrag dat al betaald is.
	 */
	public float $betaald = 0.0;

	/**
	 * Order datum
	 *
	 * @var int $datum Unix timestamp van aanleggen order.
	 */
	public int $datum;

	/**
	 * Verwijzing naar credit order.
	 *
	 * @var int $credit_id Id van de credit order, indien gecrediteerd.
	 */
	public int $credit_id = 0;

	/**
	 * Verwijzing naar originele order
	 *
	 * @var int $origineel_id Id van de originele order ingeval van credit.
	 */
	public int $origineel_id = 0;

	/**
	 * Gesloten vlag
	 *
	 * @var bool $gesloten True als de order gesloten is.
	 */
	public bool $gesloten = false;

	/**
	 * Credit vlag
	 *
	 * @var bool $credit True als het een creditering betreft.
	 */
	public bool $credit = false;

	/**
	 * Order historie.
	 *
	 * @var array $historie Order historie.
	 */
	public array $historie = [];

	/**
	 * Klant gegevens voor op de factuur.
	 *
	 * @var array $klant NAW gegevens.
	 */
	public array $klant = [
		'naam'  => '',
		'adres' => '',
		'email' => '',
	];

	/**
	 * Het eventuele klant nummer.
	 *
	 * @var int $klant_id WP user id.
	 */
	public int $klant_id = 0;

	/**
	 * De order mutatie datum
	 *
	 * @var int $mutatie_datum Unix order mutatiedatum/tijd.
	 */
	public int $mutatie_datum = 0;

	/**
	 * De order verval datum
	 *
	 * @var int $verval_datum Unix order vervaldatum/tijd.
	 */
	public int $verval_datum = 0;

	/**
	 * De order referentie
	 *
	 * @var string $referentie De order referentie.
	 */
	public string $referentie;

	/**
	 * De order regels
	 *
	 * @var Orderregels $regels De orderregel objecten.
	 */
	public Orderregels $orderregels;

	/**
	 * Eventuele opmerking op factuur.
	 *
	 * @var string $opmerking Opmerking
	 */
	public string $opmerking = '';

	/**
	 * Het factuur nummer.
	 *
	 * @var int $factuurnr factuur nummer.
	 */
	public int $factuurnr = 0;

	/**
	 * Het Mollie transactie id.
	 *
	 * @var string $transactie_id transactie id.
	 */
	public string $transactie_id = '';

	/**
	 * Maak het object aan.
	 *
	 * @param int|string $arg  Het order id of de referentie.
	 * @param array|null $load (optioneel) data waarmee het object geladen kan worden (ivm performance).
	 */
	public function __construct( int|string $arg, ?array $load = null ) {
		global $wpdb;
		$this->datum        = strtotime( 'now' );
		$this->verval_datum = strtotime( '+ 14 days 00:00' );
		$this->orderregels  = new Orderregels();
		$resultaat          = null;
		if ( ! is_null( $load ) ) {
			$resultaat = $load;
		} elseif ( is_numeric( $arg ) ) {
			$resultaat = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_orders WHERE id = %d", intval( $arg ) ), ARRAY_A );
		} elseif ( $arg ) {
			$this->referentie = $arg;
			$resultaat        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_orders WHERE referentie = %s OR transactie_id = %s ORDER BY id DESC LIMIT 1", $arg, $arg ), ARRAY_A ) ?? 0;
		}
		if ( ! empty( $resultaat ) ) {
			$timezone            = get_option( 'timezone_string' ) ?: 'Europe/Amsterdam';
			$this->id            = intval( $resultaat['id'] );
			$this->betaald       = floatval( $resultaat['betaald'] );
			$this->datum         = strtotime( $resultaat['datum'] . " $timezone" );
			$this->credit_id     = intval( $resultaat['credit_id'] );
			$this->origineel_id  = intval( $resultaat['origineel_id'] );
			$this->credit        = boolval( $resultaat['credit'] );
			$this->gesloten      = boolval( $resultaat['gesloten'] );
			$this->historie      = json_decode( $resultaat['historie'], true );
			$this->klant         = json_decode( $resultaat['klant'], true );
			$this->klant_id      = intval( $resultaat['klant_id'] );
			$this->mutatie_datum = strtotime( $resultaat['mutatie_datum'] . " $timezone" );
			$this->verval_datum  = strtotime( $resultaat['verval_datum'] . " $timezone" );
			$this->referentie    = $resultaat['referentie'];
			$this->opmerking     = htmlspecialchars_decode( $resultaat['opmerking'] );
			$this->factuurnr     = intval( $resultaat['factuurnr'] );
			$this->transactie_id = $resultaat['transactie_id'];
			foreach ( json_decode( $resultaat['regels'], true ) as $regel ) {
				$this->orderregels->toevoegen( new Orderregel( $regel['artikel'], floatval( $regel['aantal'] ), floatval( $regel['prijs'] ), floatval( $regel['btw'] ) ) );
			}
		}
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
	 * Erase de order
	 */
	public function erase() : void {
		global $wpdb;
		$wpdb->delete( "{$wpdb->prefix}kleistad_orders", [ 'id' => $this->id ] );
	}

	/**
	 * Bepaal of de order nog annuleerbaar is. Order mag niet al gecrediteerd zijn.
	 */
	public function is_annuleerbaar() : bool {
		return ! $this->credit_id && '@' !== $this->referentie[0];
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
		$wpdb->query( 'START TRANSACTION READ WRITE' );

		if ( ! empty( $reden ) ) {
			// Lokale tijd, dus wp_date.
			$this->historie[] = sprintf( '%s %s', wp_date( 'd-m-Y H:i' ), $reden );
		}
		$this->gesloten      = $this->credit_id || ( 0.01 >= abs( $this->get_te_betalen() ) );
		$this->factuurnr     = $this->factuurnr ?: 1 + intval( $wpdb->get_var( "SELECT MAX(factuurnr) FROM {$wpdb->prefix}kleistad_orders" ) );
		$this->mutatie_datum = $this->id ? time() : 0;
		$orderdata           = [
			'id'            => $this->id,
			'betaald'       => $this->betaald,
			'datum'         => wp_date( 'Y-m-d H:i:s', $this->datum ),
			'credit_id'     => $this->credit_id,
			'origineel_id'  => $this->origineel_id,
			'gesloten'      => intval( $this->gesloten ),
			'credit'        => intval( $this->credit ),
			'historie'      => wp_json_encode( $this->historie ),
			'klant'         => wp_json_encode( $this->klant ),
			'klant_id'      => $this->klant_id,
			'mutatie_datum' => wp_date( 'Y-m-d H:i:s', $this->mutatie_datum ),
			'verval_datum'  => wp_date( 'Y-m-d H:i:s', $this->verval_datum ),
			'referentie'    => $this->referentie,
			'regels'        => $this->orderregels->get_json_export(),
			'opmerking'     => $this->opmerking,
			'factuurnr'     => $this->factuurnr,
			'transactie_id' => $this->transactie_id,
		];
		if ( $this->id ) {
			$wpdb->update( "{$wpdb->prefix}kleistad_orders", $orderdata, [ 'id' => $this->id ] );
		} else {
			$wpdb->insert( "{$wpdb->prefix}kleistad_orders", $orderdata );
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
		$this->verval_datum  = $artikel->get_verval_datum();
		foreach ( $orderregels as $orderregel ) {
			$this->orderregels->toevoegen( $orderregel );
		}
		$this->save( sprintf( 'Order en factuur aangemaakt, nieuwe status betaald is € %01.2f', $this->betaald ) );
		do_action( 'kleistad_order_stornering', $this );
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
		$nieuwe_order->save( sprintf( "Deze factuur vervangt %s vanwege korting\n%s", $this->get_factuurnummer(), $opmerking ) );
		do_action( 'kleistad_order_stornering', $nieuwe_order ); // Er zou stornering nodig kunnen zijn.
		do_action( 'kleistad_betaalinfo_update', $this->klant_id );
		$factuur = new Factuur();
		return $factuur->run( $nieuwe_order );
	}

	/**
	 * Boek een bedrag terug
	 *
	 * @param float  $bedrag     Het bedrag.
	 * @param float  $kosten     De kosten die in rekening worden gebracht.
	 * @param string $vermelding De vermelding van het terug te storten bedrag.
	 * @param string $opmerking  Een eventuele opmerking op de factuur.
	 *
	 * @return string
	 */
	public function terugboeken( float $bedrag, float $kosten, string $vermelding, string $opmerking = '' ) : string {
		$nieuwe_order              = clone $this;
		$nieuwe_order->gesloten    = false;
		$nieuwe_order->orderregels = new Orderregels();
		$nieuwe_order->orderregels->toevoegen( new Orderregel( $vermelding, 1, - $bedrag ) );
		$nieuwe_order->orderregels->toevoegen( new Orderregel( 'administratie kosten', 1, $kosten ) );
		$nieuwe_order->betaald = 0.0;
		$nieuwe_order->save( sprintf( "Terugboeken bedrag\n%s", $opmerking ) );
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
			$betaald         = $this->betaald;
			$this->betaald   = 0.0;
			$credit_order    = $this->crediteer( 'creditering ivm wijziging order', false );
			$this->credit_id = $credit_order->id;
			$this->save( 'Geannuleerd, credit factuur aangemaakt' );
			do_action( 'kleistad_order_stornering', $credit_order );
			do_action( 'kleistad_betaalinfo_update', $this->klant_id );
			$nieuwe_order             = new Order( '' );  // Forceer een nieuwe order.
			$nieuwe_order->referentie = $artikel->get_referentie();
			return $nieuwe_order->bestel( $betaald, sprintf( "Deze factuur vervangt %s\n%s", $this->get_factuurnummer(), $opmerking ), $this->transactie_id );
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
	public function afboeken() : void {
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
