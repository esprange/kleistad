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
 * De admin-specifieke functies van de plugin voor stooksaldo beheer.
 */
class Kleistad_Admin_Stooksaldo_Handler {

	/**
	 * Definieer de panels
	 *
	 * @since    5.2.0
	 * @param string $plugin_name de naam.
	 */
	public function add_pages( $plugin_name ) {
		add_submenu_page( $plugin_name, 'Stooksaldo beheer', 'Stooksaldo beheer', 'manage_options', 'stooksaldo', [ $this, 'stooksaldo_page_handler' ] );
		add_submenu_page( 'stooksaldo', 'Wijzigen stooksaldo', 'Wijzigen stooksaldo', 'manage_options', 'stooksaldo_form', [ $this, 'stooksaldo_form_page_handler' ] );
	}

	/**
	 * Valideer de stooksaldo
	 *
	 * @since    5.2.0
	 *
	 * @param array $item de stooksaldo.
	 * @return bool|string
	 */
	private function validate_stooksaldo( $item ) {
		$messages = [];

		if ( ! empty( $item['saldo'] ) && ! is_numeric( $item['saldo'] ) ) {
			$messages[] = 'Kosten format is fout';
		}

		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br />', $messages );
	}

	/**
	 * Overzicht stooksaldo page handler
	 *
	 * @since    5.2.0
	 * @suppress PhanUnusedVariable
	 */
	public function stooksaldo_page_handler() {
		$message = '';
		$table   = new Kleistad_Admin_Stooksaldo();
		require 'partials/kleistad-admin-stooksaldo-page.php';
	}

	/**
	 * Toon en verwerk stooksaldo
	 *
	 * @since    5.2.0
	 * @suppress PhanUnusedPublicMethodParameter, PhanUnusedVariable
	 */
	public function stooksaldo_form_page_handler() {

		$message = '';
		$notice  = '';

		$default = [
			'id'    => 0,
			'saldo' => 0,
			'naam'  => '',
		];

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_stooksaldo' ) ) {
			$item       = wp_parse_args( $_REQUEST, $default );
			$item_valid = $this->validate_stooksaldo( $item );

			if ( true === $item_valid ) {
				$saldo         = new Kleistad_Saldo( $item['id'] );
				$saldo->bedrag = $item['saldo'];
				$beheerder     = wp_get_current_user();
				$saldo->save( 'correctie door ' . $beheerder->display_name );
			} else {
				$notice = $item_valid;
			}
		} else {
			$item = $default;
			if ( isset( $_REQUEST['id'] ) ) {
				$gebruiker = get_userdata( $_REQUEST['id'] );
				if ( ! $gebruiker ) {
					$item   = $default;
					$notice = 'De gebruiker is niet gevonden';
				} else {
					$saldo = new Kleistad_saldo( $_REQUEST['id'] );
					$item  = [
						'id'    => $_REQUEST['id'],
						'naam'  => $gebruiker->display_name,
						'saldo' => $saldo->bedrag,
					];
				}
			}
		}
		add_meta_box( 'stooksaldo_form_meta_box', 'Stooksaldo', [ $this, 'stooksaldo_form_meta_box_handler' ], 'stooksaldo', 'normal', 'default' );

		require 'partials/kleistad-admin-stooksaldo-form-page.php';
	}

	/**
	 * Toon de stooksaldo meta box
	 *
	 * @since    5.2.0
	 *
	 * @param array $item de stooksaldo.
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function stooksaldo_form_meta_box_handler( $item ) {
		require 'partials/kleistad-admin-stooksaldo-form-meta-box.php';
	}
}
