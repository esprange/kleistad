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
class Admin_Cursisten_Handler {

	/**
	 * Het display object
	 *
	 * @var Admin_Cursisten_Display $display De display class.
	 */
	private Admin_Cursisten_Display $display;

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
		add_submenu_page( 'kleistad', 'Cursisten', 'Cursisten', 'manage_options', 'cursisten', [ $this, 'cursisten_page_handler' ] );
		add_submenu_page( 'cursisten', 'Wijzigen cursist', 'Wijzigen cursist', 'manage_options', 'cursisten_form', [ $this, 'cursisten_form_page_handler' ] );
	}

	/**
	 * Cursisten overzicht page handler
	 *
	 * @since    5.2.0
	 */
	public function cursisten_page_handler() {
		$this->display->page();
	}

	/**
	 * Toon en verwerk ingevoerde cursist gegevens
	 *
	 * @since    5.2.0
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 */
	public function cursisten_form_page_handler() {
		$message                        = '';
		$notice                         = '';
		$item                           = [];
		list( $cursus_id, $cursist_id ) = sscanf( $_REQUEST['id'] ?? 'C0-0', 'C%d-%d' );
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_cursist' ) ) {
			$item = filter_input_array(
				INPUT_POST,
				[
					'naam'      => FILTER_SANITIZE_STRING,
					'cursus_id' => FILTER_SANITIZE_NUMBER_INT,
					'aantal'    => FILTER_SANITIZE_NUMBER_INT,
				]
			);
			if ( ! is_array( $item ) ) {
				return;
			}
			$nieuw_cursus_id = intval( $item['cursus_id'] );
			$nieuw_aantal    = intval( $item['aantal'] );
			$message         = 'Het was niet meer mogelijk om de wijziging door te voeren, de factuur is geblokkeerd';
			$inschrijving    = new Inschrijving( $cursus_id, $cursist_id );
			if ( $nieuw_cursus_id !== $cursus_id || $nieuw_aantal !== $inschrijving->aantal ) {
				if ( $inschrijving->actie->correctie( $nieuw_cursus_id, $nieuw_aantal ) ) {
					$message   = 'De gegevens zijn opgeslagen';
					$cursus_id = $nieuw_cursus_id;
				}
			}
		}
		if ( $cursus_id ) {
			$cursist      = get_userdata( $cursist_id );
			$inschrijving = new Inschrijving( $cursus_id, $cursist_id );
			$item         = [
				'id'          => $inschrijving->code,
				'naam'        => $cursist->display_name,
				'aantal'      => $inschrijving->aantal,
				'geannuleerd' => $inschrijving->geannuleerd,
				'cursist_id'  => $cursist_id,
				'cursus_id'   => $cursus_id,
			];
		}
		add_meta_box( 'cursisten_form_meta_box', 'Cursisten', [ $this, 'cursisten_form_meta_box_handler' ], 'cursist', 'normal', 'default' );
		$this->display->form_page( $item, 'cursist', 'cursisten', $notice, $message, false );
	}

	/**
	 * Toon de cursisten form meta box
	 *
	 * @since    5.2.0
	 *
	 * @param array $item de cursist.
	 * @suppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function cursisten_form_meta_box_handler( $item ) {
		$this->display->form_meta_box( $item, '' );
	}
}
