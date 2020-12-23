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
	private $stoken = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param int $oven_id     Het id van de oven.
	 * @param int $vanaf_datum Vanaf datum dat de stoken gevuld moeten worden.
	 * @param int $tot_datum   Tot datum dat de stoken gevuld moeten worden.
	 */
	public function __construct( int $oven_id, int $vanaf_datum, int $tot_datum ) {
		global $wpdb;
		$oven   = new Oven( $oven_id );
		$datums = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT datum FROM {$wpdb->prefix}kleistad_reserveringen WHERE oven_id = %d AND datum BETWEEN %s AND %s",
				$oven_id,
				date( 'Y-m-d 00:00:00', $vanaf_datum ),
				date( 'Y-m-d 23:59:59', $tot_datum ),
			),
			ARRAY_A
		);
		foreach ( array_column( $datums, 'datum' ) as $datum ) {
			$this->stoken[] = new Stook( $oven_id, strtotime( $datum ) );
		}
	}

	/**
	 * Voeg een stook toe.
	 *
	 * @param Stook $stooktoetevoegen Toe te voegen stook.
	 */
	public function toevoegen( Stook $stooktoetevoegen ) {
		$stooktoetevoegen->save();
		$this->stoken[] = $stooktoetevoegen;
	}

	/**
	 * Vervang een stook.
	 *
	 * @param Stook $stookvervangen Te vervangen stook.
	 */
	public function vervangen( Stook $stookvervangen ) {
		foreach ( $this->stoken as $key => $stook ) {
			if ( $stookvervangen->datum === $stook->datum ) {
				$stookvervangen->save();
				$this->stoken[ $key ] = $stookvervangen;
			}
		}
	}

	/**
	 * Verwijder een stook.
	 *
	 * @param Stook $stookverwijderen Te vervangen stook.
	 */
	public function verwijderen( Stook $stookverwijderen ) {
		foreach ( $this->stoken as $key => $stook ) {
			if ( $stookverwijderen->datum === $stook->datum ) {
				$stookverwijderen->verwijder();
				$this->stoken[ $key ] = $stookverwijderen;
			}
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
	public function next() {
		$this->current_index++;
	}

	/**
	 * Ga terug naar het begin.
	 */
	public function rewind() {
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
}
