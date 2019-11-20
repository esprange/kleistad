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
 * @property string referentie
 * @property array  regels
 * @property string opmerking
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
					]
				),
				'mutatie_datum' => null,
				'referentie'    => '',
				'regels'        => wp_json_encode( [] ),
				'opmerking'     => '',
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
			case 'klant':
			case 'historie':
				return json_decode( $this->data[ $attribuut ], true );
			case 'datum':
			case 'mutatie_datum':
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
	 * Maak het factuurnr van de order als het nog niet bestaat.
	 *
	 * @return string Het factuur nummer.
	 */
	public function factuurnr() {
		return sprintf( '%s-%06d', date( 'Y', $this->datum ), $this->id );
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
			return $this->bruto() - $this->betaald;
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
		// Als er al een id is, dan betreft het een mutatie.
		if ( $this->id ) {
			$this->mutatie_datum = time();
		}
		if ( $this->origineel_id ) {
			$origineel_order = new \Kleistad\Order( $this->origineel_id );
			$openstaand      = $origineel_order->bruto() + $this->bruto() - $this->betaald;
		} else {
			$openstaand = $this->bruto() - $this->betaald;
		}
		$this->gesloten = $this->gesloten || ( 0.01 >= abs( $openstaand ) );
		$wpdb->replace( "{$wpdb->prefix}kleistad_orders", $this->data );
		$this->id = $wpdb->insert_id;
		return $this->id;
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
	 * @param bool $zoek Toon alleen orders die voldoen aan de zoekterm, anders toon alleen openstaande ordes.
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
