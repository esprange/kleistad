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
 * De admin-specifieke functies van de plugin voor beheer stookkosten regelingen.
 */
class Kleistad_Admin_Regelingen_Handler {

	/**
	 * Definieer de panels
	 *
	 * @since    5.2.0
	 * @param string $plugin_name de naam.
	 */
	public function add_pages( $plugin_name ) {
		add_submenu_page( $plugin_name, 'Regeling stookkosten', 'Regeling stookkosten', 'manage_options', 'regelingen', [ $this, 'regelingen_page_handler' ] );
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
		$table   = new Kleistad_Admin_Regelingen();
		if ( 'delete' === $table->current_action() ) {
			$id = filter_input( INPUT_GET, 'id' );

			if ( ! is_null( $id ) ) {
				list($gebruiker_id, $oven_id) = sscanf( $id, '%d-%d' );
				$regelingen                   = new Kleistad_Regelingen();
				$regelingen->delete_and_save( $gebruiker_id, $oven_id );
			}
			$message = sprintf( 'Aantal verwijderd: %d', count( $id ) );
		}
		require 'partials/kleistad-admin-regelingen-page.php';
	}

	/**
	 * Toon en verwerk regelingen
	 *
	 * @since    5.2.0
	 * @suppress PhanUnusedVariable
	 */
	public function regelingen_form_page_handler() {

		$message = '';
		$notice  = '';

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
				$regelingen = new Kleistad_Regelingen();
				$result     = $regelingen->set_and_save( $item['gebruiker_id'], $item['oven_id'], $item['kosten'] );
				if ( '' === $item['id'] ) {
					if ( $result ) {
						$message = 'De regeling is bewaard';
					} else {
						$notice = 'Er was een probleem met het opslaan van gegevens';
					}
				} else {
					if ( $result ) {
						$message = 'De regeling is gewijzigd';
					} else {
						$notice = 'Er was een probleem met het wijzigen van gegevens';
					}
				}
				$oven                   = new Kleistad_Oven( $item['oven_id'] );
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
				$regelingen                   = new Kleistad_Regelingen();
				$gebruiker_regeling           = $regelingen->get( $gebruiker_id, $oven_id );

				$gebruiker = get_userdata( $gebruiker_id );
				$oven      = new Kleistad_Oven( $oven_id );
				$item      = [
					'id'             => $_REQUEST['id'],
					'gebruiker_id'   => $gebruiker_id,
					'gebruiker_naam' => $gebruiker->display_name,
					'oven_id'        => $oven_id,
					'oven_naam'      => $oven->naam,
					'kosten'         => $gebruiker_regeling,
				];
			}
		}
		add_meta_box( 'regelingen_form_meta_box', 'Regelingen', [ $this, 'regelingen_form_meta_box_handler' ], 'regeling', 'normal', 'default' );

		require 'partials/kleistad-admin-regelingen-form-page.php';
	}

	/**
	 * Toon de regeling meta box
	 *
	 * @since    5.2.0
	 *
	 * @param array $item de regeling.
	 * @suppress PhanUnusedPublicMethodParameter, PhanUnusedVariable
	 */
	public function regelingen_form_meta_box_handler( $item ) {
		$gebruikers = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => [ 'display_name' ],
			]
		);
		$ovens      = Kleistad_Oven::all();

		require 'partials/kleistad-admin-regelingen-form-meta-box.php';
	}
}
