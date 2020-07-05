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
	 * @return bool
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
		$cursussen               = \Kleistad\Cursus::all( true );
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
			$data['open_cursussen'][ $cursus->id ] = [
				'naam'          => $cursus->naam . ( 0 === $ruimte ? ' VOL' : ( $cursus->vervallen ? ' VERVALLEN' : '' ) ),
				'selecteerbaar' => $ruimte && ! $cursus->vervallen,
				'technieken'    => $cursus->technieken,
				'meer'          => $cursus->meer,
				'ruimte'        => $ruimte,
				'bedrag'        => $cursus->bedrag(),
				'lopend'        => $cursus->start_datum < strtotime( 'today' ),
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
			]
		);
		if ( is_null( $data['input']['cursus_id'] ) ) {
			$error->add( 'verplicht', 'Er is nog geen cursus gekozen' );
			return $error;
		}
		$data['cursus'] = new \Kleistad\Cursus( (int) $data['input']['cursus_id'] );
		$ruimte         = $data['cursus']->ruimte();
		if ( 0 === $ruimte ) {
			$error->add( 'vol', 'Er zijn geen plaatsen meer beschikbaar. Inschrijving is niet mogelijk.' );
			$data['input']['cursus_id'] = 0;
		} elseif ( $ruimte < $data['input']['aantal'] ) {
			$error->add( 'vol', 'Er zijn maar ' . $ruimte . ' plaatsen beschikbaar. Pas het aantal eventueel aan.' );
			$data['input']['aantal'] = $ruimte;
		}
		if ( false === $data['input']['aantal'] ) {
			$error->add( 'aantal', 'Het aantal cursisten moet minimaal gelijk zijn aan 1' );
			$data['input']['aantal'] = 1;
		}
		if ( 0 === (int) $data['input']['gebruiker_id'] ) {
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
		if ( ! is_user_logged_in() ) {
			$gebruiker_id = upsert_user(
				[
					'ID'         => email_exists( $data['input']['user_email'] ),
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
			if ( is_super_admin() ) {
				$gebruiker_id = (int) $data['input']['gebruiker_id'];
			} else {
				$gebruiker_id = get_current_user_id();
			}
		}
		if ( is_wp_error( $gebruiker_id ) ) {
			return [
				'status' => $this->status( new \WP_Error( 'fout', 'Er is een interne fout geconstateerd. Probeer het later opnieuw.' ) ),
			];
		}

		$inschrijving = new \Kleistad\Inschrijving( (int) $data['cursus']->id, $gebruiker_id );
		if ( $inschrijving->ingedeeld ) {
			return [
				'status' => $this->status( new \WP_Error( 'dubbel', 'Volgens onze administratie ben je al ingedeeld op deze cursus. Neem eventueel contact op met Kleistad.' ) ),
			];
		}
		$inschrijving->technieken = $data['input']['technieken'];
		$inschrijving->opmerking  = $data['input']['opmerking'];
		$inschrijving->aantal     = (int) $data['input']['aantal'];
		$inschrijving->datum      = strtotime( 'today' );
		$inschrijving->save();

		$lopend = $data['cursus']->start_datum < strtotime( 'today' );

		if ( ! $lopend && 'ideal' === $data['input']['betaal'] ) {
			$ideal_uri = $inschrijving->ideal( 'Bedankt voor de betaling! De inschrijving is verwerkt en er wordt een email verzonden met bevestiging', $inschrijving->referentie() );
			if ( ! empty( $ideal_uri ) ) {
				return [ 'redirect_uri' => $ideal_uri ];
			}
			return [ 'status' => $this->status( new \WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
		} else {
			if ( ! $lopend ) {
				$inschrijving->artikel_type = 'inschrijving';
				$inschrijving->email( 'inschrijving', $inschrijving->bestel_order( 0.0, $data['cursus']->start_datum, $inschrijving->heeft_restant() ) );
			} else {
				$inschrijving->email( '_lopend' );
			}

			return [
				'content' => $this->goto_home(),
				'status'  => $this->status( 'De inschrijving is verwerkt en er is een email verzonden met nadere informatie' ),
			];
		}
	}

}
