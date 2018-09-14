<?php
/**
 * Shortcode betaling van restant cursus of vervolg abonnement.
 *
 * @link       https://www.kleistad.nl
 * @since      4.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * De class Betaling.
 */
class Kleistad_Public_Betaling extends Kleistad_ShortcodeForm {

	const ACTIE_RESTANT_CURSUS     = 'restant_cursus';
	const ACTIE_VERVOLG_ABONNEMENT = 'vervolg_abonnement';

	/**
	 *
	 * Prepareer 'betaling' form
	 *
	 * @param array $data formulier data.
	 * @return \WP_Error|bool
	 *
	 * @since   4.2.0
	 */
	public function prepare( &$data = null ) {
		$error = new WP_Error();
		$param = filter_input_array(
			INPUT_GET,
			[
				'gid'  => FILTER_SANITIZE_NUMBER_INT,
				'crss' => FILTER_SANITIZE_NUMBER_INT,
				'abo'  => FILTER_SANITIZE_NUMBER_INT,
				'hsh'  => FILTER_SANITIZE_STRING,
			]
		);

		if ( is_null( $param['hsh'] ) ) {
			return true; // Waarschijnlijk bezoek na succesvolle betaling. Pagina blijft leeg, behalve eventuele boodschap.
		}

		$gebruiker_id = $param['gid'];
		$gebruiker    = get_userdata( $gebruiker_id );

		if ( ! is_null( $param['crss'] ) ) {
			$cursus_id    = $param['crss'];
			$inschrijving = new Kleistad_Inschrijving( $gebruiker_id, $cursus_id );
			if ( $param['hsh'] === $inschrijving->controle() ) {
				if ( $inschrijving->c_betaald ) {
					$error->add( 'Betaald', 'Volgens onze informatie is er reeds betaald voor deze cursus. Neem eventueel contact op met Kleistad' );
				} else {
					$cursus = new Kleistad_Cursus( $cursus_id );
					$data   = [
						'actie'        => self::ACTIE_RESTANT_CURSUS,
						'cursist'      => $gebruiker,
						'cursus'       => $cursus,
						'inschrijving' => $inschrijving,
					];
				}
			} else {
				$error->add( 'Security', 'Je hebt geklikt op een ongeldige link of deze is nu niet geldig meer.' );
			}
		} elseif ( ! is_null( $param['abo'] ) ) {
			$abonnement = new Kleistad_Abonnement( $gebruiker_id );
			if ( $param['hsh'] === $abonnement->controle() ) {
				if ( $abonnement->incasso_actief() ) {
					$error->add( 'Betaald', 'Volgens onze informatie is er reeds betaald voor het vervolg van het abonnement. Neem eventueel contact op met Kleistad' );
				} else {
					$data = [
						'actie'      => self::ACTIE_VERVOLG_ABONNEMENT,
						'abonnee'    => $gebruiker,
						'abonnement' => $abonnement,
					];
				}
			} else {
				$error->add( 'Security', 'Je hebt geklikt op een ongeldige link of deze is nu niet geldig meer.' );
			}
		}
		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'betaling' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return \WP_ERROR|bool
	 *
	 * @since   4.2.0
	 */
	public function validate( &$data ) {
		$error = new WP_Error();

		$input = filter_input_array(
			INPUT_POST,
			[
				'cursist_id' => FILTER_SANITIZE_NUMBER_INT,
				'abonnee_id' => FILTER_SANITIZE_NUMBER_INT,
				'cursus_id'  => FILTER_SANITIZE_NUMBER_INT,
				'betaal'     => FILTER_SANITIZE_STRING,
				'actie'      => FILTER_SANITIZE_STRING,
			]
		);
		if ( self::ACTIE_RESTANT_CURSUS === $input['actie'] ) {
			$inschrijving = new Kleistad_Inschrijving( $input['cursist_id'], $input['cursus_id'] );

			if ( $inschrijving->c_betaald ) {
				$error->add( 'betaald', 'Volgens onze informatie is er reeds betaald voor deze cursus. Neem eventueel contact op met Kleistad' );
			}
		} elseif ( self::ACTIE_VERVOLG_ABONNEMENT === $input['actie'] ) {
			$abonnement = new Kleistad_Abonnement( $input['abonnee_id'] );
			if ( $abonnement->incasso_actief() ) {
				$error->add( 'betaald', 'Volgens onze informatie is er reeds betaald voor het vervolg van het abonnement. Neem eventueel contact op met Kleistad' );
			}
		}
		$data['input'] = $input;

		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Bewaar 'betaling' form gegevens
	 *
	 * @param array $data te bewaren data.
	 * @return string
	 *
	 * @since   4.2.0
	 */
	public function save( $data ) {
		if ( self::ACTIE_RESTANT_CURSUS === $data['input']['actie'] ) {
			$inschrijving = new Kleistad_Inschrijving( $data['input']['cursist_id'], $data['input']['cursus_id'] );
			if ( 'ideal' === $data['input']['betaal'] ) {
				$inschrijving->betalen(
					'Bedankt voor de betaling! Er wordt een email verzonden met bevestiging',
					false
				);
			}
		} elseif ( self::ACTIE_VERVOLG_ABONNEMENT === $data['input']['actie'] ) {
			$abonnement = new Kleistad_Abonnement( $data['input']['abonnee_id'] );
			if ( 'ideal' === $data['input']['betaal'] ) {
				$abonnement->betalen();
			}
		}
		return '';
	}
}
