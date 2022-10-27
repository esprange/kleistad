<?php
/**
 * De definitie van de werkplekken class.
 *
 * @link       https://www.kleistad.nl
 * @since      7.8.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Countable;
use Iterator;

/**
 * Kleistad Werkplekken class.
 *
 * @since 7.8.0
 */
class Werkplekken implements Countable, Iterator {

	/**
	 * De werkplekken
	 *
	 * @var array $werkplekken De werkplekken.
	 */
	private array $werkplekken = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param int $vanaf_datum Toon alleen werkplekken vanaf deze datum.
	 * @param int $tot_datum   Toon alleen werkplekken tot aan deze datum.
	 */
	public function __construct( int $vanaf_datum = 0, int $tot_datum = 0 ) {
		global $wpdb;
		$filter = $wpdb->prepare( 'WHERE datum >= %s', date( 'Y-m-d', $vanaf_datum ) );
		if ( $tot_datum ) {
			$filter .= $wpdb->prepare( ' AND datum <= %s', date( 'Y-m-d', $tot_datum ) );
		}
		$data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}kleistad_werkplekken $filter ORDER BY datum", ARRAY_A ); // phpcs:ignore
		foreach ( $data as $row ) {
			$this->werkplekken[] = new Werkplek( strtotime( $row['datum'] ), $row['gebruik'] );
		}
	}

	/**
	 * Geef het aantal werkplekken terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->werkplekken );
	}

	/**
	 * Geef de huidige werkplek terug.
	 *
	 * @return Werkplek De werkplek.
	 */
	public function current(): Werkplek {
		return $this->werkplekken[ $this->current_index ];
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
		return isset( $this->werkplekken[ $this->current_index ] );
	}

	/**
	 * Verwijder eventuele werkplek reserveringen.
	 *
	 * @param string $code  De code waarmee de reservering start.
	 * @param int    $vanaf De begin datum.
	 * @param int    $tot   De eventuele eind datum.
	 */
	public static function verwijder_werkplekken( string $code, int $vanaf, int $tot = 0 ) {
		$werkplekken = new self( $vanaf, $tot );
		foreach ( $werkplekken as $werkplek ) {
			foreach ( $werkplek->get_gebruik() as $dagdeel => $gebruik ) {
				foreach ( $gebruik as $activiteit => $posities ) {
					$nieuwe_posities = array_filter(
						$posities,
						function ( $positie ) use ( $code ) {
							return ! str_starts_with( $positie, "{$code}_" );
						}
					);
					if ( count( $posities ) !== count( $nieuwe_posities ) ) {
						$werkplek->wijzig( $dagdeel, $activiteit, $nieuwe_posities );
					}
				}
			}
		}
	}

	/**
	 * Reserveer de werkplekken
	 *
	 * @param string $code       De code waarmee de reserveringen starten.
	 * @param string $naam       De naam die zichtbaar moet worden in de reservering.
	 * @param array  $aantallen  Array met activiteit / aantal paren.
	 * @param int    $datum      De datum/tijd waarop de reservering gemaakt moet worden.
	 * @param string $dagdeel    Het dagdeel waarop de reservering gemaakt moet worden.
	 * @return string Eventueel bericht of false als er geen werkplekken gereserveerd zijn.
	 */
	public static function reserveer_werkplekken( string $code, string $naam, array $aantallen, int $datum, string $dagdeel ) : string {
		$bericht  = '';
		$totaal   = 0;
		$werkplek = new Werkplek( $datum );
		foreach ( opties()['werkruimte'] as $activiteit ) {
			$aantal = $aantallen[ $activiteit['naam'] ] ?? 0;
			if ( $aantal ) {
				$totaal       += $aantal;
				$ruimte        = $werkplek->get_ruimte( $dagdeel, $activiteit['naam'] );
				$gebruiker_ids = array_column( $werkplek->geef( $dagdeel, $activiteit['naam'] ), 'id' );
				if ( $ruimte < $aantal ) {
					$bericht = 'Niet alle werkplekken konden gereserveerd worden';
					$aantal  = $ruimte;
				}
				for ( $index = 1; $index <= $aantal; $index++ ) {
					$gebruiker_ids[] = "{$code}_{$naam}_$index";
				}
				$werkplek->wijzig( $dagdeel, $activiteit['naam'], $gebruiker_ids );
			}
		}
		if ( $totaal ) {
			return $bericht;
		}
		return 'Er zijn nog geen werkplekken gereserveerd !';
	}

}
