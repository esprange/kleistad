<?php
/**
 * Shortcode showcase gallerij.
 *
 * @link       https://www.kleistad.nl
 * @since      7.7.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

namespace Kleistad;

/**
 * De kleistad showcase beheer class.
 */
class Public_Showcase_Gallerij extends Shortcode {

	/**
	 * Prepareer het 'gallerij' overzicht
	 *
	 * @return string
	 */
	protected function prepare() : string {
		$this->data['showcases'] = new Showcases(
			[
				'post_status' => [
					Showcase::BESCHIKBAAR,
					Showcase::INGEPLAND,
					Showcase::TENTOONGESTELD,
				],
				'orderby'     => 'rand',
				'numberposts' => 12,
			]
		);
		return $this->content();
	}

}
