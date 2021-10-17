<?php
/**
 * De definitie van de docent class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.20.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use WP_User;
use stdClass;

/**
 * Kleistad Docent class.
 *
 * @since 6.20.0
 */
class Docent extends Gebruiker {

	public const NIET_BESCHIKBAAR = 0;
	public const BESCHIKBAAR      = 1;
	public const OPTIE            = 2;
	public const GERESERVEERD     = 3;
	public const META_KEY         = 'kleistad_docent_beschikbaarheid';

	/**
	 * De docent beschikbaarheid
	 *
	 * @var array $beschikbaarheid De beschikbaarheid.
	 */
	public array $beschikbaarheid = [];

	/**
	 * Constructor
	 *
	 * @param int|string|stdClass|WP_User $id      User's ID, a WP_User object, or a user object from the DB.
	 * @param string                      $name    Optional. User's username.
	 * @param int                         $site_id Optional Site ID, defaults to current site.
	 * @suppressWarnings(PHPMD.ShortVariable)
	 */
	public function __construct( $id = 0, $name = '', $site_id = null ) {
		parent::__construct( $id, $name, $site_id );
		$this->beschikbaarheid = get_user_meta( $id, self::META_KEY ) ?: [];
	}

	/**
	 * Geef de beschikbaarheid aan.
	 *
	 * @param int    $datum   De datum.
	 * @param string $dagdeel Het dagdeel.
	 *
	 * @return int
	 */
	public function beschikbaarheid( int $datum, string $dagdeel ) : int {
		if ( isset( $this->beschikbaarheid[ $datum ][ $dagdeel ] ) ) {
			return $this->beschikbaarheid[ $datum ][ $dagdeel ];
		}
		return self::NIET_BESCHIKBAAR;
	}

	/**
	 * Zet de optie.
	 *
	 * @param int    $datum   De datum.
	 * @param string $dagdeel Het dagdeel.
	 */
	public function optie( int $datum, string $dagdeel ) {
		$this->beschikbaarheid[ $datum ][ $dagdeel ] = self::OPTIE;
		$this->save();
	}

	/**
	 * Zet de reservering.
	 *
	 * @param int    $datum   De datum.
	 * @param string $dagdeel Het dagdeel.
	 */
	public function reserveer( int $datum, string $dagdeel ) {
		$this->beschikbaarheid[ $datum ][ $dagdeel ] = self::GERESERVEERD;
		$this->save();
	}

	/**
	 * Zet de beschikbaarheid.
	 *
	 * @param int    $datum   De datum.
	 * @param string $dagdeel Het dagdeel.
	 */
	public function beschikbaar( int $datum, string $dagdeel ) {
		$this->beschikbaarheid[ $datum ][ $dagdeel ] = self::BESCHIKBAAR;
		$this->save();
	}

	/**
	 * Zet de niet beschikbaarheid.
	 *
	 * @param int    $datum   De datum.
	 * @param string $dagdeel Het dagdeel.
	 */
	public function nietbeschikbaar( int $datum, string $dagdeel ) {
		$this->beschikbaarheid[ $datum ][ $dagdeel ] = self::NIET_BESCHIKBAAR;
		$this->save();
	}

	/**
	 * Bewaar de beschikbaarheid.
	 */
	private function save() {
		if ( $this->ID ) {
			$data    = [];
			$vandaag = strtotime( 'today' );
			foreach ( $this->beschikbaarheid as $dag => $beschikbaarheid ) {
				if ( $dag < $vandaag ) {
					continue;
				}
				$data[ $dag ] = $beschikbaarheid;
			}
			update_user_meta( $this->ID, self::META_KEY, $data );
		}
	}
}
