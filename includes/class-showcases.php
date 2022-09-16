<?php
/**
 * De definitie van de showcases class.
 *
 * @link       https://www.kleistad.nl
 * @since      7.6.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Countable;
use Iterator;

/**
 * Kleistad Showcases class.
 *
 * @since 7.3.5
 */
class Showcases implements Countable, Iterator {

	/**
	 * De showcases
	 *
	 * @var array $showcases De showcases.
	 */
	private array $showcases = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param array $query Eventueel aanvullende query parameters.
	 */
	public function __construct( array $query = [] ) {
		$query = array_merge(
			[
				'post_type'   => Showcase::POST_TYPE,
				'numberposts' => '-1',
				'orderby'     => 'date',
			],
			$query
		);
		$posts = get_posts( $query );
		foreach ( $posts as $post ) {
			$this->showcases[] = new Showcase( $post->ID, $post );
		}
	}

	/**
	 * Geef het aantal showcases terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->showcases );
	}

	/**
	 * Geef het huidige showcase terug.
	 *
	 * @return Showcase Het showcase.
	 */
	public function current(): Showcase {
		return $this->showcases[ $this->current_index ];
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
		$this->current_index ++;
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
		return isset( $this->showcases[ $this->current_index ] );
	}

	/**
	 * Sorteer op verkoop datum, descending.
	 *
	 * @return void
	 */
	public function sort_by_verkoop_datum() : void {
		usort(
			$this->showcases,
			function( $links, $rechts ) {
				return $rechts->verkoop_datum <=> $links->verkoop_datum;
			}
		);
	}

	/**
	 * Sorteer op aanmeld datum, descending.
	 *
	 * @return void
	 */
	public function sort_by_aanmeld_datum() : void {
		usort(
			$this->showcases,
			function( $links, $rechts ) {
				return $rechts->aanmeld_datum <=> $links->aanmeld_datum;
			}
		);
	}

	/**
	 * Controleer of er emails moeten worden verstuurd voor werkstukken die tentoongesteld gaan worden
	 */
	public static function doe_dagelijks() {
		$show_datums = Showcase::show_datums();
		$keramisten  = [];
		$vandaag     = strtotime( 'today' );
		foreach ( new self( [ 'post_status' => [ Showcase::BESCHIKBAAR ] ] ) as $showcase ) {
			foreach ( [ 0, 1 ] as $index ) { // Alleen de huidige show en eerstkomende zijn relevant.
				if ( in_array( $show_datums[ $index ], $showcase->shows, true ) ) {
					$alert_datum = $show_datums[ $index ]['start'] - WEEK_IN_SECONDS;
					if ( $showcase->mail_datum < $alert_datum && $vandaag >= $alert_datum ) {
						$showcase->mail_datum = $vandaag;
						$showcase->save();
						$keramisten[ $showcase->keramist_id ][] = $showcase->titel;
						break;
					}
				}
			}
		}
		foreach ( $keramisten as $keramist_id => $keramist_werkstukken ) {
			$emailer  = new Email();
			$keramist = get_userdata( $keramist_id );
			$emailer->send(
				[
					'to'         => "$keramist->display_name <$keramist->user_email>",
					'subject'    => 'Tentoonstellen werkstukken',
					'slug'       => 'showcase_tentoonstellen',
					'parameters' => [
						'voornaam'    => $keramist->first_name,
						'achternaam'  => $keramist->last_name,
						'werkstukken' => implode( '<br/>', $keramist_werkstukken ),
					],
				]
			);
		}
	}

}
