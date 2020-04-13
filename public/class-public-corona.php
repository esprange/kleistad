<?php
/**
 * Shortcode contact form.
 *
 * @link       https://www.kleistad.nl
 * @since      6.3.4
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad cursus inschrijving class.
 */
class Public_Corona extends ShortcodeForm {

	/**
	 * Haal de beschikbaarheid op
	 *
	 * @todo Beschikbaarheid ophalen uit optie kleistad_corona.
	 *
	 * @param  int $datum De datum.
	 * @return array De beschikbaarheid.
	 */
	private function beschikbaarheid( $datum ) {
		$beschikbaarheid = get_option( 'kleistad_corona_beschikbaarheid', [] );
		if ( isset( $beschikbaarheid[ $datum ] ) ) {
			return $beschikbaarheid[ $datum ];
		}
		return [];
	}

	/**
	 * Bepaald de eerstmogelijke datum om te reserveren.
	 *
	 * @return array De datums.
	 */
	private function mogelijke_datums() {
		$beschikbaarheid = get_option( 'kleistad_corona_beschikbaarheid', [] );
		$datum_lijst     = [];
		foreach ( $beschikbaarheid as $mogelijke_datum => $niet_gebruikt ) {
			if ( $mogelijke_datum >= strtotime( 'today 0:00' ) && $mogelijke_datum < strtotime( '+ 2 weeks 0:00' ) ) {
				$datum_lijst[] = $mogelijke_datum;
			}
		}
		return $datum_lijst;
	}

	/**
	 * Haal de reeds aanwezige reserveringen op
	 *
	 * @param  int $datum De datum.
	 * @return array De reserveringen.
	 */
	private function reserveringen( $datum ) {
		$reserveringen = [];
		$lijst         = get_option( 'kleistad_corona_' . date( 'm-d-Y', $datum ), [] );
		foreach ( $lijst as $index => $reservering ) {
			foreach ( $reservering as $werk => $ids ) {
				$current_id = get_current_user_id();
				$namen      = [];
				foreach ( $ids as $id ) {
					if ( $id !== $current_id ) {
						$namen[] = substr( get_user_by( 'id', $id )->display_name, 0, 15 );
					}
				}
				$reserveringen[ $index ][ $werk ] = [
					'namen'    => $namen,
					'aanwezig' => in_array( $current_id, $ids, true ),
				];
			}
		}
		return $reserveringen;
	}

	/**
	 *
	 * Prepareer 'corona' form
	 *
	 * @param array $data data voor display.
	 * @return bool|\WP_Error
	 *
	 * @since   6.3.4
	 */
	protected function prepare( &$data ) {
		$datums = $this->mogelijke_datums();
		if ( empty( $datums ) ) {
			return new \WP_Error( 'corona', 'Er is geen enkele beschikbaarheid' );
		}
		$datum           = filter_input( INPUT_GET, 'datum' ) ?: $datums[0];
		$current_user_id = get_current_user_id();
		if ( $current_user_id ) {
			$data = [
				'input'           => [
					'naam'  => get_user_by( 'id', $current_user_id )->first_name,
					'id'    => $current_user_id,
					'datum' => $datum,
				],
				'beschikbaarheid' => $this->beschikbaarheid( $datum ),
				'reserveringen'   => $this->reserveringen( $datum ),
				'datums'          => $datums,
			];
		} else {
			return new \WP_Error( 'corona', 'Je moet ingelogd zijn om deze functie te gebruiken' );
		}
		return true;
	}

	/**
	 * Valideer/sanitize 'corona' form
	 *
	 * @param array $data Gevalideerde data.
	 * @return \WP_Error|bool
	 *
	 * @since   6.3.4
	 */
	protected function validate( &$data ) {
		$data['input'] = filter_input_array(
			INPUT_POST,
			[
				'datum' => FILTER_SANITIZE_NUMBER_INT,
				'res'   => [
					'filter' => FILTER_DEFAULT,
					'flags'  => FILTER_REQUIRE_ARRAY,
				],
				'id'    => FILTER_SANITIZE_NUMBER_INT,
			]
		);
		return true;
	}

	/**
	 *
	 * Bewaar 'corona' form gegevens
	 *
	 * @param array $data data te bewaren.
	 * @return \WP_Error|array
	 *
	 * @since   6.3.4
	 */
	protected function save( $data ) {
		$reserveringen = get_option( 'kleistad_corona_' . date( 'm-d-Y', $data['input']['datum'] ), [] );
		$aanpassingen  = $data['input']['res'];
		$id            = intval( $data['input']['id'] );
		foreach ( $aanpassingen as $index => $aanpassing ) {
			foreach ( $aanpassing as $werk => $check ) {
				if ( ! isset( $reserveringen[ $index ][ $werk ] ) || ! in_array( $id, $reserveringen[ $index ][ $werk ], true ) ) {
					$reserveringen[ $index ][ $werk ][] = $id;
				}
			}
		}
		foreach ( $reserveringen as $index => $reservering ) {
			foreach ( $reservering as $werk => $ids ) {
				if ( ! isset( $aanpassingen[ $index ][ $werk ] ) && in_array( $id, $ids, true ) ) {
					$reserveringen[ $index ][ $werk ] = array_diff( $reserveringen[ $index ][ $werk ], [ $id ] );
				}
			}
		}
		update_option( 'kleistad_corona_' . date( 'm-d-Y', $data['input']['datum'] ), $reserveringen );
		return [
			'status' => $this->status( 'De reservering is aangepast' ),
		];
	}

}
