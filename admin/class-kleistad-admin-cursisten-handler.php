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

/**
 * De admin-specifieke functies van de plugin voor de cursisten page.
 */
class Kleistad_Admin_Cursisten_Handler {

	/**
	 * Definieer de panels
	 *
	 * @since    5.2.0
	 * @param string $plugin_name de naam.
	 */
	public function add_pages( $plugin_name ) {
		add_submenu_page( $plugin_name, 'Cursisten', 'Cursisten', 'manage_options', 'cursisten', [ $this, 'cursisten_page_handler' ] );
		add_submenu_page( 'cursisten', 'Wijzigen cursist', 'Wijzigen cursist', 'manage_options', 'cursisten_form', [ $this, 'cursisten_form_page_handler' ] );
	}

	/**
	 * Cursisten overzicht page handler
	 *
	 * @since    5.2.0
	 */
	public function cursisten_page_handler() {
		require 'partials/kleistad-admin-cursisten-page.php';
	}

	/**
	 * Toon en verwerk ingevoerde cursist gegevens
	 *
	 * @since    5.2.0
	 * @suppress PhanUnusedVariable
	 */
	public function cursisten_form_page_handler() {
		$message = '';
		$notice  = '';
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_cursist' ) ) {
			$item                      = filter_input_array(
				INPUT_POST,
				[
					'id'          => FILTER_SANITIZE_STRING,
					'naam'        => FILTER_SANITIZE_STRING,
					'cursus_id'   => FILTER_SANITIZE_NUMBER_INT,
					'i_betaald'   => FILTER_SANITIZE_NUMBER_INT,
					'c_betaald'   => FILTER_SANITIZE_NUMBER_INT,
					'aantal'      => FILTER_SANITIZE_NUMBER_INT,
					'geannuleerd' => FILTER_SANITIZE_NUMBER_INT,
				]
			);
			$code                      = $item['id'];
			$parameters                = explode( '-', substr( $code, 1 ) );
			$cursus_id                 = intval( $parameters[0] );
			$cursist_id                = intval( $parameters[1] );
			$inschrijving              = new Kleistad_Inschrijving( $cursist_id, $cursus_id );
			$inschrijving->i_betaald   = ( 0 !== intval( $item['i_betaald'] ) );
			$inschrijving->c_betaald   = ( 0 !== intval( $item['c_betaald'] ) );
			$inschrijving->geannuleerd = ( 0 !== intval( $item['geannuleerd'] ) );
			$inschrijving->aantal      = $item['aantal'];
			if ( intval( $item['cursus_id'] ) !== $cursus_id ) {
				// cursus gewijzigd.
				$inschrijving->correct( $item['cursus_id'] );
			} else {
				// attributen inschrijving gewijzigd.
				$inschrijving->save();
			}
			$message = 'De gegevens zijn opgeslagen';
		} else {
			if ( isset( $_REQUEST['id'] ) ) {
				$code         = $_REQUEST['id'];
				$parameters   = explode( '-', substr( $code, 1 ) );
				$cursus_id    = intval( $parameters[0] );
				$cursist_id   = intval( $parameters[1] );
				$cursist      = get_userdata( $cursist_id );
				$inschrijving = new Kleistad_Inschrijving( $cursist_id, $cursus_id );
				$cursus       = new Kleistad_Cursus( $cursus_id );
				$item         = [
					'id'          => $code,
					'naam'        => $cursist->display_name,
					'aantal'      => $inschrijving->aantal,
					'i_betaald'   => $inschrijving->i_betaald,
					'c_betaald'   => $inschrijving->c_betaald,
					'geannuleerd' => $inschrijving->geannuleerd,
					'cursist_id'  => $cursist_id,
					'cursus_id'   => $cursus_id,
				];
			}
		}
		add_meta_box( 'cursisten_form_meta_box', 'Cursisten', [ $this, 'cursisten_form_meta_box_handler' ], 'cursist', 'normal', 'default' );
		require 'partials/kleistad-admin-cursisten-form-page.php';
	}

	/**
	 * Toon de cursisten form meta box
	 *
	 * @since    5.2.0
	 *
	 * @param array $item de cursist.
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function cursisten_form_meta_box_handler( $item ) {
		require 'partials/kleistad-admin-cursisten-form-meta-box.php';
	}
}
