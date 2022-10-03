<?php
/**
 * De definitie van de workshop planning class
 *
 * @link       https://www.kleistad.nl
 * @since      7.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Workshopplanning class.
 *
 * @since 7.0.0
 */
class Workshopplanning {

	const WORKSHOP_DAGDEEL = [ OCHTEND, MIDDAG, NAMIDDAG ];

	/**
	 * Beschikbaarheid, een array met
	 *   veld datum:   een string in yyyy-mm-dd format
	 *   veld dagdeel: ochtend, middag, avond
	 *   veld aantal:  aantal reeds geplande activiteitien
	 *
	 * @var array $beschikbaarheid De beschikbaarheid.
	 */
	private array $beschikbaarheid;

	/**
	 * Planning wordt eenmalig berekend.
	 *
	 * @var array|null De planning.
	 */
	private static ?array $planning = null;

	/**
	 * Geef de beschikbaarheid.
	 *
	 * @return array De beschikbaarheid.
	 */
	public function get_beschikbaarheid() : array {
		if ( is_array( self::$planning ) ) {
			return self::$planning;
		}
		$start = strtotime( 'tomorrow 0:00' );
		$eind  = strtotime( '+ ' . opties()['weken_workshop'] . ' week 0:00' );
		$this->start( $start, $eind );
		$this->bepaal_docent_beschikbaarheid( $start, $eind );
		$this->bepaal_activiteit_beschikbaarheid( $start, $eind );
		return $this->schoon_beschikbaarheid();
	}

	/**
	 * Vul het beschikbaarheid array met een volledige vulling voor de periode.
	 *
	 * @param int $start De start datum.
	 * @param int $eind  De eind datum.
	 */
	private function start( int $start, int $eind ) : void {
		for ( $datum = $start; $datum <= $eind; $datum += DAY_IN_SECONDS ) {
			foreach ( self::WORKSHOP_DAGDEEL as $dagdeel ) {
				$this->beschikbaarheid[ $this->index( $datum, $dagdeel ) ] = [
					'aantal' => 0,
					'docent' => false,
				];
			}
		}
	}

	/**
	 * Controleer of er al cursussen of workshops gepland staan op een datum
	 *
	 * @param int $start De start datum.
	 * @param int $eind  De eind datum.
	 */
	private function bepaal_activiteit_beschikbaarheid( int $start, int $eind ) : void {
		foreach ( new Cursussen( $start ) as $cursus ) {
			foreach ( $cursus->lesdatums as $lesdatum ) {
				if ( $lesdatum < $start || $lesdatum > $eind || $cursus->vervallen ) {
					continue;
				}
				$this->verhoog( $lesdatum, bepaal_dagdelen( $cursus->start_tijd, $cursus->eind_tijd ) );
			}
		}
		foreach ( new Workshops( $start ) as $workshop ) {
			if ( ( $workshop->datum > $eind ) || $workshop->vervallen ) {
				continue;
			}
			$this->verhoog( $workshop->datum, bepaal_dagdelen( $workshop->start_tijd, $workshop->eind_tijd ) );
		}
	}

	/**
	 * Haal alle docenten op en bepaal hun beschikbaarheid
	 *
	 * @param int $start De start datum.
	 * @param int $eind  De eind datum.
	 */
	private function bepaal_docent_beschikbaarheid( int $start, int $eind ) : void {
		$docenten = new Docenten();
		for ( $datum = $start; $datum <= $eind; $datum = $datum + DAY_IN_SECONDS ) {
			foreach ( self::WORKSHOP_DAGDEEL as $dagdeel ) {
				foreach ( $docenten as $docent ) {
					$status = $docent->get_beschikbaarheid( $datum, $dagdeel );
					if ( Docent::BESCHIKBAAR === $status || Docent::STANDAARD === $status ) {
						$this->beschikbaarheid[ $this->index( $datum, $dagdeel ) ]['docent'] = true;
						break;
					}
				}
			}
		}
	}

	/**
	 * Hulp functie, verhoog de activiteitteller van de dag
	 *
	 * @param int   $datum    De datum.
	 * @param array $dagdelen Het dagdeel.
	 */
	private function verhoog( int $datum, array $dagdelen ) : void {
		foreach ( $dagdelen as $dagdeel ) {
			if ( in_array( $dagdeel, self::WORKSHOP_DAGDEEL, true ) ) {
				$index                                     = $this->index( $datum, $dagdeel );
				$this->beschikbaarheid[ $index ]['aantal'] = ( $this->beschikbaarheid[ $index ]['aantal'] ?? 0 ) + 1;
			}
		}
	}

	/**
	 * Hulp functie om de index eenduidig te bepalen
	 *
	 * @param int    $datum   De datum.
	 * @param string $dagdeel Het dagdeel.
	 *
	 * @return string
	 */
	private function index( int $datum, string $dagdeel ) : string {
		return strtolower( "$datum-$dagdeel" );
	}

	/**
	 * Minimaliseer de beschikbaarheid voor alleen die dagdelen waarbij er nog ruimte voor activiteiten is en een docent beschikbaar.
	 *
	 * @return array De beschikbaarheid.
	 */
	private function schoon_beschikbaarheid() : array {
		$maximum         = opties()['max_activiteit'] ?? 1;
		$activiteitpauze = opties()['actpauze'] ?? [];
		self::$planning  = [];
		foreach ( $this->beschikbaarheid as $key => $dag_dagdeel ) {
			if ( $maximum <= $dag_dagdeel['aantal'] || ! $dag_dagdeel['docent'] ) {
				continue;
			}
			list( $datum, $dagdeel ) = explode( '-', $key );
			$pauzeren                = false;
			foreach ( $activiteitpauze as $pauze ) {
				$pauze_start = strtotime( $pauze['start'] );
				$pauze_eind  = strtotime( $pauze['eind'] );
				$pauzeren    = $pauzeren || ( $datum >= $pauze_start && $datum <= $pauze_eind );
			}
			if ( ! $pauzeren ) {
				self::$planning[] = [
					'datum'   => date( 'Y-m-d', $datum ),
					'dagdeel' => $dagdeel,
				];
			}
		}
		return self::$planning;
	}

}
