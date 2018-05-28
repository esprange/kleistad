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
class Kleistad_Public_Dagdelenkaart extends Kleistad_Shortcode {

	/**
	 *
	 * Prepareer 'dagdelenkaart' form
	 *
	 * @param array $data the prepared data.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		if ( is_null( $data ) ) {
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
				'start_datum'     => '',
				'opmerking'       => '',
				'betaal'          => 'ideal',
				'mc4wp-subscribe' => '0',
			];
		}
		$atts               = shortcode_atts(
			[
				'verklaring' => '',
			], $this->atts, 'kleistad_dagdelenkaart'
		);
		$data['verklaring'] = htmlspecialchars_decode( $atts['verklaring'] );
		return true;
	}

	/**
	 * Valideer/sanitize 'dagdelenkaart' form
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
				'EMAIL'           => FILTER_SANITIZE_EMAIL,
				'email_controle'  => FILTER_SANITIZE_EMAIL,
				'FNAME'           => FILTER_SANITIZE_STRING,
				'LNAME'           => FILTER_SANITIZE_STRING,
				'straat'          => FILTER_SANITIZE_STRING,
				'huisnr'          => FILTER_SANITIZE_STRING,
				'pcode'           => FILTER_SANITIZE_STRING,
				'plaats'          => FILTER_SANITIZE_STRING,
				'telnr'           => FILTER_SANITIZE_STRING,
				'start_datum'     => FILTER_SANITIZE_STRING,
				'opmerking'       => FILTER_SANITIZE_STRING,
				'betaal'          => FILTER_SANITIZE_STRING,
				'mc4wp-subscribe' => FILTER_SANITIZE_STRING,
			]
		);

		if ( '' === $input['start_datum'] ) {
			$error->add( 'verplicht', 'Er is nog niet aangegeven wanneer de dagdelenkaart moet ingaan' );
		}
		$email = strtolower( $input['EMAIL'] );
		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			$error->add( 'verplicht', 'De invoer ' . $input['EMAIL'] . ' is geen geldig E-mail adres.' );
			$input['EMAIL']          = '';
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
		$data['input'] = $input;

		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Bewaar 'dagdelenkaart' form gegevens
	 *
	 * @param array $data the data to be saved.
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
			} else {
				$gebruiker             = new Kleistad_Gebruiker();
				$gebruiker->voornaam   = $data['input']['FNAME'];
				$gebruiker->achternaam = $data['input']['LNAME'];
				$gebruiker->straat     = $data['input']['straat'];
				$gebruiker->huisnr     = $data['input']['huisnr'];
				$gebruiker->pcode      = $data['input']['pcode'];
				$gebruiker->plaats     = $data['input']['plaats'];
				$gebruiker->email      = $data['input']['EMAIL'];
				$gebruiker->telnr      = $data['input']['telnr'];
				$gebruiker_id          = $gebruiker->save();
			}
		}

		$dagdelenkaart              = new Kleistad_Dagdelenkaart( $gebruiker_id );
		$dagdelenkaart->opmerking   = $data['input']['opmerking'];
		$dagdelenkaart->start_datum = strtotime( $data['input']['start_datum'] );
		$dagdelenkaart->save();

		if ( 'ideal' === $data['input']['betaal'] ) {
			$dagdelenkaart->betalen(
				$this->options['dagdelenkaart'],
				'Bedankt voor de betaling! Een dagdelenkaart is aangemaakt en kan bij Kleistad opgehaald worden'
			);
		} else {
			if ( $dagdelenkaart->email( '_bank' ) ) {
				return 'Er is een email verzonden met nadere informatie over de betaling';
			} else {
				$error->add( '', 'Een bevestigings email kon niet worden verzonden. Neem s.v.p. contact op met Kleistad.' );
				return $error;
			}
		}
	}
}
