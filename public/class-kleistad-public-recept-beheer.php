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
 * Included for image file upload.
 */
require_once( ABSPATH . 'wp-admin/includes/file.php' );

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_Recept_Beheer extends Kleistad_Shortcode {

	/**
	 * Helpfunctie om overzicht lijst te maken.
	 *
	 * @param array $data recept data.
	 */
	private function overzicht( &$data ) {
		/*
		 * maak een lijst van recepten
		 */
		$query = [
			'post_type' => 'kleistad_recept',
			'numberposts' => '-1',
			'author' => get_current_user_id(),
			'post_status' => [
				'publish',
				'pending',
				'private',
				'draft',
			],
		];
		$recepten = get_posts( $query );
		$data['recept'] = [];

		foreach ( $recepten as $recept ) {
			$content = json_decode( $recept->post_content, true );
			$data['recept'][] = [
				'id' => $recept->ID,
				'titel' => $recept->post_title,
				'post_status' => $recept->post_status,
				'created' => $recept->post_date,
				'modified' => $recept->post_modified,
				'foto' => $content['foto'],
			];
		}
	}

	/**
	 * Prepareer 'recept' form
	 *
	 * @param array $data data to be prepared.
	 * @return array
	 *
	 * @since   4.1.0
	 */
	public function prepare( &$data = null ) {
		$error = new WP_Error();

		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );
		if ( is_null( $action ) ) {
			$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
		}
		if ( ! is_null( $data ) ) {
			/*
			 * Er is ergens iets fout gegaan bij het invullen van het formulier, toon het opnieuw
			 */
			$data['id'] = $data['recept']['id'];
		} elseif ( 'wijzigen' === $action ) {
			/*
			 * Er is een recept gekozen om te wijzigen.
			 */
			$recept_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
			if ( wp_verify_nonce( filter_input( INPUT_GET, '_wpnonce' ), 'kleistad_wijzig_recept_' . $recept_id ) ) {
				$recept = get_post( $recept_id );

				$glazuur = get_term_by( 'name', '_glazuur', 'kleistad_recept_cat' );
				$kleur = get_term_by( 'name', '_kleur', 'kleistad_recept_cat' );
				$uiterlijk = get_term_by( 'name', '_uiterlijk', 'kleistad_recept_cat' );

				$glazuur_id = 0;
				$kleur_id = 0;
				$uiterlijk_id = 0;
				$terms = get_the_terms( $recept->ID, 'kleistad_recept_cat' );
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

				$data['id'] = $recept_id;
				$data['recept'] = [
					'id' => $recept->ID,
					'titel' => $recept->post_title,
					'post_status' => $recept->post_status,
					'created' => $recept->post_date,
					'modified' => $recept->post_modified,
					'content' => json_decode( $recept->post_content, true ),
					'glazuur' => $glazuur_id,
					'kleur' => $kleur_id,
					'uiterlijk' => $uiterlijk_id,
				];
			} else {
				$error->add( 'security', 'Security fout! !' );
				return $error;
			}
		} elseif ( 'verwijderen' === $action ) {
			/*
			 * Recept moet verwijderd worden.
			 */
			$recept_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
			if ( wp_verify_nonce( filter_input( INPUT_GET, '_wpnonce' ), 'kleistad_verwijder_recept_' . $recept_id ) ) {
				wp_delete_post( $recept_id );
			} else {
				$error->add( 'security', 'Security fout! !' );
				return $error;
			}
			$this->overzicht( $data );
		} elseif ( 'publiceren' === $action ) {
			/*
			 * Recept publicatie status moet aangepast worden
			 */
			$recept_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
			if ( wp_verify_nonce( filter_input( INPUT_GET, '_wpnonce' ), 'kleistad_publiceer_recept_' . $recept_id ) ) {
				$recept = get_post( $recept_id );
				if ( Kleistad_Roles::override() ) {
					$recept->post_status = ( 'publish' === $recept->post_status ) ? 'draft' : 'publish';
				} else {
					$recept->post_status = ( 'pending' === $recept->post_status ) ? 'draft' :
						( 'publish' === $recept->post_status ) ? 'draft' : 'publish';
				}
				$error = wp_update_post( $recept, true );
				if ( is_wp_error( $error ) ) {
					return $error;
				}
			} else {
				$error->add( 'security', 'Security fout! !' );
				return $error;
			}
			$this->overzicht( $data );
		} elseif ( 'toevoegen' === $action ) {
			/*
			 * Er moet een nieuw recept opgevoerd worden
			 */
			$data['id'] = 0;
			$data['recept'] = [
				'id' => 0,
				'titel' => '',
				'post_status' => 'draft',
				'created' => 0,
				'modified' => 0,
				'content' => [
					'kenmerk' => '',
					'herkomst' => '',
					'basis' => [],
					'toevoeging' => [],
					'stookschema' => '',
					'foto' => '',
				],
				'glazuur' => 0,
				'kleur' => 0,
				'uiterlijk' => 0,
			];
		} else {
			$this->overzicht( $data );
		}
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
		$error = new WP_Error();
		$allowedhtml = [
			'strong' => [],
			'em' => [],
			'blockquote' => [],
			'del' => [],
			'ul' => [],
			'ol' => [],
			'li' => [],
			'p' => [
				'style' => [
					'text-align: center;',
					'text-align: right;',
				],
			],
			'br' => [],
			'span' => [
				'style' => [
					'text-decoration: underline;',
				],
			],
			'a' => [
				'title' => [],
				'href' => [],
			],
		];

		$data['recept'] = filter_input_array(
			INPUT_POST, [
				'id' => FILTER_SANITIZE_NUMBER_INT,
				'titel' => FILTER_SANITIZE_STRING,
				'glazuur' => FILTER_SANITIZE_NUMBER_INT,
				'kleur' => FILTER_SANITIZE_NUMBER_INT,
				'uiterlijk' => FILTER_SANITIZE_NUMBER_INT,
			]
		);
		$data['recept']['content'] = filter_input_array(
			INPUT_POST, [
				'kenmerk' => FILTER_DEFAULT,
				'herkomst' => FILTER_DEFAULT,
				'stookschema' => FILTER_DEFAULT,
			]
		);
		$basis = filter_input_array(
			INPUT_POST, [
				'basis_component' => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags' => FILTER_REQUIRE_ARRAY,
				],
				'basis_gewicht' => [
					'filter' => FILTER_SANITIZE_NUMBER_INT,
					'flags' => FILTER_REQUIRE_ARRAY,
				],
			]
		);
		$toevoeging = filter_input_array(
			INPUT_POST, [
				'toevoeging_component' => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags' => FILTER_REQUIRE_ARRAY,
				],
				'toevoeging_gewicht' => [
					'filter' => FILTER_SANITIZE_NUMBER_INT,
					'flags' => FILTER_REQUIRE_ARRAY,
				],
			]
		);
		$data['recept']['content']['basis'] = [];
		$basis_limiet = count( $basis['basis_component'] );
		for ( $i = 0; $i < $basis_limiet; $i++ ) {
			if ( ( '' !== $basis['basis_component'][ $i ] ) && ( 0 !== intval( $basis['basis_gewicht'][ $i ] ) ) ) {
				$data['recept']['content']['basis'][ $i ] = [
					'component' => $basis['basis_component'][ $i ],
					'gewicht' => $basis['basis_gewicht'][ $i ],
				];
			}
		}
		$data['recept']['content']['toevoeging'] = [];
		$toevoeging_limiet = count( $toevoeging['toevoeging_component'] );
		for ( $i = 0; $i < $toevoeging_limiet; $i++ ) {
			if ( '' !== $toevoeging['toevoeging_component'][ $i ] && 0 !== intval( $toevoeging['toevoeging_gewicht'][ $i ] ) ) {
				$data['recept']['content']['toevoeging'][ $i ] = [
					'component' => $toevoeging['toevoeging_component'][ $i ],
					'gewicht' => $toevoeging['toevoeging_gewicht'][ $i ],
				];
			}
		}

		$data['recept']['content']['kenmerk'] = wp_kses( $data['recept']['content']['kenmerk'], $allowedhtml );
		$data['recept']['content']['herkomst'] = wp_kses( $data['recept']['content']['herkomst'], $allowedhtml );
		$data['recept']['content']['stookschema'] = wp_kses( $data['recept']['content']['stookschema'], $allowedhtml );
		$data['recept']['content']['foto'] = filter_input( INPUT_POST, 'foto_url', FILTER_SANITIZE_URL );

		if ( UPLOAD_ERR_INI_SIZE === $_FILES['foto']['error'] ) {
			$error->add( 'foto', 'De foto is te groot qua omvang !' );
		} else {
			$data['foto'] = $_FILES ['foto'];
		}

		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
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
		$error = new WP_Error();

		if ( ! is_user_logged_in() ) {
			$error->add( 'security', 'Dit formulier mag alleen ingevuld worden door ingelogde gebruikers' );
			return $error;
		} else {
			if ( isset( $data['recept'] ) ) {
				if ( ! empty( $data['foto']['name'] ) ) {
					$file = wp_handle_upload(
						$data['foto'], [
							'test_form' => false,
						]
					);
					if ( $file && ! isset( $file['error'] ) ) {
						$exif = exif_read_data( $file['file'] );
						$image = imagecreatefromjpeg( $file['file'] );
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
						}
						$result = imagejpeg( $image, $file['file'] );
						imagedestroy( $image );
						$data['recept']['content']['foto'] = $file['url'];
					} else {
						$error = new WP_Error();
						$error->add( 'fout', 'Foto kon niet worden opgeslagen: ' . $file['error'] );
						return $error;
					}
				}
				if ( $data['recept']['id'] ) {
					$recept = get_post( $data['recept']['id'] );
					$recept->post_title = $data['recept']['titel'];
					$recept->post_excerpt = 'keramiek recept : ' . $data['recept']['content']['kenmerk'];
					$recept->post_content = wp_json_encode( $data['recept']['content'] );
					$error = wp_update_post( $recept, true );
					if ( ! is_wp_error( $error ) ) {
						$recept_id = $error;
					} else {
						return $error;
					}
				} else {
					$recept = [
						'post_status' => 'draft', // Initiële publicatie status is prive.
						'post_title' => $data['recept']['titel'],
						'post_type' => 'kleistad_recept',
						'post_content' => wp_json_encode( $data['recept']['content'] ),
					];
					$error = wp_insert_post( $recept );
					if ( ! is_wp_error( $error ) ) {
						$recept_id = $error;
					} else {
						return $error;
					}
				}
				wp_set_object_terms(
					$recept_id, [
						intval( $data['recept']['glazuur'] ),
						intval( $data['recept']['kleur'] ),
						intval( $data['recept']['uiterlijk'] ),
					],
					'kleistad_recept_cat'
				);
				return 'Gegevens zijn opgeslagen';
			}
		}
	}
}
