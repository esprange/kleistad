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
 * De admin-specifieke functies van de plugin voor de cursisten page.
 */
class Admin_Cursisten_Handler extends Admin_Handler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->display = new Admin_Cursisten_Display();
	}

	/**
	 * Definieer de panels
	 *
	 * @since    5.2.0
	 */
	public function add_pages() {
		add_submenu_page( 'kleistad', 'Cursisten', 'Cursisten', 'manage_options', 'cursisten', [ $this->display, 'page' ] );
		add_submenu_page( 'cursisten', 'Wijzigen cursist', 'Wijzigen cursist', 'manage_options', 'cursisten_form', [ $this, 'form_handler' ] );
	}

	/**
	 * Toon en verwerk ingevoerde cursist gegevens
	 *
	 * @since    5.2.0
	 */
	public function form_handler() {
		$item = wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ) ?? '', 'kleistad_cursist' ) ? $this->update_cursist() : $this->cursist();
		add_meta_box( 'cursisten_form_meta_box', 'Cursisten', [ $this->display, 'form_meta_box' ], 'cursist', 'normal' );
		$this->display->form_page( $item, 'cursist', 'cursisten', $this->notice, $this->message, false );
	}

	/**
	 * Verwerk de nieuwe gegevens.
	 *
	 * @return array Het item.
	 */
	private function update_cursist() : array {
		$item = filter_input_array(
			INPUT_POST,
			[
				'id'        => FILTER_SANITIZE_STRING,
				'naam'      => FILTER_SANITIZE_STRING,
				'cursus_id' => FILTER_SANITIZE_NUMBER_INT,
				'aantal'    => FILTER_SANITIZE_NUMBER_INT,
			]
		) ?: [];
		sscanf( $item['id'] ?? 'C0-0', 'C%d-%d', $cursus_id, $cursist_id );
		$nieuw_cursus_id = intval( $item['cursus_id'] );
		$nieuw_aantal    = intval( $item['aantal'] );
		$this->message   = '';
		$inschrijving    = new Inschrijving( $cursus_id, $cursist_id );
		if ( $inschrijving->actie->correctie( $nieuw_cursus_id, $nieuw_aantal ) ) {
			$this->message = 'De gegevens zijn opgeslagen';
		}
		return $item;
	}

	/**
	 * Geef de cursist gegevens als een array
	 *
	 * @return array De cursist gegevens.
	 */
	private function cursist() : array {
		sscanf( filter_input( INPUT_GET, 'id' ) ?? 'C0-0', 'C%d-%d', $cursus_id, $cursist_id );
		$cursist      = get_userdata( $cursist_id );
		$inschrijving = new Inschrijving( $cursus_id, $cursist_id );
		return [
			'id'          => $inschrijving->code,
			'naam'        => $cursist->display_name,
			'aantal'      => $inschrijving->aantal,
			'geannuleerd' => $inschrijving->geannuleerd,
			'cursist_id'  => $cursist_id,
			'cursus_id'   => $cursus_id,
		];
	}
}
