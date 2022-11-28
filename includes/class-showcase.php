<?php
/**
 * De definitie van de showcase class
 *
 * @link       https://www.kleistad.nl
 * @since      7.6.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use WP_Post;

/**
 * Kleistad Showcase class.
 *
 * @property int    $id
 * @property string $titel
 * @property string $beschrijving
 * @property string $positie
 * @property int    $breedte
 * @property int    $diepte
 * @property int    $hoogte
 * @property float  $prijs
 * @property float  $btw_percentage
 * @property int    $jaar
 * @property string $status
 * @property int    $keramist_id
 * @property int    $verkoop_datum
 * @property int    $aanmeld_datum
 * @property int    $mail_datum
 * @property int    $foto_id
 * @property array  $shows
 */
class Showcase {

	const POST_TYPE      = 'kleistad_showcase';
	const BESCHIKBAAR    = 'beschikbaar';
	const INGEPLAND      = 'ingepland';
	const TENTOONGESTELD = 'tentoongesteld';
	const VERKOCHT       = 'publish';
	const VERWIJDERD     = 'trash';

	/**
	 * De ruwe data
	 *
	 * @var array $data De showcase data.
	 */
	private array $data;

	/**
	 * Constructor
	 *
	 * @param int|null $showcase_id Het showcase id.
	 * @param ?WP_Post $load      Eventueel al geladen post.
	 */
	public function __construct( ?int $showcase_id = null, ? WP_Post $load = null ) {
		$this->data = [
			'id'             => $showcase_id,
			'titel'          => '',
			'beschrijving'   => '',
			'breedte'        => 0,
			'diepte'         => 0,
			'hoogte'         => 0,
			'prijs'          => 0,
			'btw_percentage' => 0,
			'positie'        => '',
			'jaar'           => 0,
			'aanmeld_datum'  => 0,
			'verkoop_datum'  => 0,
			'mail_datum'     => 0,
			'status'         => self::BESCHIKBAAR,
			'foto_id'        => 0,
			'shows'          => [],
			'keramist_id'    => 0,
		];
		if ( $showcase_id ) {
			$showcase_post = $load ?: get_post( $showcase_id );
			if ( $showcase_post ) {
				$showcase_specs = maybe_unserialize( $showcase_post->post_excerpt );
				$this->data     = [
					'id'             => $showcase_post->ID,
					'titel'          => $showcase_post->post_title,
					'status'         => $showcase_post->post_status,
					'aanmeld_datum'  => strtotime( $showcase_post->post_date ),
					'beschrijving'   => $showcase_post->post_content,
					'keramist_id'    => intval( $showcase_post->post_author ),
					'breedte'        => $showcase_specs['breedte'],
					'diepte'         => $showcase_specs['diepte'],
					'hoogte'         => $showcase_specs['hoogte'],
					'prijs'          => round( $showcase_specs['prijs'], 2 ),
					'btw_percentage' => intval( $showcase_specs['btw_percentage'] ?? 0 ),
					'positie'        => $showcase_specs['positie'],
					'jaar'           => $showcase_specs['jaar'],
					'shows'          => $showcase_specs['shows'] ?? [],
					'verkoop_datum'  => $showcase_specs['verkoop_datum'] ?? [],
					'mail_datum'     => $showcase_specs['mail_datum'] ?? 0,
				];
			}
			$images = get_attached_media( 'image', $showcase_id );
			if ( $images ) { // Haal het laatste (= meest recente) plaatje op.
				$this->data['foto_id'] = end( $images )->ID;
			}
		}
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( string $attribuut ) {
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
	}

	/**
	 * Bepaal de status
	 *
	 * @return string
	 */
	public function show_status() : string {
		if ( self::VERKOCHT === $this->status ) {
			return 'verkocht';
		}
		if ( self::VERWIJDERD === $this->status ) {
			return 'verwijderd';
		}
		$vandaag = strtotime( 'today' );
		foreach ( $this->shows as $show ) {
			if ( $show['eind'] < $vandaag ) {
				continue; // De show is al voorbij.
			}
			if ( $show['start'] > $vandaag ) {
				return self::INGEPLAND;
			}
			return self::TENTOONGESTELD;
		}
		return 'beschikbaar';
	}

	/**
	 * Bepaal of de showcase wordt tentoongesteld.
	 *
	 * @return bool
	 */
	public function is_tentoongesteld() : bool {
		$vandaag = strtotime( 'today' );
		foreach ( $this->shows as $show ) {
			if ( $show['eind'] < $vandaag ) {
				continue; // De show is al voorbij.
			}
			if ( $show['start'] > $vandaag ) {
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * Verwijder de showcase
	 *
	 * @return void
	 */
	public function erase() : void {
		wp_delete_post( $this->id );
		$this->id = null;
	}

	/**
	 * Pas de shows aan.
	 *
	 * @param array $shows De nieuwe shows.
	 *
	 * @return void
	 */
	public function tentoonstellen( array $shows ) : void {
		$vandaag = strtotime( 'today' );
		foreach ( $this->shows as $key => $show ) {
			if ( $vandaag >= $show['eind'] ) {
				continue;
			}
			unset( $this->data['shows'][ $key ] );
		}
		foreach ( $shows as $nieuwe_show ) {
			$this->data['shows'][] = $nieuwe_show;
		}
		$this->save();
	}

	/**
	 * Bewaar de showcase
	 *
	 * @return int Het showcase id.
	 */
	public function save() : int {
		if ( ! $this->id ) {
			$this->id = wp_insert_post(
				[
					'post_type' => self::POST_TYPE,
				]
			);
		}
		wp_update_post(
			[
				'ID'           => $this->id,
				'post_title'   => $this->titel,
				'post_status'  => $this->status,
				'post_content' => $this->beschrijving,
				'post_excerpt' => maybe_serialize(
					[
						'breedte'        => $this->breedte,
						'hoogte'         => $this->hoogte,
						'diepte'         => $this->diepte,
						'prijs'          => $this->prijs,
						'btw_percentage' => $this->btw_percentage,
						'positie'        => $this->positie,
						'jaar'           => $this->jaar,
						'shows'          => $this->shows,
						'verkoop_datum'  => $this->verkoop_datum,
						'mail_datum'     => $this->mail_datum,
					]
				),
				'post_type'    => self::POST_TYPE,
			]
		);
		return $this->id;
	}

	/**
	 * Initialiseer de showcases als custom post type.
	 */
	public static function create_type() : void {
		register_post_type(
			self::POST_TYPE,
			[
				'public'  => true,
				'rewrite' => [
					'slug' => 'showcases',
				],
				'show_ui' => false,
			]
		);
		register_post_status( self::INGEPLAND, [ 'public' => true ] );
		register_post_status( self::TENTOONGESTELD, [ 'public' => true ] );
		register_post_status( self::BESCHIKBAAR, [ 'public' => true ] );
	}

	/**
	 * Bepaal de datums van de huidige en komende shows.
	 *
	 * @param int $datum Default huidige datum, aanpasbaar voor testdoeleinden.
	 * @return array
	 */
	public static function show_datums( int $datum = 0 ) : array {
		$datum      = $datum ?: time();
		$maand      = intval( date( 'n', $datum ) ) - 1;
		$periode    = 0 === $maand % 2 ? -2 : -3;
		$shows      = [];
		$show_count = 0;
		while ( 3 > $show_count ) {
			$start = strtotime( "first monday of $periode month 0:00", $datum );
			$eind  = strtotime( 'first monday of ' . $periode + 2 . ' month 0:00', $datum );
			if ( $datum >= $start && $datum < $eind || $show_count ) {
				$shows[] = [
					'start' => $start,
					'eind'  => $eind,
				];
			}
			$periode   += 2;
			$show_count = count( $shows );
		}
		return $shows;
	}
}
