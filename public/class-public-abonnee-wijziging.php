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

/**
 * De class Abonnee Wijziging.
 */
class Public_Abonnee_Wijziging extends ShortcodeForm {

	/**
	 * Prepareer 'abonnee_wijziging' form
	 *
	 * @param array $data data voor formulier.
	 * @return bool
	 *
	 * @since   4.0.87
	 */
	protected function prepare( &$data ) {
		$abonnee_id = get_current_user_id();
		$abonnement = new \Kleistad\Abonnement( $abonnee_id );

		$data['driemaand_datum'] = $abonnement->driemaand_datum;
		$data['abonnement']      = $abonnement;
		$data['input']['actief'] = ( ! $abonnement->geannuleerd ) && ( ! $abonnement->gepauzeerd );
		$data['input']['soort']  = $abonnement->soort;
		$data['input']['dag']    = $abonnement->dag;
		$data['input']['extras'] = $abonnement->extras;
		$data['incasso_actief']  = $abonnement->incasso_actief();
		$data['gepauzeerd']      = $abonnement->gepauzeerd;
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
	protected function validate( &$data ) {
		$error = new \WP_Error();

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
					'filter' => FILTER_SANITIZE_STRING,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
			]
		);
		if ( ! is_array( $data['input']['extras'] ) ) {
			$data['input']['extras'] = [];
		};

		if ( 'pauze' === $data['input']['wijziging'] ) {
			$data['input']['pauze_datum']    = strtotime( $data['input']['pauze_datum'] );
			$data['input']['herstart_datum'] = strtotime( $data['input']['herstart_datum'] );
			if ( $data['input']['pauze_datum'] < strtotime( 'first day of next month 00:00' ) ) {
				$error->add( 'pauze', 'Het abonnement mag niet eerder dan komende maand gepauzeerd worden' );
			}
			if ( $data['input']['herstart_datum'] - strtotime( '+' . \Kleistad\Abonnement::MIN_PAUZE_WEKEN . ' weeks', $data['input']['pauze_datum'] ) ) {
				$error->add( 'pauze', 'Het abonnement moet minimaal ' . \Kleistad\Abonnement::MIN_PAUZE_WEKEN . ' weken dagen gepauzeerd worden' );
			}
			if ( $data['input']['herstart_datum'] > strtotime( '+' . \Kleistad\Abonnement::MAX_PAUZE_WEKEN . ' weeks', $data['input']['pauze_datum'] ) ) {
				$error->add( 'pauze', 'Het abonnement mag maximaal ' . \Kleistad\Abonnement::MAX_PAUZE_WEKEN . ' weken per keer gepauzeerd worden' );
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
	 * @return \WP_Error|array
	 *
	 * @since   4.0.87
	 */
	protected function save( $data ) {
		$herstart_maand = mktime( 0, 0, 0, intval( date( 'n' ) ) + 1 + $data['input']['pauze_maanden'], 1, intval( date( 'Y' ) ) );
		$abonnement     = new \Kleistad\Abonnement( $data['input']['abonnee_id'] );
		switch ( $data['input']['wijziging'] ) {

			case 'pauze':
				$status = $abonnement->pauzeren( $data['input']['pauze_datum'], $data['input']['herstart_datum'] );
				break;
			case 'einde':
				$status = $abonnement->stoppen( $data['input']['per_datum'] );
				break;
			case 'soort':
				$status = $abonnement->wijzigen( $data['input']['per_datum'], 'soort', $data['input']['soort'], $data['input']['dag'] );
				break;
			case 'extras':
				$status = $abonnement->wijzigen( $data['input']['per_datum'], 'extras', $data['input']['extras'] );
				break;
			case 'dag':
				$status = $abonnement->wijzigen( strtotime( 'today' ), 'soort', 'beperkt', $data['input']['dag'] );
				break;
			case 'betaalwijze':
				$ideal_uri = $abonnement->betaalwijze( $data['input']['per_datum'], $data['input']['betaal'] );
				if ( is_string( $ideal_uri ) ) { // In dit geval is $status een redirect url.
					if ( ! empty( $ideal_uri ) ) {
						return [ 'redirect_uri' => $ideal_uri ];
					}
					return [ 'status' => $this->status( new \WP_Error( 'mollie', 'De betaalservice is helaas nu niet beschikbaar, probeer het later opnieuw' ) ) ];
				}
				// Blijkbaar dus een foute uri.
			default:
				$status = false;
				break;
		}
		if ( $status ) {
			return [
				'status'  => $this->status( 'De wijziging is verwerkt en er wordt een email verzonden met bevestiging' ),
				'content' => $this->display(),
			];
		}
		return [
			'status' => $this->status( new \WP_Error( 'intern', 'De wijziging van het abonnement was niet mogelijk, neem eventueel contact op met Kleistad' ) ),
		];
	}
}
