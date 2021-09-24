<?php
/**
 * Shortcode cursus inschrijving.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

use WP_Error;

/**
 * De kleistad cursus inschrijving class.
 */
class Public_Cursus_Inschrijving extends ShortcodeForm {

	/**
	 * Cursus selecties als er een filter is
	 *
	 * @var array $cursus_selecties Het filter.
	 */
	private array $cursus_selecties;

	/**
	 * Bepaal de actie
	 *
	 * @param array $data data voor display.
	 * @return string
	 */
	private function bepaal_actie( array &$data ) : string {
		$data['param']          = filter_input_array(
			INPUT_GET,
			[
				'code' => FILTER_SANITIZE_STRING,
				'hsh'  => FILTER_SANITIZE_STRING,
				'stop' => FILTER_SANITIZE_STRING,
			]
		);
		$this->cursus_selecties = empty( $data['cursus'] ) ? [] : explode( ',', preg_replace( '/\s+|C/', '', $data['cursus'] ) );
		if ( ! is_null( $data['param'] ) && ! empty( $data['param']['stop'] ) ) {
			return 'stop_wachten';
		}
		if ( ! is_null( $data['param'] ) && ! empty( $data['param']['code'] ) ) {
			return 'indelen_na_wachten';
		}
		return 'inschrijven';
	}

	/**
	 * Formulier dat getoond moet worden betreft het verwijderen van de wachtlijst.
	 *
	 * @param array $data data voor display.
	 * @return bool|WP_Error
	 */
	private function prepare_stop_wachten( array &$data ) {
		list( $cursus_id, $cursist_id ) = sscanf( $data['param']['code'], 'C%d-%d' );
		$inschrijving                   = new Inschrijving( $cursus_id, $cursist_id );
		if ( $data['param']['hsh'] !== $inschrijving->controle() ) {
			return new WP_Error( 'Security', 'Je hebt geklikt op een ongeldige link of deze is nu niet geldig meer.' );
		}
		$data['cursus_naam']  = $inschrijving->cursus->naam;
		$data['cursus_id']    = $inschrijving->cursus->id;
		$data['cursist_naam'] = get_user_by( 'id', $inschrijving->klant_id )->display_name;
		$data['gebruiker_id'] = $inschrijving->klant_id;
		return true;
	}

	/**
	 * Formulier dat getoond moet worden betreft het verwijderen van de wachtlijst.
	 *
	 * @param array $data data voor display.
	 * @return bool|WP_Error
	 */
	private function prepare_indelen_na_wachten( array &$data ) {
		list( $cursus_id, $cursist_id ) = sscanf( $data['param']['code'], 'C%d-%d' );
		$inschrijving                   = new Inschrijving( $cursus_id, $cursist_id );
		if ( $data['param']['hsh'] !== $inschrijving->controle() ) {
			return new WP_Error( 'Security', 'Je hebt geklikt op een ongeldige link of deze is nu niet geldig meer.' );
		}
		if ( $inschrijving->cursus->vol ) {
			return new WP_Error( 'Vol', 'Helaas, waarschijnlijk is iemand anders je voor geweest. De cursus is volgeboekt.' );
		}
		$data['cursus_naam']  = $inschrijving->cursus->naam;
		$data['cursus_id']    = $inschrijving->cursus->id;
		$data['cursist_naam'] = get_user_by( 'id', $inschrijving->klant_id )->display_name;
		$data['gebruiker_id'] = $inschrijving->klant_id;
		$data['ruimte']       = $inschrijving->cursus->ruimte();
		return true;
	}

