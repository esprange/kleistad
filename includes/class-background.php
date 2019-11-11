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

/**
 * Kleistad daily jobs.
 *
 * Zie https://github.com/deliciousbrains/wp-background-processing voor beschrijving van deze background job.
 */
class Background extends \WP_Background_Process {

	/**
	 * De naam van het background proces.
	 *
	 * @var string
	 */
	protected $action = 'kleistad_background';

	/**
	 * Voor de taak uit.
	 *
	 * @param mixed $taak Queue item to iterate over, in dit geval de functie uitvoeren.
	 */
	protected function task( $taak ) {
		call_user_func( $taak );
		return false;
	}

}
