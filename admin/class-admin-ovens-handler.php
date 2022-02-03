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
	 * Het display object
	 *
	 * @var Admin_Ovens_Display $display De display class.
	 */
	private Admin_Ovens_Display $display;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->display = new Admin_Ovens_Display();
	}

	/**
	 * Valideer de oven
	 *
	 * @since    5.2.0
	 * @param array $item de oven.
	 * @return bool|string
	 */
	private function validate_oven( array $item ): bool|string {
		$messages = [];

		if ( empty( $item['naam'] ) ) {
			$messages[] = 'Naam is verplicht';
		}
		foreach ( [ 'laag', 'midden', 'hoog' ] as $range ) {
			if ( ! empty( $item[ "kosten$range" ] ) && ! is_numeric( $item[ "kosten$range" ] ) ) {
				$messages[] = "Kosten $range format is fout";
			}
			if ( ! empty( $item[ "kosten$range" ] ) && ! absint( intval( $item[ "kosten$range" ] ) ) ) {
				$messages[] = 'Kosten $range kunnen niet kleiner zijn dan 0';
			}
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
		add_submenu_page( 'kleistad', 'Ovens', 'Ovens', 'manage_options', 'ovens', [ $this->display, 'page' ] );
		add_submenu_page( 'ovens', 'Toevoegen oven', 'Toevoegen oven', 'manage_options', 'ovens_form', [ $this, 'ovens_form_page_handler' ] );
	}

	/**
	 * Toon en verwerk oven gegevens
	 *
	 * @since    5.2.0
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	public function ovens_form_page_handler() {
		$message = '';
		$notice  = '';
		$item    = [];
		if ( wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ) ?? '', 'kleistad_oven' ) ) {
			$item       = filter_input_array(
				INPUT_POST,
				[
					'id'              => FILTER_SANITIZE_NUMBER_INT,
					'naam'            => FILTER_SANITIZE_STRING,
					'kosten_laag'     => [
						'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
						'flags'  => FILTER_FLAG_ALLOW_FRACTION,
					],
					'kosten_midden'   => [
						'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
						'flags'  => FILTER_FLAG_ALLOW_FRACTION,
					],
					'kosten_hoog'     => [
						'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
						'flags'  => FILTER_FLAG_ALLOW_FRACTION,
					],
					'beschikbaarheid' => [
						'filter' => FILTER_SANITIZE_STRING,
						'flags'  => FILTER_FORCE_ARRAY,
					],
				]
			) ?: [];
			$item_valid = $this->validate_oven( $item );
			$notice     = is_string( $item_valid ) ? $item_valid : '';
			if ( true === $item_valid ) {
				$oven                  = $item['id'] > 0 ? new Oven( $item['id'] ) : new Oven();
				$oven->naam            = $item['naam'];
				$oven->kosten_laag     = $item['kosten_laag'];
				$oven->kosten_midden   = $item['kosten_midden'];
				$oven->kosten_hoog     = $item['kosten_hoog'];
				$oven->beschikbaarheid = $item['beschikbaarheid'];
				$oven->save();
				$message = 'De gegevens zijn opgeslagen';
			}
		} else {
			$id                      = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
			$oven                    = $id ? new Oven( $id ) : new Oven();
			$item['id']              = $oven->id;
			$item['naam']            = $oven->naam;
			$item['kosten_laag']     = $oven->kosten_laag;
			$item['kosten_midden']   = $oven->kosten_midden;
			$item['kosten_hoog']     = $oven->kosten_hoog;
			$item['beschikbaarheid'] = $oven->beschikbaarheid;
		}
		add_meta_box( 'ovens_form_meta_box', 'Ovens', [ $this->display, 'form_meta_box' ], 'oven', 'normal' );
		$this->display->form_page( $item, 'oven', 'ovens', $notice, $message, false );
	}

}
