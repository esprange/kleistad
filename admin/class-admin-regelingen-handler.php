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
 * De admin-specifieke functies van de plugin voor beheer stookkosten regelingen.
 */
class Admin_Regelingen_Handler {

	/**
	 * Definieer de panels
	 *
	 * @since    5.2.0
	 */
	public function add_pages() {
		add_submenu_page( 'kleistad', 'Regeling stookkosten', 'Regeling stookkosten', 'manage_options', 'regelingen', [ $this, 'regelingen_page_handler' ] );
		add_submenu_page( 'regelingen', 'Toevoegen regeling', 'Toevoegen regeling', 'manage_options', 'regelingen_form', [ $this, 'regelingen_form_page_handler' ] );
	}

	/**
	 * Valideer de regeling
	 *
	 * @since    5.2.0
	 *
	 * @param array $item the regeling.
	 * @return bool|string
	 */
	private function validate_regeling( $item ) {
		$messages = [];
		if ( ! empty( $item['gebruiker_id'] ) && ! is_numeric( $item['gebruiker_id'] ) ) {
			$messages[] = 'Geen gebruiker gekozen';
		}
		if ( ! empty( $item['oven_id'] ) && ! is_numeric( $item['oven_id'] ) ) {
			$messages[] = 'Geen oven gekozen';
		}
		if ( ! empty( $item['kosten'] ) && ! is_numeric( $item['kosten'] ) ) {
			$messages[] = 'Kosten format is fout';
		}
		if ( ! empty( $item['kosten'] ) && ! ( 0.0 <= floatval( $item['kosten'] ) ) ) {
			$messages[] = 'Kosten kunnen niet kleiner zijn dan 0';
		}
		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br />', $messages );
	}
	/**
	 * Overzicht regelingen page handler
	 *
	 * @since    5.2.0
	 */
	public function regelingen_page_handler() {
		$message = '';
		$table   = new \Kleistad\Admin_Regelingen();
		if ( 'delete' === $table->current_action() ) {
			$id = filter_input( INPUT_GET, 'id' );

			if ( ! is_null( $id ) ) {
				list($gebruiker_id, $oven_id) = sscanf( $id, '%d-%d' );
				$regelingen                   = get_user_meta( $gebruiker_id, \Kleistad\Oven::REGELING, true );
				unset( $regelingen[ $oven_id ] );
				if ( empty( $regelingen ) ) {
					delete_user_meta( $gebruiker_id, \Kleistad\Oven::REGELING );
				} else {
					update_user_meta( $gebruiker_id, \Kleistad\Oven::REGELING, $regelingen );
				}
				$message = 'De gegevens zijn opgeslagen';
			}
		}
		require 'partials/admin-regelingen-page.php';
	}

	/**
	 * Toon en verwerk regelingen
	 *
	 * @since    5.2.0
	 */
	public function regelingen_form_page_handler() {

		$message  = '';
		$notice   = '';
		$single   = 'regeling';
		$multiple = 'regelingen';

		$default = [
			'id'             => '',
			'gebruiker_id'   => 0,
			'oven_id'        => 0,
			'oven_naam'      => '',
			'gebruiker_naam' => '',
			'kosten'         => 0,
		];

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_regeling' ) ) {
			$item       = wp_parse_args( $_REQUEST, $default );
			$item_valid = $this->validate_regeling( $item );
			if ( true === $item_valid ) {
				$gebruiker_regelingen = get_user_meta( $item['gebruiker_id'], \Kleistad\Oven::REGELING, true );
				if ( empty( $gebruiker_regelingen ) ) {
					$gebruiker_regelingen = [];
				}
				$gebruiker_regelingen[ $item['oven_id'] ] = $item['kosten'];
				update_user_meta( $item['gebruiker_id'], \Kleistad\Oven::REGELING, $gebruiker_regelingen );
				if ( '' === $item['id'] ) {
					$message = 'De regeling is bewaard';
				} else {
					$message = 'De regeling is gewijzigd';
				}
				$oven                   = new \Kleistad\Oven( $item['oven_id'] );
				$gebruiker              = get_userdata( $item['gebruiker_id'] );
				$item['gebruiker_naam'] = $gebruiker->display_name;
				$item['oven_naam']      = $oven->naam;
			} else {
				$notice = $item_valid;
			}
		} else {
			$item = $default;
			if ( isset( $_REQUEST['id'] ) ) {
				list($gebruiker_id, $oven_id) = sscanf( $_REQUEST['id'], '%d-%d' );
				$gebruiker_regelingen         = get_user_meta( $gebruiker_id, \Kleistad\Oven::REGELING, true );

				$gebruiker = get_userdata( $gebruiker_id );
				$oven      = new \Kleistad\Oven( $oven_id );
				$item      = [
					'id'             => $_REQUEST['id'],
					'gebruiker_id'   => $gebruiker_id,
					'gebruiker_naam' => $gebruiker->display_name,
					'oven_id'        => $oven_id,
					'oven_naam'      => $oven->naam,
					'kosten'         => $gebruiker_regelingen[ $oven_id ],
				];
			}
		}
		add_meta_box( 'regelingen_form_meta_box', 'Regelingen', [ $this, 'regelingen_form_meta_box_handler' ], 'regeling', 'normal', 'default' );

		require 'partials/admin-form-page.php';
	}

	/**
	 * Toon de regeling meta box
	 *
	 * @since    5.2.0
	 *
	 * @param array $item de regeling.
	 */
	public function regelingen_form_meta_box_handler( $item ) {
		$gebruikers = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => [ 'display_name' ],
			]
		);
		$ovens      = \Kleistad\Oven::all();

		require 'partials/admin-regelingen-form-meta-box.php';
	}
}
