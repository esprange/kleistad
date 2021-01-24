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

	const MEESTER = 'Beheerder';

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
	 * De werkplek configuratie voor deze dag
	 *
	 * @var array $dagconfig De configuratie.
	 */
	private array $dagconfig;

	/**
	 * Constructor, laad het gebruik
	 *
	 * @param int $datum De datum waar het gebruik van opgevraagd wordt.
	 *
	 * @since 6.11.0
	 */
	public function __construct( int $datum ) {
		$this->datum     = $datum;
		$werkplekconfigs = new WerkplekConfigs();
		$werkplekconfig  = $werkplekconfigs->find( $this->datum ) ?: new WerkplekConfig();
		$this->dagconfig = $werkplekconfig->config[ strftime( '%A', $this->datum ) ];
		$gebruik         = get_option( 'kleistad_werkplek_' . date( 'Ymd', $this->datum ) );
		if ( false === $gebruik ) {
			foreach ( WerkplekConfig::DAGDEEL as $dagdeel ) {
				foreach ( WerkplekConfig::ACTIVITEIT as $activiteit ) {
					$this->gebruik[ $dagdeel ][ $activiteit ] = [];
				}
			}
		}
		if ( is_array( $gebruik ) ) {
			$this->gebruik = $gebruik;
		}
		foreach ( WerkplekConfig::DAGDEEL as $dagdeel ) {
			$meester_id                                   = $werkplekconfig->meesters[ strftime( '%A', $this->datum ) ][ $dagdeel ];
			$this->gebruik[ $dagdeel ][ self::MEESTER ] ??= [ $meester_id ];
		}
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
	 * Geef de meesters van de dag terug.
	 *
	 * @return array De meesters per dagdeel.
	 */
	public function geef_meesters() : array {
		$meesters = [];
		foreach ( $this->gebruik as $dagdeel => $activiteiten ) {
			$meesters[ $dagdeel ] = get_user_by( 'id', $activiteiten[ self::MEESTER ][0] );
		}
		return $meesters;
	}

	/**
	 * Pas het gebruik aan (een array van gebruiker ids)
	 *
	 * @param string $dagdeel       Het dagdeel.
	 * @param string $activiteit    De activiteit.
	 * @param array  $gebruiker_ids De id's van de gebruikers.
	 * @return bool  Of de wijziging uitgevoerd is.
	 */
	public function wijzig( string $dagdeel, string $activiteit, array $gebruiker_ids ) : bool {
		if ( count( $gebruiker_ids ) <= $this->dagconfig[ $dagdeel ][ $activiteit ] ) {
			$this->gebruik[ $dagdeel ][ $activiteit ] = $gebruiker_ids;
			$this->save();
			return true;
		}
		return false;
	}

	/**
	 * Voeg een meester toe
	 *
	 * @param string $dagdeel     Het dagdeel.
	 * @param int    $meester_id  Het id van de meester.
	 */
	public function wijzig_meester( string $dagdeel, int $meester_id ) {
		$this->gebruik[ $dagdeel ][ self::MEESTER ] = [ $meester_id ];
		$this->save();
	}

	/**
	 * Geef de configuratie
	 *
	 * @return array De configuratie.
	 */
	public function config() : array {
		return $this->dagconfig;
	}

	/**
	 * Geef aan of de gebruiker aanwezig is op het dagdeel
	 *
	 * @param string $dagdeel       Het dagdeel.
	 * @param int    $gebruiker_id  Het id van de gebruiker.
	 * @return bool Het resultaat
	 */
	public function is_aanwezig( string $dagdeel, int $gebruiker_id ) : bool {
		foreach ( $this->gebruik[ $dagdeel ] as $activiteit => $gebruiker_ids ) {
			if ( self::MEESTER !== $activiteit && in_array( $gebruiker_id, $gebruiker_ids, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Bewaar het gebruik
	 */
	private function save() {
		update_option( 'kleistad_werkplek_' . date( 'Ymd', $this->datum ), $this->gebruik );
	}
}
