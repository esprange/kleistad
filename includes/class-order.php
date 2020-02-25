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
 * @property array  regels
 * @property string opmerking
 * @property int    factuurnr
 * @property string transactie_id
 *
 * @since 6.1.0
 */
class Order extends \Kleistad\Entity {

	/**
	 * Maak het object aan.
	 *
	 * @param int $order_id Het order id of 0.
	 */
	public function __construct( $order_id = 0 ) {
		global $wpdb;
		if ( $order_id ) {
			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_orders WHERE id = %d", $order_id ), ARRAY_A ); // phpcs:ignore
			if ( ! is_null( $result ) ) {
				$this->data = $result;
			}
		} else {
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
		}
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @since 6.1.0
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( $attribuut ) {
		switch ( $attribuut ) {
			case 'id':
			case 'credit_id':
			case 'origineel_id':
				return intval( $this->data[ $attribuut ] );
			case 'regels':
				$regels = [];
				foreach ( json_decode( $this->data['regels'], true ) as $regel ) {
					$regels[] = [
						'artikel' => $regel['artikel'],
						'aantal'  => floatval( $regel['aantal'] ),
						'prijs'   => floatval( $regel['prijs'] ),
						'btw'     => floatval( $regel['btw'] ),
					];
				}
				return $regels;
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
	public function __set( $attribuut, $waarde ) {
		switch ( $attribuut ) {
			case 'regels':
				$regels = [];
				foreach ( $waarde as $regel ) {
					$regels[] = [
						'artikel' => $regel['artikel'],
						'aantal'  => number_format( $regel['aantal'], 2, '.', '' ),
						'prijs'   => number_format( $regel['prijs'], 2, '.', '' ),
						'btw'     => number_format( $regel['btw'], 2, '.', '' ),
					];
				}
				$this->data[ $attribuut ] = wp_json_encode( $regels );
				break;
			case 'klant':
				$this->data[ $attribuut ] = wp_json_encode( $waarde );
				break;
			case 'historie':
				$nu = new \DateTime();
				$nu->setTimezone( new \DateTimeZone( get_option( 'timezone_string' ) ?: 'Europe/Amsterdam' ) );
				$historie                 = json_decode( $this->data[ $attribuut ], true );
				$historie[]               = $nu->format( 'd-m-Y H:i' ) . ": $waarde";
				$this->data[ $attribuut ] = wp_json_encode( $historie );
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
		$dd_order->historie   = 'Afboeking order door ' . wp_get_current_user()->display_name;
		$dd_order->referentie = '@-' . $this->referentie;
		$dd_order->betaald    = $te_betalen;
		$dd_order->klant      = $this->klant;
		$dd_order->regels     = [
			array_merge(
				\Kleistad\Artikel::split_bedrag( $te_betalen ),
				[
					'artikel' => 'Afboeking',
					'aantal'  => '1',
				]
			),
		];
		$dd_order->save();
		$this->betaald += $te_betalen;
		$this->historie = 'Afboeking';
		$this->save();
	}

	/**
	 * Bepaal of de order nog gecorrigeerd mag worden.
	 */
	public function geblokkeerd() {
		$blokkade = self::get_blokkade();
		return ( $this->datum < $blokkade || $this->credit_id );
	}

	/**
	 * Bepaal het totaal bedrag van de order.
	 *
	 * @return float
	 */
	public function bruto() {
		return $this->netto() + $this->btw();
	}

	/**
	 * Bepaal het totaal bedrag exclusief BTW van de order.
	 *
	 * @return float
	 */
	public function netto() {
		$netto = 0.0;
		foreach ( $this->regels as $regel ) {
			$netto += ( $regel['prijs'] ) * $regel['aantal'];
		}
		return $netto;
	}

	/**
	 * Bepaal het totaal bedrag aan BTW van de order.
	 *
	 * @return float
	 */
	public function btw() {
		$btw = 0.0;
		foreach ( $this->regels as $regel ) {
			$btw += ( $regel['btw'] ) * $regel['aantal'];
		}
		return $btw;
	}

	/**
	 * Controleer of er een terugstorting actief is. In dat geval moeten er geen bankbetalingen gedaan worden.
	 */
	public function terugstorting_actief() {
		$betalen = new \Kleistad\Betalen();
		if ( $this->transactie_id ) {
			return $betalen->terugstorting_actief( $this->transactie_id );
		}
		return false;
	}

	/**
	 * Maak het factuurnr van de order als het nog niet bestaat.
	 *
	 * @return string Het factuur nummer.
	 */
	public function factuurnr() {
		return sprintf( '%s-%06d', date( 'Y', $this->datum ), $this->factuurnr );
	}

	/**
	 * Te betalen bedrag, kan eventueel aangepast worden zoals bijvoorbeeld voor de inschrijfkosten van de cursus.
	 *
	 * @return float
	 */
	public function te_betalen() {
		if ( $this->origineel_id ) {
			$origineel_order = new \Kleistad\Order( $this->origineel_id );
			return $origineel_order->bruto() + $this->bruto() - $this->betaald;
		} else {
			return $this->credit_id ? 0.0 : $this->bruto() - $this->betaald;
		}
	}

	/**
	 * Bewaar de order in de database.
	 *
	 * @since 6.1.0
	 *
	 * @global object $wpdb     WordPress database.
	 * @return int De order id.
	 */
	public function save() {
		global $wpdb;
		if ( $this->origineel_id ) {
			$origineel_order = new \Kleistad\Order( $this->origineel_id );
			$openstaand      = $origineel_order->bruto() + $this->bruto() - $this->betaald;
		} elseif ( $this->credit_id ) {
			$openstaand = 0;
		} else {
			$openstaand = $this->bruto() - $this->betaald;
		}
		$this->gesloten = 0.01 >= abs( $openstaand );
		if ( ! $this->id ) {
			$wpdb->query( "INSERT INTO {$wpdb->prefix}kleistad_orders ( factuurnr ) VALUES ( 1 + ( SELECT MAX(factuurnr) FROM {$wpdb->prefix}kleistad_orders AS O2 ) ) " );
			$this->id        = $wpdb->insert_id;
			$this->factuurnr = intval(
				$wpdb->get_var(
					$wpdb->prepare( "SELECT factuurnr FROM {$wpdb->prefix}kleistad_orders WHERE id = %d", $this->id )
				)
			);
		} else {
			$this->mutatie_datum = time();
		}
		$wpdb->replace( "{$wpdb->prefix}kleistad_orders", $this->data );

		if ( $this->transactie_id && -0.01 > $openstaand ) {
			// Er staat een negatief bedrag open. Dat kan worden terugbetaald.
			$betalen = new \Kleistad\Betalen();
			$result  = $betalen->terugstorting( $this->transactie_id, $this->referentie, - $openstaand, 'terugstorting conform factuur ' . $this->factuurnr() );
			add_filter(
				'kleistad_melding',
				function( $html ) use ( $result ) {
					return $html . \Kleistad\Shortcode::melding(
						$result ? 1 : -1,
						$result ? 'er is opdracht gegeven om het terug te betalen bedrag over te maken' :
						'de opdracht om het bedrag terug te storten is geweigerd. Probeer het per bank over te maken'
					);
				}
			);
		}
		return $this->id;
	}

	/**
	 * Zet de blokkade datum.
	 *
	 * @param int $datum De datum in unix time.
	 */
	public static function zet_blokkade( $datum ) {
		update_option( 'kleistad_blokkade', $datum );
	}

	/**
	 * Get de blokkade datum.
	 *
	 * @return int $datum De datum in unix time.
	 */
	public static function get_blokkade() {
		return (int) get_option( 'kleistad_blokkade', strtotime( '1-1-2020' ) );
	}

	/**
	 * Zoek de meest recente bestelling o.b.v. de referentie (dus een credit factuur wordt dan eerder gevonden dan de gewone factuur).
	 *
	 * @since 6.1.0
	 *
	 * @param  string $referentie De referentie.
	 * @return int Het id van de bestelling of 0.
	 */
	public static function zoek_order( $referentie ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}kleistad_orders WHERE referentie = %s ORDER BY id DESC LIMIT 1", $referentie ) ) ?? 0;
	}

	/**
	 * Return alle orders.
	 *
	 * @param string $zoek Toon alleen orders die voldoen aan de zoekterm, anders toon alleen openstaande ordes.
	 * @return array orders.
	 */
	public static function all( $zoek = '' ) {
		global $wpdb;
		$arr    = [];
		$zoek   = strtolower( $zoek );
		$where  = empty( $zoek ) ? 'WHERE gesloten = 0' : "WHERE lower( concat ( klant, ' ', referentie ) ) LIKE '%$zoek%'";
		$orders = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_orders $where", ARRAY_A ); // phpcs:ignore
		foreach ( $orders as $order ) {
			$arr[ $order['id'] ] = new \Kleistad\Order();
			$arr[ $order['id'] ]->load( $order );
		}
		return $arr;
	}
}
