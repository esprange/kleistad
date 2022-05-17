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
 * De admin-specifieke functies van de plugin voor saldo beheer.
 */
class Admin_Saldo_Handler extends Admin_Handler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->display = new Admin_Saldo_Display();
	}

	/**
	 * Definieer de panels
	 *
	 * @since    5.2.0
	 */
	public function add_pages() {
		add_submenu_page( 'kleistad', 'Saldo beheer', 'Saldo beheer', 'manage_options', 'saldo', [ $this->display, 'page' ] );
		add_submenu_page( 'saldo', 'Wijzigen saldo', 'Wijzigen saldo', 'manage_options', 'saldo_form', [ $this, 'form_handler' ] );
	}

	/**
	 * Toon en verwerk saldo
	 *
	 * @since    5.2.0
	 * @suppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function form_handler() {
		$item = wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ) ?? '', 'kleistad_saldo' ) ? $this->update_saldo() : $this->saldo();
		add_meta_box( 'saldo_form_meta_box', 'Saldo', [ $this->display, 'form_meta_box' ], 'saldo', 'normal' );
		$this->display->form_page( $item, 'saldo', 'saldo', $this->notice, $this->message, false );
	}

	/**
	 * Valideer de saldo
	 *
	 * @since    5.2.0
	 *
	 * @param array $item de saldo.
	 * @return string
	 */
	private function validate_saldo( array $item ): string {
		$messages = [];
		if ( ! empty( $item['saldo'] ) && ! is_numeric( $item['saldo'] ) ) {
			$messages[] = 'Kosten format is fout';
		}
		return implode( '<br />', $messages );
	}

	/**
	 * Update het saldo.
	 *
	 * @return array Het saldo.
	 */
	private function update_saldo() : array {
		$item         = filter_input_array(
			INPUT_POST,
			[
				'id'    => FILTER_SANITIZE_NUMBER_INT,
				'saldo' => [
					'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
					'flags'  => FILTER_FLAG_ALLOW_FRACTION,
				],
				'naam'  => FILTER_SANITIZE_STRING,
			]
		);
		$this->notice = $this->validate_saldo( $item );
		if ( empty( $this->notice ) ) {
			$saldo = new Saldo( $item['id'] );
			$saldo->actie->correctie( $item['saldo'] );
			$this->message = 'De gegevens zijn opgeslagen';
		}
		return $item;
	}

	/**
	 * Geef het saldo
	 *
	 * @return array Het saldo.
	 */
	private function saldo() : array {
		$gebruiker_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT ) ?? 0;
		$gebruiker    = get_userdata( $gebruiker_id );
		if ( ! $gebruiker ) {
			$this->notice = 'De gebruiker is niet gevonden';
			return [];
		}
		$saldo = new Saldo( $gebruiker_id );
		return [
			'id'    => $gebruiker_id,
			'naam'  => $gebruiker->display_name,
			'saldo' => $saldo->bedrag,
		];
	}

}
