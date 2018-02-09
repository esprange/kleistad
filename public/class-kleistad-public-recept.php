<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_Recept extends Kleistad_Shortcode {

	/**
	 * Prepareer 'recept' form
	 *
	 * @param array $data data to be prepared.
	 * @return array
	 *
	 * @since   4.1.0
	 */
	public function prepare( &$data = null ) {
		return true;
	}

	/**
	 * Valideer/sanitize 'recept' form
	 *
	 * @param array $data Returned data.
	 * @return array
	 *
	 * @since   4.1.0
	 */
	public function validate( &$data ) {
		return true;
	}

	/**
	 *
	 * Bewaar 'recept' form gegevens
	 *
	 * @param array $data data to be saved.
	 * @return string
	 *
	 * @since   4.1.0
	 */
	public function save( $data ) {
		return true;
	}

	/**
	 * Ajax callback voor show recept functie.
	 *
	 * @param WP_REST_Request $request De parameters van de Ajax call.
	 * @return \WP_REST_response
	 */
	public static function callback_recept( WP_REST_Request $request ) {
		$glazuur_parent = get_term_by( 'name', '_glazuur', 'kleistad_recept_cat' );
		$kleur_parent = get_term_by( 'name', '_kleur', 'kleistad_recept_cat' );
		$uiterlijk_parent = get_term_by( 'name', '_uiterlijk', 'kleistad_recept_cat' );

		$zoek = $request->get_param( 'zoek' );

		/*
		 * Eerste stap, we passen de filters toe om te bepalen in welke groep recepten we gaan zoeken.
		 */
		$query_1 = [
			'post_type' => 'kleistad_recept',
			'numberposts' => '-1',
			'post_status' => [
				'publish',
			],
		];

		if ( isset( $zoek['terms'] ) ) {
			$query_1['tax_query']  = [
				[
					'taxonomy' => 'kleistad_recept_cat',
					'field' => 'id',
					'terms' => $zoek['terms'],
					'operator' => 'AND',
				],
			];
		}
		$recepten = get_posts( $query_1 );
		$object_ids = wp_list_pluck( $recepten, 'ID' );

		/*
		 * Tweede stap, we kijken of er een zoekterm is ingevoerd, en zo ja, doorzoek de meta velden.
		 */
		if ( '' !== $zoek['zoeker'] ) {
			/*
			 * Stap 2a, kijk of de term in de titel voorkomt
			 */
			$query_2 = [
				's' => $zoek['zoeker'],
				'post_type' => 'kleistad_recept',
				'numberposts' => '-1',
				'post__in' => $object_ids,
			];
			$recepten_2 = get_posts( $query_2 );

			/*
			 * Stap 2b, kijk of de term in de inhoud voorkomt
			 */
			$query_3 = [
				'post_type' => 'kleistad_recept',
				'numberposts' => '-1',
				'post__in' => $object_ids,
				'meta_query' => [
						[
							'key' => '_kleistad_recept',
							'value' => $zoek['zoeker'],
							'compare' => 'LIKE',
						],
					]
				];
			$recepten_3 = get_posts( $query_3 );
			$recepten = array_merge( $recepten_2, $recepten_3 );
			$object_ids = wp_list_pluck( $recepten, 'ID' );
		}

		$data['recepten'] = [];
		foreach ( $recepten as $recept ) {
			$meta = get_post_meta( $recept->ID, '_kleistad_recept', true );
			$data['recepten'][] = [
				'id' => $recept->ID,
				'titel' => $recept->post_title,
				'foto' => $meta['foto'],
			];
		}

		$data['glazuur'] = get_terms(
			[
				'taxonomy' => 'kleistad_recept_cat',
				'hide_empty' => true,
				'orderby'    => 'name',
				'object_ids' => $object_ids,
				'parent'     => $glazuur_parent->term_id,
			]
		);
		$data['kleur'] = get_terms(
			[
				'taxonomy' => 'kleistad_recept_cat',
				'hide_empty' => true,
				'orderby'    => 'name',
				'object_ids' => $object_ids,
				'parent'     => $kleur_parent->term_id,
			]
		);
		$data['uiterlijk'] = get_terms(
			[
				'taxonomy' => 'kleistad_recept_cat',
				'hide_empty' => true,
				'orderby'    => 'name',
				'object_ids' => $object_ids,
				'parent'     => $uiterlijk_parent->term_id,
			]
		);

		ob_start();
		require plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/kleistad-public-show-recept.php';
		$html = ob_get_contents();
		ob_clean();

		return new WP_REST_response(
			[
				'html' => $html,
				'zoek' => $zoek,
			]
		);

	}
}
