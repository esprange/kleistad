<?php
/**
 * De definitie van de workshops class.
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
 * Kleistad Workshops class.
 *
 * @since 6.11.0
 */
class Workshops implements Countable, Iterator {

	/**
	 * De workshops
	 *
	 * @var array $workshops De workshops.
	 */
	private array $workshops = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param int $datum Toon alleen workshops vanaf deze datum.
	 */
	public function __construct( int $datum = 0 ) {
		global $wpdb;
		$filter = date( 'Y-m-d', $datum );
		$data   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_workshops WHERE datum >= %s", $filter ), ARRAY_A );
		foreach ( $data as $row ) {
			$this->workshops[] = new Workshop( $row['id'], $row );
		}
	}

	/**
	 * Geef het aantal workshops terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->workshops );
	}

	/**
	 * Geef de huidige workshop terug.
	 *
	 * @return Workshop De workshop.
	 */
	public function current(): Workshop {
		return $this->workshops[ $this->current_index ];
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
		return isset( $this->workshops[ $this->current_index ] );
	}

	/**
	 * Voor de dagelijkse acties mbt workshops uit.
	 *
	 * @since 6.1.0
	 */
	public static function doe_dagelijks() {
		foreach ( new self() as $workshop ) {
			if ( $workshop->vervallen ) {
				continue;
			}
			self::doe_concept_vervallen( $workshop );
			self::doe_vraag_betaling( $workshop );
		}
	}

	/**
	 * Laat de workshop vervallen als deze al langer dan x weken in concept staat.
	 *
	 * @param Workshop $workshop De workshop.
	 *
	 * @return void
	 */
	private static function doe_concept_vervallen( Workshop $workshop ) : void {
		if ( $workshop->definitief || $workshop->vervallen ) {
			return;
		}
		$workshop->vervallen = $workshop->aanvraagdatum + opties()['verloopaanvraag'] * WEEK_IN_SECONDS < strtotime( 'today' );
		$workshop->save();
	}

	/**
	 * Verzend de email met factuur als deze komende week plaatsvindt.
	 *
	 * @param Workshop $workshop De workshop.
	 *
	 * @return void
	 */
	private static function doe_vraag_betaling( Workshop $workshop ) : void {
		if (
			! $workshop->definitief ||
			$workshop->betaling_email ||
			strtotime( '+7 days 00:00' ) < $workshop->datum ||
			$workshop->is_betaald()
		) {
			return;
		}
		$workshop->actie->vraag_betaling();
	}
}
