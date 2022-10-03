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
class Admin_Ovens_Handler extends Admin_Handler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->display = new Admin_Ovens_Display();
	}

	/**
	 * Definieer de panels
	 *
	 * @since    5.2.0
	 */
	public function add_pages() : void {
		add_submenu_page( 'kleistad', 'Ovens', 'Ovens', 'manage_options', 'ovens', [ $this->display, 'page' ] );
		add_submenu_page( 'ovens', 'Toevoegen oven', 'Toevoegen oven', 'manage_options', 'ovens_form', [ $this, 'form_handler' ] );
	}

	/**
	 * Toon en verwerk oven gegevens
	 *
	 * @since    5.2.0
	 */
	public function form_handler() : void {
		$item = wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ) ?? '', 'kleistad_oven' ) ? $this->update_oven() : $this->oven();
		add_meta_box( 'ovens_form_meta_box', 'Ovens', [ $this->display, 'form_meta_box' ], 'oven', 'normal' );
		$this->display->form_page( $item, 'oven', 'ovens', $this->notice, $this->message, false );
	}

	/**
	 * Valideer de oven
	 *
	 * @since    5.2.0
	 * @param array $item de oven.
	 * @return string
	 */
	private function validate_oven( array $item ): string {
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
		return implode( '<br />', $messages );
	}

	/**
	 * Update de oven.
	 *
	 * @return array De oven.
	 */
	private function update_oven() : array {
		$item         = filter_input_array(
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
		$this->notice = $this->validate_oven( $item );
		if ( empty( $this->notice ) ) {
			$oven                  = $item['id'] > 0 ? new Oven( $item['id'] ) : new Oven();
			$oven->naam            = $item['naam'];
			$oven->kosten_laag     = $item['kosten_laag'];
			$oven->kosten_midden   = $item['kosten_midden'];
			$oven->kosten_hoog     = $item['kosten_hoog'];
			$oven->beschikbaarheid = $item['beschikbaarheid'];
			$oven->save();
			$this->message = 'De gegevens zijn opgeslagen';
		}
		return $item;
	}

	/**
	 * Geef de oven.
	 *
	 * @return array De oven.
	 */
	private function oven() : array {
		$oven_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
		$oven    = $oven_id ? new Oven( $oven_id ) : new Oven();
		return [
			'id'              => $oven->id,
			'naam'            => $oven->naam,
			'kosten_laag'     => $oven->kosten_laag,
			'kosten_midden'   => $oven->kosten_midden,
			'kosten_hoog'     => $oven->kosten_hoog,
			'beschikbaarheid' => $oven->beschikbaarheid,
		];
	}
}
