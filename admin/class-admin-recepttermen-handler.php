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
	private function validate_receptterm( $item ) {
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
		$message = '';
		$notice  = '';
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_receptterm' ) ) {
			$item = filter_input_array(
				INPUT_POST,
				[
					'id'           => FILTER_SANITIZE_NUMBER_INT,
					'hoofdterm_id' => FILTER_SANITIZE_NUMBER_INT,
					'naam'         => FILTER_SANITIZE_STRING,
				]
			);
			if ( ! is_array( $item ) ) {
				return;
			}
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
				$message = 'De gegevens zijn opgeslagen';
			} else {
				$notice = $item_valid;
			}
		} else {
			$item = [
				'id'   => 0,
				'naam' => '',
			];
			if ( isset( $_REQUEST['id'] ) ) {
				if ( isset( $_REQUEST['delete'] ) ) {
					wp_delete_term( $_REQUEST['id'], Recept::CATEGORY );
					$message = 'De gegevens zijn opgeslagen';
				} else {
					$term = get_term( $_REQUEST['id'] );
					if ( ! is_wp_error( $term ) ) {
						$item = [
							'id'   => $term->term_id,
							'naam' => $term->name,
						];
					}
				}
			}
		}
		add_meta_box( 'receptterm_form_meta_box', 'receptterm', [ $this->display, 'form_meta_box' ], 'receptterm', 'normal', 'default' );
		$this->display->form_page( $item, 'receptterm', 'recepttermen', $notice, $message, false );
	}

}
