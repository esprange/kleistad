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
		$this->data['recept'] = new Recept();
		return $this->content();
	}

	/**
	 * Prepareer 'recept' wijzigen form
	 *
	 * @return string
	 */
	protected function prepare_wijzigen() : string {
		$this->data['recept'] = new Recept( $this->data['id'] );
		return $this->content();
	}

	/**
	 * Prepareer 'recept' overzicht
	 *
	 * @return string
	 */
	protected function prepare_overzicht() : string {
		$this->data['recepten'] = new Recepten( ! is_super_admin() ? [ 'author' => get_current_user_id() ] : [] );
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
		$this->data['recept']                = filter_input_array(
			INPUT_POST,
			[
				'id'        => FILTER_SANITIZE_NUMBER_INT,
				'titel'     => FILTER_SANITIZE_STRING,
				'glazuur'   => FILTER_SANITIZE_NUMBER_INT,
				'kleur'     => FILTER_SANITIZE_NUMBER_INT,
				'uiterlijk' => FILTER_SANITIZE_NUMBER_INT,
				'foto_url'  => FILTER_SANITIZE_URL,
			]
		);
		$this->data['recept']['kenmerk']     = sanitize_textarea_field( filter_input( INPUT_POST, 'kenmerk' ) );
		$this->data['recept']['herkomst']    = sanitize_textarea_field( filter_input( INPUT_POST, 'herkomst' ) );
		$this->data['recept']['stookschema'] = sanitize_textarea_field( filter_input( INPUT_POST, 'stookschema' ) );
		$this->data['recept']['basis']       = $this->component( 'basis_component', 'basis_gewicht' );
		$this->data['recept']['toevoeging']  = $this->component( 'toevoeging_component', 'toevoeging_gewicht' );

		$files = new Files();
		if ( 'bewaren' === $this->form_actie ) {
			if ( UPLOAD_ERR_INI_SIZE === $files->data['foto']['error'] ) {
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
		$recept = new Recept( $this->data['recept']['id'] );
		$recept->erase();
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
		$recept         = new Recept( $this->data['recept']['id'] );
		$recept->status = 'publish';
		$recept->save();
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
		$recept         = new Recept( $this->data['recept']['id'] );
		$recept->status = 'private';
		$recept->save();
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
		$files = new Files();
		if ( ! empty( $files->data['foto']['name'] ) ) {
			$file = wp_handle_upload(
				$files->data['foto'],
				[ 'test_form' => false ]
			);
			if ( is_array( $file ) && ! isset( $file['error'] ) ) {
				$result = $this->foto( $file['file'] );
				if ( true === $result ) {
					$this->data['recept']['foto'] = $file['url'];
				} else {
					return [ 'status' => $this->status( $result ) ];
				}
			} else {
				return [
					'status' => $this->status( new WP_Error( 'fout', 'Foto kon niet worden opgeslagen: ' . $file['error'] ) ),
				];
			}
		}
		$recept              = new Recept( $this->data['recept']['id'] );
		$recept->titel       = $this->data['recept']['titel'];
		$recept->kenmerk     = $this->data['recept']['kenmerk'];
		$recept->toevoeging  = $this->data['recept']['toevoeging'];
		$recept->basis       = $this->data['recept']['basis'];
		$recept->stookschema = $this->data['recept']['stookschema'];
		$recept->herkomst    = $this->data['recept']['herkomst'];
		$recept->glazuur     = (int) $this->data['recept']['glazuur'];
		$recept->uiterlijk   = (int) $this->data['recept']['uiterlijk'];
		$recept->kleur       = (int) $this->data['recept']['kleur'];
		$recept->foto        = $this->data['recept']['foto'];
		$recept->save();
		return [
			'status'  => $this->status( 'Gegevens zijn opgeslagen' ),
			'content' => $this->display(),
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
			$image  = imagerotate( $image, $rotate[ $exif['Orientation'] ] ?? 0, 0 );
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

/**
 * Encapsulate de Files variabele
 * phpcs:disable
 */
final class Files {

	/**
	 * Inhoud van de global var.
	 *
	 * @var array $data De files data
	 */
	public array $data;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->data = $_FILES;
	}

}
