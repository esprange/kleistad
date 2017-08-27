<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
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
class Kleistad_Public_Abonnee_Inschrijving extends Kleistad_Public_Shortcode {

	/**
	 * Prepareer 'abonnee_inschrijving' form
	 *
	 * @param array $data data to be prepared.
	 * @return array
	 *
	 * @since   4.0.0
	 */
	public function prepare( $data = null ) {
		if ( is_null( $data ) ) {
			$input = [
				'emailadres' => '',
				'voornaam' => '',
				'achternaam' => '',
				'straat' => '',
				'huisnr' => '',
				'pcode' => '',
				'plaats' => '',
				'telnr' => '',
				'abonnement_keuze' => '',
				'start' => '',
				'opmerking' => '',
			];
		} else {
			$input = $data ['input'];
		}
		$gebruikers = get_users(
			[
				'fields' => [ 'id', 'display_name' ],
				'orderby' => [ 'nicename' ],
			]
		);
		$data = [
			'gebruikers' => $gebruikers,
			'input' => $input,
		];
		return $data;
	}

	/**
	 * Valideer/sanitize 'abonnee_inschrijving' form
	 *
	 * @return array
	 *
	 * @since   4.0.0
	 */
	public function validate() {
		$error = new WP_Error();

		$input = filter_input_array(
			INPUT_POST, [
				'gebruiker_id' => FILTER_SANITIZE_EMAIL,
				'emailadres' => FILTER_SANITIZE_EMAIL,
				'voornaam' => FILTER_SANITIZE_STRING,
				'achternaam' => FILTER_SANITIZE_STRING,
				'straat' => FILTER_SANITIZE_STRING,
				'huisnr' => FILTER_SANITIZE_STRING,
				'pcode' => FILTER_SANITIZE_STRING,
				'plaats' => FILTER_SANITIZE_STRING,
				'telnr' => FILTER_SANITIZE_STRING,
				'abonnement_keuze' => FILTER_SANITIZE_STRING,
				'dag' => FILTER_SANITIZE_STRING,
				'start_datum' => FILTER_SANITIZE_STRING,
				'opmerking' => FILTER_SANITIZE_STRING,
			]
		);

		if ( '' == $input['abonnement_keuze'] ) {
			$error->add( 'verplicht', 'Er is nog geen type abonnement gekozen' );
		}
		if ( '' == $input['start_datum'] ) {
			$error->add( 'verplicht', 'Er is nog niet aangegeven wanneer het abonnement moet ingaan' );
		}
		if ( intval( $input['gebruiker_id'] ) == 0 ) {
			$input['emailadres'] = strtolower( $input['emailadres'] );
			if ( ! filter_var( $input['emailadres'], FILTER_VALIDATE_EMAIL ) ) {
				$error->add( 'verplicht', 'Een geldig E-mail adres is verplicht' );
			}
			$input['pcode'] = strtoupper( $input['pcode'] );
			if ( ! $input['voornaam'] ) {
				$error->add( 'verplicht', 'Een voornaam is verplicht' );
			}
			if ( ! $input['achternaam'] ) {
				$error->add( 'verplicht', 'Een achternaam is verplicht' );
			}
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		$data = [ 'input' => $input ];
		return $data;
	}

	/**
	 * Bewaar 'abonnee_inschrijving' form gegevens
	 *
	 * @param array $data data to be saved.
	 * @return string
	 *
	 * @since   4.0.0
	 */
	public function save( $data ) {
		$error = new WP_Error();

		if ( ! is_user_logged_in() ) {

			$gebruiker_id = email_exists( $input['emailadres'] );
			if ( $gebruiker_id ) {
				$gebruiker = new Kleistad_Gebruiker( $gebruiker_id );
				if ( Kleistad_Roles::reserveer( $gebruiker_id ) ) {
					$error->add( 'niet toegestaan', 'Het is niet mogelijk om een bestaand abonnement via dit formulier te wijzigen' );
					return $error;
				}
			} else {
				$gebruiker = new Kleistad_Gebruiker();
				$gebruiker->voornaam = $data['input']['voornaam'];
				$gebruiker->achternaam = $data['input']['achternaam'];
				$gebruiker->straat = $data['input']['straat'];
				$gebruiker->huisnr = $data['input']['huisnr'];
				$gebruiker->pcode = $data['input']['pcode'];
				$gebruiker->plaats = $data['input']['plaats'];
				$gebruiker->email = $data['input']['emailadres'];
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

		if ( is_super_admin() ) {
			return 'De inschrijving is verwerkt';
		}

		$to = "$gebruiker->voornaam $gebruiker->achternaam <$gebruiker->email>";
		if ( self::compose_email(
			$to, 'inschrijving abonnement', 'kleistad_email_abonnement', [
				'voornaam' => $gebruiker->voornaam,
				'achternaam' => $gebruiker->achternaam,
				'start_datum' => strftime( '%A %d-%m-%y', strtotime( $data['input']['start_datum'] ) ),
				'abonnement' => $abonnement->soort,
				'abonnement_code' => $abonnement->code,
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
