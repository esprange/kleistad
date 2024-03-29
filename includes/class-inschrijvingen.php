<?php
/**
 * De definitie van de inschrijvingen class.
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
 * Kleistad inschrijvingen class.
 *
 * @since 6.11.0
 */
class Inschrijvingen implements Countable, Iterator {

	/**
	 * De inschrijvingen
	 *
	 * @var array $inschrijvingen De inschrijvingen.
	 */
	private array $inschrijvingen = [];

	/**
	 * Interne index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param int|null $select_cursus_id De cursus id.
	 * @param bool     $actief           Geef alleen actieve inschrijvingen (d.w.z. niet geannuleerd).
	 *
	 * @suppressWarnings(PHPMD.BooleanArgumentFlag)
	 */
	public function __construct( int $select_cursus_id = null, bool $actief = false ) {
		global $wpdb;
		$where = [];
		if ( $actief ) {
			$where[] = 'geannuleerd = 0';
		}
		if ( ! is_null( $select_cursus_id ) ) {
			$where[] = "cursus_id = $select_cursus_id";
		}
		$query = "SELECT * FROM {$wpdb->prefix}kleistad_inschrijvingen";
		if ( count( $where ) ) {
			$query .= ' WHERE ' . implode( ' AND ', $where );
		}
		$data = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore
		if ( is_array( $data ) ) {
			foreach ( $data as $row ) {
				$this->inschrijvingen[] = new Inschrijving( $row['cursus_id'], $row['cursist_id'], $row );
			}
		}
	}

	/**
	 * Geef het aantal inschrijvingen terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->inschrijvingen );
	}

	/**
	 * Geef de huidige inschrijving terug.
	 *
	 * @return inschrijving De inschrijving.
	 */
	public function current(): Inschrijving {
		return $this->inschrijvingen[ $this->current_index ];
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
		return isset( $this->inschrijvingen[ $this->current_index ] );
	}

	/**
	 * Verwerk de inschrijving van een cursist die wacht op een plek.
	 *
	 * @param Inschrijving $inschrijving De inschrijving van de cursist.
	 * @return bool De verwerking is uitgevoerd.
	 */
	private static function wachtlijst_verwerking( Inschrijving $inschrijving ) : bool {
		if ( ! $inschrijving->ingedeeld && $inschrijving->is_op_wachtlijst() ) {
			if ( ! $inschrijving->cursus->is_lopend() && $inschrijving->wacht_datum < $inschrijving->cursus->ruimte_datum ) {
				$inschrijving->actie->plaatsbeschikbaar();
			}
			return true;
		}
		return false;
	}

	/**
	 * Verwerk de inschrijving van een cursist waar de restant betaling voor nodig is.
	 *
	 * @param Inschrijving $inschrijving De inschrijving van de cursist.
	 */
	private static function restant_verwerking( Inschrijving $inschrijving ) {
		if ( $inschrijving->ingedeeld && $inschrijving->cursus->is_binnenkort() && ! $inschrijving->restant_email ) {
			$inschrijving->actie->restant_betaling();
		}
	}

	/**
	 * Controleer of er betalingsverzoeken verzonden moeten worden.
	 *
	 * @since 6.1.0
	 */
	public static function doe_dagelijks() {
		$vandaag = strtotime( 'today' );
		foreach ( new self() as $inschrijving ) {
			/**
			 * Geen acties voor medecursisten, oude of vervallen cursus deelnemers of die zelf geannuleerd hebben.
			 */
			if ( 0 === $inschrijving->aantal ||
				$inschrijving->geannuleerd ||
				$inschrijving->cursus->vervallen ||
				$vandaag > $inschrijving->cursus->eind_datum
			) {
				continue;
			}
			/**
			 * Wachtlijst emails, voor cursisten die nog niet ingedeeld zijn en alleen als de cursus nog niet gestart is.
			 * Laatste wachtdatum is de datum er ruimte is ontstaan of 0 als er geen ruimte is. Als gisteren de ruimte ontstond is de datum dus nu.
			 * Iedereen die vooraf 'nu' wacht krijgt de email en die wacht op vervolg als er iets vrijkomt na morgen 0:00.
			 */
			if ( self::wachtlijst_verwerking( $inschrijving ) ) {
				continue;
			}
			/**
			 * Restant betaal emails, alleen voor cursisten die ingedeeld zijn en de cursus binnenkort start.
			 */
			self::restant_verwerking( $inschrijving );
		}
	}

}
