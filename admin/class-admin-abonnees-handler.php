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

use Exception;
use Mollie\Api\Exceptions\ApiException;

/**
 * De admin-specifieke functies van de plugin voor abonnee beheer.
 */
class Admin_Abonnees_Handler {

	/**
	 * Het display object
	 *
	 * @var Admin_Abonnees_Display $display De display class.
	 */
	private Admin_Abonnees_Display $display;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->display = new Admin_Abonnees_Display();
	}

	/**
	 * Definieer de panels
	 *
	 * @since 5.2.0
	 */
	public function add_pages() {
		add_submenu_page( 'kleistad', 'Abonnees', 'Abonnees', 'manage_options', 'abonnees', [ $this->display, 'page' ] );
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
		foreach ( [ 'start_datum', 'start_eind_datum', 'pauze_datum', 'herstart_datum', 'eind_datum', 'soort' ] as $veld ) {
			if ( ! empty( $item[ $veld ] ) ) {
				$abonnement->$veld = str_contains( $veld, 'datum' ) ? strtotime( $item[ $veld ] ) : $item[ $veld ];
			}
		}
		$abonnement->reguliere_datum = strtotime( 'first day of +4 month ', $abonnement->start_datum );
		$abonnement->extras          = $item['extras'];
		$abonnement->save();
		return 'De gegevens zijn opgeslagen';
	}

	/**
	 * Wijzig het mandaat van de abonnee
	 *
	 * @param array $item De informatie vanuit het formulier.
	 *
	 * @return string De status van de wijziging.
	 * @throws ApiException Moet op hoger nivo afgehandeld worden.
	 */
	private function wijzig_abonnee_mandaat( array &$item ) : string {
		$abonnement = new Abonnement( $item['id'] );
		$abonnement->actie->stop_incasso();
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
	 * @param array $item de abonnee.
	 * @return bool|string
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 */
	private function validate_abonnee( array $item ): bool|string {
		$messages = [];
		if ( strtotime( $item['start_eind_datum'] ) < strtotime( $item['start_datum'] ) ) {
			$messages[] = 'De eind datum van de startperiode kan niet voor de start datum liggen';
		}
		if ( ! empty( $item['pauze_datum'] . $item['herstart_datum'] ) ) {
			if ( empty( $item['pauze_datum'] ) !== empty( $item['herstart_datum'] ) ) {
				$messages[] = 'Ingeval van pauze moet de pauze datum èn de herstart datum ingevoerd worden';
			} else {
				if ( strtotime( $item['start_datum'] ) >= strtotime( $item['pauze_datum'] ) ) {
					$messages[] = 'De pauze datum kan niet voor de start datum liggen';
				}
				if ( strtotime( $item['herstart_datum'] ) < strtotime( $item['pauze_datum'] ) ) {
					$messages[] = 'De herstart datum kan niet voor de pauze datum liggen of de pauze datum ontbreekt';
				}
			}
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
	 *
	 * @return array De abonnee gegevens.
	 * @throws ApiException Moet op hoger nivo afgehandeld worden.
	 */
	private function geef_abonnee( int $abonnee_id ) : array {
		$abonnement = new Abonnement( $abonnee_id );
		$abonnee    = get_userdata( $abonnee_id );
		$betalen    = new Betalen();
		return [
			'id'               => $abonnee_id,
			'naam'             => $abonnee->display_name,
			'soort'            => $abonnement->soort,
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
	 * Toon en verwerk ingevoerde abonnee gegevens
	 *
	 * @since    5.2.0
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	public function abonnees_form_page_handler() {
		$notice  = '';
		$message = '';
		if ( wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ), 'kleistad_abonnee' ) ) {
			$item = filter_input_array(
				INPUT_POST,
				[
					'id'               => FILTER_SANITIZE_NUMBER_INT,
					'naam'             => FILTER_SANITIZE_STRING,
					'code'             => FILTER_SANITIZE_STRING,
					'soort'            => FILTER_SANITIZE_STRING,
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
			if ( ! is_array( $item ) ) {
				return;
			}
			if ( ! is_array( $item['extras'] ) ) {
				$item['extras'] = [];
			}
			$actie = $item['actie'];
			if ( 'status' === $actie ) {
				$item_valid = $this->validate_abonnee( $item );
				$notice     = is_string( $item_valid ) ? $item_valid : '';
				$message    = empty( $notice ) ? $this->wijzig_abonnee( $item ) : '';
			} elseif ( 'mollie' === $actie ) {
				try {
					$message = $this->wijzig_abonnee_mandaat( $item );
				} catch ( Exception $e ) {
					$notice  = 'Er is iets fout gegaan' . $e->getMessage();
					$message = '';
				}
			}
		} else {
			$id    = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT ) ?: 0;
			$actie = filter_input( INPUT_GET, 'actie', FILTER_SANITIZE_STRING );
			try {
				$item = $this->geef_abonnee( intval( $id ) );
			} catch ( Exception $e ) {
				$notice  = 'Er is iets fout gegaan : ' . $e->getMessage();
				$message = '';
				$item    = [];
			}
		}
		add_meta_box( 'abonnees_form_meta_box', 'Abonnees', [ $this->display, 'form_meta_box' ], 'abonnee', 'normal', 'default', [ 'actie' => $actie ] );
		$this->display->form_page( $item, 'abonnee', 'abonnees', $notice, $message, 'historie' === $actie );
	}

}
