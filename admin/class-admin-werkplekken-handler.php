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
	 * Valideer de werkplek
	 *
	 * @since    6.11.0
	 * @param array $item de werkplek.
	 * @return bool|string
	 */
	private function validate_werkplek( $item ) {
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
			if ( $item['nieuwste_config'] ) {
				$messages[] = 'De eerste configuratie kan geen eind datum bevatten';
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
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function werkplekken_page_handler() {
		$message = '';
		$table   = new Admin_Werkplekken();
		if ( 'delete' === $table->current_action() ) {
			$werkplekconfigs = new WerkplekConfigs();
			$werkplekconfig  = $werkplekconfigs->find_by_date( intval( filter_input( INPUT_GET, 'start_datum' ) ), intval( filter_input( INPUT_GET, 'eind_datum' ) ) );
			$werkplekconfigs->verwijder( $werkplekconfig );
			$message = 'De werkplek configuratie is verwijderd';
		}
		require 'partials/admin-werkplekken-page.php';
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
		$message  = '';
		$notice   = '';
		$single   = 'werkplek';
		$multiple = 'werkplekken';
		$item     = [];
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_werkplek' ) ) {
			$item       = filter_input_array(
				INPUT_POST,
				[
					'start_datum'     => FILTER_SANITIZE_STRING,
					'eind_datum'      => FILTER_SANITIZE_STRING,
					'config'          => [
						'filter' => FILTER_SANITIZE_STRING,
						'flags'  => FILTER_FORCE_ARRAY,
					],
					'nieuwste_config' => FILTER_SANITIZE_NUMBER_INT,
				]
			);
			$item_valid = $this->validate_werkplek( $item );
			$notice     = is_string( $item_valid ) ? $item_valid : '';
			if ( true === $item_valid ) {
				$werkplekconfigs       = new WerkplekConfigs();
				$start_datum           = strtotime( $item['start_datum'] );
				$eind_datum            = $item['eind_datum'] ? strtotime( $item['eind_datum'] ) : 0;
				$werkplek              = $werkplekconfigs->find_by_date( $start_datum, $eind_datum );
				$werkplek->start_datum = $start_datum;
				$werkplek->eind_datum  = $eind_datum;
				$werkplek->config      = $item['config'];
				$werkplekconfigs->toevoegen( $werkplek );
				$message = 'De gegevens zijn opgeslagen';
			}
		} else {
			$werkplekconfigs = new WerkplekConfigs();
			$werkplek   = $werkplekconfigs->find_by_date( intval( $_REQUEST['start_datum'] ), intval( $_REQUEST['eind_datum'] ) );
			$item['start_datum']     = date( 'd-m-Y', $werkplek->start_datum );
			$item['eind_datum']      = $werkplek->eind_datum ? date( 'd-m-Y', $werkplek->eind_datum ) : '';
			$item['config']          = $werkplek->config;
			$item['nieuwste_config'] = 0 === count( $werkplekconfigs ) || 0 === $werkplek->eind_datum;
		}
		add_meta_box( 'werkplekken_form_meta_box', 'Werkplekken', [ $this, 'werkplekken_form_meta_box_handler' ], 'werkplek', 'normal', 'default' );
		require 'partials/admin-form-page.php';
	}

	/**
	 * Toon het werkplek formulier in een meta box
	 *
	 * @param array $item de werkplek.
	 * @suppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function werkplekken_form_meta_box_handler( $item ) {
		require 'partials/admin-werkplekken-form-meta-box.php';
	}
}
