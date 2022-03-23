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
class Public_Cursus_Inschrijving extends Public_Bestelling {

	/**
	 * Formulier dat getoond moet worden betreft het verwijderen van de wachtlijst.
	 *
	 * @return string
	 */
	protected function prepare_stop_wachten() : string {
		$params = filter_input_array(
			INPUT_GET,
			[
				'code' => FILTER_SANITIZE_STRING,
				'hsh'  => FILTER_SANITIZE_STRING,
			]
		);
		sscanf( $params['code'], 'C%d-%d', $cursus_id, $cursist_id );
		$inschrijving = new Inschrijving( $cursus_id, $cursist_id );
		if ( $params['hsh'] !== $inschrijving->controle() ) {
			return $this->status( new WP_Error( 'Security', 'Je hebt geklikt op een ongeldige link of deze is nu niet geldig meer.' ) );
		}
		$this->data['cursus_naam']  = $inschrijving->cursus->naam;
		$this->data['cursus_id']    = $inschrijving->cursus->id;
		$this->data['cursist_naam'] = get_user_by( 'id', $inschrijving->klant_id )->display_name;
		$this->data['gebruiker_id'] = $inschrijving->klant_id;
		return $this->content();
	}

	/**
	 * Formulier dat getoond moet worden betreft het verwijderen van de wachtlijst.
	 *
	 * @return string
	 */
	protected function prepare_indelen_na_wachten() : string {
		$params = filter_input_array(
			INPUT_GET,
			[
				'code' => FILTER_SANITIZE_STRING,
				'hsh'  => FILTER_SANITIZE_STRING,
			]
		);
		sscanf( $params['code'], 'C%d-%d', $cursus_id, $cursist_id );
		$inschrijving = new Inschrijving( $cursus_id, $cursist_id );
		if ( $params['hsh'] !== $inschrijving->controle() ) {
			return $this->status( new WP_Error( 'Security', 'Je hebt geklikt op een ongeldige link of deze is nu niet geldig meer.' ) );
		}
		if ( $inschrijving->cursus->vol ) {
			return $this->status( new WP_Error( 'Vol', 'Helaas, waarschijnlijk is iemand anders je voor geweest. De cursus is volgeboekt.' ) );
		}
		$this->data['cursus_naam']  = $inschrijving->cursus->naam;
		$this->data['cursus_id']    = $inschrijving->cursus->id;
		$this->data['cursist_naam'] = get_user_by( 'id', $inschrijving->klant_id )->display_name;
		$this->data['gebruiker_id'] = $inschrijving->klant_id;
		$this->data['ruimte']       = $inschrijving->cursus->ruimte();
		return $this->content();
	}

