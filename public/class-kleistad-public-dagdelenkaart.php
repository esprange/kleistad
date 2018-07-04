<?php
/**
 * Shortcode dagdelenkaart.
 *
 * @link       https://www.kleistad.nl
 * @since      4.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * De kleistad dagdelenkaart class.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
class Kleistad_Public_Dagdelenkaart extends Kleistad_ShortcodeForm {

	/**
	 *
	 * Prepareer 'dagdelenkaart' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   4.3.0
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
	 * @param array $data gevalideerde data.
	 * @return \WP_Error|bool
	 *
	 * @since   4.3.0
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
			if ( strtolower( $input['email_controle'] ) !== $email ) {
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
	 * @param array $data te bewaren saved.
	 * @return \WP_Error|string
	 *
	 * @since   4.3.0
	 */
	public function save( $data ) {
		$error = new WP_Error();

		$gebruiker_id = email_exists( $data['input']['EMAIL'] );
		if ( ! $gebruiker_id ) {
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

		$dagdelenkaart              = new Kleistad_Dagdelenkaart( $gebruiker_id );
		$dagdelenkaart->opmerking   = $data['input']['opmerking'];
		$dagdelenkaart->start_datum = strtotime( $data['input']['start_datum'] );
		$dagdelenkaart->save();

		if ( 'ideal' === $data['input']['betaal'] ) {
			$dagdelenkaart->betalen(
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