	/**
	 * Formulier dat getoond moet worden betreft de reguliere inschrijving.
	 *
	 * @param array $data data voor display.
	 * @return bool|WP_Error
	 */
	private function prepare_inschrijven( array &$data ) {
		$data['gebruikers']     = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => 'display_name',
			]
		);
		$data['open_cursussen'] = [];
		if ( 1 === count( $this->cursus_selecties ) ) {
			$data['input']['cursus_id'] = $this->cursus_selecties[0];
		}
		$selecteerbaar = false;
		foreach ( new Cursussen( true ) as $cursus ) {
			if ( ! empty( $this->cursus_selecties ) ) {
				if ( ! in_array( $cursus->id, $this->cursus_selecties, false ) ) { // phpcs:ignore
					continue; // Er moeten specifieke cursussen worden getoond en deze cursus hoort daar niet bij.
				}
			} elseif ( ! $cursus->tonen ) {
				continue; // In het algemeen overzicht worden alleen cursussen getoond die daarvoor geselecteerd zijn.
			}
			$is_open                  = ! $cursus->vervallen && ( ! $cursus->vol || $cursus->is_wachtbaar() );
			$ruimte                   = $cursus->ruimte();
			$is_lopend                = $cursus->is_lopend();
			$data['open_cursussen'][] = [
				'cursus'  => $cursus,
				'is_open' => $is_open,
				'ruimte'  => $ruimte,
				'json'    => wp_json_encode(
					[
						'technieken' => $cursus->technieken,
						'meer'       => ! $is_lopend && $cursus->meer,
						'ruimte'     => min( $ruimte, 4 ),
						'bedrag'     => $cursus->bedrag(),
						'lopend'     => $is_lopend,
						'vol'        => $cursus->vol,
					]
				),
			];
			usort(
				$data['open_cursussen'],
				function ( $links, $rechts ) {
					return strtoupper( $links['cursus']->naam ) <=> strtoupper( $rechts['cursus']->naam );
				}
			);
			$selecteerbaar = $selecteerbaar || $is_open;
		}
		if ( ! $selecteerbaar ) {
			return new WP_Error( 'Inschrijven', 'Helaas is er geen cursusplek meer beschikbaar' );
		}
		return true;
	}

	/**
	 *
	 * Prepareer 'cursus_inschrijving' form
	 *
	 * @param array $data data voor display.
	 * @return bool|WP_Error
	 *
	 * @since   4.0.87
	 */
	protected function prepare( array &$data ) {
		if ( ! isset( $data['input'] ) ) {
			$data          = [];
			$data['input'] = [
				'user_email'      => '',
				'email_controle'  => '',
				'first_name'      => '',
				'last_name'       => '',
				'straat'          => '',
				'huisnr'          => '',
				'pcode'           => '',
				'plaats'          => '',
				'telnr'           => '',
				'cursus_id'       => 0,
				'gebruiker_id'    => 0,
				'aantal'          => 1,
				'technieken'      => [],
				'opmerking'       => '',
				'betaal'          => 'ideal',
				'mc4wp-subscribe' => '0',
			];
		}
		$data['actie'] = $this->bepaal_actie( $data );
		$actie         = 'prepare_' . $data['actie'];
		if ( method_exists( $this, $actie ) ) {
			return $this->$actie( $data );
		}
		return false;
	}

	/**
	 * Valideer/sanitize 'cursus_inschrijving' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return WP_Error|bool
	 *
	 * @since   4.0.87
	 */
	protected function validate( array &$data ) {
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'user_email'      => FILTER_SANITIZE_EMAIL,
				'email_controle'  => FILTER_SANITIZE_EMAIL,
				'first_name'      => FILTER_SANITIZE_STRING,
				'last_name'       => FILTER_SANITIZE_STRING,
				'straat'          => FILTER_SANITIZE_STRING,
				'huisnr'          => FILTER_SANITIZE_STRING,
				'pcode'           => FILTER_SANITIZE_STRING,
				'plaats'          => FILTER_SANITIZE_STRING,
				'telnr'           => FILTER_SANITIZE_STRING,
				'cursus_id'       => [
					'filter'    => FILTER_SANITIZE_NUMBER_INT,
					'min-range' => 1,
				],
				'gebruiker_id'    => FILTER_SANITIZE_NUMBER_INT,
				'technieken'      => [
					'filter'  => FILTER_SANITIZE_STRING,
					'flags'   => FILTER_FORCE_ARRAY,
					'options' => [ 'default' => [] ],
				],
				'aantal'          => [
					'filter'    => FILTER_SANITIZE_NUMBER_INT,
					'min-range' => 1,
				],
				'opmerking'       => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_FLAG_STRIP_LOW,
				],
				'betaal'          => FILTER_SANITIZE_STRING,
				'mc4wp-subscribe' => FILTER_SANITIZE_STRING,
			]
		);
		if ( is_array( $data['input'] ) ) {
			if ( 0 === intval( $data['input']['cursus_id'] ) ) {
				return new WP_Error( 'verplicht', 'Er is nog geen cursus gekozen' );
			}
			if ( 0 === intval( $data['input']['gebruiker_id'] ) ) {
				$error = $this->validator->gebruiker( $data['input'] );
				if ( is_wp_error( $error ) ) {
					return $error;
				}
			}
			$cursus = new Cursus( $data['input']['cursus_id'] );
			if ( $cursus->vol ) {
				if ( 1 < $data['input']['aantal'] ) {
					$data['input']['aantal'] = 1;
					return new WP_Error( 'vol', 'De cursus is helaas vol. Als je op een wachtlijst geplaatst wilt dan kan je dit alleen voor jezelf doen' );
				}
			}
			$ruimte = $cursus->ruimte();
			if ( ! $cursus->vol && $ruimte < $data['input']['aantal'] ) {
				$data['input']['aantal'] = $ruimte;
				return new WP_Error( 'vol', "Er zijn maar $ruimte plaatsen beschikbaar. Pas het aantal eventueel aan." );
			}
			return true;
		}
		return new WP_Error( 'input', 'geen juiste data ontvangen' );
	}

	/**
	 * Bewaar actie ingeval de gebruiker van de wachtlijst verwijdert wil worden.
	 *
	 * @param array $data data te bewaren.
	 * @return array
	 */
	protected function stop_wachten( array $data ) : array {
		$inschrijving = new Inschrijving( $data['input']['cursus_id'], $data['input']['gebruiker_id'] );
		if ( $inschrijving->ingedeeld ) {
			return [
				'status' => $this->status( new WP_Error( 'ingedeeld', 'Volgens onze administratie ben je al ingedeeld op deze cursus. Voor een annulering, neem contact op met Kleistad.' ) ),
			];
		}
		$inschrijving->actie->uitschrijven_wachtlijst();
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'De inschrijving is verwijderd uit de wachtlijst, je zult geen emails meer ontvangen over deze cursus' ),
		];
	}

	/**
	 * Bewaar actie ingeval de gebruiker op de wachtlijst ingedeeld wil worden.
	 *
	 * @param array $data data te bewaren.
	 * @return array
	 */
	protected function indelen_na_wachten( array $data ) : array {
		$inschrijving = new Inschrijving( $data['input']['cursus_id'], $data['input']['gebruiker_id'] );
		if ( $inschrijving->ingedeeld ) {
			return [
				'status' => $this->status( new WP_Error( 'dubbel', 'Volgens onze administratie ben je al ingedeeld op deze cursus. Neem eventueel contact op met Kleistad.' ) ),
			];
		}
		$inschrijving->artikel_type = 'inschrijving';
		$inschrijving->save();
		$ideal_uri = $inschrijving->betaling->doe_ideal( 'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging', $inschrijving->cursus->bedrag(), $inschrijving->geef_referentie() );
		if ( false === $ideal_uri ) {
			return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
		}
		return [ 'redirect_uri' => $ideal_uri ];
	}

	/**
	 * Bewaar actie ingeval de gebruiker in wil schrijven op een cursus.
	 *
	 * @param array $data data te bewaren.
	 * @return array
	 */
	protected function inschrijven( array $data ) : array {
		$gebruiker_id = Gebruiker::registreren( $data['input'] );
		if ( ! is_int( $gebruiker_id ) ) {
			return [ 'status' => $this->status( new WP_Error( 'intern', 'Er is iets fout gegaan, probeer het later opnieuw' ) ) ];
		}
		$inschrijving             = new Inschrijving( $data['input']['cursus_id'], $gebruiker_id );
		$inschrijving->technieken = $data['input']['technieken'] ?? [];
		$inschrijving->opmerking  = $data['input']['opmerking'];
		$inschrijving->aantal     = intval( $data['input']['aantal'] );
		if ( $inschrijving->ingedeeld && ! $inschrijving->geannuleerd ) {
			return [
				'status' => $this->status( new WP_Error( 'dubbel', 'Volgens onze administratie ben je al ingedeeld op deze cursus. Neem eventueel contact op met Kleistad.' ) ),
			];
		}
		$result = $inschrijving->actie->aanvraag( $data['input']['betaal'] );
		if ( false === $result ) {
			return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
		}
		if ( is_string( $result ) ) {
			return [ 'redirect_uri' => $result ];
		}
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'De inschrijving is verwerkt en er is een email verzonden met nadere informatie' ),
		];
	}

}
