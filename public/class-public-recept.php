<?php
/**
 * Shortcode recept (weergave).
 *
 * @link       https://www.kleistad.nl
 * @since      4.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_REST_Request;
use WP_REST_Response;

/**
 * De kleistad recept class.
 */
class Public_Recept extends Shortcode {

	/**
	 * Prepareer 'recept' form
	 *
	 * @since   4.1.0
	 *
	 * @return string
	 */
	protected function prepare() : string {
		return $this->content();
	}

	/**
	 * Register rest URI's.
	 *
	 * @since 4.5.3
	 */
	public static function register_rest_routes() : void{
		register_rest_route(
			KLEISTAD_API,
			'/recept',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'callback_recept' ],
				'permission_callback' => function() {
						return true;
				},
			]
		);
	}

	/**
	 * Ajax callback voor show recept functie.
	 *
	 * @param WP_REST_Request $request De parameters van de Ajax call.
	 * @return WP_REST_Response
	 */
	public static function callback_recept( WP_REST_Request $request ) : WP_REST_Response {
		$data  = [];
		$query = [
			'post_status' => [
				'publish',
			],
			's'           => $request->get_param( 'zoeker' ),
			'author__in'  => $request->get_param( 'auteurs' ),
			'tax_query'   => [
				[
					'taxonomy' => Recept::CATEGORY,
					'field'    => 'id',
					'terms'    => $request->get_param( 'terms' ),
					'operator' => 'AND',
				],
			],
		];
		switch ( $request->get_param( 'sorteer' ) ) {
			case 'nieuwste':
				$query['orderby'] = 'date';
				$query['order']   = 'DESC';
				break;
			case 'waardering':
				$query['orderby']    = 'meta_key';
				$query['order']      = 'DESC';
				$query['meta_query'] = [
					'relation' => 'OR',
					[
						'key'     => 'ratings_average',
						'compare' => 'EXISTS',
					],
					[
						'key'     => 'ratings_average',
						'value'   => '',
						'compare' => 'NOT EXISTS',
					],
				];
				break;
			case 'titel':
			default:
				$query['orderby'] = 'title';
				$query['order']   = 'ASC';
				break;
		}
		$data['recepten']  = new Recepten( $query );
		$data['auteur']    = $data['recepten']->get_auteurs();
		$data['glazuur']   = $data['recepten']->get_glazuren();
		$data['kleur']     = $data['recepten']->get_kleuren();
		$data['uiterlijk'] = $data['recepten']->get_uiterlijkheden();
		return new WP_REST_Response(
			[
				'content' => self::render( $data ),
				'terms'   => $request->get_param( 'terms' ),
				'auteurs' => $request->get_param( 'auteurs' ),
			]
		);

	}

	/**
	 * Truncate een tekstregel tot gewenste woordlengte
	 *
	 * @param string $string    Tekstregel.
	 * @return string
	 */
	private static function truncate_string( string $string ) : string {
		if ( strlen( $string ) > 25 ) {
			$string = wordwrap( $string, 25 );
			$string = substr( $string, 0, strpos( $string, "\n" ) );
		}
		return $string;
	}

	/**
	 * Toont filter opties voor term met naam
	 *
	 * @param string $titel     De h3 titel.
	 * @param string $naam      Naam van de filtergroep.
	 * @param array  $termen    Array van termen.
	 * @return string           Html tekst.
	 */
	private static function filter( string $titel, string $naam, array $termen ) : string {
		$html  = '';
		$count = count( $termen );
		$toon  = 4;
		if ( 0 < $count ) {
			$html .= "<h3>$titel</h3><ul>";
			$index = 0;
			foreach ( $termen as $id => $term ) {
				$index++;
				$style = ( $toon < $index ) ? 'display:none;' : '';
				$html .= '<li class="kleistad-filter-term" style="' . $style . '">';
				$html .= '<label><input type="checkbox" name="' . $naam . '" class="kleistad-filter" value="' . $id . '" style="display:none;" >';
				$html .= esc_html( self::truncate_string( $term ) ); // Max. 30 karakters.
				$html .= '<span style="visibility:hidden;float:right">&#9932;</span></label></li>';
				if ( ( $toon === $index ) && ( $index !== $count ) ) {
					$html .= '<li class="kleistad-filter-term">';
					$html .= '<label><input type="checkbox" name="' . $naam . '" class="kleistad-meer" value="meer" style="display:none;" >+ meer ... </label></li>';
				}
			}
			if ( $toon < $index ) {
				$html .= '<li class="kleistad-filter-term" style="display:none;" >';
				$html .= '<label><input type="checkbox" name="' . $naam . '" class="kleistad-meer" value="minder" style="display:none;" >- minder ... </label></li>';
			}
			$html .= '</ul>';
		}
		return $html;
	}

	/**
	 * Render de pagina
	 *
	 * @param array $data De recept data.
	 * @return string HTML tekst.
	 */
	private static function render( array $data ) : string {
		$count = count( $data['recepten'] );
		if ( 0 === $count ) {
			return '<br/>' . melding( -1, 'er zijn geen recepten gevonden, pas het filter aan.' );
		}
		$html  = '<div id="kleistad_filters" class="kleistad-filters" >';
		$html .= self::filter( 'Type glazuur', 'term', $data['glazuur'] );
		$html .= self::filter( 'Uiterlijk', 'term', $data['uiterlijk'] );
		$html .= self::filter( 'Kleur', 'term', $data['kleur'] );
		$html .= self::filter( 'Auteur', 'auteur', $data['auteur'] );
		$html .= '</div><div id="kleistad_recept_overzicht">';
		$index = 0;
		foreach ( $data['recepten'] as $recept ) {
			if ( ++$index > 24 ) {
				break;
			}
			$permalink = get_post_permalink( $recept->id );
			if ( is_string( $permalink ) ) {
				$html .= '<div style="width:250px;float:left;padding:15px;border:0;"><a href="' . $permalink . '" >' .
						'<div class="kleistad-recept-img" style="/* noinspection CssUnknownTarget */ background-image:url(' . "'$recept->foto'" . ');" >' .
						'</div><div class="kleistad-recept-titel" >';
				$html .= self::truncate_string( $recept->titel );
				$html .= '</div></a></div>';
			}
		}
		$html .= '</div>';
		if ( $count > $index ) {
			$html .= '<br/>' . melding( -1, 'er zijn meer recepten dan er nu getoond worden, pas het filter aan.' );
		}
		return $html;
	}

}
