<?php
/**
 * Shortcode recept beheer.
 *
 * @link       https://www.kleistad.nl
 * @since      4.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_Error;

/**
 * Include voor image file upload.
 */
require_once ABSPATH . 'wp-admin/includes/file.php';

/**
 * De kleistad recept beheer class.
 */
class Public_Recept_Beheer extends ShortcodeForm {

	/**
	 * Prepareer 'recept' toevoegen form
	 *
	 * @return string
	 */
	protected function prepare_toevoegen() : string {
		$this->data['id']     = 0;
		$this->data['recept'] = [
			'id'          => 0,
			'titel'       => '',
			'post_status' => 'draft',
			'created'     => 0,
			'modified'    => 0,
			'content'     => [
				'kenmerk'     => '',
				'herkomst'    => '',
				'basis'       => [],
				'toevoeging'  => [],
				'stookschema' => '',
				'foto'        => '',
			],
			'glazuur'     => 0,
			'kleur'       => 0,
			'uiterlijk'   => 0,
		];
		return $this->content();
	}

	/**
	 * Prepareer 'recept' wijzigen form
	 *
	 * @return string
	 */
	protected function prepare_wijzigen() : string {
		$recept       = get_post( $this->data['id'] );
		$recepttermen = new ReceptTermen();
		$glazuur_id   = 0;
		$kleur_id     = 0;
		$uiterlijk_id = 0;
		$termen       = get_the_terms( $recept->ID, Recept::CATEGORY );
		if ( is_array( $termen ) ) {
			foreach ( $termen as $term ) {
				if ( intval( $recepttermen->lijst()[ ReceptTermen::GLAZUUR ]->term_id ) === $term->parent ) {
					$glazuur_id = $term->term_id;
				}
				if ( intval( $recepttermen->lijst()[ ReceptTermen::KLEUR ]->term_id ) === $term->parent ) {
					$kleur_id = $term->term_id;
				}
				if ( intval( $recepttermen->lijst()[ ReceptTermen::UITERLIJK ]->term_id ) === $term->parent ) {
					$uiterlijk_id = $term->term_id;
				}
			}
		}

		$this->data['recept'] = [
			'id'        => $recept->ID,
			'titel'     => $recept->post_title,
			'status'    => $recept->post_status,
			'created'   => $recept->post_date,
			'modified'  => $recept->post_modified,
			'content'   => json_decode( $recept->post_content, true ),
			'glazuur'   => $glazuur_id,
			'kleur'     => $kleur_id,
			'uiterlijk' => $uiterlijk_id,
		];
		return $this->content();
	}

	/**
	 * Prepareer 'recept' overzicht
	 *
	 * @return string
	 */
	protected function prepare_overzicht() : string {
		$query = [
			'post_type'   => 'kleistad_recept',
			'numberposts' => '-1',
			'post_status' => [
				'publish',
				'pending',
				'private',
				'draft',
			],
			'orderby'     => 'date',
		];
		if ( ! is_super_admin() ) {
			$query['author'] = get_current_user_id();
		}

		$recepten               = get_posts( $query );
		$this->data['recepten'] = [];
		foreach ( $recepten as $recept ) {
			$recept_content           = json_decode( $recept->post_content, true );
			$this->data['recepten'][] = [
				'id'       => $recept->ID,
				'titel'    => $recept->post_title,
				'status'   => $recept->post_status,
				'created'  => strtotime( $recept->post_date ),
				'modified' => strtotime( $recept->post_modified ),
				'foto'     => $recept_content['foto'],
			];
		}
		return $this->content();
	}

	/**
	 * Valideer/sanitize 'recept' form
	 *
	 * @since   4.1.0
	 *
	 * @return array
	 */
	public function process() : array {
		$this->data['recept']                           = filter_input_array(
			INPUT_POST,
			[
				'id'        => FILTER_SANITIZE_NUMBER_INT,
				'titel'     => FILTER_SANITIZE_STRING,
				'glazuur'   => FILTER_SANITIZE_NUMBER_INT,
				'kleur'     => FILTER_SANITIZE_NUMBER_INT,
				'uiterlijk' => FILTER_SANITIZE_NUMBER_INT,
			]
		);
		$this->data['recept']['content']['kenmerk']     = sanitize_textarea_field( filter_input( INPUT_POST, 'kenmerk' ) );
		$this->data['recept']['content']['herkomst']    = sanitize_textarea_field( filter_input( INPUT_POST, 'herkomst' ) );
		$this->data['recept']['content']['stookschema'] = sanitize_textarea_field( filter_input( INPUT_POST, 'stookschema' ) );
		$this->data['recept']['content']['basis']       = $this->component( 'basis_component', 'basis_gewicht' );
		$this->data['recept']['content']['toevoeging']  = $this->component( 'toevoeging_component', 'toevoeging_gewicht' );
		$this->data['recept']['content']['foto']        = filter_input( INPUT_POST, 'foto_url', FILTER_SANITIZE_URL );

		if ( 'bewaren' === $this->form_actie ) {
			if ( UPLOAD_ERR_INI_SIZE === $this->files()['foto']['error'] ) {
				return $this->melding( new WP_Error( 'foto', 'De foto is te groot qua omvang !' ) );
			}
		}

		return $this->save();
	}

