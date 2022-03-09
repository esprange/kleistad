<?php
/**
 * De  class voor de input validators.
 *
 * @link       https://www.kleistad.nl
 * @since      6.17.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use WP_Error;

/**
 * De class voor validator
 */
class Validator {

	/**
	 * Valideer opvoeren nieuwe gebruiker
	 *
	 * @since 5.2.1
	 * @param array $input de ingevoerde data.
	 * @return WP_Error|bool
	 */
	public function gebruiker( array &$input ): WP_Error|bool {
		$error = new WP_Error();
		if ( ! $this->email( $input['user_email'] ) ) {
			$error->add( 'verplicht', "De invoer {$input['user_email']} is geen geldig E-mail adres." );
			$input['user_email']     = '';
			$input['email_controle'] = '';
		}
		if ( isset( $input['email_controle'] ) && 0 !== strcasecmp( $input['email_controle'], $input['user_email'] ) ) {
			$error->add( 'verplicht', "De ingevoerde e-mail adressen {$input['user_email']} en {$input['email_controle']} zijn niet identiek" );
			$input['email_controle'] = '';
		}
		if ( ! $this->telnr( $input['telnr'] ) ) {
			$error->add( 'onjuist', "Het ingevoerde telefoonnummer {$input['telnr']} lijkt niet correct. Alleen Nederlandse telefoonnummers kunnen worden doorgegeven" );
			$input['telnr'] = '';
		}
		if ( ! $this->pcode( $input['pcode'] ) ) {
			$error->add( 'onjuist', "De ingevoerde postcode {$input['pcode']} lijkt niet correct. Alleen Nederlandse postcodes kunnen worden doorgegeven" );
			$input['pcode'] = '';
		}
		if ( ! $this->naam( $input['first_name'] ) ) {
			$error->add( 'verplicht', 'Een voornaam (een of meer alfabetische karakters) is verplicht' );
			$input['first_name'] = '';
		}
		if ( ! $this->naam( $input['last_name'] ) ) {
			$error->add( 'verplicht', 'Een achternaam (een of meer alfabetische karakters) is verplicht' );
			$input['last_name'] = '';
		}
		return empty( $error->get_error_codes() ) ?: $error;
	}

	/**
	 * Hulp functie, om een telefoonnr te valideren
	 *
	 * @since 5.2.0
	 * @param string $telnr het telefoonnummer, inclusief spaties, streepjes etc.
	 * @return bool if false, dan niet gevalideerd.
	 */
	public function telnr( string &$telnr ) : bool {
		if ( empty( $telnr ) ) {
			return true;
		}
		$telnr = str_replace( [ ' ', '-' ], [ '', '' ], $telnr );
		return 1 === preg_match( '/^(((0)[1-9]{2}[0-9][-]?[1-9][0-9]{5})|((\\+31|0|0031)[1-9][0-9][-]?[1-9][0-9]{6}))$/', $telnr ) ||
				1 === preg_match( '/^(((\\+31|0|0031)6)[1-9][0-9]{7})$/i', $telnr );
	}

	/**
	 * Hulp functie, om een postcode te valideren
	 *
	 * @since 5.2.0
	 * @param string $pcode de postcode, inclusief spaties, streepjes etc.
	 * @return bool if false, dan niet gevalideerd.
	 */
	public function pcode( string &$pcode ) : bool {
		if ( empty( $pcode ) ) {
			return true;
		}
		$pcode = strtoupper( str_replace( ' ', '', $pcode ) );
		return 1 === preg_match( '/^[1-9][0-9]{3} ?[a-zA-Z]{2}$/', $pcode );
	}

	/**
	 * Hulp functie, om een naam te valideren
	 *
	 * @since 5.2.0
	 * @param string $naam de naam.
	 * @return bool if false, dan niet gevalideerd.
	 */
	public function naam( string $naam ) : bool {
		return 1 !== preg_match( '/[\^<,\"@\/{}()*$%?=>:|;#]+/i', html_entity_decode( $naam, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	/**
	 * Hulp functie, om een email
	 *
	 * @since 5.2.0
	 * @param string $email het email adres.
	 * @return bool if false, dan niet gevalideerd.
	 */
	public function email( string &$email ) : bool {
		$email = strtolower( $email );
		return filter_var( $email, FILTER_VALIDATE_EMAIL );
	}

}
