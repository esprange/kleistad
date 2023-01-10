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
use WP_REST_Response;

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
	 * Get the previous showcase in a loop without changing the current index.
	 *
	 * @return Showcase
	 */
	public function get_prev() : Showcase {
		$index = 0 > $this->current_index - 1 ? $this->count() - 1 : $this->current_index - 1;
		return $this->showcases[ $index ];
	}

	/**
	 * Get the next showcase in a loop without changing the current index.
	 *
	 * @return Showcase
	 */
	public function get_next() : Showcase {
		$index = $this->current_index + 1 === $this->count() ? 0 : $this->current_index + 1;
		return $this->showcases[ $index ];
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
		$show_datums = ( new Shows() )->get_datums();
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

	/**
	 * Register rest URI's.
	 *
	 * @since 6.20.3
	 */
	public static function register_rest_routes() : void {
		register_rest_route(
			KLEISTAD_API,
			'/showcases',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_showcases' ],
				'permission_callback' => function() {
					return true;
				},
			]
		);
	}

	/**
	 * Ajax callback voor showcase galerie functie.
	 *
	 * @return WP_REST_Response
	 */
	public static function callback_showcases() : WP_REST_Response {
		$showcase_data = [];
		$keramist_data = [];
		$showcases     = new self(
			[
				'post_status' => [ Showcase::BESCHIKBAAR ],
				'orderby'     => 'rand',
			]
		);
		foreach ( $showcases as $showcase ) {
			if ( $showcase->foto_id ) {
				$showcase_data[] = [
					'id'           => $showcase->id,
					'titel'        => $showcase->titel,
					'beschrijving' => $showcase->beschrijving,
					'foto_small'   => wp_get_attachment_image_url( $showcase->foto_id ),
					'foto_large'   => wp_get_attachment_image_url( $showcase->foto_id, 'large' ),
					'prijs'        => number_format_i18n( $showcase->prijs, 2 ),
					'status'       => $showcase->is_tentoongesteld() ? ' ( nu tentoongesteld )' : '',
					'link'         => get_permalink( $showcase->id ),
					'keramist_id'  => $showcase->keramist_id,
				];
				if ( ! in_array( $showcase->keramist_id, array_column( $keramist_data, 'id' ), true ) ) {
					$keramist        = get_user_by( 'ID', $showcase->keramist_id );
					$keramist_data[] = [
						'id'      => $keramist->ID,
						'naam'    => $keramist->display_name,
						'bio'     => $keramist->description,
						'website' => $keramist->user_url,
						'foto'    => wp_get_attachment_image_url( get_user_meta( $keramist->ID, 'profiel_foto', true ) ) ?: '',
					];
				}
			}
		}
		return new WP_REST_Response(
			[
				'showcases'  => $showcase_data,
				'keramisten' => $keramist_data,
			]
		);
	}

}
