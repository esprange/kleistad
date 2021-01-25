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
			$werkplekconfig  = $werkplekconfigs->find( intval( filter_input( INPUT_GET, 'start_datum' ) ), intval( filter_input( INPUT_GET, 'eind_datum' ) ) );
			if ( is_object( $werkplekconfig ) ) {
				$werkplekconfigs->verwijder( $werkplekconfig );
				$message = 'De werkplek configuratie is verwijderd';
			}
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
			$item = filter_input_array(
				INPUT_POST,
				[
					'start_datum'     => FILTER_SANITIZE_STRING,
					'eind_datum'      => FILTER_SANITIZE_STRING,
					'config'          => [
						'filter' => FILTER_SANITIZE_STRING,
						'flags'  => FILTER_FORCE_ARRAY,
					],
					'meesters'        => [
						'filter' => FILTER_SANITIZE_STRING,
						'flags'  => FILTER_FORCE_ARRAY,
					],
					'nieuwste_config' => FILTER_SANITIZE_NUMBER_INT,
				]
			);
			if ( is_array( $item ) ) {
				$item_valid = $this->validate_werkplek( $item );
				$notice     = is_string( $item_valid ) ? $item_valid : '';
				if ( true === $item_valid ) {
					$werkplekconfigs             = new WerkplekConfigs();
					$start_datum                 = strtotime( $item['start_datum'] );
					$eind_datum                  = $item['eind_datum'] ? strtotime( $item['eind_datum'] ) : 0;
					$werkplekconfig              = $werkplekconfigs->find( $start_datum, $eind_datum );
					if ( ! is_object( $werkplekconfig ) ) {
						$werkplekconfig = new WerkplekConfig();
					}
					$werkplekconfig->start_datum = $start_datum;
					$werkplekconfig->eind_datum  = $eind_datum;
					$werkplekconfig->config      = $this->int_array( $item['config'] );
					$werkplekconfig->meesters    = $this->int_array( $item['meesters'] );
					$werkplekconfigs->toevoegen( $werkplekconfig );
					$message = 'De gegevens zijn opgeslagen';
				}
			}
		} else { // Bestaande config opvragen of nieuwe toevoegen.
			$werkplekconfigs = new WerkplekConfigs();
			$table           = new Admin_Werkplekken();
			if ( 'copy' === $table->current_action() ) {
				$werkplekconfig = $werkplekconfigs->find( intval( filter_input( INPUT_GET, 'start_datum' ) ), intval( filter_input( INPUT_GET, 'eind_datum' ) ) );
			} else {
				$werkplekconfig = isset( $_REQUEST['start_datum'] ) && isset( $_REQUEST['eind_datum'] ) ?
					$werkplekconfigs->find( intval( $_REQUEST['start_datum'] ), intval( $_REQUEST['eind_datum'] ) ) :
					new WerkplekConfig();
			}
			$item['start_datum']     = date( 'd-m-Y', $werkplekconfig->start_datum );
			$item['eind_datum']      = $werkplekconfig->eind_datum ? date( 'd-m-Y', $werkplekconfig->eind_datum ) : '';
			$item['config']          = $werkplekconfig->config;
			$item['meesters']        = $werkplekconfig->meesters;
			$item['nieuwste_config'] = 0 === count( $werkplekconfigs );
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
