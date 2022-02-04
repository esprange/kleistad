<?php
/**
 * De admin functies van de kleistad plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      6.4.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * De admin-specifieke functies van de plugin voor de Recept termen pagina.
 */
class Admin_Recepttermen_Handler {

	/**
	 * Het display object
	 *
	 * @var Admin_Recepttermen_Display $display De display class.
	 */
	private Admin_Recepttermen_Display $display;

	/**
	 * Eventuele foutmelding.
	 *
	 * @var string $notice Foutmelding.
	 */
	private string $notice = '';

	/**
	 * Of de actie uitgevoerd is.
	 *
	 * @var string $message Actie melding.
	 */
	private string $message = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->display = new Admin_Recepttermen_Display();
	}

	/**
	 * Valideer de recept term
	 *
	 * @since    6.4.0
	 * @param array $item de receptterm.
	 * @return bool|string
	 */
	private function validate_receptterm( array $item ): bool|string {
		$messages = [];

		if ( empty( $item['naam'] ) ) {
			$messages[] = 'Naam is verplicht';
		}
		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br />', $messages );
	}

	/**
	 * Definieer de panels
	 *
	 * @since    6.4.0
	 */
	public function add_pages() {
		add_submenu_page( 'kleistad', 'Recept termen', 'Recept termen', 'manage_options', 'recepttermen', [ $this->display, 'page' ] );
		add_submenu_page( 'receptterm', 'Toevoegen/Wijzigen recept term', 'Toevoegen/Wijzigen recept term', 'manage_options', 'recepttermen_form', [ $this, 'recepttermen_form_page_handler' ] );
	}

	/**
	 * Toon en verwerk recept term gegevens
	 *
	 * @since    6.4.0
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	public function recepttermen_form_page_handler() {
		$item = wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ) ?? '', 'kleistad_receptterm' ) ? $this->update_term() : $this->geef_term();
		add_meta_box( 'receptterm_form_meta_box', 'Receptterm', [ $this->display, 'form_meta_box' ], 'receptterm', 'normal' );
		$this->display->form_page( $item, 'receptterm', 'recepttermen', $this->notice, $this->message, false, [ 'hoofdterm_id' => $item['hoofdterm_id'] ] );
	}

	/**
	 * Update de term
	 *
	 * @return array De recept term.
	 */
	private function update_term() : array {
		$item       = filter_input_array(
			INPUT_POST,
			[
				'id'           => FILTER_SANITIZE_NUMBER_INT,
				'hoofdterm_id' => FILTER_SANITIZE_NUMBER_INT,
				'naam'         => FILTER_SANITIZE_STRING,
			]
		) ?: [];
		$item_valid = $this->validate_receptterm( $item );
		if ( true === $item_valid ) {
			if ( $item['id'] > 0 ) {
				wp_update_term(
					$item['id'],
					Recept::CATEGORY,
					[
						'naam'   => $item['naam'],
						'parent' => $item['hoofdterm_id'],
					]
				);
			} else {
				wp_insert_term(
					$item['naam'],
					Recept::CATEGORY,
					[
						'parent' => $item['hoofdterm_id'],
					]
				);
			}
			$this->message = 'De gegevens zijn opgeslagen';
		} else {
			$this->notice = $item_valid;
		}
		return $item;
	}

	/**
	 * Geef de term
	 *
	 * @return array De recept term.
	 */
	private function geef_term() : array {
		$item          = [
			'id'           => 0,
			'hoofdterm_id' => filter_input( INPUT_GET, 'hoofdterm_id', FILTER_SANITIZE_NUMBER_INT ) ?: 0,
			'naam'         => '',
		];
		$receptterm_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT ) ?: 0;
		if ( $receptterm_id ) {
			if ( filter_input( INPUT_GET, 'delete' ) ) {
				wp_delete_term( $receptterm_id, Recept::CATEGORY );
				$this->message = 'De gegevens zijn opgeslagen';
			} else {
				$term = get_term( $receptterm_id );
				if ( ! is_wp_error( $term ) ) {
					$item = [
						'id'           => $term->term_id,
						'hoofdterm_id' => $term->parent,
						'naam'         => $term->name,
					];
				}
			}
		}
		return $item;
	}

}
