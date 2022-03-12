<?php
/**
 * De definitie van de recepttermen class
 *
 * @link       https://www.kleistad.nl
 * @since      7.2.3
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Recepttermen class.
 */
class ReceptTermen {

	const KLEUR     = '_kleur';
	const GRONDSTOF = '_grondstof';
	const UITERLIJK = '_uiterlijk';
	const GLAZUUR   = '_glazuur';

	/**
	 * Maak eenmalig de hoofdtermen aan.
	 *
	 * @var array $hoofdtermen De termen.
	 */
	private static array $hoofdtermen = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( empty( self::$hoofdtermen ) ) {
			foreach ( [ self::GRONDSTOF, self::KLEUR, self::UITERLIJK, self::GLAZUUR ] as $hoofdterm_naam ) {
				$term = get_term_by( 'name', $hoofdterm_naam, Recept::CATEGORY );
				if ( false === $term ) {
					$result = wp_insert_term( $hoofdterm_naam, Recept::CATEGORY );
					if ( is_array( $result ) ) {
						self::$hoofdtermen[ $hoofdterm_naam ] = get_term( $result['term_id'] );
					}
				}
				self::$hoofdtermen[ $hoofdterm_naam ] = $term;
			}
		}
	}

	/**
	 * Geef de hoofdtermen terug.
	 *
	 * @return array De hoofdterm objecten.
	 */
	public function lijst(): array {
		return self::$hoofdtermen;
	}
}
