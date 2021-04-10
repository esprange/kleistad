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
 * De admin-specifieke functies van de plugin voor stooksaldo beheer.
 */
class Admin_Stooksaldo_Handler {

	/**
	 * Het display object
	 *
	 * @var Admin_Stooksaldo_Display $display De display class.
	 */
	private Admin_Stooksaldo_Display $display;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->display = new Admin_Stooksaldo_Display();
	}

	/**
	 * Definieer de panels
	 *
	 * @since    5.2.0
	 */
	public function add_pages() {
		add_submenu_page( 'kleistad', 'Stooksaldo beheer', 'Stooksaldo beheer', 'manage_options', 'stooksaldo', [ $this->display, 'page' ] );
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
	 * Toon en verwerk stooksaldo
	 *
	 * @since    5.2.0
	 * @suppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function stooksaldo_form_page_handler() {
		$message = '';
		$notice  = '';
		$item    = [];
		$default = [
			'id'    => 0,
			'saldo' => 0,
			'naam'  => '',
		];
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_stooksaldo' ) ) {
			$item       = wp_parse_args( $_REQUEST, $default );
			$item_valid = $this->validate_stooksaldo( $item );

			if ( true === $item_valid ) {
				$saldo         = new Saldo( $item['id'] );
				$saldo->bedrag = $item['saldo'];
				$beheerder     = wp_get_current_user();
				$saldo->reden  = 'correctie door ' . $beheerder->display_name;
				$saldo->save();
				$message = 'De gegevens zijn opgeslagen';
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
					$saldo = new Saldo( $_REQUEST['id'] );
					$item  = [
						'id'    => $_REQUEST['id'],
						'naam'  => $gebruiker->display_name,
						'saldo' => $saldo->bedrag,
					];
				}
			}
		}
		add_meta_box( 'stooksaldo_form_meta_box', 'Stooksaldo', [ $this->display, 'form_meta_box' ], 'stooksaldo', 'normal', 'default' );
		$this->display->form_page( $item, 'stooksaldo', 'stooksaldo', $notice, $message, false );
	}

}
