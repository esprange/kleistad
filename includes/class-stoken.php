<?php
/**
 * De definitie van de stoken class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Countable;
use Iterator;
use Exception;

/**
 * Kleistad Stoken class.
 *
 * @since 6.11.0
 */
class Stoken implements Countable, Iterator {

	/**
	 * De stoken
	 *
	 * @var array $stoken De stoken.
	 */
	private array $stoken = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param Oven $oven        De oven.
	 * @param int  $vanaf_datum Vanaf datum dat de stoken gevuld moeten worden.
	 * @param int  $tot_datum   Tot datum dat de stoken gevuld moeten worden.
	 */
	public function __construct( Oven $oven, int $vanaf_datum, int $tot_datum = 0 ) {
		global $wpdb;
		$data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}kleistad_reserveringen WHERE oven_id = %d AND datum BETWEEN %s AND %s",
				$oven->id,
				date( 'Y-m-d 00:00:00', $vanaf_datum ),
				date( 'Y-m-d 23:59:59', $tot_datum ?: strtotime( '+1 year' ) ),
			),
			ARRAY_A
		);
		foreach ( $data as $row ) {
			$this->stoken[] = new Stook( $oven, strtotime( $row['datum'] ), $row );
		}
	}

	/**
	 * Geef het aantal stoken terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->stoken );
	}

	/**
	 * Geef de huidige stook terug.
	 *
	 * @return Stook De stook.
	 */
	public function current(): Stook {
		return $this->stoken[ $this->current_index ];
	}

	/**
	 * Geef de sleutel terug.
	 *
	 * @return int De sleutel.
	 */
	public function key(): int {
		return $this->current_index;
	}

	/**
	 * Ga naar de volgende in de lijst.
	 */
	public function next(): void {
		$this->current_index++;
	}

	/**
	 * Ga terug naar het begin.
	 */
	public function rewind(): void {
		$this->current_index = 0;
	}

	/**
	 * Bepaal of het element bestaat.
	 *
	 * @return bool Of het bestaat of niet.
	 */
	public function valid(): bool {
		return isset( $this->stoken[ $this->current_index ] );
	}

	/**
	 * Verwerk de stook. Afhankelijk van de status melden dat er een afboeking gaat plaats vinden of de werkelijke afboeking uitvoeren.
	 *
	 * @throws Exception Als het saldo of de reservering niet opgeslagen kan worden.
	 */
	public static function doe_dagelijks() {
		$ovens         = new Ovens();
		$verwerk_datum = strtotime( '- ' . opties()['termijn'] . ' days 00:00' );
		foreach ( $ovens as $oven ) {
			$stoken = new Stoken( $oven, strtotime( '- 1 week' ), strtotime( 'today' ) );
			foreach ( $stoken as $stook ) {
				if ( ! $stook->is_gereserveerd() ) {
					continue;
				}
				if ( ! $stook->verwerkt && $stook->datum <= $verwerk_datum ) {
					$stook->verwerk();
					continue;
				}
				if ( ! $stook->gemeld && $stook->datum < strtotime( 'today' ) ) {
					$stook->meld();
				}
			}
		}
	}

}
