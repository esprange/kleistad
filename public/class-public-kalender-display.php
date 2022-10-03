<?php
/**
 * Toon het kalender
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de kalender.
 */
class Public_Kalender_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function overzicht() : void {
		?>
		<div id="kleistad_fullcalendar"></div>
		<?php
	}

}
