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
	private function lijst() {
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
	 * @return array De recept data.
	 */
	private function formulier( $recept_id ) {
		$recept = get_post( $recept_id );

		$glazuur   = get_term_by( 'name', '_glazuur', 'kleistad_recept_cat' );
		$kleur     = get_term_by( 'name', '_kleur', 'kleistad_recept_cat' );
		$uiterlijk = get_term_by( 'name', '_uiterlijk', 'kleistad_recept_cat' );

		$glazuur_id   = 0;
		$kleur_id     = 0;
		$uiterlijk_id = 0;
		$terms        = get_the_terms( $recept->ID, 'kleistad_recept_cat' );
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( intval( $term->parent ) === intval( $glazuur->term_id ) ) {
					$glazuur_id = $term->term_id;
				}
				if ( intval( $term->parent ) === intval( $kleur->term_id ) ) {
					$kleur_id = $term->term_id;
				}
				if ( intval( $term->parent ) === intval( $uiterlijk->term_id ) ) {
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
	 * @return \WP_ERROR|bool
	 *
	 * @since   4.1.0
	 */
	protected function prepare( &$data = null ) {

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
	 * @return \WP_Error|bool
	 *
	 * @since   4.1.0
	 */
	protected function validate( &$data ) {
		$error                                    = new \WP_Error();
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
		$basis                                    = filter_input_array(
			INPUT_POST,
			[
				'basis_component' => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'basis_gewicht'   => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
			]
		);
		$toevoeging                               = filter_input_array(
			INPUT_POST,
			[
				'toevoeging_component' => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'toevoeging_gewicht'   => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
			]
		);
		$data['recept']['content']['basis']       = [];
		$basis_limiet                             = count( $basis['basis_component'] );
		for ( $i = 0; $i < $basis_limiet; $i++ ) {
			if ( ( '' !== $basis['basis_component'][ $i ] ) && ( 0 !== floatval( $basis['basis_gewicht'][ $i ] ) ) ) {
				$data['recept']['content']['basis'][ $i ] = [
					'component' => $basis['basis_component'][ $i ],
					'gewicht'   => str_replace( ',', '.', $basis['basis_gewicht'][ $i ] ) * 1.0,
				];
			}
		}
		$data['recept']['content']['toevoeging'] = [];
		$toevoeging_limiet                       = count( $toevoeging['toevoeging_component'] );
		for ( $i = 0; $i < $toevoeging_limiet; $i++ ) {
			if ( '' !== $toevoeging['toevoeging_component'][ $i ] && 0 !== floatval( $toevoeging['toevoeging_gewicht'][ $i ] ) ) {
				$data['recept']['content']['toevoeging'][ $i ] = [
					'component' => $toevoeging['toevoeging_component'][ $i ],
					'gewicht'   => str_replace( ',', '.', $toevoeging['toevoeging_gewicht'][ $i ] ) * 1.0,
				];
			}
		}
		$data['recept']['content']['foto'] = filter_input( INPUT_POST, 'foto_url', FILTER_SANITIZE_URL );

		if ( 'bewaren' === $data['form_actie'] ) {
			if ( UPLOAD_ERR_INI_SIZE === $_FILES['foto']['error'] ) {
				$error->add( 'foto', 'De foto is te groot qua omvang !' );
			} else {
				$data['foto'] = $_FILES ['foto'];
			}
			if ( ! empty( $error->get_error_codes() ) ) {
				return $error;
			}
		}
		return true;
	}

	/**
	 *
	 * Bewaar 'recept' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return \WP_Error|array
	 *
	 * @since   4.1.0
	 */
	protected function save( $data ) {
		$error = new \WP_Error();

		if ( 'verwijderen' === $data['form_actie'] ) {
			/*
			 * Recept moet verwijderd worden.
			 */
			wp_delete_post( $data['recept']['id'] );
			return [
				'status'  => $this->status( 'Het recept is verwijderd' ),
				'content' => $this->display(),
			];
		} elseif ( 'publiceren' === $data['form_actie'] ) {
			/*
			 * Recept publicatie status moet aangepast worden
			 */
			$recept              = get_post( $data['recept']['id'] );
			$recept->post_status = 'publish';
			wp_update_post( $recept, true );
			return [
				'status'  => $this->status( 'Het recept is aangepast' ),
				'content' => $this->display(),
			];
		} elseif ( 'verbergen' === $data['form_actie'] ) {
			/*
			 * Recept publicatie status moet aangepast worden
			 */
			$recept              = get_post( $data['recept']['id'] );
			$recept->post_status = 'private';
			wp_update_post( $recept, true );
			return [
				'status'  => $this->status( 'Het recept is aangepast' ),
				'content' => $this->display(),
			];
		} elseif ( 'bewaren' === $data['form_actie'] ) {
			if ( ! empty( $data['foto']['name'] ) ) {
				$file = wp_handle_upload(
					$data['foto'],
					[ 'test_form' => false ]
				);
				if ( is_array( $file ) && ! isset( $file['error'] ) ) {
					$exif = @exif_read_data( $file['file'] ); // phpcs:ignore
					if ( false === $exif ) {
						return new \WP_Error( 'fout', 'Foto moet een jpeg, jpg, tif of tiff bestand zijn' );
					}
					$image = imagecreatefromjpeg( $file['file'] );
					if ( false === $image ) {
						return new \WP_Error( 'fout', 'Foto lijkt niet een geldig dataformaat te bevatten' );
					}
					if ( ! empty( $exif['Orientation'] ) ) {
						switch ( $exif['Orientation'] ) {
							case 3:
								$image = imagerotate( $image, 180, 0 );
								break;
							case 6:
								$image = imagerotate( $image, -90, 0 );
								break;
							case 8:
								$image = imagerotate( $image, 90, 0 );
								break;
						}
						if ( false === $image ) {
							return new \WP_Error( 'fout', 'Foto kon niet naar juiste positie gedraaid worden' );
						}
					}
					$quality = intval( min( 75000 / filesize( $file['file'] ) * 100, 100 ) );
					imagejpeg( $image, $file['file'], $quality );
					imagedestroy( $image );
					$data['recept']['content']['foto'] = $file['url'];
				} else {
					return new \WP_Error( 'fout', 'Foto kon niet worden opgeslagen: ' . $file['error'] );
				}
			}
			if ( ! $data['recept']['id'] ) {
				$result = wp_insert_post(
					[
						'post_status' => 'draft', // Initiële publicatie status is prive.
						'post_type'   => 'kleistad_recept',
					]
				);
				if ( is_int( $result ) ) {
					$data['recept']['id'] = $result;
				} else {
					return $result;
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
					return new \WP_Error( 'intern', 'Er is iets fout gegaan, probeer het opnieuw' );
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
						'kleistad_recept_cat'
					);
					return [
						'status'  => $this->status( 'Gegevens zijn opgeslagen' ),
						'content' => $this->display(),
					];
				}
			}
			return new \WP_Error( 'database', 'De gegevens konden niet worden opgeslagen vanwege een interne fout!' );
		}
	}
}
