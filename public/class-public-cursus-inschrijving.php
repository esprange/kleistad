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
				'EMAIL'           => '',
				'email_controle'  => '',
				'FNAME'           => '',
				'LNAME'           => '',
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
			[ 'cursus' => '', 'verbergen' => '' ],
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
			$data['open_cursussen'][ $cursus->id ] = [
				'naam'          => $cursus->naam .
					', start ' . strftime( '%A %d-%m-%y', $cursus->start_datum ) .
					( $cursus->vol ? ' VOL' : ( $cursus->vervallen ? ' VERVALLEN' : '' ) ),
				'selecteerbaar' => ! $cursus->vol && ! $cursus->vervallen,
				'technieken'    => $cursus->technieken,
				'meer'          => $cursus->meer,
				'ruimte'        => $cursus->ruimte(),
				'prijs'         => ( 0 < $cursus->inschrijfkosten ? $cursus->inschrijfkosten : $cursus->cursuskosten ),
				'inschrijfgeld' => ( 0 < $cursus->inschrijfkosten ),
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
				'EMAIL'           => FILTER_SANITIZE_EMAIL,
				'email_controle'  => FILTER_SANITIZE_EMAIL,
				'FNAME'           => FILTER_SANITIZE_STRING,
				'LNAME'           => FILTER_SANITIZE_STRING,
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
				'opmerking'       => FILTER_SANITIZE_STRING,
				'betaal'          => FILTER_SANITIZE_STRING,
				'mc4wp-subscribe' => FILTER_SANITIZE_STRING,
			]
		);
		if ( false === intval( $data['input']['cursus_id'] ) ) {
			$error->add( 'verplicht', 'Er is nog geen cursus gekozen' );
			return $error;
		}
		$data['cursus'] = new \Kleistad\Cursus( $data['input']['cursus_id'] );
		$ruimte         = $data['cursus']->ruimte();
		if ( $data['cursus']->vol ) {
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
		if ( ! is_user_logged_in() ) {
			$gebruiker_id = email_exists( $data['input']['EMAIL'] );
			$gebruiker_id = Public_Main::upsert_user(
				[
					'ID'         => ( $gebruiker_id ) ? $gebruiker_id : null,
					'first_name' => $data['input']['FNAME'],
					'last_name'  => $data['input']['LNAME'],
					'telnr'      => $data['input']['telnr'],
					'user_email' => $data['input']['EMAIL'],
					'straat'     => $data['input']['straat'],
					'huisnr'     => $data['input']['huisnr'],
					'pcode'      => $data['input']['pcode'],
					'plaats'     => $data['input']['plaats'],
				]
			);
		} else {
			if ( is_super_admin() ) {
				$gebruiker_id = $data['input']['gebruiker_id'];
			} else {
				$gebruiker_id = get_current_user_id();
			}
		}

		$inschrijving = new \Kleistad\Inschrijving( $gebruiker_id, $data['cursus']->id );
		if ( $inschrijving->ingedeeld ) {
			return [
				'status' => $this->status( new \WP_Error( 'dubbel', 'Volgens onze administratie ben je al ingedeeld op deze cursus. Neem eventueel contact op met Kleistad.' ) ),
			];
		}
		$inschrijving->technieken = $data['input']['technieken'];
		$inschrijving->opmerking  = $data['input']['opmerking'];
		$inschrijving->aantal     = intval( $data['input']['aantal'] );
		$inschrijving->datum      = strtotime( 'today' );
		$inschrijving->save();

		$lopend = $data['cursus']->start_datum < strtotime( 'today' );

		if ( ! $lopend && 'ideal' === $data['input']['betaal'] ) {
			return [
				'redirect_uri' => $inschrijving->betalen(
					'Bedankt voor de betaling! De inschrijving is verwerkt en er wordt een email verzonden met bevestiging',
					true
				),
			];
		} else {
			$inschrijving->email( $lopend ? 'lopende' : 'inschrijving' );
			return [
				'content' => $this->goto_home(),
				'status'  => $this->status( 'De inschrijving is verwerkt en er is een email verzonden met nadere informatie' ),
			];
		}
	}

}
