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
class Kleistad_Public_Abonnee_Wijziging extends Kleistad_Shortcode {

	/**
	 * Prepareer 'abonnee_wijziging' form
	 *
	 * @param array $data data voor formulier.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	public function prepare( &$data ) {
		$abonnee_id = get_current_user_id();
		$abonnement = new Kleistad_Abonnement( $abonnee_id );

		$data['driemaand_datum'] = mktime( 0, 0, 0, date( 'n', $abonnement->start_datum ) + 3, date( 'j', $abonnement->start_datum ), date( 'Y', $abonnement->start_datum ) );
		$data['input']['actief'] = ( ! $abonnement->geannuleerd ) && ( ! $abonnement->gepauzeerd );
		$data['input']['soort']  = $abonnement->soort;
		$data['input']['dag']    = $abonnement->dag;
		return true;
	}

	/**
	 * Valideer/sanitize 'abonnee_wijziging' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return array
	 *
	 * @since   4.0.87
	 */
	public function validate( &$data ) {
		$error = new WP_Error();

		$input = filter_input_array(
			INPUT_POST, [
				'abonnee_id'    => FILTER_SANITIZE_NUMBER_INT,
				'actie'         => FILTER_SANITIZE_STRING,
				'dag'           => FILTER_SANITIZE_STRING,
				'soort'         => FILTER_SANITIZE_STRING,
				'betaal'        => FILTER_SANITIZE_STRING,
				'pauze_maanden' => FILTER_SANITIZE_NUMBER_INT,
				'per_datum'     => FILTER_SANITIZE_NUMBER_INT,
			]
		);

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
	 * @return string
	 *
	 * @since   4.0.87
	 */
	public function save( $data ) {
		$error = new WP_Error();
		if ( ! is_user_logged_in() ) {
			$error->add( 'security', 'Dit formulier mag alleen ingevuld worden door ingelogde gebruikers' );
			return $error;
		}

		$herstart_maand = mktime( 0, 0, 0, date( 'n' ) + 1 + intval( $data['input']['pauze_maanden'] ), 1, date( 'Y' ) );
		$abonnement     = new Kleistad_Abonnement( $data['input']['abonnee_id'] );

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
			case 'betaalwijze':
				$status = $abonnement->betaalwijze( $data['input']['per_datum'], $data['input']['betaal'] );
				break;
			default:
				$status = false;
				break;
		}
		if ( $status ) {
			return 'De wijziging is verwerkt en er wordt een email verzonden met bevestiging';
		} else {
			$error->add( '', 'De wijziging van het abonnement was niet mogelijk, neem eventueel contact op met Kleistad' );
		}
		return $error;
	}
}
