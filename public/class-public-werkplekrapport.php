<?php
/**
 * Shortcode werkplekrapport (voor bestuur).
 *
 * @link       https://www.kleistad.nl
 * @since      6.12.1
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad werkplekrapport class.
 */
class Public_Werkplekrapport extends Shortcode {

	/**
	 *
	 * Prepareer 'werkplekrapport' form
	 *
	 * @param array $data data voor display.
	 * @return bool
	 *
	 * @since   6.12.1
	 */
	protected function prepare( &$data ) {
		$atts          = shortcode_atts(
			[ 'actie' => '' ],
			$this->atts
		);
		$data['actie'] = $atts['actie'];
		$input         = filter_input_array(
			INPUT_GET,
			[
				'gebruiker_id' => FILTER_SANITIZE_NUMBER_INT,
				'vanaf_datum'  => FILTER_SANITIZE_STRING,
				'tot_datum'    => FILTER_SANITIZE_STRING,
			]
		);
		if ( empty( $input['vanaf_datum'] ) ) {
			if ( 'individueel' === $data['actie'] ) {
				$data['gebruikers'] = $this->geef_gebruikers();
			}
			return true;
		}
		$data['vanaf_datum']  = strtotime( $input['vanaf_datum'] );
		$data['tot_datum']    = strtotime( $input['tot_datum'] );
		$data['gebruiker_id'] = intval( $input['gebruiker_id'] );
		if ( 'individueel' === $data['actie'] ) {
			$data['rapport'] = $this->individueelgebruik( $data['vanaf_datum'], $data['tot_datum'], $data['gebruiker_id'] );
			return true;
		}
		$data['rapport'] = $this->groepsgebruik( $data['vanaf_datum'], $data['tot_datum'] );
		return true;
	}

	/**
	 * Geef de gebruikers welke gebruik gemaakt hebben van werkplekken, 3 maanden terug en 3 maanden vooruit.
	 */
	private function geef_gebruikers() : array {
		$start_datum = strtotime( '- 3 month' );
		$eind_datum  = strtotime( '+ 3 month' );
		$gebruikers  = [];
		for ( $datum = $start_datum; $datum <= $eind_datum; $datum += DAY_IN_SECONDS ) {
			$werkplekgebruik = new WerkplekGebruik( $datum );
			$gebruikers      = array_merge( $gebruikers, $werkplekgebruik->geef() );
		}
		$gebruikers = array_map(
			'unserialize',
			array_unique(
				array_map(
					function( $element ) {
						return serialize( (array) $element ); // phpcs:ignore
					},
					$gebruikers
				)
			)
		);
		return $gebruikers;
	}

	/**
	 * Geef het individueel gebruik over een specifieke periode.
	 *
	 * @param int $vanaf_datum  De start datum van de periode.
	 * @param int $tot_datum    De eind datum van de periode.
	 * @param int $gebruiker_id Het id van de gebruiker.
	 * @return array Het gebruik in de vorm van een array datum - dagdeel - activiteit.
	 */
	private function individueelgebruik( int $vanaf_datum, int $tot_datum, int $gebruiker_id ) : array {
		$rapport = [];
		for ( $datum = $vanaf_datum; $datum <= $tot_datum; $datum += DAY_IN_SECONDS ) {
			$werkplekgebruik = new WerkplekGebruik( $datum );
			foreach ( $werkplekgebruik->geef_gebruik() as $dagdeel => $gebruik ) {
				foreach ( $gebruik as $activiteit => $werkplek ) {
					if ( in_array( $gebruiker_id, $werkplek, true ) ) {
						$rapport[ $datum ][ $dagdeel ] = $activiteit;
					}
				}
			}
		}
		return $rapport;
	}

	/**
	 * Geef het groeps gebruik over een specifieke periode.
	 *
	 * @param int $vanaf_datum  De start datum van de periode.
	 * @param int $tot_datum    De eind datum van de periode.
	 * @return array Het gebruik in de vorm van een array datum - dagdeel - activiteit = gebruiker.
	 */
	private function groepsgebruik( int $vanaf_datum, int $tot_datum ) : array {
		$rapport = [];
		for ( $datum = $vanaf_datum; $datum <= $tot_datum; $datum += DAY_IN_SECONDS ) {
			$werkplekgebruik   = new WerkplekGebruik( $datum );
			$rapport[ $datum ] = $werkplekgebruik->geef_gebruik();
		}
		return $rapport;
	}

}
