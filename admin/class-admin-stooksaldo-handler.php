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
	 * Eventuele foutmelding.
	 *
	 * @var string $notice Foutmelding.
	 */
	private string $notice = '';

	/**
	 * Of de actie uitgevoerd is.
	 *
	 * @var string $message Actie melding.
	 */
	private string $message = '';

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
	private function validate_stooksaldo( array $item ): bool|string {
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
		$item = wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ) ?? '', 'kleistad_stooksaldo' ) ? $this->update_saldo() : $this->geef_saldo();
		add_meta_box( 'stooksaldo_form_meta_box', 'Stooksaldo', [ $this->display, 'form_meta_box' ], 'stooksaldo', 'normal' );
		$this->display->form_page( $item, 'stooksaldo', 'stooksaldo', $this->notice, $this->message, false );
	}

	/**
	 * Update het saldo.
	 *
	 * @return array Het saldo.
	 */
	private function update_saldo() : array {
		$item       = filter_input_array(
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
		$item_valid = $this->validate_stooksaldo( $item );

		if ( true === $item_valid ) {
			$saldo = new Saldo( $item['id'] );
			$saldo->actie->correctie( $item['saldo'] );
			$this->message = 'De gegevens zijn opgeslagen';
		} else {
			$this->notice = $item_valid;
		}
		return $item;
	}

	/**
	 * Geef het saldo
	 *
	 * @return array Het saldo.
	 */
	private function geef_saldo() : array {
		$gebruiker_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT ) ?? 0;
		$gebruiker    = get_userdata( $gebruiker_id );
		if ( ! $gebruiker ) {
			$item         = [];
			$this->notice = 'De gebruiker is niet gevonden';
		} else {
			$saldo = new Saldo( $gebruiker_id );
			$item  = [
				'id'    => $gebruiker_id,
				'naam'  => $gebruiker->display_name,
				'saldo' => $saldo->bedrag,
			];
		}
		return $item;
	}

}
