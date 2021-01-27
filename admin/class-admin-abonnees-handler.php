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
	 * @param array $item De informatie vanuit het formulier.
	 * @return string De status van de wijziging.
	 */
	private function wijzig_abonnee( array $item ) : string {
		$abonnement = new Abonnement( $item['id'] );
		foreach ( [ 'start_datum', 'start_eind_datum', 'pauze_datum', 'herstart_datum', 'eind_datum', 'soort', 'dag' ] as $veld ) {
			if ( ! empty( $item[ $veld ] ) ) {
				$abonnement->$veld = ( false !== strpos( $veld, 'datum' ) ) ? strtotime( $item[ $veld ] ) : $item[ $veld ];
			}
		}
		$abonnement->extras = $item['extras'];
		$abonnement->save();
		return 'De gegevens zijn opgeslagen';
	}

	/**
	 * Wijzig het mandaat van de abonnee
	 *
	 * @param array $item De informatie vanuit het formulier.
	 * @return string De status van de wijziging.
	 */
	private function wijzig_abonnee_mandaat( array $item ) : string {
		$abonnement = new Abonnement( $item['id'] );
		$abonnement->stop_incasso();
		$betalen             = new Betalen();
		$item['mollie_info'] = $betalen->info( $item['id'] );
		$item['mandaat']     = false;
		return 'De gegevens zijn opgeslagen';
	}

	/**
	 * Valideer de abonnee
	 *
	 * @since    5.2.0
	 *
	 * @param array  $item de abonnee.
	 * @return bool|string
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	private function validate_abonnee( $item ) {
		$messages = [];
		if ( ! empty( $item['start_datum'] ) && strtotime( $item['start_datum'] ) < strtotime( $item['inschrijf_datum'] ) ) {
			$messages[] = 'De start datum kan niet voor de inschrijf datum liggen';
		}
		if ( ! empty( $item['pauze_datum'] ) && ( empty( $item['start_datum'] ) || strtotime( $item['start_datum'] ) >= strtotime( $item['pauze_datum'] ) ) ) {
			$messages[] = 'De pauze datum kan niet voor de start datum liggen of de start datum ontbreekt';
		}
		if ( ! empty( $item['herstart_datum'] ) && ( empty( $item['pauze_datum'] ) || strtotime( $item['herstart_datum'] ) < strtotime( $item['pauze_datum'] ) ) ) {
			$messages[] = 'De herstart datum kan niet voor de pauze datum liggen of de pauze datum ontbreekt';
		}
		if ( ! empty( $item['start_eind_datum'] ) && ( empty( $item['start_datum'] ) || strtotime( $item['start_eind_datum'] ) < strtotime( $item['start_datum'] ) ) ) {
			$messages[] = 'De eind datum van de startperiode kan niet voor de start datum liggen of de start datum ontbreekt';
		}
		if ( ! empty( $item['eind_datum'] ) && strtotime( $item['eind_datum'] ) <= strtotime( $item['inschrijf_datum'] ) ) {
			$messages[] = 'De eind datum van het abonnement kan niet voor de inschrijf datum liggen';
		}
		if ( ! empty( $item['soort'] ) && 'beperkt' === $item['soort'] && empty( $item['dag'] ) ) {
			$messages[] = 'Als de abonnement soort beperkt is dan moet er een dag gekozen worden';
		}
		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br/>', $messages );
	}

	/**
	 * Geef de abonnee gegevens als een array
	 *
	 * @param int $abonnee_id Het WP id.
	 * @return array De abonnee gegevens.
	 */
	private function geef_abonnee( $abonnee_id ) : array {
		$abonnement = new Abonnement( $abonnee_id );
		$abonnee    = get_userdata( $abonnee_id );
		$betalen    = new Betalen();
		return [
			'id'               => $abonnee_id,
			'naam'             => $abonnee->display_name,
			'soort'            => $abonnement->soort,
			'dag'              => ( 'beperkt' === $abonnement->soort ? $abonnement->dag : '' ),
			'code'             => $abonnement->code,
			'extras'           => $abonnement->extras,
			'geannuleerd'      => $abonnement->is_geannuleerd(),
			'gepauzeerd'       => $abonnement->is_gepauzeerd(),
			'inschrijf_datum'  => strftime( '%d-%m-%Y', $abonnement->datum ),
			'start_datum'      => strftime( '%d-%m-%Y', $abonnement->start_datum ),
			'start_eind_datum' => strftime( '%d-%m-%Y', $abonnement->start_eind_datum ),
			'pauze_datum'      => ( $abonnement->pauze_datum ? strftime( '%d-%m-%Y', $abonnement->pauze_datum ) : '' ),
			'eind_datum'       => ( $abonnement->eind_datum ? strftime( '%d-%m-%Y', $abonnement->eind_datum ) : '' ),
			'herstart_datum'   => ( $abonnement->herstart_datum ? strftime( '%d-%m-%Y', $abonnement->herstart_datum ) : '' ),
			'mandaat'          => $betalen->heeft_mandaat( $abonnee_id ),
			'historie'         => $abonnement->historie ?: [],
			'mollie_info'      => $betalen->info( $abonnee_id ),
		];
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
	 *
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	public function abonnees_form_page_handler() {
		$single   = 'abonnee';
		$multiple = 'abonnees';
		$actie    = null;
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_abonnee' ) ) {
			$item = filter_input_array(
				INPUT_POST,
				[
					'id'               => FILTER_SANITIZE_NUMBER_INT,
					'naam'             => FILTER_SANITIZE_STRING,
					'code'             => FILTER_SANITIZE_STRING,
					'soort'            => FILTER_SANITIZE_STRING,
					'dag'              => FILTER_SANITIZE_STRING,
					'gestart'          => FILTER_SANITIZE_NUMBER_INT,
					'geannuleerd'      => FILTER_SANITIZE_NUMBER_INT,
					'gepauzeerd'       => FILTER_SANITIZE_NUMBER_INT,
					'inschrijf_datum'  => FILTER_SANITIZE_STRING,
					'start_datum'      => FILTER_SANITIZE_STRING,
					'start_eind_datum' => FILTER_SANITIZE_STRING,
					'pauze_datum'      => FILTER_SANITIZE_STRING,
					'eind_datum'       => FILTER_SANITIZE_STRING,
					'herstart_datum'   => FILTER_SANITIZE_STRING,
					'mandaat'          => FILTER_SANITIZE_NUMBER_INT,
					'extras'           => [
						'filter' => FILTER_SANITIZE_STRING,
						'flags'  => FILTER_REQUIRE_ARRAY,
					],
					'actie'            => FILTER_SANITIZE_STRING,
				]
			);
			if ( is_array( $item ) ) {
				if ( ! is_array( $item['extras'] ) ) {
					$item['extras'] = [];
				}
				$actie = $item['actie'];
				if ( 'status' === $actie ) {
					$item_valid = $this->validate_abonnee( $item );
					$notice     = is_string( $item_valid ) ? $item_valid : '';
					$message    = empty( $notice ) ? $this->wijzig_abonnee( $item ) : '';
				} elseif ( 'mollie' === $actie ) {
					$message = $this->wijzig_abonnee_mandaat( $item );
				}
			}
		} elseif ( isset( $_REQUEST['id'] ) ) {
			$actie   = $_REQUEST['actie'];
			$item    = $this->geef_abonnee( intval( $_REQUEST['id'] ) );
			$notice  = '';
			$message = '';
			if ( 'historie' === $actie ) {
				$display_only = true;
			}
		}
		add_meta_box( 'abonnees_form_meta_box', 'Abonnees', [ $this, 'abonnees_form_meta_box_handler' ], 'abonnee', 'normal', 'default', [ $actie ] );
		require 'partials/admin-form-page.php';
	}

	/**
	 * Toon de abonnees form meta box
	 *
	 * @since    5.2.0
	 *
	 * @param array $item de abonnee.
	 * @param array $request de aanroep parameters.
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function abonnees_form_meta_box_handler( $item, $request ) {
		$actie = $request['args'][0];
		require 'partials/admin-abonnees-form-meta-box.php';
	}

}
