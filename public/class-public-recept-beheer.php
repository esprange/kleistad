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
	 * Helpfunctie om overzicht lijst te maken.
	 *
	 * @return array De recepten data.
	 */
	private function lijst(): array {
		/*
		 * maak een lijst van recepten
		 */
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

		$recepten = get_posts( $query );
		$lijst    = [];
		foreach ( $recepten as $recept ) {
			$content = json_decode( $recept->post_content, true );
			$lijst[] = [
				'id'       => $recept->ID,
				'titel'    => $recept->post_title,
				'status'   => $recept->post_status,
				'created'  => strtotime( $recept->post_date ),
				'modified' => strtotime( $recept->post_modified ),
				'foto'     => $content['foto'],
			];
		}

		return $lijst;
	}

	/**
	 * Bereid een recept wijziging voor.
	 *
	 * @param int $recept_id Het recept.
	 *
	 * @return array De recept data.
	 */
	private function formulier( int $recept_id ): array {
		$recept = get_post( $recept_id );

		$glazuur_id   = 0;
		$kleur_id     = 0;
		$uiterlijk_id = 0;
		$termen       = get_the_terms( $recept->ID, Recept::CATEGORY );
		if ( is_array( $termen ) ) {
			foreach ( $termen as $term ) {
				if ( intval( Recept::hoofdtermen()[ Recept::GLAZUUR ]->term_id ) === $term->parent ) {
					$glazuur_id = $term->term_id;
				}
				if ( intval( Recept::hoofdtermen()[ Recept::KLEUR ]->term_id ) === $term->parent ) {
					$kleur_id = $term->term_id;
				}
				if ( intval( Recept::hoofdtermen()[ Recept::UITERLIJK ]->term_id ) === $term->parent ) {
					$uiterlijk_id = $term->term_id;
				}
			}
		}

		return [
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
	}

	/**
	 * Prepareer 'recept' form
	 *
	 * @param array $data data voor display.
	 *
	 * @return bool
	 *
	 * @since   4.1.0
	 */
	protected function prepare( array &$data ) {

		if ( 'toevoegen' === $data['actie'] ) {
			/*
			 * Er moet een nieuw recept opgevoerd worden
			 */
			$data['id']     = 0;
			$data['recept'] = [
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
		} elseif ( 'wijzigen' === $data['actie'] ) {
			/*
			 * Er is een recept gekozen om te wijzigen.
			 */
			$data['recept'] = $this->formulier( $data['id'] );
		} else {
			$data['recepten'] = $this->lijst();
		}

		return true;
	}

	/**
	 * Valideer/sanitize 'recept' form
	 *
	 * @param array $data Gevalideerde data.
	 *
	 * @return WP_Error|bool
	 *
	 * @since   4.1.0
	 */
	protected function validate( array &$data ) {
		$data['recept']                           = filter_input_array(
			INPUT_POST,
			[
				'id'        => FILTER_SANITIZE_NUMBER_INT,
				'titel'     => FILTER_SANITIZE_STRING,
				'glazuur'   => FILTER_SANITIZE_NUMBER_INT,
				'kleur'     => FILTER_SANITIZE_NUMBER_INT,
				'uiterlijk' => FILTER_SANITIZE_NUMBER_INT,
			]
		);
		$data['recept']['content']['kenmerk']     = sanitize_textarea_field( filter_input( INPUT_POST, 'kenmerk' ) );
		$data['recept']['content']['herkomst']    = sanitize_textarea_field( filter_input( INPUT_POST, 'herkomst' ) );
		$data['recept']['content']['stookschema'] = sanitize_textarea_field( filter_input( INPUT_POST, 'stookschema' ) );
		$data['recept']['content']['basis']       = $this->component( 'basis_component', 'basis_gewicht' );
		$data['recept']['content']['toevoeging']  = $this->component( 'toevoeging_component', 'toevoeging_gewicht' );
		$data['recept']['content']['foto']        = filter_input( INPUT_POST, 'foto_url', FILTER_SANITIZE_URL );

		if ( 'bewaren' === $data['form_actie'] ) {
			if ( UPLOAD_ERR_INI_SIZE === $_FILES['foto']['error'] ) {
				return new WP_Error( 'foto', 'De foto is te groot qua omvang !' );
			}
		}

		return true;
	}

	/**
	 * Recept moet verwijderd worden.
	 *
	 * @param array $data De input data.
	 * @return array
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private function verwijderen( array $data ): array {
		wp_delete_post( $data['recept']['id'] );
		return [
			'status'  => $this->status( 'Het recept is verwijderd' ),
			'content' => $this->display(),
		];
	}

	/**
	 * Recept publicatie status moet aangepast worden
	 *
	 * @param array $data De input data.
	 * @return array
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private function publiceren( array $data ): array {
		$recept              = get_post( $data['recept']['id'] );
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
	 * @param array $data De input data.
	 * @return array
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private function verbergen( array $data ): array {
		$recept              = get_post( $data['recept']['id'] );
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
	 * @param array $data De input data.
	 * @return array
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private function bewaren( array $data ): array {
		if ( ! empty( $_FILES['foto']['name'] ) ) {
			$file = wp_handle_upload(
				$_FILES['foto'],
				[ 'test_form' => false ]
			);
			if ( is_array( $file ) && ! isset( $file['error'] ) ) {
				$result = $this->foto( $file['file'] );
				if ( true === $result ) {
					$data['recept']['content']['foto'] = $file['url'];
				} else {
					return [ 'status' => $this->status( $result ) ];
				}
			} else {
				return [
					'status' => $this->status( new WP_Error( 'fout', 'Foto kon niet worden opgeslagen: ' . $file['error'] ) ),
				];
			}
		}
		if ( ! $data['recept']['id'] ) {
			$result = wp_insert_post(
				[
					'post_status' => 'draft', // Initiële publicatie status is prive.
					'post_type'   => 'kleistad_recept',
				]
			);
			if ( $result ) {
				$data['recept']['id'] = $result;
			} else {
				return [
					'status' => $this->status( new WP_Error( 'fout', 'Recept kon niet worden toegevoegd' ) ),
				];
			}
		}
		$recept = get_post( $data['recept']['id'] );
		if ( ! is_null( $recept ) ) {
			$recept->post_title   = (string) $data['recept']['titel'];
			$recept->post_excerpt = 'keramiek recept : ' . $data['recept']['content']['kenmerk'];
			$json_content         = wp_json_encode( $data['recept']['content'], JSON_UNESCAPED_UNICODE );
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
						intval( $data['recept']['glazuur'] ),
						intval( $data['recept']['kleur'] ),
						intval( $data['recept']['uiterlijk'] ),
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
	private function foto( string $image_file ) {
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

}
