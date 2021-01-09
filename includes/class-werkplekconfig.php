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
		$default_config = [];
		foreach ( $this->geef_atelierdagen() as $dag ) {
			foreach ( self::DAGDEEL as $dagdeel ) {
				foreach ( self::ACTIVITEIT as $activiteit ) {
					$default_config[ $dag ][ $dagdeel ][ $activiteit ] = 0;
				}
			}
		}
		$this->config = [
			'config'      => $default_config,
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
	 * Bepaal of dit de actieve configuratie is
	 *
	 * @param int $datum De datum welke gecontroleerd wordt.
	 * @return bool
	 */
	public function is_actief( int $datum ) : bool {
		return $this->start_datum <= $datum && ( 0 === $this->eind_datum || $datum <= $this->eind_datum );
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
