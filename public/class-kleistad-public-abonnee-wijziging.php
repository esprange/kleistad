<?php
/**
 * Shortcode abonnement wijzigingen.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * De class Abonnee Wijziging.
 */
class Kleistad_Public_Abonnee_Wijziging extends Kleistad_ShortcodeForm {

	/**
	 * Prepareer 'abonnee_wijziging' form
	 *
	 * @param array $data data voor formulier.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data = null ) {
		$abonnee_id = get_current_user_id();
		$abonnement = new Kleistad_Abonnement( $abonnee_id );

		$data['driemaand_datum'] = mktime( 0, 0, 0, intval( date( 'n', $abonnement->start_datum ) ) + 3, intval( date( 'j', $abonnement->start_datum ) ), intval( date( 'Y', $abonnement->start_datum ) ) );
		$data['abonnement']      = $abonnement;
		$data['input']['actief'] = ( ! $abonnement->geannuleerd ) && ( ! $abonnement->gepauzeerd );
		$data['input']['soort']  = $abonnement->soort;
		$data['input']['dag']    = $abonnement->dag;
		$data['input']['extras'] = $abonnement->extras;
		$data['incasso_actief']  = $abonnement->incasso_actief();
		return true;
	}

	/**
	 * Valideer/sanitize 'abonnee_wijziging' form
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
				'abonnee_id'    => FILTER_SANITIZE_NUMBER_INT,
				'actie'         => FILTER_SANITIZE_STRING,
				'dag'           => FILTER_SANITIZE_STRING,
				'soort'         => FILTER_SANITIZE_STRING,
				'betaal'        => FILTER_SANITIZE_STRING,
				'pauze_maanden' => FILTER_SANITIZE_NUMBER_INT,
				'per_datum'     => FILTER_SANITIZE_NUMBER_INT,
				'extras'        => [
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
			]
		);
		if ( ! is_array( $input['extras'] ) ) {
			$input['extras'] = [];
		};

		$err = $error->get_error_codes();
		if ( ! empty( $err ) ) {
			return $error;
		}

		$data = [
			'input' => $input,
		];
		return true;
	}

	/**
	 * Bewaar 'abonnee_wijziging' form gegevens
	 *
	 * @param array $data te bewaren data.
	 * @return \WP_Error|string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		$error = new WP_Error();
		if ( ! is_user_logged_in() ) {
			$error->add( 'security', 'Dit formulier mag alleen ingevuld worden door ingelogde gebruikers' );
			return $error;
		}

		$herstart_maand = mktime( 0, 0, 0, intval( date( 'n' ) ) + 1 + $data['input']['pauze_maanden'], 1, intval( date( 'Y' ) ) );
		$abonnement     = new Kleistad_Abonnement( $data['input']['abonnee_id'] );
		$bericht        = 'De wijziging is verwerkt en er wordt een email verzonden met bevestiging';

		switch ( $data['input']['actie'] ) {

			case 'pauze':
				$status = $abonnement->pauzeren( $data['input']['per_datum'], $herstart_maand );
				break;
			case 'herstart':
				$status = $abonnement->herstarten( $data['input']['per_datum'] );
				break;
			case 'einde':
				$status = $abonnement->annuleren( $data['input']['per_datum'] );
				break;
			case 'wijziging':
				$status = $abonnement->wijzigen( $data['input']['per_datum'], $data['input']['soort'], $data['input']['dag'] );
				break;
			case 'extras':
				$status = $abonnement->wijzigen( $data['input']['per_datum'], $data['input']['extras'] );
				break;
			case 'dag':
				$abonnement->dag = $data['input']['dag'];
				$abonnement->save();
				$status  = true;
				$bericht = 'De wijziging is verwerkt';
				break;
			case 'betaalwijze':
				$status = $abonnement->betaalwijze( $data['input']['per_datum'], $data['input']['betaal'] );
				break;
			default:
				$status = false;
				break;
		}
		if ( $status ) {
			return $bericht;
		} else {
			$error->add( '', 'De wijziging van het abonnement was niet mogelijk, neem eventueel contact op met Kleistad' );
		}
		return $error;
	}
}
