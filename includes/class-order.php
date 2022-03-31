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
	 * Het factuur object.
	 *
	 * @var Factuur $factuur De factuur.
	 */
	private Factuur $factuur;

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
			'verval_datum'  => date( 'Y-m-d 00:00:0', strtotime( '+30 days 00:00' ) ),
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
		$this->factuur     = new Factuur();
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
	 * @param int    $verval_datum  De datum waarop de factuur vervalt.
	 * @param string $opmerking     De optionele opmerking in de factuur.
	 * @param string $transactie_id De betalings id.
	 * @param bool   $factuur_nodig Of er een factuur aangemaakt moet worden.
	 *
	 * @return string De url van de factuur.
	 * @suppressWarnings(PHPMD.BooleanArgumentFlag)
	 */
	public function bestel( float $bedrag, int $verval_datum, string $opmerking = '', string $transactie_id = '', bool $factuur_nodig = true ): string {
		if ( $this->credit ) {
			return ''; // Credit orders worden niet hergebruikt.
		}
		$artikelregister     = new Artikelregister();
		$artikel             = $artikelregister->get_object( $this->referentie );
		$this->betaald      += $bedrag; // Als er al eerder op de order betaald is, het bedrag toevoegen.
		$this->klant_id      = $artikel->klant_id;
		$this->klant         = $artikel->get_naw_klant();
		$this->opmerking     = $opmerking;
		$this->transactie_id = $transactie_id ?? $this->transactie_id; // Overschrijf het transactie_id alleen als er een ideal betaling is gedaan.
		$this->verval_datum  = $verval_datum;
		$this->orderregels   = $artikel->get_factuurregels();
		$this->save( $factuur_nodig ? sprintf( 'Order en factuur aangemaakt, nieuwe status betaald is € %01.2f', $bedrag ) : 'Order aangemaakt' );

		do_action( 'kleistad_betaalinfo_update', $this->klant_id );
		return $factuur_nodig ? $this->factuur->run( $this ) : '';
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
		if ( $this->credit_id ) {
			return false; // Een credit order is niet te crediteren.
		}
		$credit_order    = $this->crediteer( $opmerking, false, $restant );
		$this->credit_id = $credit_order->id;
		$this->save( 'Geannuleerd, credit factuur aangemaakt' );

		do_action( 'kleistad_order_annulering', $this->referentie );
		do_action( 'kleistad_betaalinfo_update', $this->klant_id );
		return $this->factuur->run( $credit_order );
	}

	/**
	 * Een bestelling wijzigen ivm korting.
	 *
	 * @param float  $korting   De te geven korting.
	 * @param string $opmerking De opmerking in de factuur.
	 *
	 * @return string De url van de factuur of fout.
	 */
	public function korting( float $korting, string $opmerking = '' ): string {
		$credit_order            = $this->crediteer( 'creditering ivm korting op order', true );
		$nieuwe_order            = clone $this;
		$nieuwe_order->gesloten  = false;
		$nieuwe_order->opmerking = $opmerking;
		$nieuwe_order->orderregels->toevoegen( new Orderregel( Orderregel::KORTING, 1, - $korting ) );
		$nieuwe_order->save( sprintf( 'Correctie factuur i.v.m. korting € %01.2f', $korting ) );
		$this->factuur->run( $credit_order ); // Wordt niet verstuurd.
		$this->credit_id = $credit_order->id;
		$this->save( 'gecrediteerd i.v.m. korting' );

		do_action( 'kleistad_order_stornering', $this->referentie );
		do_action( 'kleistad_betaalinfo_update', $nieuwe_order->klant_id );
		return $this->factuur->run( $nieuwe_order );
	}

	/**
	 * Een bestelling wijzigen.
	 *
	 * @param string $referentie De (nieuwe) verwijzing naar het gewijzigde artikel.
	 * @param string $opmerking  De optionele opmerking in de factuur.
	 *
	 * @return string De url van de factuur of false.
	 * @noinspection PhpNonStrictObjectEqualityInspection
	 */
	public function wijzig( string $referentie, string $opmerking = '' ): string {
		$artikelregister = new Artikelregister();
		$artikel         = $artikelregister->get_object( $referentie );
		if ( $artikel->get_referentie() === $this->referentie &&
			$artikel->get_naw_klant() === $this->klant &&
			$artikel->get_factuurregels() == $this->orderregels ) { // phpcs:ignore
			return '';
		}
		$credit_order              = $this->crediteer( 'creditering ivm wijziging order', true );
		$nieuwe_order              = clone $this;
		$nieuwe_order->referentie  = $referentie;
		$nieuwe_order->orderregels = $artikel->get_factuurregels();
		$nieuwe_order->klant       = $artikel->get_naw_klant();
		$nieuwe_order->opmerking   = $opmerking;
		$nieuwe_order->gesloten    = false;
		$nieuwe_order->save( 'Correctie factuur i.v.m. wijziging artikel' );
		$this->factuur->run( $credit_order ); // Wordt niet verstuurd.
		$this->credit_id = $credit_order->id;
		$this->save( 'gecrediteerd i.v.m. wijziging' );

		do_action( 'kleistad_order_stornering', $this->referentie );
		do_action( 'kleistad_betaalinfo_update', $this->klant_id );
		return $this->factuur->run( $nieuwe_order );
	}

	/**
	 * Een bestelling betalen.
	 *
	 * @param float  $bedrag        Het betaalde bedrag.
	 * @param string $transactie_id De betalings id.
	 * @param bool   $factuur_nodig Of er wel / niet een factuur aangemaakt moet worden.
	 * @return string Pad naar de factuur of leeg.
	 * @suppressWarnings(PHPMD.BooleanArgumentFlag)
	 */
	public function ontvang( float $bedrag, string $transactie_id, bool $factuur_nodig = false ): string {
		$this->betaald      += $bedrag;
		$this->transactie_id = $transactie_id;
		$this->save( sprintf( '%s bedrag € %01.2f nieuwe status betaald is € %01.2f', 0 <= $bedrag ? 'Betaling' : 'Stornering', abs( $bedrag ), $this->betaald ) );
		do_action( 'kleistad_betaalinfo_update', $this->klant_id );
		return ( $factuur_nodig ) ? $this->factuur->run( $this ) : '';
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
	 * @return string
	 */
	public function herzenden() : string {
		return $this->factuur->run( $this );
	}

	/**
	 * Hulp functie om de huidige order ter crediteren.
	 *
	 * @param string $reden    De reden.
	 * @param bool   $gesloten Of de order meteen gesloten moet worden.
	 * @param float  $restant  Eventuele kosten.
	 *
	 * @return Order
	 */
	private function crediteer( string $reden, bool $gesloten, float $restant = 0.0 ) : Order {
		$credit_order               = clone $this;
		$credit_order->verval_datum = strtotime( 'tomorrow' );
		$credit_order->opmerking    = 'creditering vanwege vervanging factuur';
		$credit_order->orderregels  = new Orderregels();
		$credit_order->gesloten     = $gesloten;
		$credit_order->credit       = true;
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
