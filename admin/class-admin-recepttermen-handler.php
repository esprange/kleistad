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
class Admin_Recepttermen_Handler extends Admin_Handler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->display = new Admin_Recepttermen_Display();
	}

	/**
	 * Definieer de panels
	 *
	 * @since    6.4.0
	 */
	public function add_pages() {
		add_submenu_page( 'kleistad', 'Recept termen', 'Recept termen', 'manage_options', 'recepttermen', [ $this, 'page_handler' ] );
		add_submenu_page( 'receptterm', 'Toevoegen/Wijzigen recept term', 'Toevoegen/Wijzigen recept term', 'manage_options', 'recepttermen_form', [ $this, 'form_handler' ] );
	}

	/**
	 * Overzicht regelingen page handler
	 */
	public function page_handler() {
		if ( wp_verify_nonce( filter_input( INPUT_GET, 'nonce' ) ?? '', 'kleistad_receptterm' ) && 'delete' === filter_input( INPUT_GET, 'action' ) ) {
			$receptterm_id = filter_input( INPUT_GET, 'id' );
			if ( ! is_null( $receptterm_id ) ) {
				wp_delete_term( $receptterm_id, Recept::CATEGORY );
				$this->message = 'De gegevens zijn opgeslagen';
			}
		}
		$this->display->page();
	}

	/**
	 * Toon en verwerk recept term gegevens
	 *
	 * @since    6.4.0
	 */
	public function form_handler() {
		$item = wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ) ?? '', 'kleistad_receptterm' ) ? $this->update_receptterm() : $this->receptterm();
		add_meta_box( 'receptterm_form_meta_box', 'Receptterm', [ $this->display, 'form_meta_box' ], 'receptterm', 'normal' );
		$this->display->form_page( $item, 'receptterm', 'recepttermen', $this->notice, $this->message, false, [ 'hoofdterm_id' => $item['hoofdterm_id'] ] );
	}

	/**
	 * Valideer de recept term
	 *
	 * @since    6.4.0
	 * @param array $item de receptterm.
	 * @return string
	 */
	private function validate_receptterm( array $item ): string {
		$messages = [];

		if ( empty( $item['naam'] ) ) {
			$messages[] = 'Naam is verplicht';
		}
		return implode( '<br />', $messages );
	}

	/**
	 * Update de term
	 *
	 * @return array De recept term.
	 */
	private function update_receptterm() : array {
		$item         = filter_input_array(
			INPUT_POST,
			[
				'id'           => FILTER_SANITIZE_NUMBER_INT,
				'hoofdterm_id' => FILTER_SANITIZE_NUMBER_INT,
				'naam'         => FILTER_SANITIZE_STRING,
			]
		) ?: [];
		$this->notice = $this->validate_receptterm( $item );
		if ( empty( $this->notice ) ) {
			$this->message = 'De gegevens zijn opgeslagen';
			if ( $item['id'] > 0 ) {
				wp_update_term(
					$item['id'],
					Recept::CATEGORY,
					[
						'naam'   => $item['naam'],
						'parent' => $item['hoofdterm_id'],
					]
				);
				return $item;
			}
			wp_insert_term(
				$item['naam'],
				Recept::CATEGORY,
				[
					'parent' => $item['hoofdterm_id'],
				]
			);
		}
		return $item;
	}

	/**
	 * Geef de term
	 *
	 * @return array De recept term.
	 */
	private function receptterm() : array {
		$item          = [
			'id'           => 0,
			'hoofdterm_id' => filter_input( INPUT_GET, 'hoofdterm_id', FILTER_SANITIZE_NUMBER_INT ) ?: 0,
			'naam'         => '',
		];
		$receptterm_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT ) ?: 0;
		if ( $receptterm_id ) {
			$term = get_term( $receptterm_id );
			if ( ! is_wp_error( $term ) ) {
				$item = [
					'id'           => $term->term_id,
					'hoofdterm_id' => $term->parent,
					'naam'         => $term->name,
				];
			}
		}
		return $item;
	}

}
