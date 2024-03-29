<?php
/**
 * Definieer de dagelijkse jobs class
 *
 * @link       https://www.kleistad.nl
 * @since      6.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use WP_Background_Process;

/**
 * Kleistad daily jobs.
 *
 * Zie https://github.com/deliciousbrains/wp-background-processing voor beschrijving van deze background job.
 */
class Background extends WP_Background_Process {

	/**
	 * De naam van het background proces.
	 *
	 * @var string
	 */
	protected $action = 'kleistad_background';

	/**
	 * Voor de taak uit.
	 *
	 * @param mixed $item Queue item to iterate over, in dit geval de functie uitvoeren.
	 *
	 * @noinspection PhpMissingReturnTypeInspection
	 */
	protected function task( $item ) {
		call_user_func( '\\' . __NAMESPACE__ . '\\' . $item );
		return false;
	}

}
