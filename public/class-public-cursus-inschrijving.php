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
	 * Bepaal de actie
	 *
	 * @param array $data data voor display.
	 * @return string
	 */
	private function bepaal_actie( &$data ) : string {
		$data['param'] = filter_input_array(
			INPUT_GET,
			[
				'code' => FILTER_SANITIZE_STRING,
				'hsh'  => FILTER_SANITIZE_STRING,
				'stop' => FILTER_SANITIZE_STRING,
			]
		);
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
	private function prepare_inschrijven( array &$data ) : bool {
		$atts                    = shortcode_atts(
			[
				'cursus'    => '',
				'verbergen' => '',
			],
			$this->atts,
			'kleistad_cursus_inschrijving'
		);
		$data['gebruikers']      = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => 'display_name',
			]
		);
		$vandaag                 = strtotime( 'today' );
		$data['cursus_selectie'] = true;
		$data['verbergen']       = $atts['verbergen'];
		$data['open_cursussen']  = [];
		$cursussen               = new Cursussen();
		$cursus_selecties        = '' !== $atts['cursus'] ? explode( ',', preg_replace( '/\s+|C/', '', $atts['cursus'] ) ) : [];
		if ( 1 === count( $cursus_selecties ) ) {
			$data['cursus_selectie']    = false;
			$data['input']['cursus_id'] = $cursus_selecties[0];
		}
		foreach ( $cursussen as $cursus ) {
			if ( $vandaag >= $cursus->eind_datum ) {
				continue; // De cursus is gereed.
			}
			if ( ! empty( $cursus_selecties ) ) {
				if ( ! in_array( $cursus->id, $cursus_selecties, false ) ) { // phpcs:ignore
					continue; // Er moeten specifieke cursussen worden getoond en deze cursus hoort daar niet bij.
				}
			} elseif ( ! $cursus->tonen ) {
				continue; // In het algemeen overzicht worden alleen cursussen getoond die daarvoor geselecteerd zijn.
			}
			$data['open_cursussen'][ $cursus->id ] = $cursus;
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
	protected function prepare( &$data ) {
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
		switch ( $data['actie'] ) {
			case 'stop_wachten':
				return $this->prepare_stop_wachten( $data );
			case 'indelen_na_wachten':
				return $this->prepare_indelen_na_wachten( $data );
			case 'inschrijven':
				return $this->prepare_inschrijven( $data );
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
	protected function validate( &$data ) {
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
			if ( false === intval( $data['input']['cursus_id'] ) ) {
				return new WP_Error( 'verplicht', 'Er is nog geen cursus gekozen' );
			}
			if ( 0 === intval( $data['input']['gebruiker_id'] ) ) {
				$error = $this->validate_gebruiker( $data['input'] );
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
	 * @param Inschrijving $inschrijving De inschrijving.
	 * @return array
	 */
	private function save_stop_wachten( Inschrijving $inschrijving ) : array {
		if ( $inschrijving->ingedeeld ) {
			return [
				'status' => $this->status( new WP_Error( 'ingedeeld', 'Volgens onze administratie ben je al ingedeeld op deze cursus. Voor een annulering, neem contact op met Kleistad.' ) ),
			];
		}
		$inschrijving->uitschrijven_wachtlijst();
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( 'De inschrijving is verwijderd uit de wachtlijst, je zult geen emails meer ontvangen over deze cursus' ),
		];
	}

	/**
	 * Bewaar actie ingeval de gebruiker op de wachtlijst ingedeeld wil worden.
	 *
	 * @param Inschrijving $inschrijving De inschrijving.
	 * @return array
	 */
	private function save_indelen_na_wachten( Inschrijving $inschrijving ) : array {
		if ( $inschrijving->ingedeeld ) {
			return [
				'status' => $this->status( new WP_Error( 'dubbel', 'Volgens onze administratie ben je al ingedeeld op deze cursus. Neem eventueel contact op met Kleistad.' ) ),
			];
		}
		$inschrijving->artikel_type = 'inschrijving';
		$inschrijving->save();
		$ideal_uri = $inschrijving->doe_idealbetaling( 'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging' );
		if ( false === $ideal_uri ) {
			return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
		}
		return [ 'redirect_uri' => $ideal_uri ];
	}

	/**
	 * Bewaar actie ingeval de gebruiker in wil schrijven op een cursus.
	 *
	 * @param Inschrijving $inschrijving De inschrijving.
	 * @param string       $betaalwijze  De wijze waarop de gebruiker wil betalen.
	 * @return array
	 */
	private function save_inschrijven( Inschrijving $inschrijving, string $betaalwijze ) : array {
		if ( $inschrijving->geannuleerd ) { // Blijkbaar eerder geannuleerd, eerst resetten.
			$inschrijving->ingedeeld    = false;
			$inschrijving->geannuleerd  = false;
			$inschrijving->ingeschreven = false;
		};
		if ( $inschrijving->ingeschreven ) {
			return [
				'status' => $this->status( new WP_Error( 'dubbel', 'Volgens onze administratie ben je al ingeschreven op deze cursus. Neem eventueel contact op met Kleistad.' ) ),
			];
		}
		$inschrijving->save();
		$verwerking = 'verwerkt';
		if ( $inschrijving->cursus->vol ) {
			$verwerking = 'op de wachtlijst geplaatst';
			$inschrijving->verzend_email( '_wachtlijst' );
		} elseif ( $inschrijving->cursus->start_datum < strtotime( 'today' ) ) {
			$inschrijving->verzend_email( '_lopend' );
		} elseif ( 'stort' === $betaalwijze ) {
			$inschrijving->verzend_email( 'inschrijving', $inschrijving->bestel_order( 0.0, $inschrijving->cursus->start_datum, $inschrijving->heeft_restant() ) );
		} elseif ( 'ideal' === $betaalwijze ) {
			$ideal_uri = $inschrijving->doe_idealbetaling( 'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging' );
			if ( false === $ideal_uri ) {
				return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
			}
			return [ 'redirect_uri' => $ideal_uri ];
		}
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( "De inschrijving is $verwerking en er is een email verzonden met nadere informatie" ),
		];
	}

	/**
	 *
	 * Bewaar 'cursus_inschrijving' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return WP_Error|array
	 *
	 * @since   4.0.87
	 */
	protected function save( $data ) {
		$gebruiker_id = intval( $data['input']['gebruiker_id'] );
		if ( ! $gebruiker_id ) {
			$gebruiker_id = email_exists( $data['input']['user_email'] );
			$gebruiker_id = upsert_user(
				[
					'ID'         => $gebruiker_id ? $gebruiker_id : null,
					'first_name' => $data['input']['first_name'],
					'last_name'  => $data['input']['last_name'],
					'telnr'      => $data['input']['telnr'],
					'user_email' => $data['input']['user_email'],
					'straat'     => $data['input']['straat'],
					'huisnr'     => $data['input']['huisnr'],
					'pcode'      => $data['input']['pcode'],
					'plaats'     => $data['input']['plaats'],
				]
			);
			if ( ! is_int( $gebruiker_id ) ) {
				return [ 'status' => $this->status( new WP_Error( 'intern', 'Er is iets fout gegaan, probeer het later opnieuw' ) ) ];
			}
		}
		$inschrijving               = new Inschrijving( $data['input']['cursus_id'], $gebruiker_id );
		$inschrijving->technieken   = $data['input']['technieken'] ?? $inschrijving->technieken;
		$inschrijving->opmerking    = $data['input']['opmerking'] ?? $inschrijving->opmerking;
		$inschrijving->aantal       = intval( $data['input']['aantal'] ) ?: $inschrijving->aantal;
		$inschrijving->wacht_datum  = $inschrijving->cursus->vol ? strtotime( 'today' ) : 0;
		$inschrijving->artikel_type = 'inschrijving';
		switch ( $data['form_actie'] ) {
			case 'stop_wachten':
				return $this->save_stop_wachten( $inschrijving );
			case 'indelen_na_wachten':
				return $this->save_indelen_na_wachten( $inschrijving );
			case 'inschrijven':
				return $this->save_inschrijven( $inschrijving, $data['input']['betaal'] );
		}
		return [ 'status' => $this->status( new WP_Error( 'intern', 'interne fout, probeer het eventueel opnieuw' ) ) ];
	}

}
