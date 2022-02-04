<?php
/**
 * De admin functies van de kleistad plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * De admin-specifieke functies van de plugin voor de werkplekken page.
 */
class Admin_Werkplekken_Handler {

	/**
	 * Het display object
	 *
	 * @var Admin_Werkplekken_Display $display De display class.
	 */
	private Admin_Werkplekken_Display $display;

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
		$this->display = new Admin_Werkplekken_Display();
	}

	/**
	 * Valideer de werkplek
	 *
	 * @since    6.11.0
	 * @param array $item de werkplek.
	 * @return bool|string
	 */
	private function validate_werkplek( array $item ): bool|string {
		$messages = [];

		$start_datum = strtotime( $item['start_datum'] );
		if ( false === $start_datum ) {
			$messages[] = 'De start datum is ongeldig';
		}
		if ( $item['eind_datum'] ) {
			$eind_datum = strtotime( $item['eind_datum'] );
			if ( false === $eind_datum ) {
				$messages[] = 'De eind datum is ongeldig';
			}
			if ( $start_datum && $eind_datum && $eind_datum <= $start_datum ) {
				$messages[] = 'De eind datum kan niet voor de start datum liggen';
			}
		}
		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br />', $messages );
	}

	/**
	 * Definieer de panels
	 *
	 * @since    6.11.0
	 */
	public function add_pages() {
		add_submenu_page( 'kleistad', 'Werkplekken', 'Werkplekken', 'manage_options', 'werkplekken', [ $this, 'werkplekken_page_handler' ] );
		add_submenu_page( 'werkplekken', 'Toevoegen werkplekbeschikbaarheid', 'Toevoegen werkplekbeschikbaarheid', 'manage_options', 'werkplekken_form', [ $this, 'werkplekken_form_page_handler' ] );
	}

	/**
	 * Werkplekken overzicht page handler
	 *
	 * @since    6.11.0
	 */
	public function werkplekken_page_handler() {
		if ( wp_verify_nonce( filter_input( INPUT_GET, 'nonce' ) ?? '', 'kleistad_werkplek' ) &&
			'delete' === filter_input( INPUT_GET, 'action' ) ) {
			$start_datum = filter_input( INPUT_GET, 'start_datum' );
			$eind_datum  = filter_input( INPUT_GET, 'eind_datum' );
			if ( ! is_null( $start_datum ) && ! is_null( $eind_datum ) ) {
				$werkplekconfigs = new WerkplekConfigs();
				$werkplekconfig  = $werkplekconfigs->find( intval( $start_datum ), intval( $eind_datum ) );
				if ( is_object( $werkplekconfig ) ) {
					$werkplekconfigs->verwijder( $werkplekconfig );
				}
			}
		}
		$this->display->page();
	}

	/**
	 * Toon en verwerk werkplek gegevens
	 *
	 * @since    6.11.0
	 *
	 * @suppressWarnings(PHPMD.UnusedLocalVariable)
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	public function werkplekken_form_page_handler() {
		$item = wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ) ?? '', 'kleistad_werkplek' ) ? $this->update_werkplek() : $this->geef_werkplek();
		add_meta_box( 'werkplekken_form_meta_box', 'Werkplekken', [ $this->display, 'form_meta_box' ], 'werkplek', 'normal' );
		$this->display->form_page( $item, 'werkplek', 'werkplekken', $this->notice, $this->message, false );
	}

	/**
	 * Update de werkplek
	 *
	 * @return array De werkplek.
	 */
	private function update_werkplek() : array {
		$item         = filter_input_array(
			INPUT_POST,
			[
				'start_datum' => FILTER_SANITIZE_STRING,
				'eind_datum'  => FILTER_SANITIZE_STRING,
				'config'      => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_FORCE_ARRAY,
				],
				'config_eind' => FILTER_SANITIZE_STRING,
				'meesters'    => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_FORCE_ARRAY,
				],
			]
		) ?: [];
		$item_valid   = $this->validate_werkplek( $item );
		$this->notice = is_string( $item_valid ) ? $item_valid : '';
		if ( true === $item_valid ) {
			$werkplekconfigs = new WerkplekConfigs();
			$start_datum     = strtotime( $item['start_datum'] );
			$eind_datum      = $item['eind_datum'] ? strtotime( $item['eind_datum'] ) : 0;
			$werkplekconfig  = $werkplekconfigs->find( $start_datum, $eind_datum );
			if ( ! is_object( $werkplekconfig ) ) {
				$werkplekconfig = new WerkplekConfig();
			}
			$werkplekconfig->start_datum = $start_datum;
			$werkplekconfig->eind_datum  = $eind_datum;
			$werkplekconfig->config      = $this->int_array( $item['config'] );
			$werkplekconfig->meesters    = $this->int_array( $item['meesters'] );
			$werkplekconfigs->toevoegen( $werkplekconfig );
			$this->message = 'De gegevens zijn opgeslagen';
		}
		return $item;
	}

	/**
	 * Geef de werkplek
	 *
	 * @return array De werkplek.
	 */
	private function geef_werkplek() : array {
		$params          = filter_input_array(
			INPUT_GET,
			[
				'start_datum' => FILTER_SANITIZE_STRING,
				'eind_datum'  => FILTER_SANITIZE_STRING,
			],
			false
		);
		$bestaatreeds    = ! empty( $params );
		$werkplekconfigs = new WerkplekConfigs();
		$werkplekconfig  = $bestaatreeds ? $werkplekconfigs->find( intval( $params['start_datum'] ), intval( $params['eind_datum'] ) ) : new WerkplekConfig();
		return [
			'start_datum' => strftime( '%d-%m-%Y', $werkplekconfig->start_datum ),
			'eind_datum'  => $werkplekconfig->eind_datum ? strftime( '%d-%m-%Y', $werkplekconfig->eind_datum ) : '',
			'config'      => $werkplekconfig->config,
			'meesters'    => $werkplekconfig->meesters,
			'config_eind' => 0 === count( $werkplekconfigs ) || ( $bestaatreeds && 0 === $werkplekconfig->eind_datum ),
		];
	}

	/**
	 * Converteer een multidimension array met string waarden naar int
	 *
	 * @param  array $array Het array.
	 * @return array
	 */
	private function int_array( array $array ) : array {
		array_walk(
			$array,
			function ( &$element ) {
				$element = is_array( $element ) ? $this->int_array( $element ) : intval( $element );
			}
		);
		return $array;
	}
}
