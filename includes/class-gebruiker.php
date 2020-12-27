<?php
/**
 * De definitie van de gebruiker class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use WP_User;

/**
 * Kleistad Gebruiker class.
 *
 * @since 6.11.0
 *
 * @property string straat
 * @property string huisnr
 * @property string pcode
 * @property string plaats
 * @property string telnr
 */
class Gebruiker extends WP_User {

	/**
	 * Bepaal of de gebruiker nu actief is.
	 *
	 * @return bool True als actief.
	 */
	public function is_actief() {
		return true;
	}
}
