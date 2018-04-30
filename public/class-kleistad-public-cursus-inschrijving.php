<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public_Cursus_Inschrijving extends Kleistad_Shortcode {

	/**
	 *
	 * Prepareer 'cursus_inschrijving' form
	 *
	 * @param array $data data to be prepared.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {

		if ( is_null( $data ) ) {
			$data['input'] = [
				'EMAIL' => '',
				'FNAME' => '',
				'LNAME' => '',
				'straat' => '',
				'huisnr' => '',
				'pcode' => '',
				'plaats' => '',
				'telnr' => '',
				'cursus_id' => 0,
				'aantal' => 1,
				'opmerking' => '',
			];
		}
		$gebruikers = get_users(
			[
				'fields' => [ 'id', 'display_name' ],
				'orderby' => [ 'nicename' ],
			]
		);
		$data['gebruikers'] = $gebruikers;

		$open_cursussen = [];
		$cursus_store = new Kleistad_Cursussen();
		$cursussen = $cursus_store->get();
		foreach ( $cursussen as $cursus ) {

			if ( $cursus->eind_datum < time() ) {
				continue;
			}
			$open_cursussen[ $cursus->id ] = [
				'naam' => $cursus->naam .
					', start ' . strftime( '%A %d-%m-%y', $cursus->start_datum ) .
					( $cursus->vol ? ' VOL' : ( $cursus->vervallen ? ' VERVALLEN' : '' ) ),
				'selecteerbaar' => ! $cursus->vol && ! $cursus->vervallen,
				'technieken' => $cursus->technieken,
				'meer' => $cursus->meer,
				'ruimte' => $cursus->ruimte,
				'prijs' => ( 0 < $cursus->inschrijfkosten ? $cursus->inschrijfkosten : $cursus->cursuskosten ),
				'lopend' => $cursus->start_datum < strtotime( 'today' ),
			];
		}
		$data['open_cursussen'] = $open_cursussen;
		return true;
	}

	/**
	 * Valideer/sanitize 'cursus_inschrijving' form
	 *
	 * @param array $data Returned data.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function validate( &$data ) {
		$error = new WP_Error();

		$input = filter_input_array(
			INPUT_POST, [
				'EMAIL' => FILTER_SANITIZE_EMAIL,
				'FNAME' => FILTER_SANITIZE_STRING,
				'LNAME' => FILTER_SANITIZE_STRING,
				'straat' => FILTER_SANITIZE_STRING,
				'huisnr' => FILTER_SANITIZE_STRING,
				'pcode' => FILTER_SANITIZE_STRING,
				'plaats' => FILTER_SANITIZE_STRING,
				'telnr' => FILTER_SANITIZE_STRING,
				'cursus_id' => FILTER_SANITIZE_NUMBER_INT,
				'gebruiker_id' => FILTER_SANITIZE_NUMBER_INT,
				'technieken' => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags' => FILTER_FORCE_ARRAY,
				],
				'opmerking' => FILTER_SANITIZE_STRING,
				'aantal' => FILTER_SANITIZE_STRING,
				'betaal' => FILTER_SANITIZE_STRING,
				'bank' => FILTER_SANITIZE_STRING,
			]
		);

		$cursus = null;
		if ( 0 === intval( $input['cursus_id'] ) ) {
			$error->add( 'verplicht', 'Er is nog geen cursus gekozen' );
		} else {
			$cursus = new Kleistad_Cursus( $input['cursus_id'] );
			if ( is_null( $cursus->id ) ) {
				$error->add( 'onbekend', 'De gekozen cursus is niet bekend' );
			} elseif ( $cursus->vol ) {
				$error->add( 'vol', 'De gekozen cursus is vol. Inschrijving is niet mogelijk.' );
			} else {
				$ruimte = $cursus->ruimte;
				if ( 0 === $ruimte ) {
					$error->add( 'vol', 'Er zijn geen plaatsen meer beschikbaar. Inschrijving is niet mogelijk.' );
				} elseif ( $ruimte < $input['aantal'] ) {
					$error->add( 'vol', 'Er zijn maar ' . $ruimte . ' plaatsen beschikbaar. Pas het aantal eventueel aan.' );
				}
			}
		}
		if ( 1 > $input['aantal'] ) {
			$error->add( 'aantal', 'Het aantal cursisten moet minimaal gelijk zijn aan 1' );
		}
		if ( 0 === intval( $input['gebruiker_id'] ) ) {
			$input['EMAIL'] = strtolower( $input['EMAIL'] );
			if ( ! filter_var( $input['EMAIL'], FILTER_VALIDATE_EMAIL ) ) {
				$error->add( 'verplicht', 'Een geldig E-mail adres is verplicht' );
			}
			$input['pcode'] = strtoupper( $input['pcode'] );
			if ( ! $input['FNAME'] ) {
				$error->add( 'verplicht', 'Een voornaam is verplicht' );
			}
			if ( ! $input['LNAME'] ) {
				$error->add( 'verplicht', 'Een achternaam is verplicht' );
			}
		}
		$data ['input'] = $input;
		$data ['cursus'] = $cursus;

		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 *
	 * Bewaar 'cursus_inschrijving' form gegevens
	 *
	 * @param array $data data to be saved.
	 * @return string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		$error = new WP_Error();

		if ( ! is_user_logged_in() ) {
			$gebruiker = new Kleistad_Gebruiker();
			$gebruiker->voornaam = $data['input']['FNAME'];
			$gebruiker->achternaam = $data['input']['LNAME'];
			$gebruiker->straat = $data['input']['straat'];
			$gebruiker->huisnr = $data['input']['huisnr'];
			$gebruiker->pcode = $data['input']['pcode'];
			$gebruiker->plaats = $data['input']['plaats'];
			$gebruiker->email = $data['input']['EMAIL'];
			$gebruiker->telnr = $data['input']['telnr'];
			$gebruiker_id = $gebruiker->save();
		} else {
			if ( is_super_admin() ) {
				$gebruiker_id = $data['input']['gebruiker_id'];
			} else {
				$gebruiker_id = get_current_user_id();
			}
			$gebruiker = new Kleistad_Gebruiker( $gebruiker_id );
		}

		$inschrijving = new Kleistad_Inschrijving( $gebruiker_id, $data['cursus']->id );
		$inschrijving->technieken = $data['input']['technieken'];
		$inschrijving->opmerking = $data['input']['opmerking'];
		$inschrijving->aantal = intval( $data['input']['aantal'] );
		$inschrijving->datum = time();
		$inschrijving->save();

		$lopend = $data['cursus']->start_datum < strtotime( 'today' );

		if ( ! $lopend && 'ideal' === $data['input']['betaal'] ) {
			$inschrijving->betalen(
				$data['input']['aantal'] * ( 0 < $data['cursus']->inschrijfkosten ? $data['cursus']->inschrijfkosten : $data['cursus']->cursuskosten ),
				$data['input']['bank'],
				'Bedankt voor de betaling! De inschrijving is verwerkt en er wordt een email verzonden met bevestiging'
			);
		} else {
			if ( $inschrijving->email( $lopend ? 'lopend' : 'inschrijf' ) ) {
				return 'De inschrijving is verwerkt en er is een email verzonden met nadere informatie';
			} else {
				$error->add( '', 'De inschrijving is verwerkt maar een bevestigings email kon niet worden verzonden. Neem s.v.p. contact op met Kleistad.' );
				return $error;
			}
		}
	}

}
