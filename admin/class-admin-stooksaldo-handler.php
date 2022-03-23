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
class Admin_Stooksaldo_Handler extends Admin_Handler {

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
		add_submenu_page( 'stooksaldo', 'Wijzigen stooksaldo', 'Wijzigen stooksaldo', 'manage_options', 'stooksaldo_form', [ $this, 'form_handler' ] );
	}

	/**
	 * Toon en verwerk stooksaldo
	 *
	 * @since    5.2.0
	 * @suppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function form_handler() {
		$item = wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ) ?? '', 'kleistad_stooksaldo' ) ? $this->update_saldo() : $this->saldo();
		add_meta_box( 'stooksaldo_form_meta_box', 'Stooksaldo', [ $this->display, 'form_meta_box' ], 'stooksaldo', 'normal' );
		$this->display->form_page( $item, 'stooksaldo', 'stooksaldo', $this->notice, $this->message, false );
	}

	/**
	 * Valideer de stooksaldo
	 *
	 * @since    5.2.0
	 *
	 * @param array $item de stooksaldo.
	 * @return string
	 */
	private function validate_stooksaldo( array $item ): string {
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
		$this->notice = $this->validate_stooksaldo( $item );
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
