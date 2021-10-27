<?php
/**
 * De definitie van de cursussen class.
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
 * Kleistad Cursussen class.
 *
 * @since 6.11.0
 */
class Cursussen implements Countable, Iterator {

	/**
	 * De cursussen
	 *
	 * @var array $cursussen De cursussen.
	 */
	private array $cursussen = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param bool $actief Toon alleen toekomstige en actieve cursussen.
	 * @suppressWarnings(PHPMD.BooleanArgumentFlag)
	 */
	public function __construct( bool $actief = false ) {
		global $wpdb;
		$vandaag = date( 'Y-m-d' );
		$where   = $actief ? "WHERE eind_datum >= '$vandaag'" : '';
		$data    = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_cursussen $where", ARRAY_A ); // phpcs:ignore
		foreach ( $data as $row ) {
			$this->cursussen[] = new Cursus( $row['id'], $row );
		}
	}

	/**
	 * Geef het aantal cursussen terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->cursussen );
	}

	/**
	 * Geef de huidige cursus terug.
	 *
	 * @return Cursus De cursus.
	 */
	public function current(): Cursus {
		return $this->cursussen[ $this->current_index ];
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
		return isset( $this->cursussen[ $this->current_index ] );
	}

	/**
	 * Actualiseer de cursus vol status van de nieuw of lopende cursussen.
	 */
	public static function doe_dagelijks() {
		$vandaag = strtotime( 'today' );
		foreach ( new self() as $cursus ) {
			if ( $vandaag > $cursus->eind_datum ) {
				continue;
			}
			/**
			 * Als de cursus nog niet voltooid is en er nu ruimte is, pas dan de status aan.
			 * Dit is ook nodig voor cursussen die al gestart zijn.
			 */
			if ( 0 === $cursus->ruimte() ) {
				/**
				 * Er is geen ruimte. Dus doe de acties die bij een volle cursus horen.
				 */
				$cursus->registreer_vol();
			} elseif ( $cursus->vol ) {
				/**
				 * Er is nu ruimte beschikbaar gekomen.
				 */
				$cursus->registreer_ruimte();
			}
		}
	}
}
