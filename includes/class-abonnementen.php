<?php
/**
 * De definitie van de abonnementen class.
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
 * Kleistad Abonnementen class.
 *
 * @since 6.11.0
 */
class Abonnementen implements Countable, Iterator {

	/**
	 * De abonnementen
	 *
	 * @var array $abonnementen De abonnementen.
	 */
	private array $abonnementen = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 */
	public function __construct() {
		$abonnees = get_users(
			[
				'fields'       => [ 'ID' ],
				'meta_key'     => Abonnement::META_KEY,
				'meta_compare' => '!==',
				'meta_value'   => '',
			]
		);
		foreach ( $abonnees as $abonnee ) {
			$this->abonnementen[] = new Abonnement( $abonnee->ID );
		}
	}

	/**
	 * Geef het aantal abonnementen terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->abonnementen );
	}

	/**
	 * Geef de huidige abonnement terug.
	 *
	 * @return Abonnement De abonnement.
	 */
	public function current(): Abonnement {
		return $this->abonnementen[ $this->current_index ];
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
		return isset( $this->abonnementen[ $this->current_index ] );
	}

	/**
	 * Dagelijkse job
	 */
	public static function doe_dagelijks() {
		$vandaag = strtotime( 'today' );
		foreach ( new self() as $abonnement ) {
			if ( $vandaag < $abonnement->start_datum ) {
				// Gestopte abonnementen en abonnementen die nog moeten starten hebben geen actie nodig.
				continue;
			}
			if ( $abonnement->is_geannuleerd() ) {
				// Abonnementen waarvan de einddatum verstreken is worden gestopt.
				$abonnement->actie->autoriseer( false );
				$abonnement->save();
				continue;
			}

			// Abonnementen waarvan de starttermijn over 1 week verstrijkt krijgen de overbrugging email en factuur, mits er nog geen einddatum ingevuld is.
			if ( $vandaag < $abonnement->reguliere_datum ) {
				if ( $vandaag >= strtotime( '-7 days', $abonnement->start_eind_datum ) && ! $abonnement->eind_datum && ! $abonnement->overbrugging_email ) {
					$abonnement->actie->overbrugging();
				}
				continue; // Meer actie is niet nodig. Abonnee zit nog in startperiode of overbrugging.
			}
			// Hierna wordt er niets meer aan het abonnement aangepast, nu nog factureren indien nodig.
			$abonnement->actie->factureer();
		}
	}

}
