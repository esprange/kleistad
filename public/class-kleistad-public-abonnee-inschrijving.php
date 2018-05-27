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
class Kleistad_Public_Abonnee_Inschrijving extends Kleistad_Shortcode {

	/**
	 * Prepareer 'abonnee_inschrijving' form
	 *
	 * @param array $data data to be prepared.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data ) {
		if ( is_null( $data ) ) {
			$data['input'] = [
				'gebruiker_id' => 0,
				'EMAIL' => '',
				'email_controle' => '',
				'FNAME' => '',
				'LNAME' => '',
				'straat' => '',
				'huisnr' => '',
				'pcode' => '',
				'plaats' => '',
				'telnr' => '',
				'abonnement_keuze' => '',
				'dag' => '',
				'start_datum' => '',
				'opmerking' => '',
				'betaal' => 'ideal',
				'mc4wp-subscribe' => '0',
			];
		}
		$atts = shortcode_atts(
			[
				'verklaring' => '',
			], $this->atts, 'kleistad_abonnee_inschrijving'
		);
		$gebruikers = get_users(
			[
				'fields' => [ 'id', 'display_name' ],
				'orderby' => [ 'nicename' ],
			]
		);
		$data['gebruikers'] = $gebruikers;
		$data['verklaring'] = htmlspecialchars_decode( $atts['verklaring'] );
		$data['bedrag_beperkt'] = 3 * $this->options['beperkt_abonnement'] + $this->options['borg_kast'];
		$data['bedrag_onbeperkt'] = 3 * $this->options['onbeperkt_abonnement'] + $this->options['borg_kast'];

		return true;
	}

	/**
	 * Valideer/sanitize 'abonnee_inschrijving' form
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
				'gebruiker_id' => FILTER_SANITIZE_NUMBER_INT,
				'EMAIL' => FILTER_SANITIZE_EMAIL,
				'email_controle' => FILTER_SANITIZE_EMAIL,
				'FNAME' => FILTER_SANITIZE_STRING,
				'LNAME' => FILTER_SANITIZE_STRING,
				'straat' => FILTER_SANITIZE_STRING,
				'huisnr' => FILTER_SANITIZE_STRING,
				'pcode' => FILTER_SANITIZE_STRING,
				'plaats' => FILTER_SANITIZE_STRING,
				'telnr' => FILTER_SANITIZE_STRING,
				'abonnement_keuze' => FILTER_SANITIZE_STRING,
				'dag' => FILTER_SANITIZE_STRING,
				'start_datum' => FILTER_SANITIZE_STRING,
				'opmerking' => FILTER_SANITIZE_STRING,
				'betaal' => FILTER_SANITIZE_STRING,
				'mc4wp-subscribe' => FILTER_SANITIZE_STRING,
			]
		);

		if ( '' === $input['abonnement_keuze'] ) {
			$error->add( 'verplicht', 'Er is nog geen type abonnement gekozen' );
		}
		if ( '' === $input['start_datum'] ) {
			$error->add( 'verplicht', 'Er is nog niet aangegeven wanneer het abonnement moet ingaan' );
		}
		if ( 0 === intval( $input['gebruiker_id'] ) ) {
			$email = strtolower( $input['EMAIL'] );
			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				$error->add( 'verplicht', 'De invoer ' . $input['EMAIL'] . ' is geen geldig E-mail adres.' );
				$input['EMAIL'] = '';
				$input['email_controle'] = '';
			} else {
				$input['EMAIL'] = $email;
				if ( strtolower( $input['email_controle'] !== $email ) ) {
					$error->add( 'verplicht', 'De ingevoerde e-mail adressen ' . $input['EMAIL'] . ' en ' . $input['email_controle'] . ' zijn niet identiek' );
					$input['email_controle'] = '';
				} else {
					$input['email_controle'] = $email;
				}
			}

			$input['pcode'] = strtoupper( str_replace( ' ', '', $input['pcode'] ) );

			$voornaam = preg_replace( '/[^a-zA-Z\s]/', '', $input['FNAME'] );
			if ( '' === $voornaam ) {
				$error->add( 'verplicht', 'Een voornaam (een of meer alfabetische karakters) is verplicht' );
				$input['FNAME'] = '';
			}
			$achternaam = preg_replace( '/[^a-zA-Z\s]/', '', $input['LNAME'] );
			if ( '' === $achternaam ) {
				$error->add( 'verplicht', 'Een achternaam (een of meer alfabetische karakters) is verplicht' );
				$input['LNAME'] = '';
			}
		}
		$data['input'] = $input;

		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Bewaar 'abonnee_inschrijving' form gegevens
	 *
	 * @param array $data data to be saved.
	 * @return string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		$error = new WP_Error();

		if ( ! is_user_logged_in() ) {

			$gebruiker_id = email_exists( $data['input']['EMAIL'] );
			if ( $gebruiker_id ) {
				$gebruiker = new Kleistad_Gebruiker( $gebruiker_id );
				if ( Kleistad_Roles::reserveer( $gebruiker_id ) ) {
					$error->add( 'niet toegestaan', 'Het is niet mogelijk om een bestaand abonnement via dit formulier te wijzigen' );
					return $error;
				}
			} else {
				$gebruiker = new Kleistad_Gebruiker();
				$gebruiker->voornaam   = $data['input']['FNAME'];
				$gebruiker->achternaam = $data['input']['LNAME'];
				$gebruiker->straat     = $data['input']['straat'];
				$gebruiker->huisnr     = $data['input']['huisnr'];
				$gebruiker->pcode      = $data['input']['pcode'];
				$gebruiker->plaats     = $data['input']['plaats'];
				$gebruiker->email      = $data['input']['EMAIL'];
				$gebruiker->telnr      = $data['input']['telnr'];
				$gebruiker_id = $gebruiker->save();
			}
		} elseif ( is_super_admin() ) {
			$gebruiker_id = $data['input']['gebruiker_id'];
			$gebruiker = new Kleistad_Gebruiker( $gebruiker_id );
		} else {
			$error->add( 'niet toegestaan', 'Het is niet mogelijk om een bestaand abonnement via dit formulier te wijzigen' );
			return $error;
		}

		$abonnement = new Kleistad_Abonnement( $gebruiker_id );
		$abonnement->soort       = $data['input']['abonnement_keuze'];
		$abonnement->opmerking   = $data['input']['opmerking'];
		$abonnement->start_datum = strtotime( $data['input']['start_datum'] );
		$abonnement->dag         = $data['input']['dag'];
		$abonnement->save();

		$status = $abonnement->start( $abonnement->start_datum, $data['input']['betaal'] );
		if ( $status ) {
			return 'De inschrijving van het abonnement is verwerkt en er wordt een email verzonden met bevestiging';
		} else {
			$error->add( '', 'De inschrijving van het abonnement was niet mogelijk, neem eventueel contact op met Kleistad' );
		}
		return $error;
	}

}
