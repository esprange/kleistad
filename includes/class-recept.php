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

use WP_Post;

/**
 * Kleistad Recept class.
 *
 * @property int    $id
 * @property string $titel
 * @property string $status
 * @property int    $created
 * @property int    $modified
 * @property string $kenmerk
 * @property string $herkomst
 * @property array  $basis
 * @property array  $toevoeging
 * @property string $stookschema
 * @property string $foto
 * @property int    $glazuur
 * @property int    $kleur
 * @property int    $uiterlijk
 * @property string $glazuur_naam
 * @property string $kleur_naam
 * @property string $uiterlijk_naam
 */
class Recept {

	const POST_TYPE = 'kleistad_recept';
	const CATEGORY  = 'kleistad_recept_cat';

	/**
	 * De ruwe data
	 *
	 * @var array $data De recept data.
	 */
	private array $data;

	/**
	 * De auteur van het recept.
	 *
	 * @var int $auteur_id Het id van de auteur.
	 */
	public int $auteur_id;

	/**
	 * Constructor
	 *
	 * @param int|null $recept_id Het recept id.
	 * @param ?WP_Post $load      Eventueel al geladen post.
	 */
	public function __construct( ?int $recept_id = null, ? WP_Post $load = null ) {
		$this->data = [
			'id'          => $recept_id,
			'titel'       => '',
			'created'     => 0,
			'modified'    => 0,
			'kenmerk'     => '',
			'herkomst'    => '',
			'basis'       => [],
			'toevoeging'  => [],
			'stookschema' => '',
			'status'      => '',
			'foto'        => '',
			'glazuur'     => 0,
			'kleur'       => 0,
			'uiterlijk'   => 0,
		];
		if ( $recept_id ) {
			$recept_post = $load ?: get_post( $recept_id );
			if ( $recept_post ) {
				$this->auteur_id = $recept_post->post_author;
				$this->data      = array_merge(
					json_decode( $recept_post->post_content, true ) ?? [],
					[
						'id'        => $recept_post->ID,
						'titel'     => $recept_post->post_title,
						'status'    => $recept_post->post_status,
						'created'   => $recept_post->post_date,
						'modified'  => $recept_post->post_modified,
						'glazuur'   => $this->eigenschap_id( ReceptTermen::GLAZUUR ),
						'kleur'     => $this->eigenschap_id( ReceptTermen::KLEUR ),
						'uiterlijk' => $this->eigenschap_id( ReceptTermen::UITERLIJK ),
					]
				);
			}
		}
		$this->normering();
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( string $attribuut ) {
		if ( str_contains( $attribuut, '_naam' ) ) {
			$selector     = strtok( $attribuut, '_' );
			$recepttermen = new ReceptTermen();
			foreach ( $recepttermen as $receptterm ) {
				if ( intval( $recepttermen->lijst()[ $selector ]->term_id ) === $receptterm->parent ) {
					return $receptterm->name;
				}
			}
		}
		if ( isset( $this->data[ $attribuut ] ) ) {
			return $this->data[ $attribuut ];
		}
		return null;
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( string $attribuut, mixed $waarde ) {
		$this->data[ $attribuut ] = $waarde;
		$this->normering();
	}

	/**
	 * Verwijder het recept
	 *
	 * @return void
	 */
	public function erase() {
		wp_delete_post( $this->id );
		$this->id = null;
	}

	/**
	 * Bewaar het recept
	 *
	 * @return int Het recept id.
	 */
	public function save() : int {
		if ( ! $this->id ) {
			$this->id = wp_insert_post(
				[
					'post_type' => self::POST_TYPE,
				]
			);
		}
		$json_content = wp_json_encode(
			[
				'kenmerk'     => $this->data['kenmerk'],
				'herkomst'    => $this->data['herkomst'],
				'basis'       => $this->data['basis'],
				'toevoeging'  => $this->data['toevoeging'],
				'stookschema' => $this->data['stookschema'],
				'foto'        => $this->data['foto'],
			],
			JSON_UNESCAPED_UNICODE
		);
		wp_update_post(
			[
				'ID'           => $this->id,
				'post_content' => $json_content,
				'post_title'   => $this->titel,
				'post_status'  => $this->status,
				'post_excerpt' => 'keramiek recept : ' . $this->kenmerk,
				'post_type'    => self::POST_TYPE,
			]
		);
		wp_set_object_terms(
			$this->id,
			[
				$this->glazuur,
				$this->kleur,
				$this->uiterlijk,
			],
			self::CATEGORY
		);
		return $this->id;
	}

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
	}

	/**
	 * Bepaal de genormeerde verdeling van componenten.
	 */
	private function normering() {
		$normeren = 0.0;
		foreach ( $this->basis as $basis ) {
			$normeren += $basis['gewicht'];
		}
		$som = 0.0;
		foreach ( $this->basis as $basis ) {
			$som += round( $basis['gewicht'] * 100 / $normeren, 2 );
		}
		$restant = 100.0 - $som;
		foreach ( $this->basis as $index => $basis ) {
			$this->data['basis'][ $index ]['norm_gewicht'] = round( $basis['gewicht'] * 100 / $normeren, 2 ) + $restant;
			$restant                                       = 0;
		}
		$normeren = $normeren ?: 1.0; // Stel dat er alleen toevoegingen zijn opgevoerd, dan is normen nul.
		foreach ( $this->toevoeging as $index => $toevoeging ) {
			$this->data['toevoeging'][ $index ]['norm_gewicht'] = round( $toevoeging['gewicht'] * 100 / $normeren, 2 );
		}
	}

	/**
	 * Bepaal het term id.
	 *
	 * @param string $selector De eigenschap.
	 *
	 * @return int|void
	 */
	private function eigenschap_id( string $selector ) {
		$recepttermen = new ReceptTermen();
		foreach ( get_the_terms( $this->id, self::CATEGORY ) ?: [] as $term ) {
			if ( $recepttermen->lijst()[ $selector ]->term_id === $term->parent ) {
				return $term->term_id;
			}
		}
	}
}
