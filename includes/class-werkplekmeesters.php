<?php
/**
 * De definitie van de werkplek (ad hoc) meesters class
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad WerkplekMeesters class.
 *
 * @since 6.11.0
 *
 * @property int    datum
 */
class WerkplekMeesters {

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
	private array $meesters;

	/**
	 * Constructor, laad het gebruik
	 *
	 * @param int $datum De datum waar het gebruik van opgevraagd wordt.
	 *
	 * @since 6.11.0
	 */
	public function __construct( int $datum ) {
		$this->datum = $datum;
		$adhoc       = get_option( 'kleistad_adhoc_' . date( 'Ymd', $this->datum ) );
		if ( false === $adhoc ) {
			$this->meesters = [];
			return;
		}
		$this->meesters = $adhoc;
	}

	/**
	 * Geef de ad hoc meester terug
	 *
	 * @return array De ad_hoc werkplaats meester ids.
	 */
	public function geef() {
		return $this->meesters;
	}

	/**
	 * Pas de ad hoc meester aan
	 *
	 * @param string $dagdeel    Het dagdeel.
	 * @param int    $meester_id Het id van de meester.
	 */
	public function wijzig( string $dagdeel, $meester_id ) {
		$this->meesters[ $dagdeel ] = $meester_id;
		$this->save();
	}

	/**
	 * Verwijder de ad hoc meester zodat de standaard gebruikt wordt
	 *
	 * @param string $dagdeel    Het dagdeel.
	 */
	public function verwijder( string $dagdeel ) {
		unset( $this->meesters[ $dagdeel ] );
		$this->save();
	}

	/**
	 * Bewaar de ad hoc meesters
	 */
	private function save() {
		if ( count( $this->meesters ) ) {
			update_option( 'kleistad_adhoc_' . date( 'Ymd', $this->datum ), $this->meesters );
			return;
		}
		delete_option( 'kleistad_adhoc_' . date( 'Ymd', $this->datum ) );
	}
}
