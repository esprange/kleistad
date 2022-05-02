<?php
/**
 * De definitie van de werkplek configuratie class
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad WerkplekConfig class.
 *
 * @since 6.11.0
 *
 * @property int    start_datum
 * @property int    eind_datum
 * @property array  meesters
 * @property array  config
 */
class WerkplekConfig {

	/**
	 * De werkplekdata
	 *
	 * @var array $data De ruwe data.
	 */
	private array $config_data;

	/**
	 * Constructor, laad de configuratie
	 *
	 * @since 6.11.0
	 */
	public function __construct() {
		$default_config   = [];
		$default_meesters = [];
		foreach ( $this->get_atelierdagen() as $dag ) {
			foreach ( WerkplekGebruik::WERKPLEK_DAGDEEL as $dagdeel ) {
				foreach ( opties()['werkruimte'] as $activiteit ) {
					$default_config[ $dag ][ $dagdeel ][ $activiteit['naam'] ] = 0;
					$default_meesters[ $dag ][ $dagdeel ]                      = 0;
				}
			}
		}
		$this->config_data = [
			'config'      => $default_config,
			'meesters'    => $default_meesters,
			'start_datum' => strtotime( 'last Monday' ),
			'eind_datum'  => 0,
		];
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @since 6.11.0
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( string $attribuut ) {
		return array_key_exists( $attribuut, $this->config_data ) ? $this->config_data[ $attribuut ] : null;
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @since 6.11.0
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 * @return void
	 */
	public function __set( string $attribuut, mixed $waarde ) : void {
		$this->config_data[ $attribuut ] = $waarde;
	}

	/**
	 * Wijzig de werkplaatsmeesters voor de configuratie van een specifieke datum
	 *
	 * @param int $datum De datum van de wijziging.
	 */
	public function adhoc_meesters( int $datum ) {
		$atelierdag       = wp_date( 'l', $datum );
		$werkplekmeesters = new WerkplekMeesters( $datum );
		$meester_ids      = $werkplekmeesters->get_ids();
		foreach ( array_keys( $this->meesters[ $atelierdag ] ) as $dagdeel ) {
			if ( isset( $meester_ids[ $dagdeel ] ) ) {
				$this->config_data['meesters'][ $atelierdag ][ $dagdeel ] = $meester_ids[ $dagdeel ];
			}
		}
	}

	/**
	 * De atelierdagen.
	 *
	 * @return array
	 */
	private function get_atelierdagen() : array {
		$dagen = [];
		for ( $dagteller = 0; $dagteller < 7; $dagteller++ ) {
			$dagen[] = wp_date( 'l', strtotime( "next Monday +$dagteller days" ) );
		}
		return $dagen;
	}

}
