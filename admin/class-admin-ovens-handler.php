<?php
/**
 * De admin functies van de kleistad plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      5.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * De admin-specifieke functies van de plugin voor de oven page.
 */
class Admin_Ovens_Handler {

	/**
	 * Valideer de oven
	 *
	 * @since    5.2.0
	 * @param array $item de oven.
	 * @return bool|string
	 */
	private function validate_oven( $item ) {
		$messages = [];

		if ( empty( $item['naam'] ) ) {
			$messages[] = 'Naam is verplicht';
		}
		if ( ! empty( $item['kosten'] ) && ! is_numeric( $item['kosten'] ) ) {
			$messages[] = 'Kosten format is fout';
		}
		if ( ! empty( $item['kosten'] ) && ! absint( intval( $item['kosten'] ) ) ) {
			$messages[] = 'Kosten kunnen niet kleiner zijn dan 0';
		}
		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br />', $messages );
	}

	/**
	 * Definieer de panels
	 *
	 * @since    5.2.0
	 */
	public function add_pages() {
		add_submenu_page( 'kleistad', 'Ovens', 'Ovens', 'manage_options', 'ovens', [ $this, 'ovens_page_handler' ] );
		add_submenu_page( 'ovens', 'Toevoegen oven', 'Toevoegen oven', 'manage_options', 'ovens_form', [ $this, 'ovens_form_page_handler' ] );
	}

	/**
	 * Ovens overzicht page handler
	 *
	 * @since    5.2.0
	 */
	public function ovens_page_handler() {
		require 'partials/admin-ovens-page.php';
	}

	/**
	 * Toon en verwerk oven gegevens
	 *
	 * @since    5.2.0
	 */
	public function ovens_form_page_handler() {
		$message = '';
		$notice  = '';
		$item    = [];
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_oven' ) ) {
			$item       = filter_input_array(
				INPUT_POST,
				[
					'id'              => FILTER_SANITIZE_NUMBER_INT,
					'naam'            => FILTER_SANITIZE_STRING,
					'kosten'          => [
						'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
						'flags'  => FILTER_FLAG_ALLOW_FRACTION,
					],
					'beschikbaarheid' => [
						'filter' => FILTER_SANITIZE_STRING,
						'flags'  => FILTER_FORCE_ARRAY,
					],
				]
			);
			$item_valid = $this->validate_oven( $item );
			if ( true === $item_valid ) {
				if ( $item['id'] > 0 ) {
					$oven = new \Kleistad\Oven( $item['id'] );
				} else {
					$oven = new \Kleistad\Oven();
				}
				$oven->naam            = $item['naam'];
				$oven->kosten          = $item['kosten'];
				$oven->beschikbaarheid = $item['beschikbaarheid'];
				$oven->save();
				$message = 'De gegevens zijn opgeslagen';
			} else {
				$notice = $item_valid;
			}
		} else {
			if ( isset( $_REQUEST['id'] ) ) {
				$oven = new \Kleistad\Oven( $_REQUEST['id'] );
			} else {
				$oven = new \Kleistad\Oven();
			}
			$item['id']              = $oven->id;
			$item['naam']            = $oven->naam;
			$item['kosten']          = $oven->kosten;
			$item['beschikbaarheid'] = $oven->beschikbaarheid;
		}
		add_meta_box( 'ovens_form_meta_box', 'Ovens', [ $this, 'ovens_form_meta_box_handler' ], 'oven', 'normal', 'default' );
		require 'partials/admin-ovens-form-page.php';
	}

	/**
	 * Toon het oven formulier in een meta box
	 *
	 * @param array $item de oven.
	 */
	public function ovens_form_meta_box_handler( $item ) {
		require 'partials/admin-ovens-form-meta-box.php';
	}
}