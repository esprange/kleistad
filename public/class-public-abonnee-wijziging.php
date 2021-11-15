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
	 * @return bool|WP_Error
	 *
	 * @since   4.0.87
	 */
	protected function prepare() {
		$abonnee_id               = get_current_user_id();
		$betalen                  = new Betalen();
		$this->data['abonnement'] = new Abonnement( $abonnee_id );
		if ( $this->data['abonnement']->start_datum ) {
			$this->data['incasso_actief'] = $betalen->heeft_mandaat( $abonnee_id );
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
	protected function validate( array &$data ) {
		$error         = new WP_Error();
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'abonnee_id'     => FILTER_SANITIZE_NUMBER_INT,
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

		if ( 'pauze' === $data['form_actie'] ) {
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
	 * Initieer een pauze.
	 *
	 * @param array $data Te bewaren data.
	 *
	 * @return array
	 */
	protected function pauze( array $data ) : array {
		$abonnement = new Abonnement( $data['input']['abonnee_id'] );
		return $this->wijzig( $abonnement->actie->pauzeren( $data['input']['pauze_datum'], $data['input']['herstart_datum'] ) );
	}

	/**
	 * Initieer een abonnement eind.
	 *
	 * @param array $data Te bewaren data.
	 *
	 * @return array
	 */
	protected function einde( array $data ) : array {
		$abonnement = new Abonnement( $data['input']['abonnee_id'] );
		return $this->wijzig( $abonnement->actie->stoppen( $data['input']['per_datum'] ) );
	}

	/**
	 * Initieer een abonnement soort wijziging.
	 *
	 * @param array $data Te bewaren data.
	 *
	 * @return array
	 */
	protected function soort( array $data ) : array {
		$abonnement = new Abonnement( $data['input']['abonnee_id'] );
		return $this->wijzig( $abonnement->actie->wijzigen( $data['input']['per_datum'], 'soort', $data['input']['soort'], $data['input']['dag'] ) );
	}

	/**
	 * Initieer een abonnement extras wijziging.
	 *
	 * @param array $data Te bewaren data.
	 *
	 * @return array
	 */
	protected function extras( array $data ) : array {
		$abonnement = new Abonnement( $data['input']['abonnee_id'] );
		return $this->wijzig( $abonnement->actie->wijzigen( $data['input']['per_datum'], 'extras', $data['input']['extras'] ) );
	}

	/**
	 * Initieer een abonnement dag wijziging.
	 *
	 * @param array $data Te bewaren data.
	 *
	 * @return array
	 */
	protected function dag( array $data ) : array {
		$abonnement = new Abonnement( $data['input']['abonnee_id'] );
		return $this->wijzig( $abonnement->actie->wijzigen( strtotime( 'today' ), 'soort', 'beperkt', $data['input']['dag'] ) );
	}

	/**
	 * Initieer een abonnement betaalwijze wijziging.
	 *
	 * @param array $data Te bewaren data.
	 *
	 * @return array
	 */
	protected function betaalwijze( array $data ) : array {
		$abonnement = new Abonnement( $data['input']['abonnee_id'] );
		if ( 'ideal' === $data['input']['betaal'] ) {
			$ideal_uri = $abonnement->actie->start_incasso();
			if ( false === $ideal_uri ) {
				return [ 'status' => $this->status( new WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
			}
			return [ 'redirect_uri' => $ideal_uri ];
		}
		return $this->wijzig( $abonnement->actie->stop_incasso() );
	}

	/**
	 * Hulp functie voor afronding wijziging.
	 *
	 * @param bool $status Resultaat dat gerapporteerd moet worden.
	 *
	 * @return array
	 */
	private function wijzig( bool $status ) : array {
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
