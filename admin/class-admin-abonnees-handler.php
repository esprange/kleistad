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
 * De admin-specifieke functies van de plugin voor abonnee beheer.
 */
class Admin_Abonnees_Handler {

	/**
	 * Definieer de panels
	 *
	 * @since 5.2.0
	 */
	public function add_pages() {
		add_submenu_page( 'kleistad', 'Abonnees', 'Abonnees', 'manage_options', 'abonnees', [ $this, 'abonnees_page_handler' ] );
		add_submenu_page( 'abonnees', 'Wijzigen abonnee', 'Wijzigen abonnee', 'manage_options', 'abonnees_form', [ $this, 'abonnees_form_page_handler' ] );
	}

	/**
	 * Wijzig het abonnement.
	 *
	 * @since 5.2.0
	 * @param array  $item De informatie vanuit het formulier.
	 * @param string $actie de actie waar het om gaat.
	 * @param string $submit de subactie.
	 * @return string De status van de wijziging.
	 */
	private function wijzig_abonnee( $item, $actie, $submit ) {
		$abonnement          = new \Kleistad\Abonnement( $item['id'] );
		$vandaag             = strtotime( 'today' );
		$item['mollie_info'] = \Kleistad\Betalen::info( $item['id'] );
		switch ( $actie ) {
			case 'status':
				switch ( $submit ) {
					case 'pauzeren':
						$abonnement->pauzeren( strtotime( $item['pauze_datum'] ), strtotime( $item['herstart_datum'] ), true );
						$item['gepauzeerd'] = $vandaag >= $item['pauze_datum'];
						break;
					case 'herstarten':
						$abonnement->herstarten( strtotime( $item['herstart_datum'] ), true );
						$item['gepauzeerd'] = $vandaag < $item['herstart_datum'];
						break;
					case 'starten':
						$abonnement->start( strtotime( $item['start_datum'] ), 'stort', true );
						$item['gestart'] = $vandaag >= $item['start_datum'];
						break;
					case 'stoppen':
						$abonnement->annuleren( strtotime( $item['eind_datum'] ), true );
						$item['geannuleerd'] = $vandaag >= $item['eind_datum'];
						break;
				}
				break;
			case 'soort':
				if ( ( $abonnement->soort !== $item['soort'] ) || ( $abonnement->dag !== $item['dag'] ) ) {
					$abonnement->wijzigen( $vandaag, 'soort', $item['soort'], $item['dag'], true );
				}
				break;
			case 'extras':
				if ( $abonnement->extras !== $item['extras'] ) {
					$abonnement->wijzigen( $vandaag, 'extras', $item['extras'], '', true );
				}
				break;
			case 'mollie':
				if ( $item['mandaat'] ) {
					$abonnement->betaalwijze( $vandaag, 'stort', true );
					$item['mandaat'] = false;
				}
				break;
			default:
				break;
		}
		return 'De gegevens zijn opgeslagen';
	}

	/**
	 * Valideer de abonnee
	 *
	 * @since    5.2.0
	 *
	 * @param array  $item de abonnee.
	 * @param string $actie de actie waar het om gaat.
	 * @param string $submit de subactie.
	 * @return bool|string
	 */
	private function validate_abonnee( $item, $actie, $submit ) {
		$messages = [];

		if ( 'status' === $actie ) {
			switch ( $submit ) {
				case 'pauzeren':
					if ( false === strtotime( $item['pauze_datum'] ) ) {
						$messages[] = 'Pauze datum ontbreekt of is ongeldig';
					}
					// Bij pauzeren moet herstart_datum ook getest worden.
				case 'herstarten':
					if ( false === strtotime( $item['herstart_datum'] ) ) {
						$messages[] = 'Herstart datum ontbreekt of is ongeldig';
					}
					break;
				case 'starten':
					if ( false === strtotime( $item['start_datum'] ) ) {
						$messages[] = 'Start datum ontbreekt of is ongeldig';
					}
					break;
				case 'stoppen':
					if ( false === strtotime( $item['eind_datum'] ) ) {
						$messages[] = 'Eind datum ontbreekt of is ongeldig';
					}
					break;
				default:
					break;
			}
		}

		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br />', $messages );
	}

	/**
	 * Abonnees overzicht page handler
	 *
	 * @since    5.2.0
	 */
	public function abonnees_page_handler() {
		require 'partials/admin-abonnees-page.php';
	}

