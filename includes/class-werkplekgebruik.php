<?php
/**
 * De definitie van de werkplek gebruik class
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad WerkplekGebruik class.
 *
 * @since 6.11.0
 *
 * @property int    datum
 */
class WerkplekGebruik {

	/**
	 * De atelier dag
	 *
	 * @var int $datum De atelier dag.
	 */
	private int $datum;

	/**
	 * De werkplekdata
	 *
	 * @var array $data De ruwe data.
	 */
	private array $gebruik;

	/**
	 * Constructor, laad het gebruik
	 *
	 * @param int $datum De datum waar het gebruik van opgevraagd wordt.
	 *
	 * @since 6.11.0
	 */
	public function __construct( int $datum ) {
		$this->datum = $datum;
		$gebruik     = get_option( 'kleistad_werkplek_' . date( 'Ymd', $datum ) );
		if ( false === $gebruik ) {
			foreach ( WerkplekConfig::DAGDEEL as $dagdeel ) {
				foreach ( WerkplekConfig::ACTIVITEIT as $activiteit ) {
					$this->gebruik[ $dagdeel ][ $activiteit ] = [];
				}
			}
			return;
		}
		$this->gebruik = $gebruik;
	}

	/**
	 * Geef het gebruik terug (een array van gebruiker ids)
	 *
	 * @param string $dagdeel    Het dagdeel.
	 * @param string $activiteit De activiteit.
	 * @return array array van WP_User objects, gesorteerd op display naam.
	 */
	public function geef( string $dagdeel = '', string $activiteit = '' ) : array {
		$gebruiker_ids = [];
		foreach ( $this->gebruik as $dagdeel_key => $gebruik ) {
			if ( empty( $dagdeel ) || $dagdeel === $dagdeel_key ) {
				foreach ( $gebruik as $activiteit_key => $werkplek ) {
					if ( empty( $activiteit ) || $activiteit === $activiteit_key ) {
						$gebruiker_ids = array_merge( $gebruiker_ids, $werkplek );
					}
				}
			}
		}
		return empty( $gebruiker_ids ) ? [] : get_users(
			[
				'include' => array_unique( $gebruiker_ids ),
				'orderby' => 'display_name',
				'fields'  => [ 'ID', 'display_name' ],
			]
		);
	}

	/**
	 * Geef het gebruik terug voor verdere analyse.
	 *
	 * @return array Het resultaat.
	 */
	public function geef_gebruik() : array {
		return $this->gebruik;
	}

	/**
	 * Pas het gebruik aan (een array van gebruiker ids)
	 *
	 * @param string $dagdeel       Het dagdeel.
	 * @param string $activiteit    De activiteit.
	 * @param array  $gebruiker_ids De id's van de gebruikers.
	 */
	public function wijzig( string $dagdeel, string $activiteit, array $gebruiker_ids ) {
		$this->gebruik[ $dagdeel ][ $activiteit ] = $gebruiker_ids;
		$this->save();
	}

	/**
	 * Bewaar het gebruik
	 */
	private function save() {
		update_option( 'kleistad_werkplek_' . date( 'Ymd', $this->datum ), $this->gebruik );
	}
}
