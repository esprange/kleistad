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
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

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
		$this->data['recepten'] = new Recepten( is_super_admin() ? [] : [ 'author' => get_current_user_id() ] );
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
		$this->data['recept']['id']          = intval( $this->data['recept']['id'] );
		$this->data['recept']['kenmerk']     = sanitize_textarea_field( filter_input( INPUT_POST, 'kenmerk' ) );
		$this->data['recept']['herkomst']    = sanitize_textarea_field( filter_input( INPUT_POST, 'herkomst' ) );
		$this->data['recept']['stookschema'] = sanitize_textarea_field( filter_input( INPUT_POST, 'stookschema' ) );
		$this->data['recept']['basis']       = $this->component( 'basis_component', 'basis_gewicht' );
		$this->data['recept']['toevoeging']  = $this->component( 'toevoeging_component', 'toevoeging_gewicht' );
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
		$recept              = new Recept( $this->data['recept']['id'] );
		$recept->titel       = $this->data['recept']['titel'];
		$recept->kenmerk     = $this->data['recept']['kenmerk'] ?? '';
		$recept->toevoeging  = $this->data['recept']['toevoeging'];
		$recept->basis       = $this->data['recept']['basis'];
		$recept->stookschema = $this->data['recept']['stookschema'] ?? '';
		$recept->herkomst    = $this->data['recept']['herkomst'] ?? '';
		$recept->glazuur     = (int) $this->data['recept']['glazuur'];
		$recept->uiterlijk   = (int) $this->data['recept']['uiterlijk'];
		$recept->kleur       = (int) $this->data['recept']['kleur'];
		$recept->save();
		if ( $_FILES['foto']['size'] ) {
			$result = media_handle_upload( 'foto', $recept->id );
			if ( is_wp_error( $result ) ) {
				return [ 'status' => $this->status( $result ) ];
			}
		}
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

}
