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
	 *
	 * @param int $datum Toon alleen workshops vanaf deze datum.
	 */
	public function __construct( int $datum = 0 ) {
		$posts = get_posts(
			[
				'post_type'      => WorkshopAanvraag::POST_TYPE,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'date_query'     => [
					[
						'column' => 'post_date',
						'after'  => date( 'Y-m-d', $datum ),
					],
				],
			]
		);
		foreach ( $posts as $post ) {
			$this->aanvragen[] = new WorkshopAanvraag( $post );
		}
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
		return isset( $this->aanvragen[ $this->current_index ] );
	}

}
