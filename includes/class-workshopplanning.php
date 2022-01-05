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

use WP_Async_Request;

/**
 * Kleistad Workshopplanning class.
 *
 * @since 7.0.0
 */
class Workshopplanning extends WP_Async_Request {

	/**
	 * Unieke titel, benodigd voor background processing
	 *
	 * @var string $action Background proces titel.
	 */
	protected $action = 'workshopplanning';

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
	 * Geef de beschikbaarheid.
	 *
	 * @return array De beschikbaarheid.
	 */
	public function geef_beschikbaaarheid() : array {
		$data = get_transient( 'kleistad_workshopplanning' );
		if ( false === $data ) {
			$this->handle();
			$data = $this->beschikbaarheid;
		}
		$beschikbaarheid = [];
		foreach ( array_keys( $data ) as $datum_dagdeel ) {
			list( $datum, $dagdeel ) = explode( '_', $datum_dagdeel );
			$beschikbaarheid[]       = [
				'datum'   => $datum,
				'dagdeel' => $dagdeel,
			];
		}
		return $beschikbaarheid;
	}

	/**
	 * Wrapper om de handle functie publiek beschikbaar te krijgen.
	 */
	public function update_beschikbaarheid() {
		$this->handle();
	}

	/**
	 * Bepaal de beschikbaarheid en sla deze op in een transient.
	 * eventuele parameters zijn beschikbaar via POST
	 */
	protected function handle() {
		$start = strtotime( 'tomorrow 0:00' );
		$eind  = strtotime( '+ 3 month 0:00' );
		$this->start( $start, $eind );
		$this->bepaal_docent_beschikbaarheid( $start, $eind );
		$this->bepaal_activiteit_beschikbaarheid( $start, $eind );
		$this->schoon_beschikbaarheid();
		set_transient( 'kleistad_workshopplanning', $this->beschikbaarheid );
	}

	/**
	 * Vul het beschikbaarheid array met een volledige vulling voor de periode.
	 *
	 * @param int $start De start datum.
	 * @param int $eind  De eind datum.
	 */
	private function start( int $start, int $eind ) {
		for ( $datum = $start; $datum <= $eind; $datum += DAY_IN_SECONDS ) {
			foreach ( DAGDEEL as $dagdeel ) {
				$this->beschikbaarheid[ date( 'Y-m-d', $datum ) . '_' . strtolower( $dagdeel ) ] = [
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
	private function bepaal_activiteit_beschikbaarheid( int $start, int $eind ) {
		foreach ( new Cursussen( $start ) as $cursus ) {
			foreach ( $cursus->lesdatums as $lesdatum ) {
				if ( $cursus->start_datum > $eind ) {
					continue;
				}
				$this->verhoog( $lesdatum, bepaal_dagdeel( $cursus->start_tijd, $cursus->eind_tijd ) );
			}
		}
		foreach ( new Workshops( $start ) as $workshop ) {
			if ( $workshop->datum > $eind ) {
				continue;
			}
			$this->verhoog( $workshop->datum, bepaal_dagdeel( $workshop->start_tijd, $workshop->eind_tijd ) );
		}
		foreach ( new WorkshopAanvragen( $start ) as $workshop_aanvraag ) {
			if ( is_null( $workshop_aanvraag->plandatum ) || $workshop_aanvraag->plandatum > $eind || $workshop_aanvraag->workshop_id ) {
				continue;
			}
			$this->verhoog( $workshop_aanvraag->plandatum, $workshop_aanvraag->dagdeel );
		}
	}

	/**
	 * Haal alle docenten op en bepaal hun beschikbaarheid
	 *
	 * @param int $start De start datum.
	 * @param int $eind  De eind datum.
	 */
	private function bepaal_docent_beschikbaarheid( int $start, int $eind ) {
		$docenten = new Docenten();
		for ( $datum = $start; $datum <= $eind; $datum = $datum + DAY_IN_SECONDS ) {
			foreach ( DAGDEEL as $dagdeel ) {
				foreach ( $docenten as $docent ) {
					if ( Docent::BESCHIKBAAR === $docent->beschikbaarheid( $datum, $dagdeel ) ) {
						$this->beschikbaarheid[ date( 'Y-m-d', $datum ) . '_' . strtolower( $dagdeel ) ]['docent'] = true;
						break;
					}
				}
			}
		}
	}

	/**
	 * Hulp functie, verhoog de activiteitteller van de dag
	 *
	 * @param int    $datum   De datum.
	 * @param string $dagdeel Het dagdeel.
	 */
	private function verhoog( int $datum, string $dagdeel ) {
		$index                                     = date( 'Y-m-d', $datum ) . "_$dagdeel";
		$this->beschikbaarheid[ $index ]['aantal'] = ( $this->beschikbaarheid[ $index ]['aantal'] ?? 0 ) + 1;
	}

	/**
	 * Minimaliseer de beschikbaarheid voor alleen die dagdelen waarbij er nog ruimte voor activiteiten is en een docent beschikbaar.
	 */
	private function schoon_beschikbaarheid() {
		$maximum = opties()['max_activiteit'] ?? 1;
		foreach ( $this->beschikbaarheid as $key => $dag_dagdeel ) {
			if ( $maximum <= $dag_dagdeel['aantal'] || ! $dag_dagdeel['docent'] ) {
				unset( $this->beschikbaarheid[ $key ] );
			}
		}
	}


}