	/**
	 * Recept moet verwijderd worden.
	 *
	 * @return array
	 */
	protected function verwijderen(): array {
		wp_delete_post( $this->data['recept']['id'] );
		return [
			'status'  => $this->status( 'Het recept is verwijderd' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Recept publicatie status moet aangepast worden
	 *
	 * @return array
	 */
	protected function publiceren(): array {
		$recept              = get_post( $this->data['recept']['id'] );
		$recept->post_status = 'publish';
		wp_update_post( $recept, true );

		return [
			'status'  => $this->status( 'Het recept is aangepast' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Recept publicatie status moet aangepast worden
	 *
	 * @return array
	 */
	protected function verbergen(): array {
		$recept              = get_post( $this->data['recept']['id'] );
		$recept->post_status = 'private';
		wp_update_post( $recept, true );

		return [
			'status'  => $this->status( 'Het recept is aangepast' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Recept moet worden opgeslagen
	 *
	 * @return array
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function bewaren(): array {
		if ( ! empty( $this->files()['foto']['name'] ) ) {
			$file = wp_handle_upload(
				$this->files()['foto'],
				[ 'test_form' => false ]
			);
			if ( is_array( $file ) && ! isset( $file['error'] ) ) {
				$result = $this->foto( $file['file'] );
				if ( true === $result ) {
					$this->data['recept']['content']['foto'] = $file['url'];
				} else {
					return [ 'status' => $this->status( $result ) ];
				}
			} else {
				return [
					'status' => $this->status( new WP_Error( 'fout', 'Foto kon niet worden opgeslagen: ' . $file['error'] ) ),
				];
			}
		}
		if ( ! $this->data['recept']['id'] ) {
			$result = wp_insert_post(
				[
					'post_status' => 'draft', // Initiële publicatie status is prive.
					'post_type'   => 'kleistad_recept',
				]
			);
			if ( $result ) {
				$this->data['recept']['id'] = $result;
			} else {
				return [
					'status' => $this->status( new WP_Error( 'fout', 'Recept kon niet worden toegevoegd' ) ),
				];
			}
		}
		$recept = get_post( $this->data['recept']['id'] );
		if ( ! is_null( $recept ) ) {
			$recept->post_title   = (string) $this->data['recept']['titel'];
			$recept->post_excerpt = 'keramiek recept : ' . $this->data['recept']['content']['kenmerk'];
			$json_content         = wp_json_encode( $this->data['recept']['content'], JSON_UNESCAPED_UNICODE );
			if ( is_string( $json_content ) ) {
				$recept->post_content = $json_content;
			} else {
				return [
					'status' => $this->status( new WP_Error( 'intern', 'Er is iets fout gegaan, probeer het opnieuw' ) ),
				];
			}
			$recept_id = wp_update_post( $recept, true );
			if ( is_int( $recept_id ) ) {
				wp_set_object_terms(
					$recept_id,
					[
						intval( $this->data['recept']['glazuur'] ),
						intval( $this->data['recept']['kleur'] ),
						intval( $this->data['recept']['uiterlijk'] ),
					],
					Recept::CATEGORY
				);

				return [
					'status'  => $this->status( 'Gegevens zijn opgeslagen' ),
					'content' => $this->display(),
				];
			}
		}
		return [
			'status' => $this->status( new WP_Error( 'database', 'De gegevens konden niet worden opgeslagen vanwege een interne fout!' ) ),
		];
	}

	/**
	 * Zet de input om naar een componenten array
	 *
	 * @param string $namen     De input namen van de componenten.
	 * @param string $gewichten De input ingevoerde gewichten.
	 *
	 * @return array
	 */
	private function component( string $namen, string $gewichten ) : array {
		$input       = filter_input_array(
			INPUT_POST,
			[
				$namen     => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				$gewichten => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
			]
		);
		$componenten = [];
		$aantal      = count( $input[ $namen ] );
		for ( $index = 0; $index < $aantal; $index++ ) {
			if ( ! empty( $input[ $namen ][ $index ] ) && ( 0.0 !== floatval( $input[ $gewichten ][ $index ] ) ) ) {
				$componenten[] = [
					'component' => $input[ $namen ][ $index ],
					'gewicht'   => str_replace( ',', '.', $input[ $gewichten ][ $index ] ) * 1.0,
				];
			}
		}
		return $componenten;
	}

	/**
	 * Verwerk foto.
	 *
	 * @param string $image_file Path naar een image file.
	 * @return WP_Error|bool True als verwerkt of error als er iets fout is gegaan.
	 */
	private function foto( string $image_file ): WP_Error|bool {
		$exif = @exif_read_data( $image_file ); // phpcs:ignore
		if ( false === $exif ) {
			return new WP_Error( 'fout', 'Foto moet een jpeg, jpg, tif of tiff bestand zijn' );
		}
		$image = imagecreatefromjpeg( $image_file );
		if ( false === $image ) {
			return new WP_Error( 'fout', 'Foto lijkt niet een geldig dataformaat te bevatten' );
		}
		if ( ! empty( $exif['Orientation'] ) ) {
			$rotate = [
				3 => 180,
				6 => -90,
				8 => 90,
			];
			$image  = imagerotate( $image, $rotate[ $exif['Orientation'] ], 0 );
			if ( ! is_object( $image ) ) {
				return new WP_Error( 'fout', 'Foto kon niet naar juiste positie gedraaid worden' );
			}
		}
		$quality = intval( min( 75000 / filesize( $image_file ) * 100, 100 ) );
		imagejpeg( $image, $image_file, $quality );
		imagedestroy( $image );
		return true;
	}

	/**
	 * Geef de global $_FILES variabele.
	 *
	 * @return array
	 */
	private function files() : array {
		$files = new class() {
			/**
			 * Encapsulate de global variable.
			 *
			 * @return array De inhoud van de variabele.
			 */
			public function data() : array {
				return $_FILES;
			}
		};
		return $files->data();
	}
}
