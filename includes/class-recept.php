<?php
/**
 * De definitie van de recept class
 *
 * @link       https://www.kleistad.nl
 * @since      5.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Recept class.
 */
class Recept {

	const POST_TYPE = 'kleistad_recept';
	const CATEGORY  = 'kleistad_recept_cat';
	const KLEUR     = '_kleur';
	const GRONDSTOF = '_grondstof';
	const UITERLIJK = '_uiterlijk';
	const GLAZUUR   = '_glazuur';

	/**
	 * De hoofdterm objecten.
	 *
	 * @var array De objecten.
	 */
	private static array $hoofdtermen;

	/**
	 * Initialiseer de recepten als custom post type.
	 */
	public static function create_type() {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'            => [
					'name'               => 'Keramiek recepten',
					'singular_name'      => 'Keramiek recept',
					'add_new'            => 'Toevoegen',
					'add_new_item'       => 'Recept toevoegen',
					'edit'               => 'Wijzigen',
					'edit_item'          => 'Recept wijzigen',
					'view'               => 'Inzien',
					'view_item'          => 'Recept inzien',
					'search_items'       => 'Recept zoeken',
					'not_found'          => 'Niet gevonden',
					'not_found_in_trash' => 'Niet in prullenbak gevonden',
				],
				'public'            => true,
				'supports'          => [
					'title',
					'comments',
					'thumbnail',
				],
				'rewrite'           => [
					'slug' => 'recepten',
				],
				'show_ui'           => false,
				'show_in_admin_bar' => false,
				'show_in_nav_menus' => false,
			]
		);
		register_taxonomy(
			self::CATEGORY,
			self::POST_TYPE,
			[
				'hierarchical'      => true,
				'labels'            => [
					'name'          => 'Recept categoriën',
					'singular_name' => 'Recept categorie',
					'search_items'  => 'Zoek recept categorie',
					'all_items'     => 'Alle recept categoriën',
					'edit_item'     => 'Wijzig recept categorie',
					'update_item'   => 'Sla recept categorie op',
					'add_new_item'  => 'Voeg recept categorie toe',
					'new_item_name' => 'Nieuwe recept recept categorie',
					'menu_name'     => 'Recept categoriën',
				],
				'query_var'         => true,
				'show_ui'           => true,
				'show_admin_column' => true,
			]
		);
		register_taxonomy_for_object_type( self::CATEGORY, self::POST_TYPE );

		add_filter(
			'wp_dropdown_cats',
			/**
			 * Voegt 'required' toe aan dropdown list.
			 *
			 * @param string $output Door wp_dropdown_categories aangemaakte select list.
			 * @param array  $arr
			 * @return string
			 */
			function( string $output, array $arr ) : string {
				if ( self::CATEGORY === $arr['taxonomy'] ) {
					return preg_replace( '^' . preg_quote( '<select ' ) . '^', '<select required ', $output ); // phpcs:ignore
				}
				return $output;
			},
			10,
			2
		);
		if ( empty( self::$hoofdtermen ) ) {
			foreach ( [ self::GRONDSTOF, self::KLEUR, self::UITERLIJK, self::GLAZUUR ] as $hoofdterm_naam ) {
				$term = get_term_by( 'name', $hoofdterm_naam, self::CATEGORY );
				if ( false === $term ) {
					$result = wp_insert_term( $hoofdterm_naam, self::CATEGORY );
					if ( is_array( $result ) ) {
						self::$hoofdtermen[ $hoofdterm_naam ] = get_term( $result['term_id'] );
					}
					return;
				}
				self::$hoofdtermen[ $hoofdterm_naam ] = $term;
			}
		}
	}

	/**
	 * Geef de hoofdtermen terug, maakt deze eventueel aan als ze nog niet bestaan.
	 *
	 * @return array De hoofdterm objecten.
	 */
	public static function hoofdtermen(): array {
		return self::$hoofdtermen;
	}
}
