<?php
/**
 * De definitie van de workshopaanvragen class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.17.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Countable;
use Iterator;

/**
 * Kleistad Workshopaanvragen class.
 *
 * @since 6.17.0
 */
class WorkshopAanvragen implements Countable, Iterator {

	/**
	 * De aanvragen
	 *
	 * @var array $aanvragen De aanvragen.
	 */
	private array $aanvragen = [];

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
		$posts = get_posts(
			[
				'post_type'      => WorkshopAanvraag::POST_TYPE,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			]
		);
		foreach ( $posts as $post ) {
			$this->aanvragen[] = new WorkshopAanvraag( $post );
		}
	}

	/**
	 * Voeg een workshopaanvraag toe.
	 *
	 * @param WorkshopAanvraag $aanvraagtoetevoegen Toe te voegen workshop.
	 */
	public function toevoegen( WorkshopAanvraag $aanvraagtoetevoegen ) {
		$aanvraagtoetevoegen->save();
		$this->aanvragen[] = $aanvraagtoetevoegen;
	}

	/**
	 * Geef het aantal workshopaanvragen terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->aanvragen );
	}

	/**
	 * Geef de huidige workshopaanvraag terug.
	 *
	 * @return WorkshopAanvraag De workshopaanvraag.
	 */
	public function current(): WorkshopAanvraag {
		return $this->aanvragen[ $this->current_index ];
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
		return isset( $this->aanvragen[ $this->current_index ] );
	}

}
