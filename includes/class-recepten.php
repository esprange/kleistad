<?php
/**
 * De definitie van de recepten class.
 *
 * @link       https://www.kleistad.nl
 * @since      7.3.5
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Countable;
use Iterator;

/**
 * Kleistad Recepten class.
 *
 * @since 7.3.5
 */
class Recepten implements Countable, Iterator {

	/**
	 * De recepten
	 *
	 * @var array $recepten De recepten.
	 */
	private array $recepten = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De recept termen
	 *
	 * @var ReceptTermen $recepttermen De termen.
	 */
	private ReceptTermen $recepttermen;

	/**
	 * De constructor
	 *
	 * @param array $query Eventueel aanvullende query parameters.
	 */
	public function __construct( array $query = [] ) {
		$query = array_merge(
			[
				'post_type'   => Recept::POST_TYPE,
				'numberposts' => '-1',
				'post_status' => [
					'publish',
					'pending',
					'private',
					'draft',
				],
				'orderby'     => 'date',
			],
			$query
		);
		$posts = get_posts( $query );
		foreach ( $posts as $post ) {
			$this->recepten[] = new Recept( $post->ID, $post );
		}
		$this->recepttermen = new ReceptTermen();
	}

	/**
	 * Geef het aantal recepten terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->recepten );
	}

	/**
	 * Geef het huidige recept terug.
	 *
	 * @return Recept Het recept.
	 */
	public function current(): Recept {
		return $this->recepten[ $this->current_index ];
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
		return isset( $this->recepten[ $this->current_index ] );
	}

	/**
	 * Geef de auteur id's terug van de verzameling recepten.
	 *
	 * @return array De id's.
	 */
	public function get_auteurs(): array {
		$auteur_ids = [];
		foreach ( $this->recepten as $recept ) {
			$auteur_ids[] = $recept->auteur_id;
		}
		$result = [];
		foreach ( array_unique( $auteur_ids ) as $auteur_id ) {
			$result[ $auteur_id ] = get_user_by( 'ID', $auteur_id )->display_name;
		};
		return $result;
	}

	/**
	 * Geef de glazuren.
	 *
	 * @return array
	 */
	public function get_glazuren(): array {
		$result = [];
		foreach ( get_terms(
			[
				'taxonomy'   => Recept::CATEGORY,
				'hide_empty' => true,
				'orderby'    => 'name',
				'object_ids' => $this->get_recept_ids(),
				'parent'     => $this->recepttermen->lijst()[ ReceptTermen::GLAZUUR ]->term_id,
			]
		) as $term ) {
			$result[ $term->term_id ] = $term->name;
		}
		return $result;
	}

	/**
	 * Geef de kleuren.
	 *
	 * @return array
	 */
	public function get_kleuren(): array {
		$result = [];
		foreach ( get_terms(
			[
				'taxonomy'   => Recept::CATEGORY,
				'hide_empty' => true,
				'orderby'    => 'name',
				'object_ids' => $this->get_recept_ids(),
				'parent'     => $this->recepttermen->lijst()[ ReceptTermen::KLEUR ]->term_id,
			]
		) as $term ) {
			$result[ $term->term_id ] = $term->name;
		}
		return $result;
	}

	/**
	 * Geef de uiterlijkheden.
	 *
	 * @return array
	 */
	public function get_uiterlijkheden(): array {
		$result = [];
		foreach ( get_terms(
			[
				'taxonomy'   => Recept::CATEGORY,
				'hide_empty' => true,
				'orderby'    => 'name',
				'object_ids' => $this->get_recept_ids(),
				'parent'     => $this->recepttermen->lijst()[ ReceptTermen::UITERLIJK ]->term_id,
			]
		) as $term ) {
			$result[ $term->term_id ] = $term->name;
		}
		return $result;
	}

	/**
	 * Geef de post id's terug van de verzameling recepten.
	 *
	 * @return array De id's.
	 */
	private function get_recept_ids(): array {
		$recept_ids = [];
		foreach ( $this->recepten as $recept ) {
			$recept_ids[] = $recept->id;
		}
		return $recept_ids;
	}

}
