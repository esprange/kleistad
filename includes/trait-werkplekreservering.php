<?php

namespace Kleistad;

trait WerkplekReservering {

	/**
	 * Verwijder eventuele werkplek reserveringen.
	 *
	 * @param string $code  De code waarmee de reservering start.
	 * @param int    $vanaf De begin datum.
	 * @param int    $tot   De eventuele eind datum.
	 */
	public function verwijder_werkplekken( string $code, int $vanaf, int $tot = 0 ) : void {
		$werkplekken = new Werkplekken( $vanaf, $tot );
		foreach ( $werkplekken as $werkplek ) {
			foreach ( $werkplek->get_gebruik() as $dagdeel => $gebruik ) {
				foreach ( $gebruik as $activiteit => $posities ) {
					$nieuwe_posities = array_filter(
						$posities,
						function ( $positie ) use ( $code ) {
							return ! str_starts_with( $positie, "{$code}_" );
						}
					);
					if ( count( $posities ) !== count( $nieuwe_posities ) ) {
						$werkplek->wijzig( $dagdeel, $activiteit, $nieuwe_posities );
					}
				}
			}
		}
	}

	/**
	 * Reserveer de werkplekken
	 *
	 * @param string $code       De code waarmee de reserveringen starten.
	 * @param string $naam       De naam die zichtbaar moet worden in de reservering.
	 * @param array  $aantallen  Array met activiteit / aantal paren.
	 * @param int    $datum      De datum/tijd waarop de reservering gemaakt moet worden.
	 * @param string $dagdeel    Het dagdeel waarop de reservering gemaakt moet worden.
	 * @return string Eventueel bericht of false als er geen werkplekken gereserveerd zijn.
	 */
	public function reserveer_werkplekken( string $code, string $naam, array $aantallen, int $datum, string $dagdeel ) : string {
		$bericht  = '';
		$totaal   = 0;
		$werkplek = new Werkplek( $datum );
		foreach ( opties()['werkruimte'] as $activiteit ) {
			$aantal = $aantallen[ $activiteit['naam'] ] ?? 0;
			if ( $aantal ) {
				$totaal       += $aantal;
				$ruimte        = $werkplek->get_ruimte( $dagdeel, $activiteit['naam'] );
				$gebruiker_ids = array_column( $werkplek->geef( $dagdeel, $activiteit['naam'] ), 'id' );
				if ( $ruimte < $aantal ) {
					$bericht = 'Niet alle werkplekken konden gereserveerd worden';
					$aantal  = $ruimte;
				}
				for ( $index = 1; $index <= $aantal; $index++ ) {
					$gebruiker_ids[] = "{$code}_{$naam}_$index";
				}
				$werkplek->wijzig( $dagdeel, $activiteit['naam'], $gebruiker_ids );
			}
		}
		if ( $totaal ) {
			return $bericht;
		}
		return 'Er zijn nog geen werkplekken gereserveerd !';
	}

}

