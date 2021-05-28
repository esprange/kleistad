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
 * @property string historie
 * @property array  klant
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
	 * @param int|string $arg  Het order id of de referentie of 0.
	 * @param array|null $load (optioneel) data waarmee het object geladen kan worden (ivm performance).
	 */
	public function __construct( $arg = 0, ?array $load = null ) {
		global $wpdb;
		$this->data = [
			'id'            => 0,
			'betaald'       => 0.0,
			'datum'         => date( 'Y-m-d H:i:s' ),
			'credit_id'     => 0,
			'origineel_id'  => 0,
			'gesloten'      => false,
			'historie'      => wp_json_encode( [] ),
			'klant'         => wp_json_encode(
				[
					'naam'  => '',
					'adres' => '',
					'email' => '',
				]
			),
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
			$resultaat = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_orders WHERE referentie = %s ORDER BY id DESC LIMIT 1", $arg ), ARRAY_A ) ?? 0;
		}
		if ( ! empty( $resultaat ) ) {
			$this->data = $resultaat;
		}
		$this->orderregels = new Orderregels( $this->data['regels'] );
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
		switch ( $attribuut ) {
			case 'id':
			case 'credit_id':
			case 'origineel_id':
				return intval( $this->data[ $attribuut ] );
			case 'klant':
			case 'historie':
				return json_decode( $this->data[ $attribuut ], true );
			case 'datum':
			case 'mutatie_datum':
			case 'verval_datum':
				return strtotime( $this->data[ $attribuut ] );
			case 'gesloten':
				return boolval( $this->data[ $attribuut ] );
			case 'betaald':
				return (float) $this->data[ $attribuut ];
			default:
				return is_string( $this->data[ $attribuut ] ) ? htmlspecialchars_decode( $this->data[ $attribuut ] ) : $this->data[ $attribuut ];
		}
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @since 6.1.0
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( string $attribuut, $waarde ) : void {
		switch ( $attribuut ) {
			case 'klant':
				$this->data[ $attribuut ] = wp_json_encode( $waarde );
				break;
			case 'historie':
				break;
			case 'datum':
			case 'mutatie_datum':
			case 'verval_datum':
				$this->data[ $attribuut ] = date( 'Y-m-d h:m:s', $waarde );
				break;
			case 'gesloten':
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
	 * Afboeken van een order.
	 */
	public function afboeken() {
		$te_betalen           = $this->te_betalen();
		$dd_order             = new self();
		$dd_order->referentie = '@-' . $this->referentie;
		$dd_order->betaald    = $te_betalen;
		$dd_order->klant      = $this->klant;
		$dd_order->orderregels->toevoegen( new Orderregel( 'Afboeking', 1, $te_betalen ) );
		$dd_order->save( sprintf( 'Afboeking order door %s', wp_get_current_user()->display_name ) );
		$this->betaald += $te_betalen;
		$this->save( 'Afboeking' );
	}

	/**
	 * Bepaal of de order nog gecorrigeerd mag worden.
	 */
	public function is_geblokkeerd() : bool {
		return $this->datum < get_blokkade() || boolval( $this->credit_id ) || '@' === substr( $this->referentie, 0, 1 );
	}

	/**
	 * Bepaal of de order nog annuleerbaar is.
	 */
	public function is_annuleerbaar() : bool {
		return ! boolval( $this->credit_id ) && '@' !== $this->referentie[0];
	}

	/**
	 * Bepaal of het een credit order is.
	 */
	public function is_credit() : bool {
		return boolval( $this->origineel_id );
	}

	/** Beppal of de order afboekbaar is, na de Wettelijke betaaltermijn 30 dagen.
	 */
	public function is_afboekbaar() : bool {
		return 0 < $this->te_betalen() && strtotime( 'today' ) > strtotime( '+30 days', $this->verval_datum );
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
	public function factuurnummer() : string {
		return sprintf( '%s-%06d', date( 'Y', $this->datum ), $this->factuurnr );
	}

	/**
	 * Te betalen bedrag, kan eventueel aangepast worden zoals bijvoorbeeld voor de inschrijfkosten van de cursus.
	 *
	 * @return float
	 */
	public function te_betalen() : float {
		if ( $this->gesloten ) {
			return 0;
		}
		if ( $this->is_credit() ) {
			$origineel_order = new Order( $this->origineel_id );
			return round( $origineel_order->orderregels->bruto() + $this->orderregels->bruto() - $this->betaald, 2 );
		}
		return round( $this->orderregels->bruto() - $this->betaald, 2 );
	}

	/**
	 * Bewaar de order in de database.
	 *
	 * @since 6.1.0
	 *
	 * @global object $wpdb     WordPress database.
	 * @param string $reden De mutatie reden.
	 * @return int De order id.
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 */
	public function save( string $reden ) : int {
		global $wpdb;
		$historie               = $this->historie;
		$historie[]             = sprintf( '%s %s', strftime( '%x %H:%M' ), $reden );
		$this->data['historie'] = wp_json_encode( $historie );
		$this->gesloten         = ( 0 < $this->credit_id ) ?: 0.01 >= abs( $this->te_betalen() );
		$this->regels           = $this->orderregels->export();
		$wpdb->query( 'START TRANSACTION READ WRITE' );
		if ( ! $this->id ) {
			$this->factuurnr = 1 + intval( $wpdb->get_var( "SELECT MAX(factuurnr) FROM {$wpdb->prefix}kleistad_orders" ) );
			$wpdb->insert( "{$wpdb->prefix}kleistad_orders", $this->data );
			$this->id = $wpdb->insert_id;
		} else {
			$this->mutatie_datum = time();
			$wpdb->update( "{$wpdb->prefix}kleistad_orders", $this->data, [ 'id' => $this->id ] );
		}
		$wpdb->query( 'COMMIT' );

		if ( $this->transactie_id && -0.01 > $this->te_betalen() ) {
			// Er staat een negatief bedrag open. Dat kan worden terugbetaald.
			$betalen = new Betalen();
			$betalen->terugstorting( $this->transactie_id, $this->referentie, - $this->te_betalen(), 'Kleistad: zie factuur ' . $this->factuurnummer() );
		}
		return $this->id;
	}
}
