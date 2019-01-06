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

/**
 * De kleistad cursus inschrijving class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Cursus_Inschrijving extends Kleistad_ShortcodeForm {

	/**
	 *
	 * Prepareer 'cursus_inschrijving' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {

		if ( is_null( $data ) ) {
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
			[ 'cursus' => '' ],
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
		$open_cursussen          = [];
		$cursussen               = Kleistad_Cursus::all( true );
		$cursus_selecties        = '' !== $atts['cursus'] ? explode( ',', preg_replace( '/\s+|C/', '', $atts['cursus'] ) ) : [];
		if ( 1 === count( $cursus_selecties ) ) {
			$data['cursus_selectie']    = false;
			$data['input']['cursus_id'] = $cursus_selecties[0];
		}
		foreach ( $cursussen as $cursus ) {
			if ( ! empty( $cursus_selecties ) && ! in_array( $cursus->id, $cursus_selecties, false ) ) { // phpcs:ignore
				continue;
			}

			$open_cursussen[ $cursus->id ] = [
				'naam'          => $cursus->naam .
					', start ' . strftime( '%A %d-%m-%y', $cursus->start_datum ) .
					( $cursus->vol ? ' VOL' : ( $cursus->vervallen ? ' VERVALLEN' : '' ) ),
				'selecteerbaar' => ! $cursus->vol && ! $cursus->vervallen,
				'technieken'    => $cursus->technieken,
				'meer'          => $cursus->meer,
				'ruimte'        => $cursus->ruimte,
				'prijs'         => ( 0 < $cursus->inschrijfkosten ? $cursus->inschrijfkosten : $cursus->cursuskosten ),
				'inschrijfgeld' => ( 0 < $cursus->inschrijfkosten ),
				'lopend'        => $cursus->start_datum < strtotime( 'today' ),
			];
		}
		$data['open_cursussen'] = $open_cursussen;
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
	public function validate( &$data ) {
		$error          = new WP_Error();
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
				'cursus_id'       => FILTER_SANITIZE_NUMBER_INT,
				'gebruiker_id'    => FILTER_SANITIZE_NUMBER_INT,
				'technieken'      => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_FORCE_ARRAY,
				],
				'opmerking'       => FILTER_SANITIZE_STRING,
				'aantal'          => FILTER_SANITIZE_STRING,
				'betaal'          => FILTER_SANITIZE_STRING,
				'mc4wp-subscribe' => FILTER_SANITIZE_STRING,
			]
		);
		if ( 0 === intval( $data['input']['cursus_id'] ) ) {
			$error->add( 'verplicht', 'Er is nog geen cursus gekozen' );
		} else {
			$data['cursus'] = new Kleistad_Cursus( $data['input']['cursus_id'] );
			if ( is_null( $data['cursus']->id ) ) {
				$error->add( 'onbekend', 'De gekozen cursus is niet bekend' );
				$data['input']['cursus_id'] = 0;
			} elseif ( $data['cursus']->vol ) {
				$error->add( 'vol', 'De gekozen cursus is vol. Inschrijving is niet mogelijk.' );
				$data['input']['cursus_id'] = 0;
			} else {
				$ruimte = $data['cursus']->ruimte;
				if ( 0 === $ruimte ) {
					$error->add( 'vol', 'Er zijn geen plaatsen meer beschikbaar. Inschrijving is niet mogelijk.' );
					$data['input']['cursus_id'] = 0;
				} elseif ( $ruimte < $data['input']['aantal'] ) {
					$error->add( 'vol', 'Er zijn maar ' . $ruimte . ' plaatsen beschikbaar. Pas het aantal eventueel aan.' );
					$data['input']['aantal'] = $ruimte;
				}
			}
		}
		if ( 1 > $data['input']['aantal'] ) {
			$error->add( 'aantal', 'Het aantal cursisten moet minimaal gelijk zijn aan 1' );
			$data['input']['aantal'] = 1;
		}
		if ( 0 === intval( $data['input']['gebruiker_id'] ) ) {
			$email = strtolower( $data['input']['EMAIL'] );
			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				$error->add( 'verplicht', 'De invoer ' . $data['input']['EMAIL'] . ' is geen geldig E-mail adres.' );
				$data['input']['EMAIL']          = '';
				$data['input']['email_controle'] = '';
			} else {
				$data['input']['EMAIL'] = $email;
				if ( strtolower( $data['input']['email_controle'] ) !== $email ) {
					$error->add( 'verplicht', 'De ingevoerde e-mail adressen ' . $data['input']['EMAIL'] . ' en ' . $data['input']['email_controle'] . ' zijn niet identiek' );
					$data['input']['email_controle'] = '';
				} else {
					$data['input']['email_controle'] = $email;
				}
			}

			$data['input']['pcode'] = strtoupper( str_replace( ' ', '', $data['input']['pcode'] ) );

			$voornaam = preg_replace( '/[^a-zA-Z\s]/', '', $data['input']['FNAME'] );
			if ( '' === $voornaam ) {
				$error->add( 'verplicht', 'Een voornaam (een of meer alfabetische karakters) is verplicht' );
				$data['input']['FNAME'] = '';
			}
			$achternaam = preg_replace( '/[^a-zA-Z\s]/', '', $data['input']['LNAME'] );
			if ( '' === $achternaam ) {
				$error->add( 'verplicht', 'Een achternaam (een of meer alfabetische karakters) is verplicht' );
				$data['input']['LNAME'] = '';
			}
		}
		if ( is_null( $data['input']['technieken'] ) ) {
			$data['input']['technieken'] = [];
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
	 * @return \WP_Error|string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		$error = new WP_Error();

		if ( ! is_user_logged_in() ) {
			$gebruiker_id = email_exists( $data['input']['EMAIL'] );
			$gebruiker_id = Kleistad_Public::upsert_user(
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

		$inschrijving = new Kleistad_Inschrijving( $gebruiker_id, $data['cursus']->id );
		if ( $inschrijving->ingedeeld ) {
			$error->add( 'dubbel', 'Volgens onze administraie ben je al ingedeeld op deze cursus. Neem eventueel contact op met Kleistad.' );
			return $error;
		}
		$inschrijving->technieken = $data['input']['technieken'];
		$inschrijving->opmerking  = $data['input']['opmerking'];
		$inschrijving->aantal     = intval( $data['input']['aantal'] );
		$inschrijving->datum      = strtotime( 'today' );
		$inschrijving->save();

		$lopend = $data['cursus']->start_datum < strtotime( 'today' );

		if ( ! $lopend && 'ideal' === $data['input']['betaal'] ) {
			$inschrijving->betalen(
				'Bedankt voor de betaling! De inschrijving is verwerkt en er wordt een email verzonden met bevestiging',
				true
			);
		} else {
			if ( $inschrijving->email( $lopend ? 'lopende' : 'inschrijving' ) ) {
				return 'De inschrijving is verwerkt en er is een email verzonden met nadere informatie';
			} else {
				$error->add( '', 'De inschrijving is verwerkt maar een bevestigings email kon niet worden verzonden. Neem s.v.p. contact op met Kleistad.' );
				return $error;
			}
		}
	}

}
