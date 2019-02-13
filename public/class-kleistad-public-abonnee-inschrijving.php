<?php
/**
 * Shortcode abonnee inschrijvingen.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * De class Abonnee Inschrijving.
 */
class Kleistad_Public_Abonnee_Inschrijving extends Kleistad_ShortcodeForm {

	/**
	 * Prepareer 'abonnee_inschrijving' form
	 *
	 * @param array $data data voor het formulier.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		if ( is_null( $data ) ) {
			$data          = [];
			$data['input'] = [
				'gebruiker_id'     => 0,
				'EMAIL'            => '',
				'email_controle'   => '',
				'FNAME'            => '',
				'LNAME'            => '',
				'straat'           => '',
				'huisnr'           => '',
				'pcode'            => '',
				'plaats'           => '',
				'telnr'            => '',
				'abonnement_keuze' => '',
				'extras'           => [],
				'dag'              => '',
				'start_datum'      => '',
				'opmerking'        => '',
				'betaal'           => 'ideal',
				'mc4wp-subscribe'  => '0',
			];
		}
		$atts               = shortcode_atts(
			[ 'verklaring' => '' ],
			$this->atts,
			'kleistad_abonnee_inschrijving'
		);
		$gebruikers         = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => [ 'nicename' ],
			]
		);
		$data['gebruikers'] = $gebruikers;
		$data['verklaring'] = htmlspecialchars_decode( $atts['verklaring'] );

		return true;
	}

	/**
	 * Valideer/sanitize 'abonnee_inschrijving' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return \WP_Error|bool
	 *
	 * @since   4.0.87
	 */
	public function validate( &$data ) {
		$error = new WP_Error();

		$input = filter_input_array(
			INPUT_POST,
			[
				'gebruiker_id'     => FILTER_SANITIZE_NUMBER_INT,
				'EMAIL'            => FILTER_SANITIZE_EMAIL,
				'email_controle'   => FILTER_SANITIZE_EMAIL,
				'FNAME'            => FILTER_SANITIZE_STRING,
				'LNAME'            => FILTER_SANITIZE_STRING,
				'straat'           => FILTER_SANITIZE_STRING,
				'huisnr'           => FILTER_SANITIZE_STRING,
				'pcode'            => FILTER_SANITIZE_STRING,
				'plaats'           => FILTER_SANITIZE_STRING,
				'telnr'            => FILTER_SANITIZE_STRING,
				'abonnement_keuze' => FILTER_SANITIZE_STRING,
				'extras'           => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'dag'              => FILTER_SANITIZE_STRING,
				'start_datum'      => FILTER_SANITIZE_STRING,
				'opmerking'        => FILTER_SANITIZE_STRING,
				'betaal'           => FILTER_SANITIZE_STRING,
				'mc4wp-subscribe'  => FILTER_SANITIZE_STRING,
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
			if ( ! empty( $input['telnr'] ) ) {
				$telnr = str_replace( [ ' ', '-' ], [ '', '' ], $input['telnr'] );
				if ( ! ( preg_match( '/^(((0)[1-9]{2}[0-9][-]?[1-9][0-9]{5})|((\\+31|0|0031)[1-9][0-9][-]?[1-9][0-9]{6}))$/', $telnr ) ||
					preg_match( '/^(((\\+31|0|0031)6){1}[1-9]{1}[0-9]{7})$/i', $telnr ) ) ) {
					$error->add( 'onjuist', 'Het ingevoerde telefoonnummer lijkt niet correct. Alleen Nederlandse telefoonnummers kunnen worden doorgegeven' );
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
		if ( ! is_array( $input['extras'] ) ) {
			$input['extras'] = [];
		};
		$data['input'] = $input;

		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Bewaar 'abonnee_inschrijving' form gegevens
	 *
	 * @param array $data te bewaren data.
	 * @return \WP_Error|string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		$error = new WP_Error();

		if ( ! is_user_logged_in() ) {

			$gebruiker_id = email_exists( $data['input']['EMAIL'] );
			if ( false !== $gebruiker_id && Kleistad_Roles::reserveer( $gebruiker_id ) ) {
				$error->add( 'niet toegestaan', 'Het is niet mogelijk om een bestaand abonnement via dit formulier te wijzigen' );
				return $error;
			};
			$gebruiker_id = Kleistad_Public::upsert_user(
				[
					'ID'         => ( false !== $gebruiker_id ) ? $gebruiker_id : null,
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
		} elseif ( is_super_admin() ) {
			$gebruiker_id = $data['input']['gebruiker_id'];
		} else {
			$error->add( 'niet toegestaan', 'Het is niet mogelijk om een bestaand abonnement via dit formulier te wijzigen' );
			return $error;
		}

		$abonnement              = new Kleistad_Abonnement( $gebruiker_id );
		$abonnement->soort       = $data['input']['abonnement_keuze'];
		$abonnement->opmerking   = $data['input']['opmerking'];
		$abonnement->start_datum = strtotime( $data['input']['start_datum'] );
		$abonnement->geannuleerd = false;
		$abonnement->gepauzeerd  = false;
		$abonnement->dag         = $data['input']['dag'];
		$abonnement->extras      = $data['input']['extras'];
		$abonnement->save();

		$abonnement->start( $abonnement->start_datum, $data['input']['betaal'] );
		return 'De inschrijving van het abonnement is verwerkt en er wordt een email verzonden met bevestiging';
	}

}
