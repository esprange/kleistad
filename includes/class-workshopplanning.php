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

	const META_KEY = 'kleistad_workshopplanning';

	const WORKSHOP_DAGDEEL = [ OCHTEND, MIDDAG ];

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
		$data = get_transient( self::META_KEY );
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
	 * Bepaal de beschikbaarheid en sla deze op in een transient.
	 * eventuele parameters zijn beschikbaar via POST
	 */
	protected function handle() {
		$start = strtotime( 'tomorrow 0:00' );
		$eind  = strtotime( '+ ' . opties()['weken_workshop'] . ' week 0:00' );
		$this->start( $start, $eind );
		$this->bepaal_docent_beschikbaarheid( $start, $eind );
		$this->bepaal_activiteit_beschikbaarheid( $start, $eind );
		$this->schoon_beschikbaarheid();
		set_transient( self::META_KEY, $this->beschikbaarheid, DAY_IN_SECONDS );
	}

	/**
	 * Vul het beschikbaarheid array met een volledige vulling voor de periode.
	 *
	 * @param int $start De start datum.
	 * @param int $eind  De eind datum.
	 */
	private function start( int $start, int $eind ) {
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
		foreach ( new WorkshopAanvragen( $start - MONTH_IN_SECONDS ) as $workshop_aanvraag ) {
			if ( ! is_null( $workshop_aanvraag->plandatum ) && $workshop_aanvraag->is_inverwerking() && $workshop_aanvraag->plandatum < $eind ) {
				$this->verhoog( $workshop_aanvraag->plandatum, WorkshopAanvraag::MOMENT[ $workshop_aanvraag->dagdeel ]['dagdeel'] );
			}
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
			foreach ( self::WORKSHOP_DAGDEEL as $dagdeel ) {
				foreach ( $docenten as $docent ) {
					$status = $docent->beschikbaarheid( $datum, $dagdeel );
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
	 * @param int    $datum   De datum.
	 * @param string $dagdeel Het dagdeel.
	 */
	private function verhoog( int $datum, string $dagdeel ) {
		if ( in_array( $dagdeel, self::WORKSHOP_DAGDEEL, true ) ) {
			$index                                     = $this->index( $datum, $dagdeel );
			$this->beschikbaarheid[ $index ]['aantal'] = ( $this->beschikbaarheid[ $index ]['aantal'] ?? 0 ) + 1;
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
		return date( 'Y-m-d', $datum ) . '_' . strtolower( $dagdeel );
	}

	/**
	 * Minimaliseer de beschikbaarheid voor alleen die dagdelen waarbij er nog ruimte voor activiteiten is en een docent beschikbaar.
	 */
	private function schoon_beschikbaarheid() {
		$maximum         = opties()['max_activiteit'] ?? 1;
		$activiteitpauze = opties()['actpauze'] ?? [];
		foreach ( $this->beschikbaarheid as $key => $dag_dagdeel ) {
			if ( $maximum <= $dag_dagdeel['aantal'] || ! $dag_dagdeel['docent'] ) {
				unset( $this->beschikbaarheid[ $key ] );
				continue;
			}
			$dag = strtotime( explode( '_', $key )[0] );
			foreach ( $activiteitpauze as $pauze ) {
				$pauze_start = strtotime( $pauze['start'] );
				$pauze_eind  = strtotime( $pauze['eind'] );
				if ( $dag >= $pauze_start && $dag <= $pauze_eind ) {
					unset( $this->beschikbaarheid[ $key ] );
				}
			}
		}
	}

}
