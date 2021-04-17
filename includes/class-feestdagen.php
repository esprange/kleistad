<?php
/**
 * De nederlandse feestdagen.
 *
 * @link       https://www.kleistad.nl
 * @since      6.15.03
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Geef de Nederlandse feestdagen.
 */
class Feestdagen {

	/**
	 * Geef de dagen, eventueel met aanpassingen uit de database
	 *
	 * @param integer $jaar Het jaar waarvan de dagen nodig zijn.
	 * @return array De feestdagen.
	 * @suppressWarnings(PHPMD.UndefinedVariable)
	 */
	public function dagen( int $jaar ) : array {
		static $jaren = [];
		if ( isset( $jaren[ $jaar ] ) ) {
			return $jaren[ $jaar ];
		}
		$feestdagen = get_option( "feestdagen_$jaar" );
		if ( false !== $feestdagen ) {
			$jaren[ $jaar ] = $feestdagen;
			return $feestdagen;
		}
		$feestdagen = $this->bereken( $jaar );
		add_option( "feestdagen_$jaar", $feestdagen );
		$jaren[ $jaar ] = $feestdagen;
		return $feestdagen;
	}

	/**
	 * Bepaal of de datum een feestdag is
	 *
	 * @param integer $datum De datum.
	 * @return boolean
	 */
	public function is_feestdag( int $datum ) : bool {
		return in_array( $datum, $this->dagen( (int) date( 'Y', $datum ) ), true );
	}

	/**
	 * Berekenen de feestdagen van het jaar
	 *
	 * @param integer $jaar Het jaar waarvan de dagen nodig zijn.
	 * @return array De feestdagen.
	 */
	private function bereken( int $jaar ) : array {
		$paasdatum = strtotime( "$jaar-03-21" ) + easter_days( $jaar ) * DAY_IN_SECONDS;
		$paasdag   = (int) date( 'j', $paasdatum );
		$paasmaand = (int) date( 'n', $paasdatum );

		$feestdagen = array(
			// Nieuwjaarsdag.
			mktime( 0, 0, 0, 1, 1, $jaar ),
			// 1e Kerstdag.
			mktime( 0, 0, 0, 12, 25, $jaar ),
			// 2e Kerstdag.
			mktime( 0, 0, 0, 12, 26, $jaar ),
		);

		// Bevrijdingsdag.
		if ( ( $jaar % 5 ) === 0 ) {
			$feestdagen[] = mktime( 0, 0, 0, 5, 5, $jaar );
		}

		// Koninginnedag.
		// Verplaats naar zaterdag als het valt op zondag.
		$feestdagen[] = mktime( 0, 0, 0, 4, ( (int) date( 'w', mktime( 0, 0, 0, 4, 27, $jaar ) ) === 0 ) ? 26 : 27, $jaar );  // Verplaats naar zaterdag.

		// Onderstaande dagen hebben een datum afhankelijk van Pasen.
		// Goede Vrijdag (= pasen - 2).
		// $feestdagen[] = strtotime( '-2 days', mktime( 0, 0, 0, $paasmaand, $paasdag, $jaar ) ); // phpcs:ignore
		// 1e Paasdag.
		$feestdagen[] = mktime( 0, 0, 0, $paasmaand, $paasdag, $jaar );
		// 2e Paasdag (= pasen +1).
		$feestdagen[] = strtotime( '+1 days', mktime( 0, 0, 0, $paasmaand, $paasdag, $jaar ) );
		// Hemelvaartsdag (= pasen + 39).
		$feestdagen[] = strtotime( '+39 days', mktime( 0, 0, 0, $paasmaand, $paasdag, $jaar ) );
		// 1e Pinksterdag (= pasen + 49).
		$feestdagen[] = strtotime( '+49 days', mktime( 0, 0, 0, $paasmaand, $paasdag, $jaar ) );
		// 2e Pinksterdag (= pasen + 50).
		$feestdagen[] = strtotime( '+50 days', mktime( 0, 0, 0, $paasmaand, $paasdag, $jaar ) );

		sort( $feestdagen );
		return $feestdagen;
	}

}
