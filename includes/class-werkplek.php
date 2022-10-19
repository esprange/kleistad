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
 * Kleistad Werkplek class.
 *
 * @since 6.11.0
 */
class Werkplek {

	const WERKPLEK_DAGDEEL = [ OCHTEND, MIDDAG, AVOND ];
	const MEESTER          = 'Beheerder';

	/**
	 * De atelier dag
	 *
	 * @var int $datum De atelier dag.
	 */
	public int $datum;

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
	 * @param int     $datum De datum waar het gebruik van opgevraagd wordt.
	 * @param ?string $load  Data waarmee het object geladen kan worden (optioneel).
	 *
	 * @since 6.11.0
	 */
	public function __construct( int $datum, string $load = null ) {
		static $werkplekconfigs = null;
		global $wpdb;
		$this->datum = $datum;
		if ( is_null( $werkplekconfigs ) ) {
			$werkplekconfigs = new WerkplekConfigs();
		}
		$werkplekconfig  = $werkplekconfigs->find( $this->datum ) ?: new WerkplekConfig();
		$this->dagconfig = $werkplekconfig->config[ wp_date( 'l', $this->datum ) ];
		if ( $load ) {
			$this->gebruik = maybe_unserialize( $load );
			return;
		}
		$result  = $wpdb->get_row(
			$wpdb->prepare( "SELECT * from {$wpdb->prefix}kleistad_werkplekken WHERE datum=%s", date( 'Y-m-d', $datum ) ),
			ARRAY_A
		);
		$gebruik = ! is_null( $result ) ? maybe_unserialize( $result['gebruik'] ) : false;
		if ( false === $gebruik ) {
			foreach ( self::WERKPLEK_DAGDEEL as $dagdeel ) {
				foreach ( opties()['werkruimte'] as $activiteit ) {
					$this->gebruik[ $dagdeel ][ $activiteit['naam'] ] = [];
				}
			}
		}
		if ( is_array( $gebruik ) ) {
			$this->gebruik = $gebruik;
		}
		foreach ( self::WERKPLEK_DAGDEEL as $dagdeel ) {
			$meester_id                                   = $werkplekconfig->meesters[ wp_date( 'l', $this->datum ) ][ $dagdeel ];
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
		$workshop_ids  = [];
		foreach ( $this->gebruik as $dagdeel_key => $gebruik ) {
			if ( empty( $dagdeel ) || $dagdeel === $dagdeel_key ) {
				foreach ( $gebruik as $activiteit_key => $posities ) {
					if ( empty( $activiteit ) || $activiteit === $activiteit_key ) {
						$gebruiker_ids = array_merge(
							$gebruiker_ids,
							array_filter( $posities, 'is_numeric' )
						);
						$workshop_ids  = array_merge(
							$workshop_ids,
							array_filter(
								$posities,
								function( $positie ) {
									return str_starts_with( $positie, Workshop::DEFINITIE['prefix'] );
								}
							)
						);
					}
				}
			}
		}
		$gebruikers = [];
		if ( ! empty( $gebruiker_ids ) ) {
			$gebruikers = array_map(
				function( $gebruiker ) {
					return [
						'id'   => $gebruiker->ID,
						'naam' => $gebruiker->display_name,
					];
				},
				get_users(
					[
						'include' => array_unique( $gebruiker_ids ),
						'orderby' => 'display_name',
						'fields'  => [ 'ID', 'display_name' ],
					]
				)
			);
		}
		if ( ! empty( $workshop_ids ) ) {
			$gebruikers = array_merge(
				$gebruikers,
				array_map(
					function( $workshop_id ) {
						$workshop_params = explode( '_', substr( $workshop_id, 1 ) );
						return [
							'id'   => $workshop_id,
							'naam' => $workshop_params[1],
						];
					},
					$workshop_ids
				)
			);
		}
		return $gebruikers;
	}

	/**
	 * Geef het gebruik terug voor verdere analyse.
	 *
	 * @return array Het resultaat.
	 */
	public function get_gebruik() : array {
		return $this->gebruik;
	}

	/**
	 * Geef de meesters van de dag terug.
	 *
	 * @return array De meesters per dagdeel.
	 */
	public function get_meesters() : array {
		$meesters = [];
		foreach ( $this->gebruik as $dagdeel => $activiteiten ) {
			$meesters[ $dagdeel ] = get_user_by( 'id', $activiteiten[ self::MEESTER ][0] );
		}
		return $meesters;
	}

	/**
	 * Geef aan hoeveel ruimte er nog is voor het dagdeel en activiteit.
	 *
	 * @param string $dagdeel    Het dagdeel.
	 * @param string $activiteit De activiteit.
	 *
	 * @return int
	 */
	public function get_ruimte( string $dagdeel, string $activiteit ) : int {
		return max( 0, $this->dagconfig[ $dagdeel ][ $activiteit ] - count( $this->gebruik[ $dagdeel ][ $activiteit ] ) );
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
	public function wijzig_meester( string $dagdeel, int $meester_id ) : void {
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
	private function save() : void {
		global $wpdb;
		$wpdb->replace(
			"{$wpdb->prefix}kleistad_werkplekken",
			[
				'datum'   => date( 'Y-m-d', $this->datum ),
				'gebruik' => maybe_serialize( $this->gebruik ),
			]
		);
	}
}
