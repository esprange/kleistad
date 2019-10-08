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

/**
 * De kleistad recept class.
 */
class Public_Recept extends Shortcode {

	/**
	 * Prepareer 'recept' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.1.0
	 */
	protected function prepare( &$data ) {
		return true;
	}

	/**
	 * Register rest URI's.
	 *
	 * @since 4.5.3
	 */
	public static function register_rest_routes() {
		register_rest_route(
			Public_Main::api(),
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
	 * @param \WP_REST_Request $request De parameters van de Ajax call.
	 * @return \WP_REST_Response
	 */
	public static function callback_recept( \WP_REST_Request $request ) {
		$data             = [];
		$glazuur_parent   = get_term_by( 'name', '_glazuur', \Kleistad\Recept::CATEGORY );
		$kleur_parent     = get_term_by( 'name', '_kleur', \Kleistad\Recept::CATEGORY );
		$uiterlijk_parent = get_term_by( 'name', '_uiterlijk', \Kleistad\Recept::CATEGORY );

		$query = [
			'post_type'   => \Kleistad\Recept::POST_TYPE,
			'numberposts' => '-1',
			'post_status' => [
				'publish',
			],
			's'           => $request->get_param( 'zoeker' ),
			'author__in'  => $request->get_param( 'auteurs' ),
			'tax_query'   => [
				[
					'taxonomy' => \Kleistad\Recept::CATEGORY,
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
		$recepten = get_posts( $query );

		$object_ids     = wp_list_pluck( $recepten, 'ID' );
		$auteur_ids     = array_unique( wp_list_pluck( $recepten, 'post_author' ) );
		$auteurs        = get_users(
			[
				'include' => $auteur_ids,
				'fields'  => [
					'display_name',
					'ID',
				],
			]
		);
		$data['auteur'] = wp_list_pluck( $auteurs, 'display_name', 'ID' );

		$data['recepten'] = [];
		foreach ( $recepten as $recept ) {
			$content            = json_decode( $recept->post_content, true );
			$data['recepten'][] = [
				'id'    => $recept->ID,
				'titel' => $recept->post_title,
				'foto'  => $content['foto'],
			];
		}

		$data['glazuur']   = get_terms(
			[
				'taxonomy'   => \Kleistad\Recept::CATEGORY,
				'hide_empty' => true,
				'orderby'    => 'name',
				'object_ids' => $object_ids,
				'parent'     => $glazuur_parent->term_id,
				'fields'     => 'id=>name',
			]
		);
		$data['kleur']     = get_terms(
			[
				'taxonomy'   => \Kleistad\Recept::CATEGORY,
				'hide_empty' => true,
				'orderby'    => 'name',
				'object_ids' => $object_ids,
				'parent'     => $kleur_parent->term_id,
				'fields'     => 'id=>name',
			]
		);
		$data['uiterlijk'] = get_terms(
			[
				'taxonomy'   => \Kleistad\Recept::CATEGORY,
				'hide_empty' => true,
				'orderby'    => 'name',
				'object_ids' => $object_ids,
				'parent'     => $uiterlijk_parent->term_id,
				'fields'     => 'id=>name',
			]
		);

		ob_start();
		require plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/public-show-recept.php';
		return new \WP_REST_Response(
			[
				'content' => ob_get_clean(),
				'terms'   => $request->get_param( 'terms' ),
				'auteurs' => $request->get_param( 'auteurs' ),
			]
		);

	}
}
