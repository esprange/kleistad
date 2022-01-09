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
	public const STANDAARD        = 4;
	public const META_KEY         = 'kleistad_docent_beschikbaarheid';

	/**
	 * De docent beschikbaarheid
	 *
	 * @var array $beschikbaardata De beschikbaarheid.
	 */
	public array $beschikbaardata = [];

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
		$result = get_user_meta( $id, self::META_KEY, true ) ?: [];
		if ( is_array( $result ) ) {
			$this->beschikbaardata = $result;
		}
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
		return $this->beschikbaardata[ $datum ][ $dagdeel ] ?? ( $this->beschikbaardata[ intval( date( 'N', $datum ) ) - 1 ][ $dagdeel ] ?? self::NIET_BESCHIKBAAR );
	}

	/**
	 * Update de planning voor meerdere dagen.
	 *
	 * @param array $lijst De lijst van dagen.
	 */
	public function beschikbaarlijst( array $lijst ) {
		foreach ( $lijst as $item ) {
			$datum = intval( $item['datum'] );
			$this->beschikbaardata[ $datum ][ $item['dagdeel'] ] = $item['status'] ?
				( 10 > $datum ? self::STANDAARD : self::BESCHIKBAAR ) : self::NIET_BESCHIKBAAR;
		}
		update_user_meta( $this->ID, self::META_KEY, $this->beschikbaardata );
		do_action( 'kleistad_planning' );
	}
}
