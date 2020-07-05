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
		require 'partials/admin-cursisten-page.php';
	}

	/**
	 * Toon en verwerk ingevoerde cursist gegevens
	 *
	 * @since    5.2.0
	 */
	public function cursisten_form_page_handler() {
		$message  = '';
		$notice   = '';
		$single   = 'cursist';
		$multiple = 'cursisten';
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_cursist' ) ) {
			$item                       = filter_input_array(
				INPUT_POST,
				[
					'id'        => FILTER_SANITIZE_STRING,
					'naam'      => FILTER_SANITIZE_STRING,
					'cursus_id' => FILTER_SANITIZE_NUMBER_INT,
				]
			);
			$code                       = $item['id'];
			[ $cursus_id, $cursist_id ] = array_map( 'intval', explode( '-', substr( $code, 1 ) ) );
			$inschrijving               = new \Kleistad\Inschrijving( $cursus_id, $cursist_id );
			$message                    = 'De gegevens zijn opgeslagen';
			if ( (int) $item['cursus_id'] !== $cursus_id ) {
				// cursus gewijzigd.
				if ( false === $inschrijving->correct( $item['cursus_id'] ) ) {
					$message = 'Het was niet meer mogelijk om de wijziging door te voeren, de factuur is geblokkeerd';
				}
			} else {
				// attributen inschrijving gewijzigd.
				$inschrijving->save();
			}
		} else {
			if ( isset( $_REQUEST['id'] ) ) {
				$code                       = $_REQUEST['id'];
				[ $cursus_id, $cursist_id ] = array_map( 'intval', explode( '-', substr( $code, 1 ) ) );
				$inschrijving               = new \Kleistad\Inschrijving( $cursus_id, $cursist_id );
				$item                       = [
					'id'          => $code,
					'naam'        => get_userdata( $cursist_id )->display_name,
					'aantal'      => $inschrijving->aantal,
					'geannuleerd' => $inschrijving->geannuleerd,
					'cursist_id'  => $cursist_id,
					'cursus_id'   => $cursus_id,
				];
			}
		}
		add_meta_box( 'cursisten_form_meta_box', 'Cursisten', [ $this, 'cursisten_form_meta_box_handler' ], 'cursist', 'normal', 'default' );
		require 'partials/admin-form-page.php';
	}

	/**
	 * Toon de cursisten form meta box
	 *
	 * @since    5.2.0
	 *
	 * @param array $item de cursist.
	 */
	public function cursisten_form_meta_box_handler( $item ) {
		require 'partials/admin-cursisten-form-meta-box.php';
	}
}
