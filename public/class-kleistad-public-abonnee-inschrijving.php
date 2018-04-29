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
				'EMAIL' => '',
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
				'gebruiker_id' => FILTER_SANITIZE_EMAIL,
				'EMAIL' => FILTER_SANITIZE_EMAIL,
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
				'bank' => FILTER_SANITIZE_STRING,
			]
		);

		if ( '' === $input['abonnement_keuze'] ) {
			$error->add( 'verplicht', 'Er is nog geen type abonnement gekozen' );
		}
		if ( '' === $input['start_datum'] ) {
			$error->add( 'verplicht', 'Er is nog niet aangegeven wanneer het abonnement moet ingaan' );
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
				$gebruiker->voornaam = $data['input']['FNAME'];
				$gebruiker->achternaam = $data['input']['LNAME'];
				$gebruiker->straat = $data['input']['straat'];
				$gebruiker->huisnr = $data['input']['huisnr'];
				$gebruiker->pcode = $data['input']['pcode'];
				$gebruiker->plaats = $data['input']['plaats'];
				$gebruiker->email = $data['input']['EMAIL'];
				$gebruiker->telnr = $data['input']['telnr'];
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
		$abonnement->soort = $data['input']['abonnement_keuze'];
		$abonnement->opmerking = $data['input']['opmerking'];
		$abonnement->start_datum = strtotime( $data['input']['start_datum'] );
		$abonnement->dag = $data['input']['dag'];
		$abonnement->save();

		$prijs = 3 * $this->options[ $abonnement->soort . '_abonnement' ] + $this->options['borg_kast'];
		if ( 'ideal' === $data['input']['betaal'] ) {
			$abonnement->betalen(
				$prijs,
				$data['input']['bank'],
				'Bedankt voor de betaling! De inschrijving is verwerkt en er wordt een email verzonden met bevestiging'
			);
		} else {
			if ( $abonnement->email( '' ) ) {
				return 'De inschrijving is verwerkt en er is een email verzonden met nadere informatie over de betaling';
			} else {
				$error->add( '', 'De inschrijving is verwerkt maar een bevestigings email kon niet worden verzonden. Neem s.v.p. contact op met Kleistad.' );
				return $error;
			}
		}

		$to = "$gebruiker->voornaam $gebruiker->achternaam <$gebruiker->email>";
		if ( Kleistad_Public::compose_email(
			$to, 'inschrijving abonnement', 'kleistad_email_abonnement', [
				'voornaam' => $gebruiker->voornaam,
				'achternaam' => $gebruiker->achternaam,
				'start_datum' => strftime( '%A %d-%m-%y', strtotime( $data['input']['start_datum'] ) ),
				'abonnement' => $abonnement->soort,
				'abonnement_code' => $abonnement->code,
				'abonnement_dag' => $abonnement->dag,
				'abonnement_opmerking' => $abonnement->opmerking,
				'abonnement_startgeld' => number_format( 3 * $this->options[ $abonnement->soort . '_abonnement' ], 2, ',', '' ),
				'abonnement_maandgeld' => number_format( $this->options[ $abonnement->soort . '_abonnement' ], 2, ',', '' ),
			]
		) ) {
			return 'De inschrijving is verwerkt en er is een email verzonden met bevestiging';
		} else {
			$error->add( '', 'De inschrijving is verwerkt maar een bevestigings email kon niet worden verzonden' );
			return $error;
		}
	}

}
