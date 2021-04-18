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
		$feestdagen = get_option( "kleistad_feestdagen_$jaar" );
		if ( ! is_array( $feestdagen ) ) {
			$feestdagen = $this->bereken( $jaar );
			add_option( "kleistad_feestdagen_$jaar", $feestdagen );
		}
		$jaren[ $jaar ] = $feestdagen;
		return $jaren[ $jaar ];
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
		$paasdatum  = strtotime( "$jaar-03-21" ) + easter_days( $jaar ) * DAY_IN_SECONDS;
		$paasdag    = (int) date( 'j', $paasdatum );
		$paasmaand  = (int) date( 'n', $paasdatum );
		$feestdagen = array(
			'nieuwjaarsdag' => mktime( 0, 0, 0, 1, 1, $jaar ),
			'1e kerstdag'   => mktime( 0, 0, 0, 12, 25, $jaar ),
			'2e kerstdag'   => mktime( 0, 0, 0, 12, 26, $jaar ),
		);
		if ( ( $jaar % 5 ) === 0 ) {
			$feestdagen['bevrijdingsdag'] = mktime( 0, 0, 0, 5, 5, $jaar );
		}
		// Verplaats naar zaterdag als het valt op zondag.
		$feestdagen['koningsdag'] = mktime( 0, 0, 0, 4, ( (int) date( 'w', mktime( 0, 0, 0, 4, 27, $jaar ) ) === 0 ) ? 26 : 27, $jaar );  // Verplaats naar zaterdag.
		// Onderstaande dagen hebben een datum afhankelijk van Pasen.
		// goede vrijdag  is -2 days t.o.v. pasen.
		$feestdagen['1e paasdag']     = mktime( 0, 0, 0, $paasmaand, $paasdag, $jaar );
		$feestdagen['2e paasdag']     = strtotime( '+1 days', mktime( 0, 0, 0, $paasmaand, $paasdag, $jaar ) );
		$feestdagen['hemelvaart']     = strtotime( '+39 days', mktime( 0, 0, 0, $paasmaand, $paasdag, $jaar ) );
		$feestdagen['1e pinksterdag'] = strtotime( '+49 days', mktime( 0, 0, 0, $paasmaand, $paasdag, $jaar ) );
		$feestdagen['2e pinksterdag'] = strtotime( '+50 days', mktime( 0, 0, 0, $paasmaand, $paasdag, $jaar ) );
		return $feestdagen;
	}

}
