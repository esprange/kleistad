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

/**
 * De kleistad cursus inschrijving class.
 */
class Public_Cursus_Inschrijving extends ShortcodeForm {

	/**
	 *
	 * Prepareer 'cursus_inschrijving' form
	 *
	 * @param array $data data voor display.
	 * @return bool|\WP_Error
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data ) {
		$result = $this->prepare_wachtlijst_indeling( $data );
		if ( false !== $result ) {
			return $result;
		}
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
				'orderby' => [ 'nicename' ],
			]
		);
		$data['cursus_selectie'] = true;
		$data['verbergen']       = $atts['verbergen'];
		$data['open_cursussen']  = [];
		$cursussen               = Cursus::all( true );
		$cursus_selecties        = '' !== $atts['cursus'] ? explode( ',', preg_replace( '/\s+|C/', '', $atts['cursus'] ) ) : [];
		if ( 1 === count( $cursus_selecties ) ) {
			$data['cursus_selectie']    = false;
			$data['input']['cursus_id'] = $cursus_selecties[0];
		}
		foreach ( $cursussen as $cursus ) {
			if ( ! empty( $cursus_selecties ) ) {
				if ( ! in_array( $cursus->id, $cursus_selecties, false ) ) { // phpcs:ignore
					continue; // Er moeten specifieke cursussen worden getoond en deze cursus hoort daar niet bij.
				}
			} elseif ( ! $cursus->tonen ) {
				continue; // In het algemeen overzicht worden alleen cursussen getoond die daarvoor geselecteerd zijn.
			}
			$ruimte                                = $cursus->ruimte();
			$lopend                                = $cursus->start_datum < strtotime( 'today' );
			$wachtbaar                             = $cursus->start_datum > strtotime( 'tomorrow + 1 day' );
			$data['open_cursussen'][ $cursus->id ] = [
				'naam'          => $cursus->naam . ( $cursus->vervallen ? ' VERVALLEN' : ( $cursus->vol ? ' VOL' : '' ) ),
				'selecteerbaar' => ! $cursus->vervallen && ( ! $cursus->vol || $wachtbaar ),
				'technieken'    => $cursus->technieken,
				'meer'          => $cursus->meer,
				'ruimte'        => min( $ruimte, 4 ),
				'vol'           => $cursus->vol,
				'bedrag'        => $cursus->bedrag(),
				'lopend'        => $lopend,
			];
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'cursus_inschrijving' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return \WP_Error|bool
	 *
	 * @since   4.0.87
	 */
	protected function validate( &$data ) {
		$error          = new \WP_Error();
		$data['cursus'] = null;
		$data['input']  = filter_input_array(
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
				'wacht'           => [
					'filter'  => FILTER_VALIDATE_INT,
					'options' => [ 'default' => 0 ],
				],
				'uitschrijven'    => [
					'filter'  => FILTER_VALIDATE_INT,
					'options' => [ 'default' => 0 ],
				],
			]
		);
		if ( false === intval( $data['input']['cursus_id'] ) ) {
			$error->add( 'verplicht', 'Er is nog geen cursus gekozen' );
			return $error;
		}
		$data['cursus'] = new Cursus( $data['input']['cursus_id'] );
		if ( $data['input']['uitschrijven'] ) {
			if ( false === intval( $data['input']['cursus_id'] ) || false === intval( $data['input']['gebruiker_id'] ) ) {
				$error->add( 'intern', 'Er is iets mis gegaan, neem eventueel contact op met Kleistad' );
				return $error;
			}
			return true;
		}
		if ( $data['cursus']->vol ) {
			if ( 1 < $data['input']['aantal'] ) {
				$error->add( 'vol', 'Er zijn geen plaatsen meer beschikbaar. Je kan eventueel op een wachtlijst geplaatst worden' );
				$data['input']['aantal'] = 1;
			}
		} else {
			$ruimte = $data['cursus']->ruimte();
			if ( $ruimte < $data['input']['aantal'] ) {
				$error->add( 'vol', "Er zijn maar $ruimte plaatsen beschikbaar. Pas het aantal eventueel aan." );
				$data['input']['aantal'] = $ruimte;
			} elseif ( 0 === intval( $data['input']['aantal'] ) ) {
				$error->add( 'aantal', 'Het aantal cursisten moet minimaal gelijk zijn aan 1' );
				$data['input']['aantal'] = 1;
			}
		}
		if ( 0 === intval( $data['input']['gebruiker_id'] ) ) {
			$this->validate_gebruiker( $error, $data['input'] );
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 *
	 * Bewaar 'cursus_inschrijving' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return \WP_Error|array
	 *
	 * @since   4.0.87
	 */
	protected function save( $data ) {
		if ( 0 === intval( $data['input']['gebruiker_id'] ) ) {
			$gebruiker_id = email_exists( $data['input']['user_email'] );
			$gebruiker_id = upsert_user(
				[
					'ID'         => ( $gebruiker_id ) ? $gebruiker_id : null,
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
		} else {
			$gebruiker_id = $data['input']['gebruiker_id'];
		}

		$inschrijving = new Inschrijving( $data['cursus']->id, $gebruiker_id );
		if ( $data['input']['uitschrijven'] ) {
			if ( ! $inschrijving->ingedeeld ) {
				$inschrijving->geannuleerd = true;
				$inschrijving->save();
				return [
					'content' => $this->goto_home(),
					'status'  => $this->status( 'De inschrijving is verwijderd uit de wachtlijst, je zult geen emails meer ontvangen over deze cursus' ),
				];
			} else {
				return [
					'status' => $this->status( new \WP_Error( 'ingedeeld', 'Volgens onze administratie ben je al ingedeeld op deze cursus. Voor een annulering, neem contact op met Kleistad.' ) ),
				];
			}
		}
		if ( $inschrijving->geannuleerd ) { // Blijkbaar eerder geannuleerd.
			$inschrijving->ingedeeld   = false;
			$inschrijving->geannuleerd = false;
		};
		if ( $inschrijving->ingedeeld ) {
			return [
				'status' => $this->status( new \WP_Error( 'dubbel', 'Volgens onze administratie ben je al ingedeeld op deze cursus. Neem eventueel contact op met Kleistad.' ) ),
			];
		} elseif ( ! $data['input']['wacht'] ) {
			if ( $inschrijving->ingeschreven ) {
				return [
					'status' => $this->status( new \WP_Error( 'dubbel', 'Volgens onze administratie ben je al ingeschreven op deze cursus. Neem eventueel contact op met Kleistad.' ) ),
				];
			} else {
				$inschrijving->technieken = $data['input']['technieken'];
				$inschrijving->opmerking  = $data['input']['opmerking'];
				$inschrijving->aantal     = intval( $data['input']['aantal'] );
			}
		}
		$inschrijving->wacht_datum  = ( $data['cursus']->vol ) ? strtotime( 'today' ) : 0;
		$inschrijving->artikel_type = 'inschrijving';
		$inschrijving->save();

		$verwerking = 'verwerkt';
		$bijlage    = '';
		$email      = 'inschrijving';
		if ( $data['cursus']->vol ) {
			$verwerking = 'op de wachtlijst geplaatst';
			$email      = '_wachtlijst';
		} elseif ( $data['cursus']->start_datum < strtotime( 'today' ) ) {
			$email = '_lopend';
		} elseif ( 'stort' === $data['input']['betaal'] ) {
			$email   = 'inschrijving';
			$bijlage = $inschrijving->bestel_order( 0.0, $data['cursus']->start_datum, $inschrijving->heeft_restant() );
		} elseif ( 'ideal' === $data['input']['betaal'] || $data['input']['wacht'] ) {
			$ideal_uri = $inschrijving->ideal( 'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging', $inschrijving->referentie() );
			if ( ! empty( $ideal_uri ) ) {
				return [ 'redirect_uri' => $ideal_uri ];
			}
			return [ 'status' => $this->status( new \WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
		}
		$inschrijving->email( $email, $bijlage );
		return [
			'content' => $this->goto_home(),
			'status'  => $this->status( "De inschrijving is $verwerking en er is een email verzonden met nadere informatie" ),
		];
	}

	/**
	 * Als aangeroepen met url parameters dan
	 *
	 * @param array $data De uit te wisselen data.
	 * @return bool|\WP_Error Het resultaat
	 */
	private function prepare_wachtlijst_indeling( &$data ) {
		$param = filter_input_array(
			INPUT_GET,
			[
				'code' => FILTER_SANITIZE_STRING,
				'hsh'  => FILTER_SANITIZE_STRING,
				'stop' => FILTER_SANITIZE_STRING,
			]
		);
		if ( ! is_null( $param ) && ! empty( $param['code'] ) ) {
			$inschrijving = Inschrijving::vind( $param['code'] );
			if ( $param['hsh'] !== $inschrijving->controle() ) {
				return new \WP_Error( 'Security', 'Je hebt geklikt op een ongeldige link of deze is nu niet geldig meer.' );
			}
			if ( empty( $param['stop'] ) && $inschrijving->cursus->vol ) {
				return new \WP_Error( 'Vol', 'Helaas, waarschijnlijk is iemand anders je voor geweest. De cursus is volgeboekt.' );
			}
			$data['cursus_naam']  = $inschrijving->cursus->naam;
			$data['cursus_id']    = $inschrijving->cursus->id;
			$data['cursist_naam'] = get_user_by( 'id', $inschrijving->klant_id )->display_name;
			$data['gebruiker_id'] = $inschrijving->klant_id;
			if ( empty( $param['stop'] ) ) {
				$data['wacht']  = true;
				$data['ruimte'] = $inschrijving->cursus->ruimte();
			} else {
				$data['uitschrijven'] = true;
			}
			return true;
		}
		return false;
	}

}
