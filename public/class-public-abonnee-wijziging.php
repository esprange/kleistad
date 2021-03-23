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

namespace Kleistad;

use WP_Error;

/**
 * De class Abonnee Wijziging.
 */
class Public_Abonnee_Wijziging extends ShortcodeForm {

	/**
	 * Prepareer 'abonnee_wijziging' form
	 *
	 * @param array $data data voor formulier.
	 * @return bool|WP_Error
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data ) {
		$abonnee_id         = get_current_user_id();
		$betalen            = new Betalen();
		$data['abonnement'] = new Abonnement( $abonnee_id );
		if ( $data['abonnement']->start_datum ) {
			$data['incasso_actief'] = $betalen->heeft_mandaat( $abonnee_id );
			return true;
		}
		return new WP_Error( 'abonnement', 'Je hebt geen actief abonnement, neem eventueel contact op met een bestuurslid' );
	}

	/**
	 * Valideer/sanitize 'abonnee_wijziging' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return WP_Error|bool
	 *
	 * @since   4.0.87
	 */
	protected function validate( &$data ) {
		$error = new WP_Error();

		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'abonnee_id'     => FILTER_SANITIZE_NUMBER_INT,
				'wijziging'      => FILTER_SANITIZE_STRING,
				'dag'            => FILTER_SANITIZE_STRING,
				'soort'          => FILTER_SANITIZE_STRING,
				'betaal'         => FILTER_SANITIZE_STRING,
				'pauze_datum'    => FILTER_SANITIZE_STRING,
				'herstart_datum' => FILTER_SANITIZE_STRING,
				'per_datum'      => FILTER_SANITIZE_NUMBER_INT,
				'extras'         => [
					'filter'  => FILTER_SANITIZE_STRING,
					'flags'   => FILTER_REQUIRE_ARRAY,
					'options' => [ 'default' => [] ],
				],
			]
		);

		if ( 'pauze' === $data['input']['wijziging'] ) {
			$data['input']['pauze_datum']    = strtotime( $data['input']['pauze_datum'] );
			$data['input']['herstart_datum'] = strtotime( $data['input']['herstart_datum'] );
			if ( $data['input']['herstart_datum'] < strtotime( '+' . Abonnement::MIN_PAUZE_WEKEN . ' weeks', $data['input']['pauze_datum'] ) ) {
				$error->add( 'pauze', 'Het abonnement moet minimaal ' . Abonnement::MIN_PAUZE_WEKEN . ' weken dagen gepauzeerd worden' );
			}
			if ( $data['input']['herstart_datum'] > strtotime( '+' . Abonnement::MAX_PAUZE_WEKEN . ' weeks', $data['input']['pauze_datum'] ) ) {
				$error->add( 'pauze', 'Het abonnement mag maximaal ' . Abonnement::MAX_PAUZE_WEKEN . ' weken per keer gepauzeerd worden' );
			}
		}

		if ( ! empty( $error->get_error_codes() ) ) {
			return $error;
		}
		return true;
	}

	/**
	 * Bewaar 'abonnee_wijziging' form gegevens
	 *
	 * @param array $data te bewaren data.
	 * @return WP_Error|array
	 * @suppressWarnings(PHPMD.CyclomaticComplexity)
	 *
	 * @since   4.0.87
	 */
	protected function save( $data ) {
		$abonnement = new Abonnement( $data['input']['abonnee_id'] );
		switch ( $data['input']['wijziging'] ) {
			case 'pauze':
				$status = $abonnement->actie->pauzeren( $data['input']['pauze_datum'], $data['input']['herstart_datum'] );
				break;
			case 'einde':
				$status = $abonnement->actie->stoppen( $data['input']['per_datum'] );
				break;
			case 'soort':
				$status = $abonnement->actie->wijzigen( $data['input']['per_datum'], 'soort', $data['input']['soort'], $data['input']['dag'] );
				break;
			case 'extras':
				$status = $abonnement->actie->wijzigen( $data['input']['per_datum'], 'extras', $data['input']['extras'] );
				break;
			case 'dag':
				$status = $abonnement->actie->wijzigen( strtotime( 'today' ), 'soort', 'beperkt', $data['input']['dag'] );
				break;
			case 'betaalwijze':
				if ( 'ideal' === $data['input']['betaal'] ) {
					$ideal_uri = $abonnement->actie->start_incasso();
					if ( false === $ideal_uri ) {
						return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
					}
					return [ 'redirect_uri' => $ideal_uri ];
				}
				$status = $abonnement->actie->stop_incasso();
				break;
			default:
				$status = false;
		}
		if ( $status ) {
			return [
				'status'  => $this->status( 'De wijziging is verwerkt en er wordt een email verzonden met bevestiging' ),
				'content' => $this->display(),
			];
		}
		return [
			'status' => $this->status( new WP_Error( 'intern', 'De wijziging van het abonnement was niet mogelijk, neem eventueel contact op met Kleistad' ) ),
		];
	}
}
