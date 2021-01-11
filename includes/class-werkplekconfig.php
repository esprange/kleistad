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
 * @property array  config
 * @property array  meesters
 */
class WerkplekConfig {

	public const ACTIVITEIT = [ 'Handvormen', 'Draaien', 'Bovenverdieping' ]; // Vooralsnog draaien, handvormen en bovenverdieping.
	public const DAGDEEL    = [ 'Ochtend', 'Middag', 'Avond' ]; // Vooralsnog ochtend, middag en avond.
	public const ACTIEKLEUR = [
		'Handvormen'      => 'rgb( 255, 229, 153 )',
		'Draaien'         => 'rgb( 247, 202, 172 )',
		'Bovenverdieping' => 'rgb( 217, 217, 217 )',
	];

	/**
	 * De werkplekdata
	 *
	 * @var array $data De ruwe data.
	 */
	private array $config;

	/**
	 * Constructor, laad de configuratie
	 *
	 * @since 6.11.0
	 */
	public function __construct() {
		$default_config   = [];
		$default_meesters = [];
		foreach ( $this->geef_atelierdagen() as $dag ) {
			foreach ( self::DAGDEEL as $dagdeel ) {
				foreach ( self::ACTIVITEIT as $activiteit ) {
					$default_config[ $dag ][ $dagdeel ][ $activiteit ] = 0;
					$default_meesters[ $dag ][ $dagdeel ]              = 0;
				}
			}
		}
		$this->config = [
			'config'      => $default_config,
			'meesters'    => $default_meesters,
			'start_datum' => strtotime( 'today' ),
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
	public function __get( $attribuut ) {
		return array_key_exists( $attribuut, $this->config ) ? $this->config[ $attribuut ] : null;
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
	public function __set( $attribuut, $waarde ) {
		$this->config[ $attribuut ] = $waarde;
	}

	/**
	 * Wijzig de werkplaatsmeesters voor de configuratie van een specifieke datum
	 *
	 * @param int $datum De datum van de wijziging.
	 */
	public function adhoc_meesters( int $datum ) {
		$atelierdag       = strftime( '%A', $datum );
		$werkplekmeesters = new WerkplekMeesters( $datum );
		$meester_ids      = $werkplekmeesters->geef();
		foreach ( array_keys( $this->meesters[ $atelierdag ] ) as $dagdeel ) {
			if ( isset( $meester_ids[ $dagdeel ] ) ) {
				$this->config['meesters'][ $atelierdag ][ $dagdeel ] = $meester_ids[ $dagdeel ];
			}
		}
	}

	/**
	 * De atelierdagen.
	 *
	 * @return array
	 */
	public function geef_atelierdagen() {
		$dagen = [];
		for ( $dagteller = 0; $dagteller < 7; $dagteller++ ) {
			$dagen[] = strftime( '%A', strtotime( "next Monday +$dagteller days" ) );
		}
		return $dagen;
	}

}