	/**
	 * Toon en verwerk ingevoerde abonnee gegevens
	 *
	 * @since    5.2.0
	 */
	public function abonnees_form_page_handler() {
		$message = '';
		$notice  = '';
		$actie   = null;
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_abonnee' ) ) {
			$item = filter_input_array(
				INPUT_POST,
				[
					'id'              => FILTER_SANITIZE_NUMBER_INT,
					'naam'            => FILTER_SANITIZE_STRING,
					'code'            => FILTER_SANITIZE_STRING,
					'soort'           => FILTER_SANITIZE_STRING,
					'dag'             => FILTER_SANITIZE_STRING,
					'gestart'         => FILTER_SANITIZE_NUMBER_INT,
					'geannuleerd'     => FILTER_SANITIZE_NUMBER_INT,
					'gepauzeerd'      => FILTER_SANITIZE_NUMBER_INT,
					'inschrijf_datum' => FILTER_SANITIZE_STRING,
					'start_datum'     => FILTER_SANITIZE_STRING,
					'pauze_datum'     => FILTER_SANITIZE_STRING,
					'eind_datum'      => FILTER_SANITIZE_STRING,
					'herstart_datum'  => FILTER_SANITIZE_STRING,
					'mandaat'         => FILTER_SANITIZE_NUMBER_INT,
					'extras'          => [
						'filter' => FILTER_SANITIZE_STRING,
						'flags'  => FILTER_REQUIRE_ARRAY,
					],
					'actie'           => FILTER_SANITIZE_STRING,
					'submit'          => FILTER_SANITIZE_STRING,
				]
			);
			if ( ! is_array( $item['extras'] ) ) {
				$item['extras'] = [];
			}
			$actie      = $item['actie'];
			$submit     = strtolower( $item['submit'] );
			$item_valid = $this->validate_abonnee( $item, $actie, $submit );
			if ( true === $item_valid ) {
				$message = $this->wijzig_abonnee( $item, $actie, $submit );
			} else {
				$notice = $item_valid;
			}
		} else {
			if ( isset( $_REQUEST['id'] ) ) {
				$abonnee_id = $_REQUEST['id'];
				$actie      = $_REQUEST['actie'];
				$abonnement = new \Kleistad\Abonnement( $abonnee_id );
				$abonnee    = get_userdata( $abonnee_id );
				$item       = [
					'id'              => $abonnee_id,
					'naam'            => $abonnee->display_name,
					'soort'           => $abonnement->soort,
					'dag'             => ( 'beperkt' === $abonnement->soort ? $abonnement->dag : '' ),
					'code'            => $abonnement->code,
					'extras'          => $abonnement->extras,
					'geannuleerd'     => $abonnement->geannuleerd,
					'gepauzeerd'      => $abonnement->gepauzeerd,
					'gestart'         => \Kleistad\Roles::reserveer( $abonnee_id ),
					'inschrijf_datum' => ( $abonnement->datum ? strftime( '%d-%m-%Y', $abonnement->datum ) : '' ),
					'start_datum'     => ( $abonnement->start_datum ? strftime( '%d-%m-%Y', $abonnement->start_datum ) : '' ),
					'pauze_datum'     => ( $abonnement->pauze_datum ? strftime( '%d-%m-%Y', $abonnement->pauze_datum ) : '' ),
					'eind_datum'      => ( $abonnement->eind_datum ? strftime( '%d-%m-%Y', $abonnement->eind_datum ) : '' ),
					'herstart_datum'  => ( $abonnement->herstart_datum ? strftime( '%d-%m-%Y', $abonnement->herstart_datum ) : '' ),
					'mandaat'         => ( '' !== $abonnement->subscriptie_id ),
					'mollie_info'     => \Kleistad\Betalen::info( $abonnee_id ),
				];
			}
		}
		add_meta_box( 'abonnees_form_meta_box', 'Abonnees', [ $this, 'abonnees_form_meta_box_handler' ], 'abonnee', 'normal', 'default', [ $actie ] );
		require 'partials/admin-abonnees-form-page.php';
	}

	/**
	 * Toon de abonnees form meta box
	 *
	 * @since    5.2.0
	 *
	 * @param array $item de abonnee.
	 * @param array $request de aanroep parameters.
	 */
	public function abonnees_form_meta_box_handler( $item, $request ) {
		$actie = $request['args'][0];
		require 'partials/admin-abonnees-form-meta-box.php';
	}

}