	/**
	 * Formulier dat getoond moet worden betreft de reguliere inschrijving.
	 *
	 * @return string
	 */
	protected function prepare_inschrijven() : string {
		if ( ! isset( $this->data['input'] ) ) {
			$this->data['input'] = [
				'user_email'     => '',
				'email_controle' => '',
				'first_name'     => '',
				'last_name'      => '',
				'straat'         => '',
				'huisnr'         => '',
				'pcode'          => '',
				'plaats'         => '',
				'telnr'          => '',
				'cursus_id'      => 0,
				'gebruiker_id'   => 0,
				'aantal'         => 1,
				'technieken'     => [],
				'opmerking'      => '',
				'betaal'         => 'ideal',
			];
		}
		$this->data['gebruikers']     = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => 'display_name',
			]
		);
		$this->data['open_cursussen'] = [];
		$cursus_selecties             = empty( $this->data['cursus'] ) ? [] : explode( ',', preg_replace( '/\s+|C/', '', $this->data['cursus'] ) );
		if ( 1 === count( $cursus_selecties ) ) {
			$this->data['input']['cursus_id'] = $cursus_selecties[0];
		}
		$selecteerbaar = false;
		foreach ( new Cursussen( strtotime( 'today' ) ) as $cursus ) {
			if ( ! empty( $cursus_selecties ) ) {
				if ( ! in_array( $cursus->id, $cursus_selecties, false ) ) { // phpcs:ignore
					continue; // Er moeten specifieke cursussen worden getoond en deze cursus hoort daar niet bij.
				}
			} elseif ( ! $cursus->tonen ) {
				continue; // In het algemeen overzicht worden alleen cursussen getoond die daarvoor geselecteerd zijn.
			}
			$ruimte                         = $cursus->ruimte();
			$this->data['open_cursussen'][] = [
				'cursus'  => $cursus,
				'is_open' => $cursus->is_open(),
				'ruimte'  => $ruimte,
				'json'    => wp_json_encode(
					[
						'technieken' => $cursus->technieken,
						'naam'       => $cursus->naam,
						'meer'       => $cursus->meer,
						'ruimte'     => min( $ruimte, 4 ),
						'bedrag'     => $cursus->bedrag(),
						'lopend'     => $cursus->is_lopend(),
						'vol'        => $cursus->vol,
					]
				),
			];
			usort(
				$this->data['open_cursussen'],
				function ( $links, $rechts ) {
					return strtoupper( $links['cursus']->naam ) <=> strtoupper( $rechts['cursus']->naam );
				}
			);
			$selecteerbaar = $selecteerbaar || $cursus->is_open();
		}
		if ( ! $selecteerbaar ) {
			return $this->status( new WP_Error( 'Inschrijven', 'Helaas is er geen cursusplek meer beschikbaar' ) );
		}
		return $this->content();
	}

	/**
	 * Default actie is inschrijven.
	 *
	 * @return string
	 */
	protected function prepare(): string {
		if ( Shortcode::STANDAARD_ACTIE === $this->display_actie ) {
			$this->display_actie = 'inschrijven';
		}
		return parent::prepare();
	}

	/**
	 * Valideer/sanitize 'cursus_inschrijving' form
	 *
	 * @since   4.0.87
	 *
	 * @return array
	 */
	public function process() : array {
		$this->data['input'] = filter_input_array(
			INPUT_POST,
			[
				'user_email'     => FILTER_SANITIZE_EMAIL,
				'email_controle' => FILTER_SANITIZE_EMAIL,
				'first_name'     => FILTER_SANITIZE_STRING,
				'last_name'      => FILTER_SANITIZE_STRING,
				'straat'         => FILTER_SANITIZE_STRING,
				'huisnr'         => FILTER_SANITIZE_STRING,
				'pcode'          => FILTER_SANITIZE_STRING,
				'plaats'         => FILTER_SANITIZE_STRING,
				'telnr'          => FILTER_SANITIZE_STRING,
				'cursus_id'      => [
					'filter'    => FILTER_SANITIZE_NUMBER_INT,
					'min-range' => 1,
				],
				'gebruiker_id'   => FILTER_SANITIZE_NUMBER_INT,
				'technieken'     => [
					'filter'  => FILTER_SANITIZE_STRING,
					'flags'   => FILTER_FORCE_ARRAY,
					'options' => [ 'default' => [] ],
				],
				'aantal'         => [
					'filter'    => FILTER_SANITIZE_NUMBER_INT,
					'min-range' => 1,
				],
				'opmerking'      => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_FLAG_STRIP_LOW,
				],
				'betaal'         => FILTER_SANITIZE_STRING,
			]
		);
		if ( is_array( $this->data['input'] ) ) {
			if ( 0 === intval( $this->data['input']['cursus_id'] ) ) {
				return $this->melding( new WP_Error( 'verplicht', 'Er is nog geen cursus gekozen' ) );
			}
			if ( 0 === intval( $this->data['input']['gebruiker_id'] ) ) {
				$error = $this->validator->gebruiker( $this->data['input'] );
				if ( ! is_bool( $error ) ) {
					return $this->melding( $error );
				}
			}
			$cursus = new Cursus( $this->data['input']['cursus_id'] );
			if ( $cursus->vol ) {
				if ( 1 < $this->data['input']['aantal'] ) {
					$this->data['input']['aantal'] = 1;
					return $this->melding( new WP_Error( 'vol', 'De cursus is helaas vol. Als je op een wachtlijst geplaatst wilt dan kan je dit alleen voor jezelf doen' ) );
				}
			}
			$ruimte = $cursus->ruimte();
			if ( ! $cursus->vol && $ruimte < $this->data['input']['aantal'] ) {
				$this->data['input']['aantal'] = $ruimte;
				return $this->melding( new WP_Error( 'vol', "Er zijn maar $ruimte plaatsen beschikbaar. Pas het aantal eventueel aan." ) );
			}
			return $this->save();
		}
		return $this->melding( new WP_Error( 'input', 'geen juiste data ontvangen' ) );
	}

	/**
	 * Bewaar actie ingeval de gebruiker van de wachtlijst verwijdert wil worden.
	 *
	 * @return array
	 */
	protected function stop_wachten() : array {
		$inschrijving = new Inschrijving( $this->data['input']['cursus_id'], $this->data['input']['gebruiker_id'] );
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
	 * @return array
	 */
	protected function indelen_na_wachten() : array {
		$inschrijving = new Inschrijving( $this->data['input']['cursus_id'], $this->data['input']['gebruiker_id'] );
		if ( $inschrijving->ingedeeld ) {
			return [
				'status' => $this->status( new WP_Error( 'dubbel', 'Volgens onze administratie ben je al ingedeeld op deze cursus. Neem eventueel contact op met Kleistad.' ) ),
			];
		}
		$inschrijving->artikel_type = 'inschrijving';
		$inschrijving->save();
		$ideal_uri = $inschrijving->betaling->doe_ideal( 'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging', $inschrijving->cursus->bedrag(), $inschrijving->get_referentie() );
		if ( false === $ideal_uri ) {
			return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
		}
		return [ 'redirect_uri' => $ideal_uri ];
	}

	/**
	 * Bewaar actie ingeval de gebruiker in wil schrijven op een cursus.
	 *
	 * @return array
	 */
	protected function inschrijven() : array {
		$gebruiker_id = registreren( $this->data['input'] );
		if ( ! is_int( $gebruiker_id ) ) {
			return [ 'status' => $this->status( new WP_Error( 'intern', 'Er is iets fout gegaan, probeer het later opnieuw' ) ) ];
		}
		$inschrijving             = new Inschrijving( $this->data['input']['cursus_id'], $gebruiker_id );
		$inschrijving->technieken = $this->data['input']['technieken'] ?? [];
		$inschrijving->opmerking  = $this->data['input']['opmerking'];
		$inschrijving->aantal     = intval( $this->data['input']['aantal'] );
		if ( $inschrijving->ingedeeld && ! $inschrijving->geannuleerd ) {
			return [
				'status' => $this->status( new WP_Error( 'dubbel', 'Volgens onze administratie ben je al ingedeeld op deze cursus. Neem eventueel contact op met Kleistad.' ) ),
			];
		}
		$result = $inschrijving->actie->aanvraag( $this->data['input']['betaal'] );
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
