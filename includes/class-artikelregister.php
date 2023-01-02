<?php
/**
 * De definitie van de artikelregister class
 *
 * @link       https://www.kleistad.nl
 * @since      6.10.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Countable;
use Iterator;

/**
 * Kleistad Artikelregister class.
 *
 * @since 6.10.0
 */
class Artikelregister  implements Countable, Iterator {

	/**
	 * Het register
	 *
	 * @var array $register Het register.
	 */
	private static array $register = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * Constructor
	 *
	 * @param array|null $artikelclasses De optionele artikel class namen.
	 */
	public function __construct( ?array $artikelclasses = null ) {
		if ( ! is_null( $artikelclasses ) ) {
			foreach ( $artikelclasses as $artikelclass ) {
				$class            = '\\' . __NAMESPACE__ . '\\' . $artikelclass;
				self::$register[] = array_merge(
					[ 'class' => $artikelclass ],
					$class::DEFINITIE
				);
			}
		}
	}

	/**
	 * Geef de artikel naam
	 *
	 * @param string|null $referentie De optionele artikel referentie.
	 * @return string
	 */
	public function get_naam( ?string $referentie = null ) : string {
		if ( ( is_null( $referentie ) ) ) {
			return $this->current()['naam'];
		}
		foreach ( self::$register as $artikel ) {
			if ( $referentie[0] === $artikel['prefix'] ) {
				return $artikel['naam'];
			}
		}
		return '';
	}

	/**
	 * Geef het object
	 *
	 * @param string $referentie De artikel referentie.
	 * @return Artikel|null
	 */
	public function get_object( string $referentie ) : ?Artikel {
		foreach ( self::$register as $artikel ) {
			if ( $referentie[0] === $artikel['prefix'] ) {
				$parameters = explode( '-', substr( $referentie, 1 ) );
				$class      = '\\' . __NAMESPACE__ . '\\' . $artikel['class'];
				switch ( $artikel['pcount'] ) {
					case 1:
						$object = new $class( (int) $parameters[0] );
						break;
					case 2:
						$object = new $class( (int) $parameters[0], (int) $parameters[1] );
						break;
					case 3:
						$object = new $class( (int) $parameters[0], (int) $parameters[1], (int) $parameters[2] );
						break;
					default:
						fout( __CLASS__, "object {$artikel['class']} kan niet met {$artikel['pcount']} parameters aangemaakt worden." );
						return null;
				}
				$object->artikel_type = $parameters[ $artikel['pcount'] ] ?? $object->artikel_type;
				return $object;
			}
		}
		return null;
	}

	/**
	 * Geef het aantal geregistreerde artikelen terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( self::$register );
	}

	/**
	 * Geef het huidige artikel definitie terug.
	 *
	 * @return array De artikel definitie.
	 */
	public function current(): array {
		return self::$register[ $this->current_index ];
	}

	/**
	 * Geef de sleutel terug.
	 *
	 * @return int De sleutel.
	 */
	public function key(): int {
		return $this->current_index;
	}

	/**
	 * Ga naar de volgende in de lijst.
	 */
	public function next(): void {
		$this->current_index++;
	}

	/**
	 * Ga terug naar het begin.
	 */
	public function rewind(): void {
		$this->current_index = 0;
	}

	/**
	 * Bepaal of het element bestaat.
	 *
	 * @return bool Of het bestaat of niet.
	 */
	public function valid(): bool {
		return isset( self::$register[ $this->current_index ] );
	}

}
