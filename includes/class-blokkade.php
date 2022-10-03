<?php
/**
 * BLokkade class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.19.2
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Definitie van de adres class.
 */
class Blokkade {

	const DATUM = 'kleistad_blokkade';

	/**
	 * Haal de blokkade op. Voor het geval de functie in een loop wordt aangeroepen wordt gebruik gemaakt van caching dmv een static.
	 *
	 * @return int De datum.
	 */
	public function get() : int {
		static $blokkade_datum = 0;
		if ( 0 === $blokkade_datum ) {
			$blokkade_datum = (int) get_option( self::DATUM, strtotime( '1-1-2020' ) );
		}
		return $blokkade_datum;
	}

	/**
	 * Wijzig de blokkade datum naar eerstvolgende.
	 *
	 * @return bool Of de datum wel of niet gewijzigd is.
	 */
	public function set() : bool {
		$blokkade_datum = $this->get();
		while ( strtotime( '+4 month', $blokkade_datum ) < strtotime( 'today' ) ) {
			$blokkade_datum = strtotime( '+3 month', $blokkade_datum );
		}
		return update_option( self::DATUM, $blokkade_datum );
	}

	/**
	 * Check of de datum geblokkeerd is.
	 *
	 * @param int $datum De datum in unix time.
	 */
	public function check( int $datum ) : bool {
		return ( $datum < $this->get() );
	}

	/**
	 * Controleer dagelijks of de blokkadedatum gewijzigd moet worden.
	 */
	public static function doe_dagelijks() : void {
		/**
		 * Bepaal de laatste blokkade.
		 */
		$blokkade = new self();
		$blokkade->set();
	}

	/**
	 * Check of de blokkade datum wijzigbaar is.
	 *
	 * @return bool Het resultaat van de controle.
	 */
	public function wijzigbaar() : bool {
		return strtotime( '+ 3 month', $this->get() ) < strtotime( 'today' );
	}
}
